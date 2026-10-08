<?php
session_start();
require_once '../db_connect.php';

// If user is not logged in, send them to login
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $userEmail = $_SESSION['user_email'];

// Fetch user data for Avatar
 $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
 $stmt->execute([$userEmail]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);
 $userName = $user['name'] ?? $userEmail;
 $initials = strtoupper(substr($userName, 0, 1));
if (strpos($userName, ' ') !== false) {
    $parts = explode(' ', $userName);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

// Get the booking ID from the URL (?id=123)
 $booking_id = $_GET['id'] ?? 0;

// Fetch the specific booking from the database for this user
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

// If booking doesn't exist or doesn't belong to the logged-in user, show error
if (!$booking) {
    die("Invoice not found or you do not have permission to view it.");
}

// Calculate totals
 $priceNumber = floatval(str_replace(['Rs. ', ','], '', $booking['price']));
 $tax = 0;
 $total = $priceNumber + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorquePoint | Invoice #<?php echo $booking['id']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="invoice.css">
    
    <!-- Avatar Dropdown CSS -->
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --border-color: #E2E8F0; }
        .avatar-dropdown { position: relative; display: flex; align-items: center; margin-left: 10px; }
        .header-avatar { width: 40px; height: 40px; background: var(--primary); color: var(--surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
        .header-avatar:hover { border-color: var(--primary-hover); }
        .dropdown-menu { position: absolute; top: 120%; right: 0; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 160px; z-index: 1000; display: none; flex-direction: column; overflow: hidden; }
        .dropdown-menu a { padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border-color); transition: 0.2s; }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover { background: var(--bg-main); color: var(--primary); }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="../home/home.php" class="brand" style="text-decoration: none; color: inherit;">
            <div class="brand-icon">
                <img src="../logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            </div>
            <div class="brand-text">
                <h2>Torque<span>Point</span></h2>
            </div>
        </a>
        
        <!-- Avatar Dropdown (Paths adjusted for invoice folder) -->
        <div class="avatar-dropdown">
            <div class="header-avatar" onclick="toggleDropdown()"><?php echo $initials; ?></div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="../services/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="../services/history.php"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                <a href="../services/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container">
        <div class="invoice-card">
            <!-- Invoice Top Section -->
            <div class="invoice-header">
                <div>
                    <h1>Service Invoice</h1>
                    <p class="invoice-date">Date: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></p>
                </div>
                <div class="invoice-meta">
                    <p><strong>Invoice #:</strong> <span>INV-<?php echo htmlspecialchars($booking['id']); ?></span></p>
                    <p><strong>Status:</strong> <span class="status-badge status-warning"><?php echo htmlspecialchars($booking['status']); ?></span></p>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Bill To Section -->
            <div class="bill-to">
                <h3>Bill To:</h3>
                <p><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?></p>
                <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>

            <!-- Service Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>SERVICE DESCRIPTION</th>
                        <th>VEHICLE</th>
                        <th>EST. TIME</th>
                        <th class="text-right">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong><br><span style="color:var(--text-muted); font-size:12px;">Service & Maintenance</span></td>
                        <td><?php echo htmlspecialchars($booking['vehicle_details']); ?></td>
                        <td><?php echo htmlspecialchars($booking['est_time']); ?></td>
                        <td class="text-right"><?php echo htmlspecialchars($booking['price']); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="divider"></div>

            <!-- Totals Section -->
            <div class="invoice-totals">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>Rs. <?php echo number_format($priceNumber, 2); ?></span>
                </div>
                <div class="totals-row">
                    <span>Service Tax (0%)</span>
                    <span>Rs. <?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="totals-row total">
                    <span>TOTAL DUE</span>
                    <span>Rs. <?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <!-- Payment Button (Stripe) -->
            <a href="stripe-checkout.php?booking_id=<?php echo $booking['id']; ?>" class="btn-pay" style="text-decoration: none; display: block; text-align: center;">
                <i class="fa-brands fa-cc-visa"></i>
                 Pay Securely
            </a>
            
            <a href="../services/history.php" class="back-link" style="display: block; text-align: center; margin-top: 20px; color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500;">
                <i class="fa-solid fa-arrow-left"></i> Back to Services
            </a>
        </div>
    </main>

    <script src="invoice.js"></script>
    
    <!-- Avatar Dropdown Script -->
    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdownMenu');
            menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
        }
        window.onclick = function(event) {
            if (!event.target.matches('.header-avatar')) {
                const menu = document.getElementById('dropdownMenu');
                if (menu && menu.style.display === 'flex') { menu.style.display = 'none'; }
            }
        }
    </script>
</body>
</html>