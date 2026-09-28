<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// 1. Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// 2. Get the booking ID sent from JavaScript
 $input = file_get_contents('php://input');
 $data = json_decode($input, true);
 $booking_id = $data['booking_id'] ?? 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'No booking ID provided.']);
    exit;
}

// 3. Update the database status to 'Paid'
try {
    // We also check user_email in the WHERE clause for security, 
    // so a user can only pay for THEIR own booking.
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Paid' WHERE id = ? AND user_email = ?");
    $stmt->execute([$booking_id, $_SESSION['user_email']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Payment successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already paid.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>