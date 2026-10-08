<?php
session_start();
require_once '../db_connect.php';

// Security: Must be logged in AND have the 'mechanic' role
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $stmt = $pdo->prepare("SELECT name, mechanic_id FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['mechanic_id'] === null) {
    echo "Access Denied. You are not a registered mechanic.";
    exit;
}

 $mechanic_id = $user['mechanic_id'];

// Fetch ONLY bookings assigned to THIS mechanic that are currently active
// (We hide 'Completed' and 'Cancelled' to keep the list clean)
 $sql = "SELECT * FROM bookings WHERE assigned_mechanic_id = ? AND status NOT IN ('Completed', 'Cancelled', 'Pending Payment', 'Paid') ORDER BY created_at ASC";
 $stmt = $pdo->prepare($sql);
 $stmt->execute([$mechanic_id]);
 $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Portal | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --warning: #F59E0B; --danger: #EF4444; --border: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand img { width: 40px; height: 40px; border-radius: 8px; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; } .brand span { color: var(--primary); }
        .mech-badge { background: rgba(245, 158, 11, 0.1); color: var(--warning); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-head { margin-bottom: 24px; }
        .page-head h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; }
        .page-head p { color: var(--text-muted); font-size: 15px; margin-top: 4px; }

        .job-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .job-info h3 { font-family: 'Montserrat', sans-serif; font-size: 18px; margin-bottom: 8px; }
        .job-info p { color: var(--text-muted); font-size: 14px; margin-bottom: 4px; }
        .job-info strong { color: var(--text-main); }
        
        .job-actions { display: flex; flex-direction: column; gap: 8px; min-width: 200px; }
        .status-select { padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; color: var(--text-main); background: var(--bg-main); cursor: pointer; }
        .status-select:focus { outline: none; border-color: var(--primary); }

        .empty-state { text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 60px 20px; }
        .empty-state i { font-size: 40px; color: var(--border); margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Montserrat', sans-serif; font-size: 18px; margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); font-size: 14px; }
    </style>
</head>
<body>

    <div class="header">
        <a href="mechanic.php" class="brand">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <h2>Torque<span>Point</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 20px;">Mechanic</span></h2>
        </a>
        <div style="display: flex; align-items: center; gap: 16px;">
            <span class="mech-badge"><i class="fa-solid fa-wrench"></i> <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-head">
            <h1>My Assigned Jobs</h1>
            <p>These are the vehicles currently assigned to you. Please update the status as you work.</p>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-mug-hot"></i>
                <h3>No Active Jobs</h3>
                <p>You have no vehicles assigned to you right now. Enjoy a cup of coffee!</p>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-info">
                        <h3><?php echo htmlspecialchars($job['vehicle_details']); ?></h3>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($job['service_name']); ?></p>
                        <p><strong>Current Status:</strong> <?php echo htmlspecialchars($job['status']); ?></p>
                    </div>
                    <div class="job-actions">
                        <select class="status-select" onchange="updateJobStatus(<?php echo $job['id']; ?>, this.value)">
                            <option value="">-- Update Status --</option>
                            <?php if ($job['status'] == 'Vehicle Received' || $job['status'] == 'Awaiting Parts'): ?>
                                <option value="In Progress">Start Working (In Progress)</option>
                            <?php endif; ?>
                            <option value="Awaiting Parts">Mark as Awaiting Parts</option>
                            <option value="Ready for Pickup">Mark as Ready for Pickup</option>
                            <option value="Completed">Mark as Completed</option>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function updateJobStatus(bookingId, newStatus) {
            if (!newStatus) return;

            fetch('../admin/update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId, new_status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Reload to update the list
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