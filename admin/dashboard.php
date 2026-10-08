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

// Fetch ALL bookings from the database, newest first
 $bookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate quick stats for the dashboard cards
 $totalBookings = count($bookings);
 $totalRevenue = 0;
 $pendingCount = 0;

foreach ($bookings as $b) {
    if ($b['status'] === 'Paid') {
        $priceNum = floatval(str_replace(['Rs.', ' ', ','], '', $b['price']));
        $totalRevenue += $priceNum;
    } else {
        $pendingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC;
            --primary-hover: #0042A5;
            --bg-main: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --border-color: #E2E8F0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ===== HEADER ===== */
        .top-header {
            width: 100%; padding: 20px 6%;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: var(--surface);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand-logo-img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; }
        .brand-text h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; }
        .brand-text h2 span { color: var(--primary); }
        
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .admin-badge { background: rgba(0, 82, 204, 0.1); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .user-info { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; }

        /* ===== MAIN CONTAINER ===== */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .page-head { margin-bottom: 32px; }
        .page-head h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; color: var(--text-main); }

        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .stat-label { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
        .stat-value { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; color: var(--text-main); }
        .stat-icon { float: right; font-size: 24px; opacity: 0.2; }
        .stat-card.revenue .stat-value { color: var(--success); }
        .stat-card.pending .stat-value { color: var(--warning); }

        /* ===== DATA TABLE ===== */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { 
            text-align: left; padding: 16px 24px; 
            background: var(--bg-main); color: var(--text-muted); 
            font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600; 
            text-transform: uppercase; letter-spacing: 0.5px; 
            border-bottom: 1px solid var(--border-color);
        }
        .data-table td { padding: 20px 24px; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border-color); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #FCFCFD; }

        .id-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-muted); }
        .text-muted { color: var(--text-muted); font-size: 13px; }

        /* Status Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .btn-view { background: transparent; color: var(--primary); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: var(--bg-main); border-color: var(--primary); }

        /* Responsive */
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .top-header { padding: 16px 4%; }
            .user-info { display: none; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="dashboard.php" class="brand">
            <!-- Adjust logo path if needed. Assuming it's in the services folder -->
            <img src="../services/logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            <div class="brand-text">
                <h2>Torque<span>Point</span> Admin</h2>
            </div>
        </a>
        <div class="header-actions">
            <span class="admin-badge"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
            <div class="user-info">
                <i class="fa-solid fa-user-tie"></i>
                <?php echo htmlspecialchars($_SESSION['user_email']); ?>
            </div>
            <a href="../services/logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container">
        <div class="page-head">
            <h1>Service Center Overview</h1>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-car-side stat-icon" style="color: var(--primary);"></i>
                <div class="stat-label">TOTAL BOOKINGS</div>
                <div class="stat-value"><?php echo $totalBookings; ?></div>
            </div>
            <div class="stat-card revenue">
                <i class="fa-solid fa-arrow-trend-up stat-icon" style="color: var(--success);"></i>
                <div class="stat-label">TOTAL REVENUE (PAID)</div>
                <div class="stat-value">Rs. <?php echo number_format($totalRevenue, 2); ?></div>
            </div>
            <div class="stat-card pending">
                <i class="fa-solid fa-hourglass-half stat-icon" style="color: var(--warning);"></i>
                <div class="stat-label">PENDING PAYMENTS</div>
                <div class="stat-value"><?php echo $pendingCount; ?></div>
            </div>
        </div>

        <!-- DATA TABLE -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>JOB ID</th>
                        <th>CUSTOMER EMAIL</th>
                        <th>VEHICLE</th>
                        <th>SERVICE TYPE</th>
                        <th>PRICE</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No bookings have been made yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="id-mono">#INV-<?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['user_email']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['vehicle_details']); ?>
                                    <div class="text-muted"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td class="id-mono"><?php echo htmlspecialchars($booking['price']); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = $booking['status'] === 'Paid' ? 'status-paid' : 'status-pending';
                                        echo "<span class='status-badge $statusClass'>{$booking['status']}</span>";
                                    ?>
                                </td>
                                <td>
                                    <a href="../invoice/invoice.php?id=<?php echo $booking['id']; ?>" class="btn-view">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>