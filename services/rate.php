<?php
session_start();
require_once '../db_connect.php';

// Ensure user is logged in
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

 $booking_id = $_GET['id'] ?? 0;
if (!$booking_id) {
    die("Invalid link.");
}

// Check if booking belongs to user and fetch if already rated
 $stmt = $pdo->prepare("SELECT b.*, r.id as rating_id FROM bookings b LEFT JOIN ratings r ON b.id = r.booking_id WHERE b.id = ? AND b.user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found or you do not have permission to rate it.");
}

// If they submit the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($booking['rating_id']) {
        $error = "You have already rated this service.";
    } else {
        $rating = $_POST['rating'] ?? 0;
        $review = trim($_POST['review'] ?? '');
        
        if ($rating > 0 && $rating <= 5) {
            $insertStmt = $pdo->prepare("INSERT INTO ratings (booking_id, user_email, rating, review) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$booking_id, $_SESSION['user_email'], $rating, $review]);
            
            // Refresh booking variable to show the thank you message
            $stmt->execute([$booking_id, $_SESSION['user_email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Please select a valid star rating.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Service | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --border: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* HEADER */
        .top-header { width: 100%; padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); background: var(--surface); box-shadow: 0 1px 3px rgba(0,0,0,0.05); z-index: 10; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand img { width: 40px; height: 40px; border-radius: 8px; }
        .brand-text h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; } .brand-text h2 span { color: var(--primary); }

        /* AVATAR DROPDOWN */
        .avatar-dropdown { position: relative; display: flex; align-items: center; margin-left: 10px; }
        .header-avatar { width: 40px; height: 40px; background: var(--primary); color: var(--surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
        .header-avatar:hover { border-color: var(--primary-hover); }
        .dropdown-menu { position: absolute; top: 120%; right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 160px; z-index: 1000; display: none; flex-direction: column; overflow: hidden; }
        .dropdown-menu a { padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); transition: 0.2s; }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover { background: var(--bg-main); color: var(--primary); }

        /* MAIN WRAPPER */
        .wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .card { background: var(--surface); padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 450px; width: 100%; text-align: center; border: 1px solid var(--border); }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 24px; margin-bottom: 8px; }
        p { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; }
        .stars { display: flex; justify-content: center; gap: 12px; margin-bottom: 24px; }
        .stars input { display: none; }
        .stars label { font-size: 32px; color: #CBD5E1; cursor: pointer; transition: color 0.2s; }
        .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: #F59E0B; }
        .stars { flex-direction: row-reverse; justify-content: flex-end; }
        textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Inter'; font-size: 14px; resize: vertical; margin-bottom: 16px; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; }
        .error { color: #EF4444; font-size: 13px; margin-bottom: 16px; }
        .success-box i { font-size: 40px; color: var(--success); margin-bottom: 16px; }
        .success-box a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="../home/home.php" class="brand">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="brand-text"><h2>Torque<span>Point</span></h2></div>
        </a>
        
        <!-- Avatar Dropdown -->
        <div class="avatar-dropdown">
            <div class="header-avatar" onclick="toggleDropdown()"><?php echo $initials; ?></div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="wrapper">
        <div class="card">
            <?php if ($booking['rating_id']): ?>
                <!-- ALREADY RATED STATE -->
                <div class="success-box">
                    <i class="fa-solid fa-circle-check"></i>
                    <h1>Thank You!</h1>
                    <p>We appreciate your feedback. You rated this service.</p>
                    <br>
                    <a href="history.php">Back to History</a>
                </div>
            <?php elseif (isset($error) && empty($_POST['review'])): ?>
                <!-- ERROR STATE -->
                <h1>Oops!</h1>
                <p class="error"><?php echo $error; ?></p>
                <a href="history.php" class="btn">Back to History</a>
            <?php else: ?>
                <!-- RATING FORM STATE -->
                <h1>Rate Your Service</h1>
                <p>How was your experience with <strong><?php echo htmlspecialchars($booking['vehicle_details']); ?></strong>?</p>
                
                <form method="POST">
                    <div class="stars">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1"><i class="fa-solid fa-star"></i></label>
                    </div>
                    <textarea name="review" rows="3" placeholder="Leave a comment (optional)..."></textarea>
                    <button type="submit" class="btn">Submit Rating</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tawk.to Live Chat Script -->
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/6abf9188df2d5634c099c01d/1k3u5101i';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>

    <script>
        // Avatar Dropdown Toggle
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