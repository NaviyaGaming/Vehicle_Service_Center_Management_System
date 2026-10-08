<?php
session_start();
require_once '../db_connect.php';

// Security: Check if logged in AND is an admin
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    echo "Access Denied. You are not an administrator.";
    exit;
}

// Get search/filter parameters from URL if they exist
 $search = $_GET['search'] ?? '';
 $status = $_GET['status'] ?? '';

 $sql = "SELECT * FROM bookings WHERE 1=1";
 $params = [];

if (!empty($search)) {
    $sql .= " AND (user_email LIKE ? OR vehicle_details LIKE ? OR service_name LIKE ? OR id LIKE ?)";
    $searchTerm = "%" . $search . "%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
if (!empty($status)) {
    $sql .= " AND status = ?";
    array_push($params, $status);
}

 $sql .= " ORDER BY created_at DESC";
 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers to force download as a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=torquepoint_bookings.csv');

// Open output stream
 $output = fopen('php://output', 'w');

// Write the column headers
fputcsv($output, ['Job ID', 'Customer Email', 'Vehicle Details', 'Service Name', 'Est. Time', 'Price', 'Status', 'Date Booked']);

// Write the data rows
foreach ($bookings as $b) {
    fputcsv($output, [
        '#INV-' . $b['id'],
        $b['user_email'],
        $b['vehicle_details'],
        $b['service_name'],
        $b['est_time'],
        $b['price'],
        $b['status'],
        date('Y-m-d H:i:s', strtotime($b['created_at']))
    ]);
}

fclose($output);
exit;
?>