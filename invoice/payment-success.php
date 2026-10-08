<?php
session_start();
require_once '../db_connect.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

// Require Dompdf
require_once '../dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

 $booking_id = $_GET['booking_id'] ?? 0;

// 1. Update Database Status to Paid
 $stmt = $pdo->prepare("UPDATE bookings SET status = 'Paid' WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);

// 2. Fetch the updated booking details for the email and PDF
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
 $stmt->execute([$booking_id]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found.");
}

// Calculate totals for PDF
 $priceNumber = floatval(str_replace(['Rs.', ' ', ','], '', $booking['price']));
 $tax = 0;
 $total = $priceNumber + $tax;

// 3. Generate the PDF in memory (Do not download to browser)
 $html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #0F172A; font-size: 14px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0052CC; padding-bottom: 20px; }
        .brand { font-size: 28px; font-weight: bold; color: #0052CC; }
        .invoice-meta { text-align: right; font-size: 12px; color: #64748B; }
        .bill-to { margin-top: 30px; }
        .bill-to h3 { font-size: 12px; color: #64748B; text-transform: uppercase; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th { background: #F8FAFC; text-align: left; padding: 12px; font-size: 12px; color: #64748B; border-bottom: 1px solid #E2E8F0; }
        td { padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #E2E8F0; }
        .totals { margin-top: 30px; width: 300px; margin-left: auto; }
        .totals div { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .grand-total { font-weight: bold; font-size: 18px; border-top: 2px solid #0F172A; margin-top: 10px; padding-top: 10px; }
        .grand-total .amount { color: #0052CC; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">TorquePoint</div>
        <div class="invoice-meta">
            <strong>Invoice #INV-' . $booking['id'] . '</strong><br>
            Date: ' . date('M d, Y', strtotime($booking['created_at'])) . '<br>
            Status: <strong>Paid</strong>
        </div>
    </div>
    
    <div class="bill-to">
        <h3>Bill To</h3>
        <strong>' . htmlspecialchars($_SESSION['user_name'] ?? 'Customer') . '</strong><br>
        ' . htmlspecialchars($_SESSION['user_email']) . '
    </div>

    <table>
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
                <td><strong>' . htmlspecialchars($booking['service_name']) . '</strong><br><span style="color:#64748B; font-size:12px;">Service & Maintenance</span></td>
                <td>' . htmlspecialchars($booking['vehicle_details']) . '</td>
                <td>' . htmlspecialchars($booking['est_time']) . '</td>
                <td class="text-right">' . htmlspecialchars($booking['price']) . '</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal:</span> <span>Rs. ' . number_format($priceNumber, 2) . '</span></div>
        <div><span>Tax (0%):</span> <span>Rs. ' . number_format($tax, 2) . '</span></div>
        <div class="grand-total"><span>Total Paid:</span> <span class="amount">Rs. ' . number_format($total, 2) . '</span></div>
    </div>
</body>
</html>';

 $dompdf = new Dompdf();
 $dompdf->loadHtml($html);
 $dompdf->setPaper('A4', 'portrait');
 $dompdf->render();
 $pdf_output = $dompdf->output(); // Get the PDF as a string

// 4. Send Email Notification via PHPMailer
 $mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'pointtorque@gmail.com'; 
    $mail->Password = 'vfsz hneu wgcb bysp';       
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('pointtorque@gmail.com', 'TorquePoint Service Center');
    $mail->addAddress($_SESSION['user_email']); 
    
    // Attach the generated PDF to the email!
    $mail->addStringAttachment($pdf_output, "TorquePoint-Invoice-{$booking['id']}.pdf");

        // Format the date nicely (e.g., "October 16, 2026")
    $formatted_date = $booking['booking_date'] ? date('F j, Y', strtotime($booking['booking_date'])) : 'Not specified';
    $formatted_time = $booking['booking_time'] ?? 'Not specified';

    $mail->isHTML(true);
    $mail->Subject = "Payment Successful - Invoice #INV-{$booking['id']}";
    $mail->Body = "
        <h2>Thank you for your payment!</h2>
        <p>Your vehicle service has been successfully booked and paid for.</p>
        
        <div style='background: #F8FAFC; border-left: 4px solid #0052CC; padding: 16px; margin: 20px 0; border-radius: 4px;'>
            <h3 style='margin: 0 0 8px 0; color: #0F172A; font-family: Montserrat, sans-serif;'>Appointment Details</h3>
            <p style='margin: 0; color: #64748B; font-size: 14px;'><strong>Date:</strong> {$formatted_date}</p>
            <p style='margin: 4px 0 0 0; color: #64748B; font-size: 14px;'><strong>Time:</strong> {$formatted_time}</p>
        </div>

        <p><strong>Vehicle:</strong> {$booking['vehicle_details']}</p>
        <p><strong>Service:</strong> {$booking['service_name']}</p>
        <p><strong>Amount Paid:</strong> {$booking['price']}</p>
        <p><strong>Status:</strong> Paid</p>
        <br>
        <p>Please find your detailed PDF invoice attached to this email.</p>
        <p>Best Regards,<br>TorquePoint Team</p>
    ";

    $mail->send();
    $email_message = "Email receipt sent successfully with PDF attachment!";
} catch (Exception $e) {
    $email_message = "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
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