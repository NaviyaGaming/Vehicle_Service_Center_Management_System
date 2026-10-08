<?php
session_start();
require_once '../db_connect.php';
<<<<<<< HEAD

// Include PHPMailer
=======
// Include PHPMailer
//check 2
>>>>>>> refs/rewritten/main-2
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

<<<<<<< HEAD
// 3. MECHANIC ASSIGNMENT LOGIC

// If starting a job, assign a mechanic
if ($new_status === 'Vehicle Received') {
    // Find the first available mechanic
    $mechStmt = $pdo->query("SELECT id, name FROM mechanics WHERE is_available = 1 ORDER BY id ASC LIMIT 1");
    $mechanic = $mechStmt->fetch(PDO::FETCH_ASSOC);

    if (!$mechanic) {
        // No mechanics available!
        echo json_encode(['success' => false, 'message' => "Cannot start job: All 4 mechanics are currently busy!"]);
        exit; // Stop the process entirely
    }

    // Assign mechanic to booking and make them busy
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ?, assigned_mechanic_id = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $mechanic['id'], $booking_id]);
    
    $pdo->prepare("UPDATE mechanics SET is_available = 0 WHERE id = ?")->execute([$mechanic['id']]);
    
    $alert_message = "Status updated. Assigned to mechanic: {$mechanic['name']}.";

} 
// If finishing a job, free the mechanic
elseif ($new_status === 'Ready for Pickup' || $new_status === 'Completed') {
    
    // Find who was assigned to this booking
    $stmt = $pdo->prepare("SELECT assigned_mechanic_id FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking && $booking['assigned_mechanic_id']) {
        // Free up the mechanic
        $pdo->prepare("UPDATE mechanics SET is_available = 1 WHERE id = ?")->execute([$booking['assigned_mechanic_id']]);
        // Unassign them from the booking (so they don't get freed twice)
        $pdo->prepare("UPDATE bookings SET assigned_mechanic_id = NULL WHERE id = ?")->execute([$booking_id]);
    }
    
    // Update booking status
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $booking_id]);
    
    $alert_message = "Status updated to '$new_status'. Mechanic is now available for the next vehicle.";

} 
// For all other statuses (Paid, Awaiting Parts, In Progress), just update the status
else {
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $booking_id]);
    $alert_message = "Status updated to '$new_status'.";
}

=======
// 3. Update the database
 $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
 $updateStmt->execute([$new_status, $booking_id]);

>>>>>>> refs/rewritten/main-2
// 4. Email Notification Logic (Exclude 'Completed' and 'Paid')
 $notify_stages = ['Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup'];

if (in_array($new_status, $notify_stages)) {
<<<<<<< HEAD
=======
    // Fetch booking details and user email
>>>>>>> refs/rewritten/main-2
    $stmt = $pdo->prepare("SELECT b.vehicle_details, b.user_email FROM bookings b WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $vehicle = $details['vehicle_details'];
        $user_email = $details['user_email'];
<<<<<<< HEAD
=======

        // Custom message for each stage
>>>>>>> refs/rewritten/main-2
        $messages = [
            'Vehicle Received' => "Your $vehicle has been received at our service center. We will begin work soon!",
            'Awaiting Parts' => "We are currently awaiting parts for your $vehicle. We will notify you when work resumes.",
            'In Progress' => "Service is now in progress for your $vehicle. Our mechanics are on the job!",
            'Ready for Pickup' => "Great news! Your $vehicle is ready for pickup. Please visit us at your earliest convenience."
        ];
        $email_body = $messages[$new_status];

<<<<<<< HEAD
=======
        // Send Email via PHPMailer
>>>>>>> refs/rewritten/main-2
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
<<<<<<< HEAD
=======
            // Log error but don't break the status update
>>>>>>> refs/rewritten/main-2
            error_log("Status update email failed: {$mail->ErrorInfo}");
        }
    }
}

<<<<<<< HEAD
echo json_encode(['success' => true, 'message' => $alert_message]);
?>
=======
echo json_encode(['success' => true, 'message' => "Status updated to '$new_status'."]);
?>
>>>>>>> refs/rewritten/main-2
