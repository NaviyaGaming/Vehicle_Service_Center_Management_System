<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// 1. Security: Check if logged in AND is an admin
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

 $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admins only.']);
    exit;
}

// 2. Get data from JavaScript
 $input = json_decode(file_get_contents('php://input'), true);
 $booking_id = $input['booking_id'] ?? 0;
 $new_status = $input['new_status'] ?? '';

// 3. Validate the status (Prevent SQL injection / bad data)
 $allowed_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
    exit;
}

// 4. Update the database
 $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
 $updateStmt->execute([$new_status, $booking_id]);

echo json_encode(['success' => true, 'message' => "Status updated to '$new_status'."]);
?>