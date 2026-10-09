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

// 1. Security Check: Ensure user is logged in AND is an admin OR a mechanic
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

 $stmt = $pdo->prepare("SELECT is_admin, role, mechanic_id FROM users WHERE email = ?");
 $stmt->execute([$_SESSION['user_email']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || ($user['is_admin'] != 1 && $user['role'] !== 'mechanic')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admins/Mechanics only.']);
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

// If the user is a MECHANIC, ensure they only update jobs assigned to THEM
if ($user['role'] === 'mechanic') {
    $stmt = $pdo->prepare("SELECT assigned_mechanic_id FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking_check = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking_check || $booking_check['assigned_mechanic_id'] != $user['mechanic_id']) {
        echo json_encode(['success' => false, 'message' => 'Access Denied: This job is not assigned to you.']);
        exit;
    }

    // Mechanics can only set these specific statuses
    if (!in_array($new_status, ['In Progress', 'Ready for Pickup', 'Completed'])) {
        echo json_encode(['success' => false, 'message' => 'Mechanics can only mark jobs as In Progress, Ready for Pickup, or Completed.']);
        exit;
    }
}
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

    if ($booking && !empty($booking['assigned_mechanic_id'])) {
        // Free up the mechanic
        $pdo->prepare("UPDATE mechanics SET is_available = 1 WHERE id = ?")->execute([$booking['assigned_mechanic_id']]);
    }
    
    // Update booking status AND clear the assigned mechanic in one clean query (Fixes the stuck name!)
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ?, assigned_mechanic_id = NULL WHERE id = ?");
    $updateStmt->execute([$new_status, $booking_id]);
    
    $alert_message = "Status updated to '$new_status'. Mechanic is now available for the next vehicle.";

} 
// For all other statuses (Paid, Awaiting Parts, In Progress), just update the status
else {
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $booking_id]);
    $alert_message = "Status updated to '$new_status'.";
}

// 4. EMAIL NOTIFICATION LOGIC 

// A. "Completed" -> Send Thank You, Rating Link, & Next Service Mileage
if ($new_status === 'Completed') {
    // Fetch booking details, user email, user name, AND vehicle mileage
    $stmt = $pdo->prepare("SELECT b.vehicle_details, b.user_email, b.service_name, b.vehicle_mileage, u.name FROM bookings b JOIN users u ON b.user_email = u.email WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $user_name = $details['name'] ?? 'Valued Customer';
        $user_email = $details['user_email'];
        $current_mileage = $details['vehicle_mileage'] ?? 0;
        
        // Calculate Next Service Mileage based on Service Type
        $service_name = strtolower($details['service_name']);
        $interval = 10000; // Default interval (e.g., for Full Service)
        
        if (strpos($service_name, 'oil') !== false) {
            $interval = 5000; // Oil change every 5,000 km
        } elseif (strpos($service_name, 'brake') !== false) {
            $interval = 20000; // Brakes every 20,000 km
        } elseif (strpos($service_name, 'tire') !== false || strpos($service_name, 'wheel') !== false) {
            $interval = 15000; // Tires every 15,000 km
        }
        
        $next_mileage = $current_mileage + $interval;
        
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
            $mail->addAddress($user_email); 
            
            $rating_link = "http://localhost/Vehicle_Service_Center_Management_System/services/rate.php?id=" . $booking_id;
            
            $mail->isHTML(true);
            $mail->Subject = "Thank you for choosing TorquePoint!";
            $mail->Body = "
                <h2>Thank you, $user_name!</h2>
                <p>We hope you are satisfied with the service provided for your {$details['vehicle_details']}. It was a pleasure having you at TorquePoint.</p>
                
                <div style='background: #F8FAFC; border-left: 4px solid #0052CC; padding: 16px; margin: 20px 0; border-radius: 4px;'>
                    <h3 style='margin: 0 0 8px 0; color: #0F172A; font-family: Montserrat, sans-serif;'>Maintenance Reminder</h3>
                    <p style='margin: 0; color: #64748B; font-size: 14px;'>Current Mileage: <strong>{$current_mileage} km</strong></p>
                    <p style='margin: 4px 0 0 0; color: #64748B; font-size: 14px;'>Next Recommended Service ({$details['service_name']}): <strong style='color: #0052CC;'>{$next_mileage} km</strong></p>
                </div>
                
                <p>We would love to hear your feedback! Please take a moment to rate your experience with us:</p>
                <p style='margin-top: 20px;'>
                    <a href='$rating_link' style='background-color: #0052CC; color: #FFFFFF; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600; font-family: Inter, sans-serif;'>
                        Rate Our Service
                    </a>
                </p>
                <p>Best Regards,<br>TorquePoint Team</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            error_log("Completion email failed: {$mail->ErrorInfo}");
        }
    }
}

// B. Other Statuses -> Send standard update emails
 $notify_stages = ['Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup'];

if (in_array($new_status, $notify_stages)) {
    $stmt = $pdo->prepare("SELECT b.vehicle_details, b.user_email, b.booking_date, b.booking_time FROM bookings b WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $vehicle = $details['vehicle_details'];
        $user_email = $details['user_email'];

        $messages = [
            'Vehicle Received' => "Your $vehicle has been received at our service center. We will begin work soon!",
            'Awaiting Parts' => "We are currently awaiting parts for your $vehicle. We will notify you when work resumes.",
            'In Progress' => "Service is now in progress for your $vehicle. Our mechanics are on the job!",
            'Ready for Pickup' => "Great news! Your $vehicle is ready for pickup. Please visit us at your earliest convenience."
        ];
        $email_body = $messages[$new_status];

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
            $mail->addAddress($user_email); 
            
        // Format date/time if they exist
        $formatted_date = $details['booking_date'] ? date('F j, Y', strtotime($details['booking_date'])) : '';
        $formatted_time = $details['booking_time'] ?? '';
        $appointment_text = '';
        if ($formatted_date) {
            $appointment_text = "<p style='color:#64748B; font-size:14px;'><strong>Appointment Date:</strong> {$formatted_date} at {$formatted_time}</p>";
        }

        $mail->isHTML(true);
        $mail->Subject = "TorquePoint Update: $new_status";
        $mail->Body = "
            <h2>Service Status Update</h2>
            <p>$email_body</p>
            $appointment_text
            <br>
            <p>Thank you for choosing TorquePoint.</p>
            <p>Best Regards,<br>TorquePoint Team</p>
        ";
            $mail->send();
        } catch (Exception $e) {
            error_log("Status update email failed: {$mail->ErrorInfo}");
        }
    }
}

// 5. PUSHER WEBSOCKET SIGNAL (Real-time update)
 $app_id = '2199143';
 $key = 'd8644afd04e61eef07a7';
 $secret = '394b686306763a34e701';
 $cluster = 'ap1';

// We use the user's email as a private channel name so only they get the update
 $channel_name = 'user-' . md5($user_email);
 $event_name = 'status-updated';
 $data = json_encode(['booking_id' => $booking_id, 'new_status' => $new_status]);

// Function to trigger Pusher event via cURL (No Composer needed!)
 $timestamp = time();
 $path = "/apps/$app_id/events";
 $params = [
    'auth_key' => $key,
    'auth_timestamp' => $timestamp,
    'auth_version' => '1.0',
    'body_md5' => md5($data)
];
ksort($params);
 $query = http_build_query($params);
 $string_to_sign = "POST\n$path\n$query";
 $signature = hash_hmac('sha256', $string_to_sign, $secret);
 $url = "https://api-$cluster.pusher.com$path?$query&auth_signature=$signature";

 $ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => $event_name,
    'channel' => $channel_name,
    'data' => $data
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
curl_close($ch);

echo json_encode(['success' => true, 'message' => $alert_message]);
?>