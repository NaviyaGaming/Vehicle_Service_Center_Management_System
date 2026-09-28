<?php
session_start();
require_once '../db_connect.php';

// If user is not logged in, send them to login
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorquePoint | Invoice #<?php echo $booking['id']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="invoice.css">
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="../home/home.html" class="brand" style="text-decoration: none; color: inherit;">
            <div class="brand-icon">
                <img src="../services/logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            </div>
            <div class="brand-text">
                <h2>Torque<span>Point</span></h2>
            </div>
        </a>
        <div class="header-link">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            Invoice
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

            <!-- Payment Button -->
            <!-- Replace the old button with this link -->
            <a href="stripe-checkout.php?booking_id=<?php echo $booking['id']; ?>" class="btn-pay" style="text-decoration: none; display: block; text-align: center;">
                <i class="fa-brands fa-cc-visa"></i>
                 Pay Securely with Stripe
            </a>
            <a href="../services/services.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Services
            </a>
        </div>
    </main>

    <script src="invoice.js"></script>
</body>
</html>