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

// Handle Add Holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block'])) {
    $date = $_POST['date'];
    $reason = $_POST['reason'] ?? 'Holiday/Closed';
    
    if ($date) {
        try {
            $stmt = $pdo->prepare("INSERT INTO blocked_dates (date, reason) VALUES (?, ?)");
            $stmt->execute([$date, $reason]);
        } catch (PDOException $e) {
            // Ignore duplicate date errors
        }
    }
    header("Location: manage_calendar.php");
    exit;
}

// Handle Remove Holiday
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM blocked_dates WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_calendar.php");
    exit;
}

// Fetch all blocked dates
 $blocked_dates = $pdo->query("SELECT * FROM blocked_dates ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch daily paid bookings if a date is selected
 $report_date = $_GET['report_date'] ?? null;
 $daily_bookings = [];
if ($report_date) {
    $paid_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
    $placeholders = implode(',', array_fill(0, count($paid_statuses), '?'));
    $stmt_report = $pdo->prepare("SELECT id, vehicle_details, booking_time, status FROM bookings WHERE booking_date = ? AND status IN ($placeholders) ORDER BY booking_time ASC");
    $stmt_report->execute(array_merge([$report_date], $paid_statuses));
    $daily_bookings = $stmt_report->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Calendar | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --danger: #EF4444; --border: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin: 0; } .brand span { color: var(--primary); }
        .nav a { text-decoration: none; color: var(--text-muted); margin-left: 20px; font-size: 14px; font-weight: 500; }
        .nav a:hover { color: var(--primary); }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h3 { margin: 0 0 16px 0; font-family: 'Montserrat'; font-size: 18px; }
        .form-row { display: flex; gap: 12px; align-items: flex-end; }
        .form-group { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 12px; font-weight: 600; color: var(--text-muted); }
        input, select { padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-family: 'Inter'; font-size: 14px; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap; height: fit-content; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .btn-delete { color: var(--danger); text-decoration: none; font-size: 14px; font-weight: 600; }
        .text-muted { color: var(--text-muted); text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><h2>Torque<span>Point</span> Admin</h2></div>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_calendar.php" style="color: var(--primary); font-weight: 600;">Manage Calendar</a>
            <a href="../services/logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Manage Garage Calendar</h1>
        
        <div class="card">
            <h3>Block a Date (Holiday/Closed)</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Date to Block</label>
                        <input type="date" name="date" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Reason (Optional)</label>
                        <input type="text" name="reason" placeholder="e.g., Poya Day, New Year">
                    </div>
                    <button type="submit" name="add_block" class="btn"><i class="fa-solid fa-ban"></i> Block Date</button>
                </div>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blocked_dates)): ?>
                        <tr><td colspan="3" class="text-muted">No dates blocked currently.</td></tr>
                    <?php else: ?>
                        <?php foreach ($blocked_dates as $b): ?>
                        <tr>
                            <td style="font-family: 'JetBrains Mono'; font-weight: 600; color: var(--danger);">
                                <?php echo date('D, M j, Y', strtotime($b['date'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($b['reason']); ?></td>
                            <td><a href="manage_calendar.php?delete=<?php echo $b['id']; ?>" class="btn-delete" onclick="return confirm('Unblock this date?')"><i class="fa-solid fa-trash"></i> Unblock</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- DAILY PAID BOOKINGS REPORT -->
        <div class="card">
            <h3>View Paid Bookings by Date</h3>
            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label>Select Date</label>
                    <input type="date" name="report_date" value="<?php echo htmlspecialchars($report_date ?? ''); ?>" required>
                </div>
                <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i> View Bookings</button>
            </form>

            <?php if ($report_date): ?>
                <table style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Time</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daily_bookings)): ?>
                            <tr><td colspan="4" class="text-muted">No paid bookings on this date.</td></tr>
                        <?php else: ?>
                            <?php foreach ($daily_bookings as $b): ?>
                            <tr>
                                <td style="font-family: 'JetBrains Mono'; font-weight: 600; color: var(--primary);">#INV-<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['booking_time'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($b['vehicle_details']); ?></td>
                                <td><?php echo htmlspecialchars($b['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted" style="text-align: left; padding: 0;">Select a date and click "View Bookings" to see the schedule.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>