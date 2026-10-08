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

// Handle Add New Mechanic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mechanic'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO mechanics (name, is_available) VALUES (?, 1)");
        $stmt->execute([$name]);
        header("Location: manage_mechanics.php");
        exit;
    }
}

// Handle Assign User Account to Mechanic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_user'])) {
    $user_email = $_POST['user_email'];
    $mechanic_id = $_POST['mechanic_id'];
    
    // Update the user's role and mechanic_id
    $stmt = $pdo->prepare("UPDATE users SET role = 'mechanic', mechanic_id = ? WHERE email = ?");
    $stmt->execute([$mechanic_id, $user_email]);
    
    header("Location: manage_mechanics.php");
    exit;
}

// Fetch all mechanics
 $mechanics = $pdo->query("SELECT * FROM mechanics ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users who are currently customers (so we can promote them to mechanic)
 $customers = $pdo->query("SELECT email, name FROM users WHERE role = 'customer' OR role IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Mechanics | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --warning: #F59E0B; --danger: #EF4444; --border: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin: 0; } .brand span { color: var(--primary); }
        .nav a { text-decoration: none; color: var(--text-muted); margin-left: 20px; font-size: 14px; font-weight: 500; }
        .nav a:hover { color: var(--primary); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h3 { margin: 0 0 16px 0; font-family: 'Montserrat'; font-size: 18px; }
        .form-row { display: flex; gap: 12px; align-items: center; }
        input, select { padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; font-family: 'Inter'; font-size: 14px; flex-grow: 1; }
        .btn { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .badge-active { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-busy { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand"><h2>Torque<span>Point</span> Admin</h2></div>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_services.php">Services</a>
            <a href="manage_mechanics.php" style="color: var(--primary); font-weight: 600;">Mechanics</a>
            <a href="../services/logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Manage Mechanics</h1>
        
        <!-- Add New Mechanic -->
        <div class="card">
            <h3>Add New Mechanic to Team</h3>
            <form method="POST">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Enter mechanic's full name..." required>
                    <button type="submit" name="add_mechanic" class="btn"><i class="fa-solid fa-plus"></i> Add Mechanic</button>
                </div>
            </form>
        </div>

        <!-- Assign User Account to Mechanic -->
        <div class="card">
            <h3>Grant Mechanic Portal Access</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">To let a mechanic log in, they must first create a standard customer account. Then, you can assign their profile here.</p>
            <form method="POST">
                <div class="form-row">
                    <select name="user_email" required>
                        <option value="">-- Select User Email --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['email'] . ' (' . $c['name'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="mechanic_id" required>
                        <option value="">-- Assign to Mechanic Profile --</option>
                        <?php foreach ($mechanics as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_user" class="btn"><i class="fa-solid fa-key"></i> Grant Access</button>
                </div>
            </form>
        </div>

        <!-- Current Mechanics List -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mechanic Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mechanics as $m): ?>
                    <tr>
                        <td style="font-family: 'JetBrains Mono';">#<?php echo $m['id']; ?></td>
                        <td><?php echo htmlspecialchars($m['name']); ?></td>
                        <td><span class="badge <?php echo $m['is_available'] ? 'badge-active' : 'badge-busy'; ?>"><?php echo $m['is_available'] ? 'Available' : 'Busy'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>