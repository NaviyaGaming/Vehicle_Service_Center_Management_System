<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read the JSON body sent by login.js
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
    exit;
}

// Look up the user by email
$stmt = $pdo->prepare("SELECT name, email, password, is_verified, is_admin, login_type FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Generic error message so attackers can't discover which emails are registered
if (!$user || !password_verify($password, $user['password'])) {
    // Special case: Google-only accounts have no password set
    if ($user && ($user['login_type'] === 'google') && empty($user['password'])) {
        echo json_encode(['success' => false, 'message' => 'This account uses Google Sign-In. Please use the "Sign in with Google" button.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

// Account exists but the email was never verified (PIN step not completed)
if ((int)$user['is_verified'] !== 1) {
    // Save pending email so verify.php accepts them and they can enter the PIN again
    $_SESSION['pending_email'] = $user['email'];
    echo json_encode(['success' => false, 'needs_verification' => true, 'message' => 'Please verify your email first.']);
    exit;
}

// Success — create the real session
session_regenerate_id(true); // prevents session fixation
$_SESSION['user_email'] = $user['email'];

// Send admins straight to the admin dashboard
$redirect = '../services/services.php';
if ((int)$user['is_admin'] === 1) {
    $redirect = '../admin/dashboard.php';
}

echo json_encode(['success' => true, 'redirect' => $redirect]);
exit;
?>
