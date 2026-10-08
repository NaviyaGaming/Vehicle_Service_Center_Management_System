<?php
session_start();
require_once '../db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Service | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --border: #E2E8F0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: var(--surface); padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 450px; width: 90%; text-align: center; border: 1px solid var(--border); }
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
</body>
</html>