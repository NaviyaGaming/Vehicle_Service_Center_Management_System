<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// 1. Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// 2. Get the data sent from JavaScript
 $input = file_get_contents('php://input');
 $data = json_decode($input, true);

if (!$data || !isset($data['vehicle']) || !isset($data['service']) || !isset($data['price'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking data.']);
    exit;
}

 $user_email = $_SESSION['user_email'];
 $vehicle = $data['vehicle'];
 $service = $data['service'];
 $time = $data['time'];
 $price = $data['price'];
 $mileage = isset($data['mileage']) ? intval($data['mileage']) : 0;

// NEW: Get Date and Time
 $booking_date = $data['booking_date'] ?? null;
 $booking_time = $data['booking_time'] ?? null;

// 3. Insert into the database (NOW INCLUDING DATE & TIME)
try {
    $stmt = $pdo->prepare("INSERT INTO bookings (user_email, vehicle_details, service_name, est_time, price, status, vehicle_mileage, booking_date, booking_time) VALUES (?, ?, ?, ?, ?, 'Pending Payment', ?, ?, ?)");
    $stmt->execute([$user_email, $vehicle, $service, $time, $price, $mileage, $booking_date, $booking_time]);
    
    $booking_id = $pdo->lastInsertId();
    
    echo json_encode(['success' => true, 'booking_id' => $booking_id]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>