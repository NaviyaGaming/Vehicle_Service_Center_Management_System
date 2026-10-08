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

// Fetch Quick Stats for the Profile Page
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_email = ?");
 $stmt->execute([$email]);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $totalBookings = count($bookings);
 $totalSpent = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'Paid') {
        $priceNum = floatval(str_replace(['Rs.', ' ', ','], '', $b['price']));
        $totalSpent += $priceNum;
    }
}

// Get user initials for Avatar
 $name = $user['name'] ?? $email;
 $initials = strtoupper(substr($name, 0, 1));
if (strpos($name, ' ') !== false) {
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

// Format Member Since Date
 $memberSince = date('M Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-title { font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 600; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }

        /* Left Column - Profile Header */
        .profile-header { text-align: center; padding: 32px 24px; }
        .avatar { width: 80px; height: 80px; background: var(--primary); color: white; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 700; }
        .profile-name { font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .profile-email { color: var(--text-muted); font-size: 14px; margin-bottom: 16px; word-break: break-all; }
        .badge { display: inline-block; background: rgba(0, 82, 204, 0.1); color: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
        .stat-box { background: var(--bg-main); padding: 16px; border-radius: 8px; text-align: center; }
        .stat-label { font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; }
        .stat-value { font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 700; color: var(--text-main); }

        /* Right Column - Edit Form */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-main); }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,82,204,0.1); }
        .form-group input:disabled { background: #f1f5f9; cursor: not-allowed; color: var(--text-muted); }
        .form-group input:read-only { background: #f1f5f9; cursor: not-allowed; color: var(--text-muted); }
        #btnContainer { display: flex; gap: 12px; }
        .btn-cancel { background: var(--text-muted); color: white; display: none; }
        .btn { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; }
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
        <!-- Left Column: Profile Info & Stats -->
        <div class="card profile-header">
            <div class="avatar"><?php echo $initials; ?></div>
            <div class="profile-name"><?php echo htmlspecialchars($user['name'] ?? 'TorquePoint User'); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
            <div class="badge"><i class="fa-solid fa-calendar-check"></i> Member since <?php echo $memberSince; ?></div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Spent</div>
                    <div class="stat-value">Rs. <?php echo number_format($totalSpent, 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Form -->
        <div class="card">
            <div class="card-title">Personal Information</div>
                <form id="profileForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Enter your full name" readonly>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+94 11 234 5678" readonly>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" id="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="123 Galle Road, Colombo" readonly>
                </div>
                
                <!-- Button Container -->
                <div id="btnContainer">
                    <button type="button" class="btn" style="background: var(--text-main);" onclick="enableEdit()"><i class="fa-solid fa-pen-to-square"></i> Edit Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

        <script>
        function enableEdit() {
            // Remove readonly from inputs
            document.getElementById('name').readOnly = false;
            document.getElementById('phone').readOnly = false;
            document.getElementById('address').readOnly = false;
            
            // Change button to 'Save'
            const btnContainer = document.getElementById('btnContainer');
            btnContainer.innerHTML = `
                <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Save Changes</button>
                <button type="button" class="btn btn-cancel" onclick="cancelEdit()"><i class="fa-solid fa-times"></i> Cancel</button>
            `;
        }

        function cancelEdit() {
            location.reload(); // Just reload to revert changes and make them readonly again
        }

        // Handle Profile Update (when Save Changes is clicked)
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
                setTimeout(() => location.reload(), 1500); // Reload to lock inputs again
            });
        });
    </script>
</body>
</html>