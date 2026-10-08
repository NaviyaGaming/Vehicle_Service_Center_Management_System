<?php
session_start();
require_once '../db_connect.php';

// Require the Dompdf autoloader
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Security: Check if logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $booking_id = $_GET['id'] ?? 0;
if (!$booking_id) {
    die("Booking ID missing.");
}

// Fetch the booking securely
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Invoice not found or you do not have permission to view it.");
}

// Calculate totals
 $priceNumber = floatval(str_replace(['Rs.', ' ', ','], '', $booking['price']));
 $tax = 0;
 $total = $priceNumber + $tax;

// Construct the HTML for the PDF
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
            Status: <strong>' . $booking['status'] . '</strong>
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
        <div class="grand-total"><span>Total Due:</span> <span class="amount">Rs. ' . number_format($total, 2) . '</span></div>
    </div>
</body>
</html>';

// Instantiate Dompdf and generate the PDF
 $dompdf = new Dompdf();
 $dompdf->loadHtml($html);
 $dompdf->setPaper('A4', 'portrait');
 $dompdf->render();

// Stream the PDF to the browser (forces download)
 $dompdf->stream("TorquePoint-Invoice-{$booking['id']}.pdf", ["Attachment" => true]);
exit;
?>