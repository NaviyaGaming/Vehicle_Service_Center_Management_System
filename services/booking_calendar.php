<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email']) || !isset($_GET['id'])) {
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

 $booking_id = $_GET['id'];
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_email = ?");
 $stmt->execute([$booking_id, $_SESSION['user_email']]);
 $booking = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$booking) { die("Booking not found."); }

// Calendar Logic: Get current month and year
 $month = isset($_GET['m']) ? intval($_GET['m']) : date('m');
 $year = isset($_GET['y']) ? intval($_GET['y']) : date('Y');

// Handle month navigation
if ($month == 0) { $month = 12; $year--; }
if ($month == 13) { $month = 1; $year++; }

 $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
 $first_day_of_week = date('w', strtotime("$year-$month-01")); // 0 (Sun) to 6 (Sat)
 $today = date('Y-m-d');

// Fetch fully booked days (4 paid slots)
 $paid_statuses = ['Paid', 'Vehicle Received', 'Awaiting Parts', 'In Progress', 'Ready for Pickup', 'Completed'];
 $placeholders = implode(',', array_fill(0, count($paid_statuses), '?'));
 $stmt = $pdo->prepare("SELECT booking_date FROM bookings WHERE status IN ($placeholders) GROUP BY booking_date HAVING COUNT(*) >= 4");
 $stmt->execute($paid_statuses);
 $booked_days = $stmt->fetchAll(PDO::FETCH_COLUMN);

// NEW: Fetch Admin Blocked Dates (Holidays)
 $stmt_blocked = $pdo->query("SELECT date FROM blocked_dates");
 $blocked_days = $stmt_blocked->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Date & Time | TorquePoint</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0052CC; --primary-hover: #0042A5; --bg-main: #F8FAFC; --surface: #FFFFFF; --text-main: #0F172A; --text-muted: #64748B; --success: #10B981; --danger: #EF4444; --border: #E2E8F0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-main); }
        .header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 20px 6%; display: flex; justify-content: space-between; align-items: center; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .brand img { width: 40px; height: 40px; border-radius: 8px; }
        .brand h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; } .brand span { color: var(--primary); }

        /* AVATAR DROPDOWN CSS */
        .avatar-dropdown { position: relative; display: flex; align-items: center; margin-left: 10px; }
        .header-avatar { width: 40px; height: 40px; background: var(--primary); color: var(--surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
        .header-avatar:hover { border-color: var(--primary-hover); }
        .dropdown-menu { position: absolute; top: 120%; right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 160px; z-index: 1000; display: none; flex-direction: column; overflow: hidden; }
        .dropdown-menu a { padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); transition: 0.2s; }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover { background: var(--bg-main); color: var(--primary); }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card h3 { font-family: 'Montserrat', sans-serif; font-size: 18px; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .summary-item strong { color: var(--text-main); }

        /* Calendar Styles */
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .cal-header h3 { margin: 0; border: none; padding: 0; font-family: 'Montserrat'; font-size: 18px; }
        .cal-nav { color: var(--primary); text-decoration: none; font-size: 18px; cursor: pointer; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .cal-day-name { text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); padding-bottom: 8px; }
        .cal-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .cal-day:hover { background: var(--bg-main); border-color: var(--primary); }
        .cal-day.past { color: #CBD5E1; background: #F8FAFC; cursor: not-allowed; border-color: transparent; }
        .cal-day.sunday { color: #CBD5E1; background: #F8FAFC; cursor: not-allowed; border-color: transparent; }
        .cal-day.booked { color: #CBD5E1; background: #F8FAFC; cursor: not-allowed; border-color: transparent; text-decoration: line-through; }
        .cal-day.blocked { color: #EF4444; background: #FEF2F2; cursor: not-allowed; border-color: transparent; text-decoration: line-through; }
        .cal-day.selected { background: var(--primary); color: white; border-color: var(--primary); }

        .time-slots { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
        .time-slot { padding: 12px; border: 1px solid var(--border); border-radius: 8px; text-align: center; cursor: pointer; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .time-slot:hover { border-color: var(--primary); color: var(--primary); }
        .time-slot.taken { color: #CBD5E1; background: #F8FAFC; cursor: not-allowed; border-color: transparent; text-decoration: line-through; }
        .time-slot.selected { background: var(--success); color: white; border-color: var(--success); }

        .btn-pay { width: 100%; padding: 14px; margin-top: 24px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .btn-pay:disabled { background: #94A3B8; cursor: not-allowed; }
    </style>
</head>
<body>

    <div class="header">
        <a href="../home/home.php" class="brand">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <h2>Torque<span>Point</span></h2>
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
    </div>

    <div class="container">
        <!-- Left Side: Calendar -->
        <div class="card">
            <div class="cal-header">
                <a class="cal-nav" href="?id=<?php echo $booking_id; ?>&m=<?php echo $month-1; ?>&y=<?php echo $year; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <h3><?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
                <a class="cal-nav" href="?id=<?php echo $booking_id; ?>&m=<?php echo $month+1; ?>&y=<?php echo $year; ?>"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
            <div class="cal-grid">
                <div class="cal-day-name">Sun</div><div class="cal-day-name">Mon</div><div class="cal-day-name">Tue</div><div class="cal-day-name">Wed</div><div class="cal-day-name">Thu</div><div class="cal-day-name">Fri</div><div class="cal-day-name">Sat</div>
                
                <?php for ($i = 0; $i < $first_day_of_week; $i++) echo "<div></div>"; ?>
                
                <?php for ($d = 1; $d <= $days_in_month; $d++): 
                    $date_str = sprintf("%04d-%02d-%02d", $year, $month, $d);
                    $day_of_week = date('w', strtotime($date_str));
                    $is_past = $date_str < $today;
                    $is_sunday = $day_of_week == 0;
                    $is_booked = in_array($date_str, $booked_days);
                    $is_blocked = in_array($date_str, $blocked_days); // Check if blocked by admin
                ?>
                    <div class="cal-day <?php echo $is_past ? 'past' : ''; ?> <?php echo $is_sunday ? 'sunday' : ''; ?> <?php echo $is_booked ? 'booked' : ''; ?> <?php echo $is_blocked ? 'blocked' : ''; ?>" onclick="selectDate('<?php echo $date_str; ?>', this)">
                        <?php echo $d; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Right Side: Summary & Time Slots -->
        <div class="card">
            <h3>Booking Summary</h3>
            <div class="summary-item"><span>Vehicle:</span> <strong><?php echo htmlspecialchars($booking['vehicle_details']); ?></strong></div>
            <div class="summary-item"><span>Service:</span> <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></div>
            <div class="summary-item"><span>Price:</span> <strong style="font-family: 'JetBrains Mono'; color: var(--primary);"><?php echo htmlspecialchars($booking['price']); ?></strong></div>

            <h3 style="margin-top: 24px;">Select Time Slot</h3>
            <div class="time-slots" id="timeSlotsContainer">
                <div class="time-slot" onclick="selectTime('09:00 AM - 11:00 AM', this)">09:00 AM</div>
                <div class="time-slot" onclick="selectTime('11:00 AM - 01:00 PM', this)">11:00 AM</div>
                <div class="time-slot" onclick="selectTime('01:00 PM - 03:00 PM', this)">01:00 PM</div>
                <div class="time-slot" onclick="selectTime('03:00 PM - 05:00 PM', this)">03:00 PM</div>
            </div>

            <button id="payBtn" class="btn-pay" disabled onclick="proceedToPayment()">Proceed to Payment</button>
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
        let selectedDate = null;
        let selectedTime = null;

        function selectDate(dateStr, element) {
            // Prevent clicking on past, sunday, fully booked, or blocked (holiday) days
            if (element.classList.contains('past') || element.classList.contains('sunday') || element.classList.contains('booked') || element.classList.contains('blocked')) return;

            selectedDate = dateStr;
            document.querySelectorAll('.cal-day').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');

            // Reset time selection
            selectedTime = null;
            document.querySelectorAll('.time-slot').forEach(el => { el.classList.remove('selected', 'taken'); });
            document.getElementById('payBtn').disabled = true;

            // Fetch booked slots for this date
            fetch(`get_booked_slots.php?date=${dateStr}`)
                .then(res => res.json())
                .then(bookedSlots => {
                    document.querySelectorAll('.time-slot').forEach(slot => {
                        const slotTime = slot.getAttribute('onclick').match(/'([^']+)'/)[1];
                        if (bookedSlots.includes(slotTime)) {
                            slot.classList.add('taken');
                            slot.style.cursor = 'not-allowed';
                            slot.removeAttribute('onclick');
                        } else {
                            slot.classList.remove('taken');
                            slot.setAttribute('onclick', `selectTime('${slotTime}', this)`);
                        }
                    });
                });
        }

        function selectTime(time, element) {
            if (element.classList.contains('taken')) return;

            selectedTime = time;
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('payBtn').disabled = false;
        }

        function proceedToPayment() {
            if (!selectedDate || !selectedTime) return;
            window.location.href = `../invoice/stripe-checkout.php?booking_id=<?php echo $booking_id; ?>&date=${selectedDate}&time=${encodeURIComponent(selectedTime)}`;
        }

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