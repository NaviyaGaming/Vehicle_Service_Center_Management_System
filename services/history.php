<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_email = ? ORDER BY created_at DESC");
 $stmt->execute([$_SESSION['user_email']]);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $userEmail = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Service History | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF;
            --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --warning: #F59E0B;
            --danger: #EF4444; --border-color: #E2E8F0; --slate-400: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); min-height: 100vh; }
        
        .top-header { width: 100%; padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); background: var(--surface); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand-logo-img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; }
        .brand-text h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; }
        .brand-text h2 span { color: var(--primary); }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .user-info { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; }

        .container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
        .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .page-head h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; color: var(--text-main); }
        .btn-primary { background: var(--primary); color: var(--surface); padding: 12px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); }

        .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 16px 24px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        .data-table td { padding: 20px 24px; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #FCFCFD; }

        .id-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-muted); }
        .text-muted { color: var(--text-muted); font-size: 13px; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-cancelled { background: rgba(100, 116, 139, 0.1); color: var(--text-muted); }

        .action-group { display: flex; gap: 8px; }
        .btn-view { background: transparent; color: var(--primary); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; display: inline-block; }
        .btn-view:hover { background: var(--bg-main); border-color: var(--primary); }
        
        .btn-cancel { background: transparent; color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-cancel:hover { background: var(--danger); color: white; border-color: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 40px; color: var(--border-color); margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Montserrat', sans-serif; font-size: 18px; margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; }

        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--text-main); color: white; padding: 12px 20px; border-radius: 8px; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        /* ==========================================
           Visual Service Tracker Styles
           ========================================== */
        .tracker { display: flex; gap: 4px; align-items: center; }
        .tracker-step { display: flex; flex-direction: column; align-items: center; position: relative; width: 90px; }
        .tracker-step:not(:last-child)::after {
            content: ''; position: absolute; top: 6px; left: 50%; width: 100%; height: 2px;
            background: var(--border-color); z-index: 0;
        }
        .tracker-step .dot {
            width: 12px; height: 12px; border-radius: 50%; background: var(--border-color);
            border: 2px solid var(--surface); z-index: 1; transition: all 0.3s ease;
        }
        .tracker-step .dot.active { background: var(--primary); }
        .tracker-step .dot.current { 
            background: var(--primary); 
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.2); 
            transform: scale(1.2); 
        }
        .tracker-label { 
            margin-top: 8px; font-size: 10px; color: var(--text-muted); 
            text-align: center; font-weight: 500; line-height: 1.2; 
        }
        .tracker-step:has(.dot.active) .tracker-label { color: var(--primary); font-weight: 600; }

        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .top-header { padding: 16px 4%; }
            .user-info { display: none; }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <a href="services.php" class="brand">
            <img src="logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            <div class="brand-text"><h2>Torque<span>Point</span></h2></div>
        </a>
        <div class="header-actions">
            <div class="user-info"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($userEmail); ?></div>
            <a href="profile.php" style="color: var(--text-main); text-decoration: none; font-size: 14px; font-weight: 500;">Profile</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-head">
            <h1>My Service History</h1>
            <a href="services.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Book New Service</a>
        </div>

        <div class="table-card">
            <?php if (count($bookings) == 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-car-side"></i>
                    <h3>No Services Yet</h3>
                    <p>You haven't booked any vehicle services yet. Get started today!</p>
                    <a href="services.php" class="btn-primary" style="display: inline-flex;">Book Your First Service</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>JOB ID</th>
                            <th>VEHICLE</th>
                            <th>SERVICE TYPE</th>
                            <th>PRICE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr id="row-<?php echo $booking['id']; ?>">
                                <td class="id-mono">#INV-<?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['vehicle_details']); ?>
                                    <div class="text-muted"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td class="id-mono"><?php echo htmlspecialchars($booking['price']); ?></td>
                                <td>
                                    <?php 
                                        $status = $booking['status'];
                                        // Define the stages for the progress bar
                                        $stages = ['Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup'];
                                        
                                        if ($status === 'Pending Payment') {
                                            echo "<span class='status-badge status-pending'>Pending Payment</span>";
                                        } elseif ($status === 'Cancelled') {
                                            echo "<span class='status-badge status-cancelled'>Cancelled</span>";
                                        } elseif ($status === 'Completed') {
                                            echo "<span class='status-badge status-paid'>Service Completed ✓</span>";
                                        } else {
                                            // Draw the visual progress bar!
                                            echo "<div class='tracker'>";
                                            foreach ($stages as $stage) {
                                                $active = false;
                                                $current = false;
                                                if ($status === $stage) { $current = true; $active = true; }
                                                
                                                // Logic to light up previous stages
                                                if ($status === 'In Progress' && in_array($stage, ['Vehicle Received'])) $active = true;
                                                if ($status === 'Ready for Pickup' && in_array($stage, ['Vehicle Received', 'Awaiting Parts', 'In Progress'])) $active = true;

                                                $dot_class = $active ? 'dot active' : 'dot';
                                                if ($current) $dot_class .= ' current';
                                                
                                                echo "<div class='tracker-step'>";
                                                echo "<div class='$dot_class'></div>";
                                                echo "<span class='tracker-label'>$stage</span>";
                                                echo "</div>";
                                            }
                                            echo "</div>";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="../invoice/invoice.php?id=<?php echo $booking['id']; ?>" class="btn-view">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                        
                                        <?php if ($booking['status'] === 'Pending Payment'): ?>
                                            <button class="btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <div class="toast" id="toast"></div>                                  
    <script>
        function cancelBooking(bookingId) {
            if (!confirm("Are you sure you want to cancel this booking?")) {
                return;
            }

            fetch('cancel_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(res => res.json())
            .then(data => {
                const toast = document.getElementById('toast');
                toast.innerText = data.message;
                toast.style.display = 'block';
                
                if (data.success) {
                    toast.style.background = '#10B981'; // Green for success
                    // Reload the page after 1.5 seconds to show updated status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toast.style.background = '#EF4444'; // Red for error
                    setTimeout(() => toast.style.display = 'none', 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking.');
            });
        }
    </script>
</body>
</html>
</body>
</html>