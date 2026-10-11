<?php
session_start();
header('Content-Type: application/json');

// Go up one folder to find db_connect.php in the main groupProject folder
require_once '../db_connect.php';

 $input = file_get_contents('php://input');
 $data = json_decode($input, true);

if (!isset($data['token'])) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

 $token = $data['token'];

// Decode the Google JWT token
 $parts = explode('.', $token);
if (count($parts) !== 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid token format']);
    exit;
}

 $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if (isset($payload['email'])) {
    $email = $payload['email'];
    $name = $payload['name'] ?? 'Google User';
    
    // --- DATABASE INSERT LOGIC ---
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 0) {
        // User doesn't exist, insert them into the database
        $insertStmt = $pdo->prepare("INSERT INTO users (email, name, login_type) VALUES (?, ?, 'google')");
        $insertStmt->execute([$email, $name]);
    }
    // -----------------------------

    // Save user details in PHP Session
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['login_type'] = 'google';
    
    echo json_encode(['success' => true, 'email' => $email]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not extract email from token']);
}
?>