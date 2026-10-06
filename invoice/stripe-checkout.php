<?php
session_start();
require_once '../db_connect.php';

// 1. SAVE DATE & TIME FROM CALENDAR
if (isset($_GET['date']) && isset($_GET['time']) && isset($_GET['booking_id'])) {
    $b_date = $_GET['date'];
    $b_time = $_GET['time'];
    $b_id = $_GET['booking_id'];
    
    $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, booking_time = ? WHERE id = ? AND user_email = ?");
    $stmt->execute([$b_date, $b_time, $b_id, $_SESSION['user_email']]);
}

// 2. Get the booking ID from the URL (e.g., stripe-checkout.php?booking_id=1)
 $booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id) {
    die("Booking ID missing.");
}

// 3. Fetch the booking from the database to get the price
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found.");
}

// (Leave the rest of your Stripe cURL code exactly as it is below this point...)

// 3. Convert your "Rs. 5,000" string into a clean integer (5000) for Stripe
 $priceString = str_replace(['Rs.', ' ', ','], '', $booking['price']);
 $amount_in_cents = (int) round(floatval($priceString) * 100); // Stripe takes amounts in cents

// 4. Stripe API Secret Key (PASTE YOUR SECRET KEY HERE)
 $secret_key = 'sk_test_51UKNnPKiHqlwhLcLg4izmctQfbWqxjiozLtwazgycGyddlRnuYsi0rMhaqvG7GAZvAjrsgKHQSqac93QzlDQ0sbd00AQLtYJ4G'; // e.g., sk_test_51...

// 5. Send request to Stripe API using cURL to create a Checkout Session
 $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ':');

// Tell Stripe what the customer is buying, and where to return them after payment
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'lkr', // Or 'usd' depending on your account
            'product_data' => ['name' => $booking['service_name'] . ' (' . $booking['vehicle_details'] . ')'],
            'unit_amount' => $amount_in_cents,
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    // After success, go to this URL to update our database and send email
    'success_url' => 'http://localhost/groupProject/invoice/payment-success.php?booking_id=' . $booking_id,
    'cancel_url' => 'http://localhost/groupProject/invoice/invoice.php?id=' . $booking_id,
]));

 $result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $response = json_decode($result, true);
    // Redirect the user to Stripe's secure hosted payment page
    header('Location: ' . $response['url']);
}
curl_close($ch);
?>