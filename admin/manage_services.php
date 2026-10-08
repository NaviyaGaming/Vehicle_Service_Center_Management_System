<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    echo "Access Denied. You are not an administrator.";
    exit;
}

// Handle Add Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $type = $_POST['vehicle_type'];
    $name = $_POST['service_name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $time = $_POST['time'];
    
    $stmt = $pdo->prepare("INSERT INTO service_packages (vehicle_type, service_name, description, price, time) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, $name, $desc, $price, $time]);
    header("Location: manage_services.php");
    exit;
}

// Handle Delete Service
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM service_packages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_services.php");
    exit;
}

// Fetch all services
 $services = $pdo->query("SELECT * FROM service_packages ORDER BY vehicle_type ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --danger: #EF4444; --border: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin: 0; } .brand span { color: var(--primary); }
        .nav a { text-decoration: none; color: var(--text-muted); margin-left: 20px; font-size: 14px; font-weight: 500; }
        .nav a:hover { color: var(--primary); }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 24px; }
        label { font-size: 12px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .btn-delete { color: var(--danger); text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><h2>Torque<span>Point</span> Admin</h2></div>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_services.php" style="color: var(--primary); font-weight: 600;">Manage Services</a>
            <a href="../services/logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Manage Service Packages & Pricing</h1>
        
        <div class="card">
            <h3 style="margin-bottom: 16px; font-family: 'Montserrat';">Add New Service Package</h3>
            <form method="POST">
                <div class="form-row">
                    <div><label>Vehicle Type</label><select name="vehicle_type" required><option value="car">Car</option><option value="suv">SUV</option><option value="van">Van</option><option value="motorcycle">Motorcycle</option></select></div>
                    <div><label>Service Name</label><input type="text" name="service_name" placeholder="e.g., AC Service" required></div>
                    <div><label>Price (Rs.)</label><input type="text" name="price" placeholder="e.g., 7,500" required></div>
                    <div><label>Est. Time</label><input type="text" name="time" placeholder="e.g., 2 Hours" required></div>
                    <div style="grid-column: span 2;"><label>Description</label><input type="text" name="description" placeholder="Brief description of service"></div>
                    <div><button type="submit" name="add_service" class="btn"><i class="fa-solid fa-plus"></i> Add</button></div>
                </div>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle Type</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars($s['vehicle_type']); ?></td>
                        <td><?php echo htmlspecialchars($s['service_name']); ?></td>
                        <td style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars($s['description']); ?></td>
                        <td style="font-family: 'JetBrains Mono'; font-weight: 600;">Rs. <?php echo htmlspecialchars($s['price']); ?></td>
                        <td><?php echo htmlspecialchars($s['time']); ?></td>
                        <td><a href="manage_services.php?delete=<?php echo $s['id']; ?>" class="btn-delete" onclick="return confirm('Delete this service?')"><i class="fa-solid fa-trash"></i> Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>