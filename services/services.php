<?php
session_start();

// If user is not logged in, redirect them back to login
if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}

 $userEmail = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorquePoint | Vehicle Service Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="services.css">
</head>
<body>

    <!-- HEADER -->
    <header class="top-header">
        <a href="home.html" class="brand" style="text-decoration: none; color: inherit;">
            <div class="brand-icon">
                <img src="../logo.png" alt="Logo" class="brand-logo-img" onerror="this.style.display='none'">
            </div>
            <div class="brand-text">
                <h2>Torque<span>Point</span></h2>
            </div>
        </a>
        
        <!-- Updated Header Link to show User Email and Logout -->
        <div class="header-link">
            <i class="fa-solid fa-user"></i>
            <?php echo htmlspecialchars($userEmail); ?>
            <a href="logout.php" style="margin-left: 15px; color: var(--danger); text-decoration: none; font-weight: 600;">Logout</a>
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

        <!-- VEHICLE SELECTION (4 STEPS) -->
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
            <button class="btn-pay" onclick="proceedToPayment()">
                Proceed to Payment
                <i class="fa-solid fa-lock"></i>
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

</body>
</html>
</body>
</html>