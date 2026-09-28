<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

 $action = $_GET['action'] ?? 'update_profile';
 $input = json_decode(file_get_contents('php://input'), true);

if ($action === 'update_profile') {
    $name = $input['name'] ?? '';
    $phone = $input['phone'] ?? '';
    $address = $input['address'] ?? '';

    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE email = ?");
    $stmt->execute([$name, $phone, $address, $_SESSION['user_email']]);
    
    $_SESSION['user_name'] = $name;
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

} elseif ($action === 'add_vehicle') {
    $type = $input['type'] ?? '';
    $make = $input['make'] ?? '';
    $model = $input['model'] ?? '';
    $year = $input['year'] ?? '';

    if (empty($type) || empty($make) || empty($model) || empty($year)) {
        echo json_encode(['success' => false, 'message' => 'All vehicle fields are required.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO user_vehicles (user_email, vehicle_type, vehicle_make, vehicle_model, vehicle_year) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_email'], $type, $make, $model, $year]);
    
    echo json_encode(['success' => true, 'message' => 'Vehicle added to your garage!']);
}
?>