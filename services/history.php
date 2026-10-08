<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit;
}

 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_email = ? ORDER BY created_at DESC");
 $stmt->execute([$_SESSION['user_email']]);
 $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $userEmail = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Service History | TorquePoint</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF;
            --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --warning: #F59E0B;
            --danger: #EF4444; --border-color: #E2E8F0; --slate-400: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); min-height: 100vh; }
        
        .top-header { width: 100%; padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); background: var(--surface); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand-logo-img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; }
        .brand-text h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; }
        .brand-text h2 span { color: var(--primary); }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .user-info { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; }

        .container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
        .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .page-head h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 600; color: var(--text-main); }
        .btn-primary { background: var(--primary); color: var(--surface); padding: 12px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); }

        .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 16px 24px; background: var(--bg-main); color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        .data-table td { padding: 20px 24px; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #FCFCFD; }

        .id-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-muted); }
        .text-muted { color: var(--text-muted); font-size: 13px; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-cancelled { background: rgba(100, 116, 139, 0.1); color: var(--text-muted); }

        .action-group { display: flex; gap: 8px; }
        .btn-view { background: transparent; color: var(--primary); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; display: inline-block; }
        .btn-view:hover { background: var(--bg-main); border-color: var(--primary); }
        
        .btn-cancel { background: transparent; color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-cancel:hover { background: var(--danger); color: white; border-color: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 40px; color: var(--border-color); margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Montserrat', sans-serif; font-size: 18px; margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; }

        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--text-main); color: white; padding: 12px 20px; border-radius: 8px; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        /* ==========================================
           Visual Service Tracker Styles
           ========================================== */
        .tracker { display: flex; gap: 4px; align-items: center; }
        .tracker-step { display: flex; flex-direction: column; align-items: center; position: relative; width: 90px; }
        .tracker-step:not(:last-child)::after {
            content: ''; position: absolute; top: 6px; left: 50%; width: 100%; height: 2px;
            background: var(--border-color); z-index: 0;
        }
        .tracker-step .dot {
            width: 12px; height: 12px; border-radius: 50%; background: var(--border-color);
            border: 2px solid var(--surface); z-index: 1; transition: all 0.3s ease;
        }
        .tracker-step .dot.active { background: var(--primary); }
        .tracker-step .dot.current { 
            background: var(--primary); 
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.2); 
            transform: scale(1.2); 
        }
        .tracker-label { 
            margin-top: 8px; font-size: 10px; color: var(--text-muted); 
            text-align: center; font-weight: 500; line-height: 1.2; 
        }
        .tracker-step:has(.dot.active) .tracker-label { color: var(--primary); font-weight: 600; }

        @media (max-width: 768px) {
            .data-table { display: block; overflow-x: auto; white-space: nowrap; }
            .top-header { padding: 16px 4%; }
            .user-info { display: none; }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <a href="services.php" class="brand">
            <img src="logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            <div class="brand-text"><h2>Torque<span>Point</span></h2></div>
        </a>
        <div class="header-actions">
            <div class="user-info"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($userEmail); ?></div>
            <a href="profile.php" style="color: var(--text-main); text-decoration: none; font-size: 14px; font-weight: 500;">Profile</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-head">
            <h1>My Service History</h1>
            <a href="services.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Book New Service</a>
        </div>

        <div class="table-card">
            <?php if (count($bookings) == 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-car-side"></i>
                    <h3>No Services Yet</h3>
                    <p>You haven't booked any vehicle services yet. Get started today!</p>
                    <a href="services.php" class="btn-primary" style="display: inline-flex;">Book Your First Service</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>JOB ID</th>
                            <th>VEHICLE</th>
                            <th>SERVICE TYPE</th>
                            <th>PRICE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr id="row-<?php echo $booking['id']; ?>">
                                <td class="id-mono">#INV-<?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['vehicle_details']); ?>
                                    <div class="text-muted"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td class="id-mono"><?php echo htmlspecialchars($booking['price']); ?></td>
                                <td>
                                    <?php 
                                        $status = $booking['status'];
                                        // Define the stages for the progress bar
                                        $stages = ['Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup'];
                                        
                                        if ($status === 'Pending Payment') {
                                            echo "<span class='status-badge status-pending'>Pending Payment</span>";
                                        } elseif ($status === 'Cancelled') {
                                            echo "<span class='status-badge status-cancelled'>Cancelled</span>";
                                        } elseif ($status === 'Completed') {
                                            echo "<span class='status-badge status-paid'>Service Completed ✓</span>";
                                        } else {
                                            // Draw the visual progress bar!
                                            echo "<div class='tracker'>";
                                            foreach ($stages as $stage) {
                                                $active = false;
                                                $current = false;
                                                if ($status === $stage) { $current = true; $active = true; }
                                                
                                                // Logic to light up previous stages
                                                if ($status === 'In Progress' && in_array($stage, ['Vehicle Received'])) $active = true;
                                                if ($status === 'Ready for Pickup' && in_array($stage, ['Vehicle Received', 'Awaiting Parts', 'In Progress'])) $active = true;

                                                $dot_class = $active ? 'dot active' : 'dot';
                                                if ($current) $dot_class .= ' current';
                                                
                                                echo "<div class='tracker-step'>";
                                                echo "<div class='$dot_class'></div>";
                                                echo "<span class='tracker-label'>$stage</span>";
                                                echo "</div>";
                                            }
                                            echo "</div>";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="../invoice/invoice.php?id=<?php echo $booking['id']; ?>" class="btn-view">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                        
                                        <?php if ($booking['status'] === 'Pending Payment'): ?>
                                            <button class="btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <div class="toast" id="toast"></div>
    <!-- Pusher JavaScript SDK -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>                                        
    <script>
        function cancelBooking(bookingId) {
            if (!confirm("Are you sure you want to cancel this booking?")) {
                return;
            }

            fetch('cancel_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(res => res.json())
            .then(data => {
                const toast = document.getElementById('toast');
                toast.innerText = data.message;
                toast.style.display = 'block';
                
                if (data.success) {
                    toast.style.background = '#10B981'; // Green for success
                    // Reload the page after 1.5 seconds to show updated status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toast.style.background = '#EF4444'; // Red for error
                    setTimeout(() => toast.style.display = 'none', 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking.');
            });
        }
        // Enable pusher logging - don't include this in production
Pusher.logToConsole = true;

// Initialize Pusher with your Key and Cluster
const pusher = new Pusher('d8644afd04e61eef07a7', {
    cluster: 'ap1'
});

// Subscribe to the user's private channel (must match the PHP channel name!)
const channelName = 'user-' + md5('<?php echo $_SESSION["user_email"]; ?>');
const channel = pusher.subscribe(channelName);

// When the 'status-updated' event is received, reload the page instantly!
channel.bind('status-updated', function(data) {
    console.log('Received Pusher Event:', data);
    
    // Show a toast notification that the status changed
    const toast = document.getElementById('toast');
    toast.innerText = 'Status Updated: ' + data.new_status + '!';
    toast.style.background = '#10B981'; // Green
    toast.style.display = 'block';
    
    // Reload the page after 1.5 seconds to show the new visual tracker
    setTimeout(() => location.reload(), 1500);
});

// Simple MD5 hash function for the email (required to match the PHP channel)
function md5(string) {
    // Basic hash to match PHP md5 for channel name (simplified for frontend)
    // Note: For production, a proper MD5 library should be used, but this works for simple strings.
    function RotateLeft(lValue, iShiftBits) { return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits)); }
    function AddUnsigned(lX, lY) { var lX4, lY4, lX8, lY8, lResult; lX8 = (lX & 0x80000000); lY8 = (lY & 0x80000000); lX4 = (lX & 0x40000000); lY4 = (lY & 0x40000000); lX = (lX & 0x3FFFFFFF); lY = (lY & 0x3FFFFFFF); lResult = (lX & 0x40000000); if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8); if (lX4 | lY4) { if (lX4) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8); else return (lResult ^ 0x40000000 ^ lX8 ^ lY8); } else return (lResult ^ lX8 ^ lY8); }
    function F(x, y, z) { return (x & y) | ((~x) & z); } function G(x, y, z) { return (x & z) | (y & (~z)); } function H(x, y, z) { return (x ^ y ^ z); } function I(x, y, z) { return (y ^ (x | (~z))); }
    function FF(a, b, c, d, x, s, ac) { a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac)); return AddUnsigned(RotateLeft(a, s), b); };
    function GG(a, b, c, d, x, s, ac) { a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac)); return AddUnsigned(RotateLeft(a, s), b); };
    function HH(a, b, c, d, x, s, ac) { a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac)); return AddUnsigned(RotateLeft(a, s), b); };
    function II(a, b, c, d, x, s, ac) { a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac)); return AddUnsigned(RotateLeft(a, s), b); };
    function ConvertToWordArray(string) { var lWordCount; var lMessageLength = string.length; var lNumberOfWords_temp1 = lMessageLength + 8; var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64; var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16; var lWordArray = Array(lNumberOfWords - 1); var lBytePosition = 0; var lByteCount = 0; while (lByteCount < lMessageLength) { lWordCount = (lByteCount - (lByteCount % 4)) / 4; lBytePosition = (lByteCount % 4) * 8; lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition)); lByteCount++; } lWordCount = (lByteCount - (lByteCount % 4)) / 4; lBytePosition = (lByteCount % 4) * 8; lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition); lWordArray[lNumberOfWords - 2] = lMessageLength << 3; lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29; return lWordArray; };
    function WordToHex(lValue) { var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount; for (lCount = 0; lCount <= 3; lCount++) { lByte = (lValue >>> (lCount * 8)) & 255; WordToHexValue_temp = "0" + lByte.toString(16); WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2); } return WordToHexValue; };
    var x = Array(); var k, AA, BB, CC, DD, a, b, c, d; var S11 = 7, S12 = 12, S13 = 17, S14 = 22; var S21 = 5, S22 = 9, S23 = 14, S24 = 20; var S31 = 4, S32 = 11, S33 = 16, S34 = 23; var S41 = 6, S42 = 10, S43 = 15, S44 = 21; x = ConvertToWordArray(string); a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
    for (k = 0; k < x.length; k += 16) { AA = a; BB = b; CC = c; DD = d; a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478); d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756); c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB); b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE); a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF); d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A); c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613); b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501); a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8); d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF); c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1); b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE); a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122); d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193); c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E); b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821); a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562); d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340); c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51); b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA); a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D); d = GG(d, a, b, c, x[k + 10], S22, 0x2441453); c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681); b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8); a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6); d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6); c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87); b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED); a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905); d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8); c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9); b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A); a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942); d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681); c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122); b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C); a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44); d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9); c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60); b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70); a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6); d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA); c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085); b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05); a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039); d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5); c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8); b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665); a = II(a, b, c, d, x[k + 0], S41, 0xF4292244); d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97); c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7); b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039); a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3); d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92); c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D); b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1); a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F); d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0); c = II(c, d, a, b, x[k + 6], S43, 0xA3014314); b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1); a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82); d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235); c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB); b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391); a = AddUnsigned(a, AA); b = AddUnsigned(b, BB); c = AddUnsigned(c, CC); d = AddUnsigned(d, DD); }
    var temp = WordToHex(a) + WordToHex(b) + WordToHex(c) + WordToHex(d); return temp.toLowerCase();
}
    </script>
</body>
</html>