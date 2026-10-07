<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

 $date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode([]);
    exit;
}

// Only count slots that have been PAID for! 'Pending Payment' or 'Cancelled' don't count.
 $paid_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
 $placeholders = implode(',', array_fill(0, count($paid_statuses), '?'));

 $sql = "SELECT booking_time FROM bookings WHERE booking_date = ? AND status IN ($placeholders)";
 $stmt = $pdo->prepare($sql);
 $stmt->execute(array_merge([$date], $paid_statuses));
 $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($bookings);
?>