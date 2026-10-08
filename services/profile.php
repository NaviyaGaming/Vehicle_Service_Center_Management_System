<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $email = $_SESSION['user_email'];

// Fetch user data
 $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
 $stmt->execute([$email]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --danger: #EF4444; --border: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand img { width: 40px; height: 40px; border-radius: 8px; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; } .brand span { color: var(--primary); }
        .nav-actions { display: flex; gap: 24px; align-items: center; }
        .nav-actions a { text-decoration: none; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .nav-actions a:hover { color: var(--primary); }
        .nav-actions .logout { color: var(--danger); font-weight: 600; }

        .container { max-width: 650px; margin: 40px auto; padding: 0 20px; }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card h2 { font-family: 'Montserrat', sans-serif; font-size: 22px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-main); }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,82,204,0.1); }
        .form-group input:disabled { background: #f1f5f9; cursor: not-allowed; color: var(--text-muted); }
        
        .btn { background: var(--primary); color: white; border: none; padding: 14px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; }
        .btn:hover { background: var(--primary-hover); }
        
        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--text-main); color: white; padding: 12px 20px; border-radius: 8px; display: none; z-index: 1000; }
    </style>
</head>
<body>

    <div class="header">
        <a href="services.php" class="brand">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <h2>Torque<span>Point</span></h2>
        </a>
        <div class="nav-actions">
            <a href="services.php">Book Service</a>
            <a href="history.php">History</a>
            <a href="profile.php" style="color: var(--primary); font-weight:600;">Profile</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Profile Info Only -->
        <div class="card">
            <h2>Personal Information</h2>
            <form id="profileForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+94 11 234 5678">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" id="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="123 Galle Road, Colombo">
                </div>
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // Handle Profile Update
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('update_profile.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: document.getElementById('name').value,
                    phone: document.getElementById('phone').value,
                    address: document.getElementById('address').value
                })
            })
            .then(res => res.json())
            .then(data => {
                const toast = document.getElementById('toast');
                toast.innerText = data.message;
                toast.style.display = 'block';
                setTimeout(() => toast.style.display = 'none', 3000);
            });
        });
    </script>
</body>
</html>