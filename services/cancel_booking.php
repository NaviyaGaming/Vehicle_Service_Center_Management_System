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
 $input = json_decode(file_get_contents('php://input'), true);
 $booking_id = $input['booking_id'] ?? 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'No booking ID provided.']);
    exit;
}

// 3. Security Check: Make sure this booking belongs to the logged-in user AND is currently "Pending Payment"
 $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

if ($booking['status'] !== 'Pending Payment') {
    echo json_encode(['success' => false, 'message' => 'Only pending bookings can be cancelled.']);
    exit;
}

// 4. Update the database status to "Cancelled"
 $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
 $updateStmt->execute([$booking_id]);

echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
?>