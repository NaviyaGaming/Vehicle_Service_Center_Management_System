<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

 $vehicle_type = $_GET['type'] ?? '';

if (empty($vehicle_type)) {
    echo json_encode([]);
    exit;
}

 $stmt = $pdo->prepare("SELECT * FROM service_packages WHERE vehicle_type = ?");
 $stmt->execute([$vehicle_type]);
 $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($services);
?>