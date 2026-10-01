<?php
session_start();
require_once '../db_connect.php';

 $errors = [];
 $name = $email = $phone = '';

// If user already logged in, send them to services
if (isset($_SESSION['user_email'])) {
    header("Location: ../services/services.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "Name, Email, and Password are required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "An account with this email already exists. Please log in.";
        }
    }

    // If no errors, insert user into database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Encrypts the password!
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, login_type) VALUES (?, ?, ?, ?, 'email')");
        $stmt->execute([$name, $email, $phone, $hashed_password]);

        // Log them in automatically
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        
        header("Location: ../services/services.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF;
            --text-main: #0F172A; --text-muted: #64748B; --danger: #EF4444; --border-color: #E2E8F0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); height: 100vh; display: flex; }
        
        /* Left Panel (Branding) */
        .left-panel {
            flex: 1.15; background: radial-gradient(ellipse at 70% 50%, #0052CC 0%, #003399 60%, #002266 100%);
            display: flex; flex-direction: column; justify-content: center; padding: 44px 56px; color: white; position: relative; overflow: hidden;
        }
        .left-panel::before { content: ""; position: absolute; inset: 0; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(ellipse at center, black 40%, transparent 78%); }
        .brand-name { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 40px; z-index: 2; position: relative; }
        .brand-name span { opacity: 0.8; }
        .tagline { font-family: 'Montserrat', sans-serif; font-size: 32px; line-height: 1.3; font-weight: 600; margin-bottom: 24px; z-index: 2; position: relative; }
        .feature-desc { font-size: 15px; line-height: 1.6; color: rgba(255, 255, 255, 0.8); max-width: 400px; z-index: 2; position: relative; }

        /* Right Panel (Form) */
        .right-panel { flex: 1; background: var(--surface); display: flex; flex-direction: column; justify-content: center; padding: 40px 64px; overflow-y: auto; }
        .form-container { max-width: 400px; width: 100%; margin: 0 auto; }
        .form-title { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; margin-bottom: 8px; }
        .form-subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 32px; }

        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        .alert-error ul { margin-left: 20px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-main); transition: all 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary); background: var(--surface); box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1); }

        .btn-primary { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); }

        .login-link { text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted); }
        .login-link a { color: var(--primary); text-decoration: none; font-weight: 600; }

        @media (max-width: 860px) {
            body { flex-direction: column; }
            .left-panel { display: none; }
            .right-panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="brand-name">Torque<span>Point</span></div>
        <div class="tagline">Join the TorquePoint Network.</div>
        <p class="feature-desc">Create an account to book vehicle services, track your repair status in real-time, and manage your service history from one central dashboard.</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-container">
            <h2 class="form-title">Create your account</h2>
            <p class="form-subtitle">Get started in less than a minute.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number (Optional)</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+94 11 234 5678">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.html">Sign In</a>
            </div>
        </div>
    </div>

</body>
</html>