<?php
session_start();
require_once '../db_connect.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 $booking_id = $_GET['booking_id'] ?? 0;

// 1. Update Database Status to Paid
 $stmt = $pdo->prepare("UPDATE bookings SET status = 'Paid' WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);

// 2. Fetch the updated booking details for the email
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
 $stmt->execute([$booking_id]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Send Email Notification via PHPMailer
 $mail = new PHPMailer(true);

try {
    // Server settings (Using Gmail's SMTP server)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'navindu.subasinghe@gmail.com'; 
    $mail->Password = 'upzh xqev rtqk unee';          
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('navindu.subasinghe@gmail.com', 'TorquePoint Service Center');
    $mail->addAddress($_SESSION['user_email']); 

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Payment Successful - Invoice #" . $booking_id;
    $mail->Body = "
        <h2>Thank you for your payment!</h2>
        <p>Your vehicle service has been successfully booked and paid for.</p>
        <p><strong>Vehicle:</strong> {$booking['vehicle_details']}</p>
        <p><strong>Service:</strong> {$booking['service_name']}</p>
        <p><strong>Amount Paid:</strong> {$booking['price']}</p>
        <p><strong>Status:</strong> Paid</p>
        <br>
        <p>Best Regards,</p>
        <p>TorquePoint Team</p>
    ";

    $mail->send();
    $email_message = "Email receipt sent successfully!";
} catch (Exception $e) {
    $email_message = "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC;
            --bg-main: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --success: #10B981;
            --border-color: #E2E8F0;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-main); 
            color: var(--text-main);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            text-align: center; 
            margin: 0; 
        }
        .card { 
            background: var(--surface); 
            padding: 48px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.05); 
            max-width: 480px; 
            width: 90%;
            border: 1px solid var(--border-color);
        }
        .icon {
            width: 64px;
            height: 64px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
        }
        h1 { 
            color: var(--text-main); 
            font-family: 'Montserrat', sans-serif; 
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px; 
        }
        p { 
            color: var(--text-muted); 
            line-height: 1.6; 
            font-size: 15px;
            margin-bottom: 16px; 
        }
        .email-status {
            font-size: 13px;
            color: var(--success);
            background: rgba(16, 185, 129, 0.05);
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .btn { 
            background: var(--primary); 
            color: var(--surface); 
            text-decoration: none; 
            font-weight: 600; 
            display: inline-block; 
            padding: 12px 24px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #0042A5;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <i class="fa-solid fa-check"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your booking has been confirmed and marked as Paid in our system.</p>
        
        <div class="email-status">
            <?php echo htmlspecialchars($email_message); ?>
        </div>
        
        <br>
        <a href="../services/history.php" class="btn">View My Service History</a>
    </div>
</body>
</html>