<?php
session_start();
require_once '../db_connect.php';

// Security check: If not logged in, or NOT an admin, redirect them away
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

// Fetch ALL bookings from the database
 $bookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorquePoint | Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF;
            --text-main: #0F172A; --text-muted: #64748B; --success: #10B981;
            --warning: #F59E0B; --danger: #EF4444; --border-color: #E2E8F0;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; padding: 0; }
        .header { background: var(--surface); border-bottom: 1px solid var(--border-color); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin: 0; } .brand span { color: var(--primary); }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        th { text-align: left; padding: 16px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .id-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><h2>Torque<span>Point</span> Admin</h2></div>
        <a href="../services/logout.php" style="color: var(--danger); text-decoration: none; font-weight: 600;">Logout</a>
    </div>

    <div class="container">
        <h1>All Service Bookings</h1>
        <table>
            <thead>
                <tr>
                    <th>JOB ID</th>
                    <th>CUSTOMER EMAIL</th>
                    <th>VEHICLE</th>
                    <th>SERVICE</th>
                    <th>PRICE</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td class="id-mono">#INV-<?php echo $booking['id']; ?></td>
                    <td><?php echo htmlspecialchars($booking['user_email']); ?></td>
                    <td><?php echo htmlspecialchars($booking['vehicle_details']); ?></td>
                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                    <td class="id-mono"><?php echo htmlspecialchars($booking['price']); ?></td>
                    <td>
                        <?php 
                            $statusClass = $booking['status'] === 'Paid' ? 'badge-paid' : 'badge-pending';
                            echo "<span class='badge $statusClass'>{$booking['status']}</span>";
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>