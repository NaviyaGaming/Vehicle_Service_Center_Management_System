<?php
session_start();
require_once '../db_connect.php';

// Include PHPMailer
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

 $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admins only.']);
    exit;
}

// 2. Get data from JavaScript
 $input = json_decode(file_get_contents('php://input'), true);
 $booking_id = $input['booking_id'] ?? 0;
 $new_status = $input['new_status'] ?? '';

 $allowed_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
    exit;
}

// 3. Update the database
 $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
 $updateStmt->execute([$new_status, $booking_id]);

// 4. Email Notification Logic (Exclude 'Completed' and 'Paid')
 $notify_stages = ['Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup'];

if (in_array($new_status, $notify_stages)) {
    // Fetch booking details and user email
    $stmt = $pdo->prepare("SELECT b.vehicle_details, b.user_email FROM bookings b WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $vehicle = $details['vehicle_details'];
        $user_email = $details['user_email'];

        // Custom message for each stage
        $messages = [
            'Vehicle Received' => "Your $vehicle has been received at our service center. We will begin work soon!",
            'Awaiting Parts' => "We are currently awaiting parts for your $vehicle. We will notify you when work resumes.",
            'In Progress' => "Service is now in progress for your $vehicle. Our mechanics are on the job!",
            'Ready for Pickup' => "Great news! Your $vehicle is ready for pickup. Please visit us at your earliest convenience."
        ];
        $email_body = $messages[$new_status];

        // Send Email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'navindu.subasinghe@gmail.com'; 
            $mail->Password = 'upzh xqev rtqk unee';       
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('navindu.subasinghe@gmail.com', 'TorquePoint Service Center');
            $mail->addAddress($user_email); 
            
            $mail->isHTML(true);
            $mail->Subject = "TorquePoint Update: $new_status";
            $mail->Body = "
                <h2>Service Status Update</h2>
                <p>$email_body</p>
                <br>
                <p>Thank you for choosing TorquePoint.</p>
                <p>Best Regards,<br>TorquePoint Team</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            // Log error but don't break the status update
            error_log("Status update email failed: {$mail->ErrorInfo}");
        }
    }
}

echo json_encode(['success' => true, 'message' => "Status updated to '$new_status'."]);
?>
