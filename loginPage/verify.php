<?php
session_start();
require_once '../db_connect.php';

// If they didn't just register, send them back to the register page
if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit;
}

 $email = $_SESSION['pending_email'];
 $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_pin = trim($_POST['pin'] ?? '');

    if (empty($entered_pin)) {
        $error = "Please enter the 6-digit PIN.";
    } else {
        // Check if PIN matches the database
        $stmt = $pdo->prepare("SELECT verification_pin FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['verification_pin'] === $entered_pin) {
            // PIN is correct! Activate account.
            $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_pin = NULL WHERE email = ?");
            $updateStmt->execute([$email]);

            // Log them in fully
            $_SESSION['user_email'] = $email;
            unset($_SESSION['pending_email']); // Clear pending state

            // Redirect to services
            header("Location: ../services/services.php");
            exit;
        } else {
            $error = "Invalid PIN. Please check your email and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --danger: #EF4444; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .verify-card { background: var(--surface); padding: 48px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 420px; width: 90%; text-align: center; border: 1px solid var(--border-color); }
        .icon-circle { width: 64px; height: 64px; background: rgba(0, 82, 204, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 28px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin-bottom: 12px; }
        p { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .email-highlight { font-weight: 600; color: var(--text-main); word-break: break-all; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        input { width: 100%; padding: 14px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 18px; text-align: center; letter-spacing: 8px; font-weight: 600; margin-bottom: 16px; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1); }
        .btn-primary { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="icon-circle"><i class="fa-solid fa-envelope-open-text"></i></div>
        <h1>Check your email</h1>
        <p>We sent a 6-digit verification PIN to <span class="email-highlight"><?php echo htmlspecialchars($email); ?></span>. Enter it below to activate your account.</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="verify.php">
            <input type="text" name="pin" maxlength="6" placeholder="123456" required>
            <button type="submit" class="btn-primary">Verify Account</button>
        </form>
    </div>
</body>
</html>