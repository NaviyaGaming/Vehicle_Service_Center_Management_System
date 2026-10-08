<?php
session_start();
require_once '../db_connect.php';

// Include PHPMailer
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 $errors = [];
 $name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "Name, Email, and Password are required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists AND is verified
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser && $existingUser['is_verified'] == 1) {
            $errors[] = "An account with this email already exists. Please log in.";
        }
    }

    // If no errors, insert/update user and send PIN
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pin = random_int(100000, 999999); // Generate 6-digit PIN

        // If email exists but unverified, update their info. Otherwise, insert new.
        if ($existingUser) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, password=?, verification_pin=? WHERE email=?");
            $stmt->execute([$name, $phone, $hashed_password, $pin, $email]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, login_type, verification_pin, is_verified) VALUES (?, ?, ?, ?, 'email', ?, 0)");
            $stmt->execute([$name, $email, $phone, $hashed_password, $pin]);
        }

        // Send PIN via Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'navindu.subasinghe@gmail.com'; 
            $mail->Password = 'upzh xqev rtqk unee';       
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('navindu.subasinghe@gmail.com', 'TorquePoint Service Center');
            $mail->addAddress($email); 
            
            $mail->isHTML(true);
            $mail->Subject = "Your TorquePoint Verification PIN";
            $mail->Body = "
                <h2>Welcome to TorquePoint!</h2>
                <p>Thank you for signing up. Please use the following 6-digit PIN to verify your account:</p>
                <h1 style='letter-spacing: 5px; color: #0052CC; font-size: 32px;'>$pin</h1>
                <p>If you did not request this, please ignore this email.</p>
            ";
            $mail->send();
            
            // Save email to session for the verify page
            $_SESSION['pending_email'] = $email;
            
            // Redirect to verification page
            header("Location: verify.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Could not send verification email. Mailer Error: {$mail->ErrorInfo}";
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --danger: #EF4444; --border-color: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); height: 100vh; display: flex; }
        .left-panel { flex: 1.15; background: radial-gradient(ellipse at 70% 50%, #0052CC 0%, #003399 60%, #002266 100%); display: flex; flex-direction: column; justify-content: center; padding: 44px 56px; color: white; position: relative; overflow: hidden; }
        .left-panel::before { content: ""; position: absolute; inset: 0; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(ellipse at center, black 40%, transparent 78%); }
        .brand-name { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 40px; z-index: 2; position: relative; }
        .brand-name span { opacity: 0.8; }
        .tagline { font-family: 'Montserrat', sans-serif; font-size: 32px; line-height: 1.3; font-weight: 600; margin-bottom: 24px; z-index: 2; position: relative; }
        .feature-desc { font-size: 15px; line-height: 1.6; color: rgba(255, 255, 255, 0.8); max-width: 400px; z-index: 2; position: relative; }
        .right-panel { flex: 1; background: var(--surface); display: flex; flex-direction: column; justify-content: center; padding: 40px 64px; overflow-y: auto; }
        .form-container { max-width: 400px; width: 100%; margin: 0 auto; }
        .form-title { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; margin-bottom: 8px; }
        .form-subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 32px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        .alert-error ul { margin-left: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper input, .form-group > input { width: 100%; padding: 12px 44px 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-main); transition: all 0.2s; }
        .form-group > input { padding: 12px 16px; }
        .input-wrapper input:focus, .form-group > input:focus { outline: none; border-color: var(--primary); background: var(--surface); box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1); }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 4px; }
        .btn-primary { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-primary:hover { background: var(--primary-hover); }
        .login-link { text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted); }
        .login-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        @media (max-width: 860px) { body { flex-direction: column; } .left-panel { display: none; } .right-panel { padding: 32px 24px; } }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="brand-name">Torque<span>Point</span></div>
        <div class="tagline">Join the TorquePoint Network.</div>
        <p class="feature-desc">Create an account to book vehicle services, track your repair status in real-time, and manage your service history from one central dashboard.</p>
    </div>
    <div class="right-panel">
        <div class="form-container">
            <h2 class="form-title">Create your account</h2>
            <p class="form-subtitle">Get started in less than a minute.</p>
            <?php if (!empty($errors)): ?>
                <div class="alert-error"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="John Doe" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="john@example.com" required></div>
                <div class="form-group"><label>Phone Number (Optional)</label><input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+94 11 234 5678"></div>
                <div class="form-group"><label>Password</label><div class="input-wrapper"><input type="password" name="password" id="password" placeholder="Min. 6 characters" required><button type="button" class="password-toggle" toggle-target="#password"><i class="fa-regular fa-eye"></i></button></div></div>
                <div class="form-group"><label>Confirm Password</label><div class="input-wrapper"><input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required><button type="button" class="password-toggle" toggle-target="#confirm_password"><i class="fa-regular fa-eye"></i></button></div></div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
            <div class="login-link">Already have an account? <a href="login.html">Sign In</a></div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetSelector = this.getAttribute('toggle-target');
                const input = document.querySelector(targetSelector);
                const icon = this.querySelector('i');
                if (input.type === 'password') { input.type = 'text'; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); } 
                else { input.type = 'password'; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
            });
        });
    </script>
</body>
</html>