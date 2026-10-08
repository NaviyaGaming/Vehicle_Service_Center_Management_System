<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

// Fetch ONLY this user's bookings
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_email = ? ORDER BY created_at DESC");
 $stmt->execute([$_SESSION['user_email']]);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Service History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --border: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; }
        .header a { text-decoration: none; color: var(--primary); font-weight: 600; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .card-info h3 { margin: 0 0 8px 0; font-size: 18px; } .card-info p { margin: 0; color: var(--text-muted); font-size: 14px; }
        .btn { background: var(--primary); color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h2>TorquePoint</h2>
        <a href="services.php">Book New Service</a>
    </div>
    <div class="container">
        <h1>My Service History</h1>
        <?php if (count($bookings) == 0): ?>
            <p>You have no past services yet.</p>
        <?php endif; ?>

        <?php foreach ($bookings as $booking): ?>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                    <p>Vehicle: <?php echo htmlspecialchars($booking['vehicle_details']); ?> | Date: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?> | Status: <?php echo $booking['status']; ?></p>
                </div>
                <a href="../invoice/invoice.php?id=<?php echo $booking['id']; ?>" class="btn">View Invoice</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>