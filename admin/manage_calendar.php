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
    header("Location: manage_calendar.php?m=" . date('m', strtotime($date)) . "&y=" . date('Y', strtotime($date)));
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
 $blocked_dates_db = $pdo->query("SELECT * FROM blocked_dates ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);
 $blocked_days_array = array_column($blocked_dates_db, 'date');
 $blocked_reasons = array_column($blocked_dates_db, 'reason', 'date');

// Fetch ALL paid bookings and group them by date
 $paid_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
 $placeholders = implode(',', array_fill(0, count($paid_statuses), '?'));
 $stmt_paid = $pdo->prepare("SELECT id, booking_date, booking_time FROM bookings WHERE booking_date IS NOT NULL AND status IN ($placeholders)");
 $stmt_paid->execute($paid_statuses);
 $all_paid_bookings = $stmt_paid->fetchAll(PDO::FETCH_ASSOC);

 $daily_invoices = [];
foreach ($all_paid_bookings as $b) {
    $date = $b['booking_date'];
    if (!isset($daily_invoices[$date])) {
        $daily_invoices[$date] = [];
    }
    $daily_invoices[$date][] = [
        'id' => $b['id'],
        'time' => $b['booking_time']
    ];
}

// Calendar Logic: Get current month and year
 $month = isset($_GET['m']) ? intval($_GET['m']) : date('m');
 $year = isset($_GET['y']) ? intval($_GET['y']) : date('Y');

if ($month == 0) { $month = 12; $year--; }
if ($month == 13) { $month = 1; $year++; }

 $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
 $first_day_of_week = date('w', strtotime("$year-$month-01"));
 $today = date('Y-m-d');
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
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        
        .layout-grid { display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px; }
        @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }

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

        /* Calendar Styles */
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .cal-header h3 { margin: 0; font-family: 'Montserrat'; font-size: 20px; }
        .cal-nav { color: var(--primary); text-decoration: none; font-size: 20px; cursor: pointer; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; }
        .cal-day-name { text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); padding-bottom: 8px; }
        .cal-day { min-height: 120px; padding: 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-weight: 500; transition: 0.2s; display: flex; flex-direction: column; gap: 6px; cursor: pointer; position: relative; }
        .cal-day:hover { border-color: var(--primary); background: #FCFCFD; }
        .cal-day.past { color: #CBD5E1; background: #F8FAFC; cursor: not-allowed; border-color: transparent; }
        .cal-day.blocked { color: #EF4444; background: #FEF2F2; cursor: not-allowed; border-color: transparent; }
        .cal-day-num { font-weight: 700; font-family: 'Montserrat', sans-serif; }
        
        .invoice-badge { background: #EFF6FF; color: var(--primary); padding: 4px 6px; border-radius: 4px; font-size: 11px; font-family: 'JetBrains Mono', monospace; font-weight: 600; display: block; text-decoration: none; border: 1px solid #DBEAFE; }
        .invoice-badge:hover { background: var(--primary); color: white; }
        
        .blocked-tag { background: #FEE2E2; color: var(--danger); padding: 4px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; text-align: center; margin-top: auto; }
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
        
        <div class="layout-grid">
            <!-- Left Side: Huge Visual Calendar -->
            <div class="card">
                <div class="cal-header">
                    <a class="cal-nav" href="?m=<?php echo $month-1; ?>&y=<?php echo $year; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                    <h3><?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
                    <a class="cal-nav" href="?m=<?php echo $month+1; ?>&y=<?php echo $year; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <div class="cal-grid">
                    <div class="cal-day-name">Sun</div><div class="cal-day-name">Mon</div><div class="cal-day-name">Tue</div><div class="cal-day-name">Wed</div><div class="cal-day-name">Thu</div><div class="cal-day-name">Fri</div><div class="cal-day-name">Sat</div>
                    
                    <?php for ($i = 0; $i < $first_day_of_week; $i++) echo "<div class='cal-day past'></div>"; ?>
                    
                    <?php for ($d = 1; $d <= $days_in_month; $d++): 
                        $date_str = sprintf("%04d-%02d-%02d", $year, $month, $d);
                        $is_past = $date_str < $today;
                        $is_blocked = in_array($date_str, $blocked_days_array);
                        $invoices = $daily_invoices[$date_str] ?? [];
                    ?>
                        <div class="cal-day <?php echo $is_past ? 'past' : ''; ?> <?php echo $is_blocked ? 'blocked' : ''; ?>" onclick="selectDateForBlock('<?php echo $date_str; ?>')">
                            <span class="cal-day-num"><?php echo $d; ?></span>
                            
                            <?php if (!$is_past && !$is_blocked): ?>
                                <?php foreach($invoices as $inv): ?>
                                    <a href="../invoice/invoice.php?id=<?php echo $inv['id']; ?>" class="invoice-badge" onclick="event.stopPropagation()">
                                        #INV-<?php echo $inv['id']; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($is_blocked): ?>
                                <div class="blocked-tag"><?php echo htmlspecialchars($blocked_reasons[$date_str] ?? 'Closed'); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Right Side: Block Form & List -->
            <div>
                <div class="card">
                    <h3>Block a Date</h3>
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label>Date to Block</label>
                            <input type="date" name="date" id="block_date_input" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label>Reason</label>
                            <input type="text" name="reason" id="block_reason_input" placeholder="e.g., Poya Day">
                        </div>
                        <button type="submit" name="add_block" class="btn" style="width: 100%;"><i class="fa-solid fa-ban"></i> Block Date</button>
                    </form>
                </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <h3 style="padding: 24px 24px 0 24px; margin-bottom: 0;">Blocked Dates List</h3>
                    <table style="margin-top: 16px;">
                        <tbody>
                            <?php if (empty($blocked_dates_db)): ?>
                                <tr><td class="text-muted">No dates blocked.</td></tr>
                            <?php else: ?>
                                <?php foreach ($blocked_dates_db as $b): ?>
                                <tr>
                                    <td style="font-family: 'JetBrains Mono'; font-weight: 600; color: var(--danger); font-size: 12px;">
                                        <?php echo date('M j, Y', strtotime($b['date'])); ?>
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($b['reason']); ?></td>
                                    <td style="text-align: right;"><a href="manage_calendar.php?delete=<?php echo $b['id']; ?>" class="btn-delete" onclick="return confirm('Unblock?')"><i class="fa-solid fa-trash"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // When admin clicks a day on the calendar, auto-fill the block date form
        function selectDateForBlock(dateStr) {
            const dateInput = document.getElementById('block_date_input');
            // Only fill if it's not already blocked
            if (!event.target.classList.contains('blocked')) {
                dateInput.value = dateStr;
                document.getElementById('block_reason_input').focus();
            }
        }
    </script>
</body>
</html>