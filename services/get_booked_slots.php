<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

 $date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode([]);
    exit;
}

// Find all bookings on this date that are NOT cancelled
 $stmt = $pdo->prepare("SELECT booking_time FROM bookings WHERE booking_date = ? AND status != 'Cancelled'");
 $stmt->execute([$date]);
 $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($bookings);
?>