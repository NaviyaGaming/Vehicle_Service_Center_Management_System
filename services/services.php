<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: ../loginPage/login.html");
    exit();
}

 $userEmail = $_SESSION['user_email'];
 $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
 $stmt->execute([$userEmail]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

 $userName = $user['name'] ?? $userEmail;
 $initials = strtoupper(substr($userName, 0, 1));
if (strpos($userName, ' ') !== false) {
    $parts = explode(' ', $userName);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorquePoint | Vehicle Service Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="services.css">
    
    <!-- Avatar Dropdown CSS -->
    <style>
        .avatar-dropdown { position: relative; display: flex; align-items: center; margin-left: 10px; }
        .header-avatar { width: 40px; height: 40px; background: var(--primary); color: var(--surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
        .header-avatar:hover { border-color: var(--primary-hover); }
        .dropdown-menu { position: absolute; top: 120%; right: 0; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-width: 160px; z-index: 1000; display: none; flex-direction: column; overflow: hidden; }
        .dropdown-menu a { padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border-color); transition: 0.2s; }
        .dropdown-menu a:last-child { border-bottom: none; }
        .dropdown-menu a:hover { background: var(--bg-main); color: var(--primary); }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="../home/home.php" class="brand" style="text-decoration: none; color: inherit;">
            <div class="brand-icon">
                <img src="../logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            </div>
            <div class="brand-text">
                <h2>Torque<span>Point</span></h2>
            </div>
        </a>
        
        <!-- Avatar and User Name -->
        <div class="avatar-dropdown">
            <div class="header-avatar" onclick="toggleDropdown()"><?php echo $initials; ?></div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container">

        <!-- HERO SECTION -->
        <section class="hero">
            <div class="small-title">TORQUEPOINT</div>
            <h1>Vehicle Service Center <span>Management System.</span></h1>
            <p class="description">
                Select your vehicle details to view available services and prices.
            </p>
        </section>

        <!-- VEHICLE SELECTION (5 STEPS) -->
        <div class="filter-box">
            <div class="form-group">
                <label for="vehicleType">1. Vehicle Type</label>
                <select id="vehicleType">
                    <option value="">-- Select Type --</option>
                    <option value="car">Car</option>
                    <option value="van">Van</option>
                    <option value="suv">SUV</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="truck">Truck</option>
                    <option value="bus">Bus</option>
                </select>
            </div>

            <div class="form-group">
                <label for="vehicleMake">2. Make</label>
                <select id="vehicleMake" disabled>
                    <option value="">-- Select Make --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="vehicleModel">3. Model</label>
                <select id="vehicleModel" disabled>
                    <option value="">-- Select Model --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="vehicleYear">4. Year</label>
                <select id="vehicleYear" disabled>
                    <option value="">-- Select Year --</option>
                </select>
            </div>

            <!-- Mileage Input -->
            <div class="form-group">
                <label for="vehicleMileage">5. Mileage (km)</label>
                <input type="number" id="vehicleMileage" placeholder="e.g., 45000" min="0">
            </div>

            <button type="button" onclick="filterServices()" class="btn-primary" id="findBtn" disabled>
                Find Services <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- SERVICE RESULTS -->
        <div id="serviceResults" class="service-results"></div>

        <!-- CHECKOUT / PAYMENT SUMMARY SECTION -->
        <div id="checkoutSection" class="checkout-section hidden">
            <h3 class="checkout-title">Order Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">VEHICLE</span>
                    <span id="summaryVehicle" class="summary-value">N/A</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">SERVICE</span>
                    <span id="summaryService" class="summary-value">N/A</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">EST. TIME</span>
                    <span id="summaryTime" class="summary-value">N/A</span>
                </div>
                <div class="summary-item summary-total">
                    <span class="summary-label">TOTAL PRICE</span>
                    <span id="summaryPrice" class="summary-value">Rs. 0.00</span>
                </div>
            </div>
            <!-- Proceed to Calendar Button -->
            <button class="btn-pay" onclick="proceedToPayment()">
                Proceed to Schedule <i class="fa-solid fa-calendar-check"></i>
            </button>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div>© 2026 TorquePoint Inc.</div>
        <div class="footer-links">
            <span>Privacy</span>
            <span>Terms</span>
            <span>Status</span>
        </div>
    </footer>

    <script src="services.js"></script>
    
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

    <!-- Avatar Dropdown Script -->
    <script>
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