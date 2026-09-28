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

// Fetch saved vehicles
 $stmt = $pdo->prepare("SELECT * FROM user_vehicles WHERE user_email = ? ORDER BY created_at DESC");
 $stmt->execute([$email]);
 $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card h2 { font-family: 'Montserrat', sans-serif; font-size: 20px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-main); }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,82,204,0.1); }
        
        .btn { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; }
        .btn:hover { background: var(--primary-hover); }
        
        .vehicle-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: var(--bg-main); border-radius: 8px; margin-bottom: 12px; border: 1px solid var(--border); }
        .vehicle-info h4 { font-size: 16px; margin-bottom: 4px; }
        .vehicle-info p { font-size: 13px; color: var(--text-muted); }
        .btn-book { background: transparent; color: var(--primary); border: 1px solid var(--primary); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-book:hover { background: var(--primary); color: white; }
        
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
        <!-- Profile Info -->
        <div class="card">
            <h2>Personal Information</h2>
            <form id="profileForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled style="background: #e2e8f0; cursor: not-allowed;">
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

        <!-- Vehicle Garage -->
        <div class="card">
            <h2>My Vehicle Garage</h2>
            
            <!-- Add New Vehicle Form -->
            <form id="vehicleForm" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label>Vehicle Type</label>
                        <select id="v_type" required>
                            <option value="">Select Type</option>
                            <option value="Car">Car</option>
                            <option value="SUV">SUV</option>
                            <option value="Van">Van</option>
                            <option value="Motorcycle">Motorcycle</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Make</label>
                        <input type="text" id="v_make" placeholder="Toyota" required>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" id="v_model" placeholder="Corolla" required>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" id="v_year" placeholder="2015" min="1990" max="2024" required>
                    </div>
                </div>
                <button type="submit" class="btn" style="background: var(--text-main);">+ Add to Garage</button>
            </form>

            <!-- Saved Vehicles List -->
            <div id="vehiclesList">
                <?php if (empty($vehicles)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 20px;">Your garage is empty. Add a vehicle above!</p>
                <?php else: ?>
                    <?php foreach ($vehicles as $v): ?>
                        <div class="vehicle-item">
                            <div class="vehicle-info">
                                <h4><?php echo htmlspecialchars($v['vehicle_year'] . ' ' . $v['vehicle_make'] . ' ' . $v['vehicle_model']); ?></h4>
                                <p>Type: <?php echo htmlspecialchars($v['vehicle_type']); ?></p>
                            </div>
                            <!-- Link to services page with GET params to autofill -->
                            <a href="services.php?auto=1&type=<?php echo urlencode($v['vehicle_type']); ?>&make=<?php echo urlencode($v['vehicle_make']); ?>&model=<?php echo urlencode($v['vehicle_model']); ?>&year=<?php echo urlencode($v['vehicle_year']); ?>" class="btn-book">
                                Book Service
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                showToast(data.message);
            });
        });

        // Handle Add Vehicle
        document.getElementById('vehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('update_profile.php?action=add_vehicle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: document.getElementById('v_type').value,
                    make: document.getElementById('v_make').value,
                    model: document.getElementById('v_model').value,
                    year: document.getElementById('v_year').value
                })
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message);
                if (data.success) {
                    setTimeout(() => location.reload(), 1500); // Reload to show new vehicle
                }
            });
        });

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.innerText = msg;
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }
    </script>
</body>
</html>