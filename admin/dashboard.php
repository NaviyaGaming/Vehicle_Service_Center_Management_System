<?php
// Turn on error reporting temporarily to see if any errors exist
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// ===== Get Search & Filter parameters from URL =====
 $search = $_GET['search'] ?? '';
 $status = $_GET['status'] ?? '';

// Fetch ALL bookings for the global charts (unfiltered)
 $allBookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch FILTERED bookings for the data table (WITH MECHANIC JOIN)
 $sql = "SELECT b.*, m.name as mechanic_name FROM bookings b LEFT JOIN mechanics m ON b.assigned_mechanic_id = m.id WHERE 1=1";
 $params = [];

if (!empty($search)) {
    $sql .= " AND (b.user_email LIKE ? OR b.vehicle_details LIKE ? OR b.service_name LIKE ? OR b.id LIKE ?)";
    $searchTerm = "%" . $search . "%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
if (!empty($status)) {
    $sql .= " AND b.status = ?";
    array_push($params, $status);
}

 $sql .= " ORDER BY b.created_at DESC";
 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC); // This is the filtered list for the table

// Calculate quick stats based on ALL bookings (not filtered)
 $totalBookings = count($allBookings);
 $totalRevenue = 0;
 $pendingCount = 0;

// Arrays for Charts
 $last7Days = [];
 $serviceCounts = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last7Days[$date] = 0;
}

foreach ($allBookings as $b) {
    if ($b['status'] === 'Paid') {
        $priceNum = floatval(str_replace(['Rs.', ' ', ','], '', $b['price']));
        $totalRevenue += $priceNum;
        $bookingDate = date('Y-m-d', strtotime($b['created_at']));
        if (array_key_exists($bookingDate, $last7Days)) {
            $last7Days[$bookingDate] += $priceNum;
        }
    } elseif ($b['status'] === 'Pending Payment') {
        $pendingCount++;
    }

    if ($b['status'] !== 'Cancelled') {
        $sName = $b['service_name'];
        if (!isset($serviceCounts[$sName])) {
            $serviceCounts[$sName] = 0;
        }
        $serviceCounts[$sName]++;
    }
}

 $chartDates = array_keys($last7Days);
 $chartDatesFormatted = array_map(fn($d) => date('D, M j', strtotime($d)), $chartDates);
 $chartRevenue = array_values($last7Days);

 $chartServiceLabels = array_keys($serviceCounts);
 $chartServiceData = array_values($serviceCounts);

// Fetch Mechanic Team Status for the widget
 $mechanics = $pdo->query("SELECT * FROM mechanics ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
 $availableMechanics = count(array_filter($mechanics, fn($m) => $m['is_available'] == 1));

// Build query string for CSV export link
 $csvQuery = http_build_query(['search' => $search, 'status' => $status]);
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
    <!-- Add Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF;
            --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --warning: #F59E0B;
            --danger: #EF4444; --border-color: #E2E8F0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); min-height: 100vh; }

        .top-header { width: 100%; padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); background: var(--surface); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand-logo-img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; }
        .brand-text h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; }
        .brand-text h2 span { color: var(--primary); }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .admin-badge { background: rgba(0, 82, 204, 0.1); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .user-info { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-head { margin-bottom: 32px; }
        .page-head h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; color: var(--text-main); }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-label { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
        .stat-value { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; color: var(--text-main); }
        .stat-icon { float: right; font-size: 24px; opacity: 0.2; }
        .stat-card.revenue .stat-value { color: var(--success); }
        .stat-card.pending .stat-value { color: var(--warning); }

        .charts-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; margin-bottom: 40px; }
        .chart-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .chart-card h3 { font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 20px; color: var(--text-main); }
        .chart-container { position: relative; height: 300px; width: 100%; }

        /* ===== Mechanic Team Status Widget ===== */
        .mech-team-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .mech-title { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px; }
        .mech-list { display: flex; gap: 20px; flex-wrap: wrap; }
        .mech-item { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; }
        .mech-dot { width: 10px; height: 10px; border-radius: 50%; }
        .mech-available { background: var(--success); }
        .mech-busy { background: var(--warning); }
        .mech-status { color: var(--text-muted); font-weight: 500; font-size: 12px; }
        .mech-count { text-align: right; }
        .mech-count-num { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; color: var(--primary); }
        .mech-count-label { font-size: 12px; color: var(--text-muted); }

        /* ===== Search & Filter Bar ===== */
        .toolbar { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .toolbar form { display: flex; gap: 12px; flex-grow: 1; align-items: center; flex-wrap: wrap; }
        .input-search { flex-grow: 1; min-width: 200px; padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; }
        .input-search:focus { outline: none; border-color: var(--primary); }
        .select-status { padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; background: var(--surface); cursor: pointer; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-export { background: var(--text-main); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-export:hover { opacity: 0.9; }

        .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 16px 24px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        .data-table td { padding: 20px 24px; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border-color); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #FCFCFD; }
        .id-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-muted); }
        .text-muted { color: var(--text-muted); font-size: 13px; }
        .btn-view { background: transparent; color: var(--primary); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: var(--bg-main); border-color: var(--primary); }

        .mechanic-badge { background: rgba(0, 82, 204, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }

        .status-dropdown { padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--surface); font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: var(--text-main); cursor: pointer; }
        .status-dropdown:focus { outline: none; border-color: var(--primary); }

        @media (max-width: 900px) { .stats-grid, .charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .data-table { display: block; overflow-x: auto; white-space: nowrap; } .top-header { padding: 16px 4%; } .user-info { display: none; } }
    </style>
</head>
<body>

    <header class="top-header">
        <a href="dashboard.php" class="brand">
            <img src="../services/logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            <div class="brand-text"><h2>Torque<span>Point</span> Admin</h2></div>
        </a>
        <div class="header-actions">
            <span class="admin-badge"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
            <div class="user-info"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
            <a href="../services/logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-head"><h1>Service Center Overview</h1></div>

        <!-- MECHANIC TEAM STATUS WIDGET -->
        <div class="mech-team-card">
            <div>
                <div class="mech-title">MECHANIC TEAM STATUS</div>
                <div class="mech-list">
                    <?php foreach($mechanics as $m): ?>
                        <div class="mech-item">
                            <span class="mech-dot <?php echo $m['is_available'] ? 'mech-available' : 'mech-busy'; ?>"></span>
                            <?php echo htmlspecialchars($m['name']); ?>
                            <span class="mech-status">(<?php echo $m['is_available'] ? 'Available' : 'Busy'; ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mech-count">
                <div class="mech-count-num"><?php echo $availableMechanics; ?> / <?php echo count($mechanics); ?></div>
                <div class="mech-count-label">Available Now</div>
            </div>
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

        <!-- CHARTS SECTION -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Revenue (Last 7 Days)</h3>
                <div class="chart-container"><canvas id="revenueChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h3>Most Popular Services</h3>
                <div class="chart-container"><canvas id="servicesChart"></canvas></div>
            </div>
        </div>

        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="toolbar">
            <form method="GET">
                <input type="text" name="search" class="input-search" placeholder="Search by Email, Vehicle, or Job ID..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status" class="select-status">
                    <option value="">All Statuses</option>
                    <option value="Pending Payment" <?php if($status == 'Pending Payment') echo 'selected'; ?>>Pending Payment</option>
                    <option value="Paid" <?php if($status == 'Paid') echo 'selected'; ?>>Paid</option>
                    <option value="Vehicle Received" <?php if($status == 'Vehicle Received') echo 'selected'; ?>>Vehicle Received</option>
                    <option value="Awaiting Parts" <?php if($status == 'Awaiting Parts') echo 'selected'; ?>>Awaiting Parts</option>
                    <option value="In Progress" <?php if($status == 'In Progress') echo 'selected'; ?>>In Progress</option>
                    <option value="Ready for Pickup" <?php if($status == 'Ready for Pickup') echo 'selected'; ?>>Ready for Pickup</option>
                    <option value="Completed" <?php if($status == 'Completed') echo 'selected'; ?>>Completed</option>
                    <option value="Cancelled" <?php if($status == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
                <a href="dashboard.php" class="btn-view" style="padding: 10px 16px;">Clear</a>
            </form>
            <a href="export_csv.php?<?php echo $csvQuery; ?>" class="btn-export">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
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
                        <th>MECHANIC</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) == 0): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No bookings found matching your search.</td></tr>
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
                                <td>
                                    <?php 
                                        if (!empty($booking['mechanic_name'])) {
                                            echo "<span class='mechanic-badge'>{$booking['mechanic_name']}</span>";
                                        } else {
                                            echo "<span class='text-muted'>Unassigned</span>";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <select class="status-dropdown" onchange="updateStatus(<?php echo $booking['id']; ?>, this.value)">
                                        <option value="Pending Payment" <?php echo ($booking['status'] == 'Pending Payment') ? 'selected' : ''; ?>>Pending Payment</option>
                                        <option value="Paid" <?php echo ($booking['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Vehicle Received" <?php echo ($booking['status'] == 'Vehicle Received') ? 'selected' : ''; ?>>Vehicle Received</option>
                                        <option value="Awaiting Parts" <?php echo ($booking['status'] == 'Awaiting Parts') ? 'selected' : ''; ?>>Awaiting Parts</option>
                                        <option value="In Progress" <?php echo ($booking['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Ready for Pickup" <?php echo ($booking['status'] == 'Ready for Pickup') ? 'selected' : ''; ?>>Ready for Pickup</option>
                                        <option value="Completed" <?php echo ($booking['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </td>
                                <td><a href="../invoice/invoice.php?id=<?php echo $booking['id']; ?>" class="btn-view"><i class="fa-solid fa-eye"></i> View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Chart.js Scripts
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartDatesFormatted); ?>,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: <?php echo json_encode($chartRevenue); ?>,
                    backgroundColor: 'rgba(0, 82, 204, 0.6)',
                    borderColor: 'rgba(0, 82, 204, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        const ctxServ = document.getElementById('servicesChart').getContext('2d');
        const colorPalette = [
            'rgba(0, 82, 204, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(139, 92, 246, 0.8)', 'rgba(14, 165, 233, 0.8)', 'rgba(236, 72, 153, 0.8)', 'rgba(217, 119, 6, 0.8)', 'rgba(20, 184, 166, 0.8)', 'rgba(99, 102, 241, 0.8)', 'rgba(132, 204, 22, 0.8)', 'rgba(168, 85, 247, 0.8)'
        ];
        new Chart(ctxServ, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($chartServiceLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($chartServiceData); ?>,
                    backgroundColor: colorPalette,
                    borderWidth: 2, borderColor: '#FFFFFF'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } } } }
        });

        // Status Update Script
        function updateStatus(bookingId, newStatus) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId, new_status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    alert(data.message); 
                    location.reload(); // Reload to update mechanic widget immediately
                } else { 
                    alert('Error: ' + data.message); 
                    location.reload(); 
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>