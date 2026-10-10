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
    $initials = strtoupper(
        substr($parts[0], 0, 1) .
        substr(end($parts), 0, 1)
    );
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

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link rel="stylesheet" href="services.css">


    <!-- Avatar Dropdown CSS -->
    <style>

        .avatar-dropdown {
            position: relative;
            display: flex;
            align-items: center;
            margin-left: 10px;
        }

        .header-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--surface);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            border: 2px solid transparent;
            transition: 0.2s;
        }

        .header-avatar:hover {
            border-color: var(--primary-hover);
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-width: 160px;
            z-index: 1000;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }

        .dropdown-menu a {
            padding: 12px 16px;
            text-decoration: none;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
            transition: 0.2s;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background: var(--bg-main);
            color: var(--primary);
        }


        /* =========================================================
           INFO POPUP - PRIVACY / TERMS / STATUS
        ========================================================= */

        .popup-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(6, 24, 61, 0.65);
            backdrop-filter: blur(3px);
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 20px;
        }

        .popup-overlay.show {
            display: flex;
        }

        .popup-card {
            background: var(--surface);
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            animation: popupIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .popup-header {
            position: relative;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            background: #06183d;
            border-bottom: 3px solid rgb(248, 243, 107);
        }

        .popup-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(125, 174, 243, 0.15);
            color: var(--pointColor);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .popup-header h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: whitesmoke;
            margin: 0;
        }

        .popup-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: none;
            border: none;
            font-size: 26px;
            line-height: 1;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: color 0.2s;
        }

        .popup-close:hover {
            color: #fff;
        }

        .popup-body {
            padding: 22px 24px;
            overflow-y: auto;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .popup-body h4 {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin: 16px 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .popup-body h4:first-child {
            margin-top: 0;
        }

        .popup-body ul {
            padding-left: 18px;
        }

        .popup-body li {
            margin-bottom: 4px;
        }

        /* Status rows */

        .status-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            color: #047857;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .status-row:last-child {
            border-bottom: none;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--success);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .popup-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 24px;
            background: var(--input-bg);
            border-top: 1px solid var(--border-color);
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: var(--text-muted);
        }

        .popup-ok {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .popup-ok:hover {
            background: var(--primary-hover);
        }

        @keyframes popupIn {

            from {
                opacity: 0;
                transform: translateY(12px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }
        .footer .links {
    transform: translateX(-65px);
}
.footer .links a {
    color: #551a8b;
    text-decoration: underline;
    cursor: pointer;
    margin-left: 3px;
}
.footer .links a {
    color: #64748b;
    text-decoration: none;
    cursor: pointer;
    margin-left: 18px;
}

.footer .links a:hover {
    color: #1d5fc7;
}
    </style>

</head>


<body>


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <header class="top-header">

        <a
            href="../home/home.php"
            class="brand"
            style="text-decoration: none; color: inherit;"
        >

            <div class="brand-icon">

                <img
                    src="../logo.png"
                    alt="Logo"
                    class="brand-logo-img"
                    onerror="this.style.display='none'"
                >

            </div>

            <div class="brand-text">

                <h2>Torque<span>Point</span></h2>

            </div>

        </a>


        <!-- Avatar and User Menu -->

        <div class="avatar-dropdown">

            <div
                class="header-avatar"
                onclick="toggleDropdown()"
            >
                <?php echo $initials; ?>
            </div>


            <div
                class="dropdown-menu"
                id="dropdownMenu"
            >

                <a href="profile.php">
                    <i class="fa-solid fa-user"></i>
                    Profile
                </a>

                <a href="history.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    History
                </a>

                <a href="../logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Logout
                </a>

            </div>

        </div>

    </header>



    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="container">


        <!-- HERO SECTION -->

        <section class="hero">

            <div class="small-title">
                TORQUEPOINT
            </div>

            <h1>
                Vehicle Service Center
                <span>Management System.</span>
            </h1>

            <p class="description">
                Select your vehicle details to view available services and prices.
            </p>

        </section>



        <!-- =====================================================
             VEHICLE SELECTION
        ====================================================== -->

        <div class="filter-box">


            <div class="form-group">

                <label for="vehicleType">
                    1. Vehicle Type
                </label>

                <select id="vehicleType">

                    <option value="">
                        -- Select Type --
                    </option>

                    <option value="car">
                        Car
                    </option>

                    <option value="van">
                        Van
                    </option>

                    <option value="suv">
                        SUV
                    </option>

                    <option value="motorcycle">
                        Motorcycle
                    </option>

                    <option value="truck">
                        Truck
                    </option>

                    <option value="bus">
                        Bus
                    </option>

                </select>

            </div>



            <div class="form-group">

                <label for="vehicleMake">
                    2. Make
                </label>

                <select id="vehicleMake" disabled>

                    <option value="">
                        -- Select Make --
                    </option>

                </select>

            </div>



            <div class="form-group">

                <label for="vehicleModel">
                    3. Model
                </label>

                <select id="vehicleModel" disabled>

                    <option value="">
                        -- Select Model --
                    </option>

                </select>

            </div>



            <div class="form-group">

                <label for="vehicleYear">
                    4. Year
                </label>

                <select id="vehicleYear" disabled>

                    <option value="">
                        -- Select Year --
                    </option>

                </select>

            </div>



            <!-- Mileage -->

            <div class="form-group">

                <label for="vehicleMileage">
                    5. Mileage (km)
                </label>

                <input
                    type="number"
                    id="vehicleMileage"
                    placeholder="e.g., 45000"
                    min="0"
                >

            </div>



            <button
                type="button"
                onclick="filterServices()"
                class="btn-primary"
                id="findBtn"
                disabled
            >

                Find Services

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </div>



        <!-- SERVICE RESULTS -->

        <div
            id="serviceResults"
            class="service-results"
        ></div>



        <!-- =====================================================
             CHECKOUT / PAYMENT SUMMARY
        ====================================================== -->

        <div
            id="checkoutSection"
            class="checkout-section hidden"
        >

            <h3 class="checkout-title">
                Order Summary
            </h3>


            <div class="summary-grid">


                <div class="summary-item">

                    <span class="summary-label">
                        VEHICLE
                    </span>

                    <span
                        id="summaryVehicle"
                        class="summary-value"
                    >
                        N/A
                    </span>

                </div>



                <div class="summary-item">

                    <span class="summary-label">
                        SERVICE
                    </span>

                    <span
                        id="summaryService"
                        class="summary-value"
                    >
                        N/A
                    </span>

                </div>



                <div class="summary-item">

                    <span class="summary-label">
                        EST. TIME
                    </span>

                    <span
                        id="summaryTime"
                        class="summary-value"
                    >
                        N/A
                    </span>

                </div>



                <div class="summary-item summary-total">

                    <span class="summary-label">
                        TOTAL PRICE
                    </span>

                    <span
                        id="summaryPrice"
                        class="summary-value"
                    >
                        Rs. 0.00
                    </span>

                </div>

            </div>



            <button
                class="btn-pay"
                onclick="proceedToPayment()"
            >

                Proceed to Schedule

                <i class="fa-solid fa-calendar-check"></i>

            </button>

        </div>

    </main>



    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <footer class="footer">

        <div>
            © 2026 TorquePoint Inc.
        </div>


        <div class="links">


            <!-- PRIVACY -->

            <a
                href="#"
                class="tooltip-link"
                data-popup="privacy"
            >
                Privacy
            </a>



            <!-- TERMS -->

            <a
                href="#"
                class="tooltip-link"
                data-popup="terms"
            >
                Terms
            </a>



            <!-- STATUS -->

            <a
                href="#"
                class="tooltip-link"
                data-popup="status"
            >
                Status
            </a>

        </div>

    </footer>



    <!-- =========================================================
         POPUP HTML
    ========================================================== -->

    <div
        class="popup-overlay"
        id="popupOverlay"
    >

        <div
            class="popup-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="popupTitle"
        >


            <!-- POPUP HEADER -->

            <div class="popup-header">

                <div class="popup-icon">

                    <i
                        id="popupIcon"
                        class="fa-solid fa-shield-halved"
                    ></i>

                </div>


                <h3 id="popupTitle">
                    Title
                </h3>


                <button
                    class="popup-close"
                    id="popupClose"
                    aria-label="Close"
                >
                    &times;
                </button>

            </div>



            <!-- POPUP BODY -->

            <div
                class="popup-body"
                id="popupBody"
            ></div>



            <!-- POPUP FOOTER -->

            <div class="popup-footer">

                <span id="popupUpdated">
                    Last updated: October 2026
                </span>

                <button
                    class="popup-ok"
                    id="popupOk"
                >
                    Got it
                </button>

            </div>

        </div>

    </div>



    <!-- =========================================================
         MAIN SERVICES JAVASCRIPT
    ========================================================== -->

    <script src="services.js"></script>



    <!-- =========================================================
         TAWK.TO LIVE CHAT
    ========================================================== -->

    <script type="text/javascript">

        var Tawk_API = Tawk_API || {};
        var Tawk_LoadStart = new Date();

        (function () {

            var s1 = document.createElement("script");
            var s0 = document.getElementsByTagName("script")[0];

            s1.async = true;

            s1.src =
                'https://embed.tawk.to/6abf9188df2d5634c099c01d/1k3u5101i';

            s1.charset = 'UTF-8';

            s1.setAttribute('crossorigin', '*');

            s0.parentNode.insertBefore(s1, s0);

        })();

    </script>



    <!-- =========================================================
         AVATAR DROPDOWN SCRIPT
    ========================================================== -->

    <script>

        function toggleDropdown() {

            const menu =
                document.getElementById('dropdownMenu');

            menu.style.display =
                (menu.style.display === 'flex')
                    ? 'none'
                    : 'flex';

        }


        window.onclick = function (event) {

            if (!event.target.matches('.header-avatar')) {

                const menu =
                    document.getElementById('dropdownMenu');

                if (
                    menu &&
                    menu.style.display === 'flex'
                ) {

                    menu.style.display = 'none';

                }

            }

        };

    </script>



    <!-- =========================================================
         PRIVACY / TERMS / STATUS POPUP JAVASCRIPT
    ========================================================== -->

    <script>

        const popupData = {

            /* =====================================================
               PRIVACY
            ===================================================== */

            privacy: {

                title: 'Privacy Policy',

                icon: 'fa-shield-halved',

                updated: 'Last updated: October 2026',

                html: `

                    <h4>What we collect</h4>

                    <ul>

                        <li>
                            Your name, email address and phone number
                        </li>

                        <li>
                            Vehicle details (make, model, plate number)
                        </li>

                        <li>
                            Your booking, service and invoice history
                        </li>

                    </ul>


                    <h4>How we use it</h4>

                    <ul>

                        <li>
                            To manage your bookings and service records
                        </li>

                        <li>
                            To send confirmations, invoices and service reminders
                        </li>

                        <li>
                            To improve our service center operations
                        </li>

                    </ul>


                    <h4>Payments &amp; sign-in</h4>

                    <p>
                        Card payments are handled securely by Stripe.
                        We never store your card number.
                        If you use Google sign-in, we only receive your
                        name and email.
                    </p>


                    <h4>Your data</h4>

                    <p>
                        We do not sell your personal data.
                        You can request a copy or deletion of your
                        account data by contacting the service center.
                    </p>

                `

            },


            /* =====================================================
               TERMS
            ===================================================== */

            terms: {

                title: 'Terms & Conditions',

                icon: 'fa-file-contract',

                updated: 'Last updated: October 2026',

                html: `

                    <h4>Your account</h4>

                    <p>
                        Keep your login details private and provide
                        accurate vehicle and contact information.
                        You are responsible for activity under your account.
                    </p>


                    <h4>Bookings</h4>

                    <ul>

                        <li>
                            Bookings are confirmed once you receive
                            a confirmation email
                        </li>

                        <li>
                            Please cancel or reschedule before your
                            appointment time so others can use the slot
                        </li>

                        <li>
                            Arriving late may require us to rebook
                            your service
                        </li>

                    </ul>


                    <h4>Pricing &amp; payments</h4>

                    <p>
                        Quoted prices are estimates.
                        If extra repairs are needed, we will contact
                        you for approval before any additional work begins.
                        Invoices are payable at the time of service completion.
                    </p>


                    <h4>Liability</h4>

                    <p>
                        We take care of every vehicle, but we are not
                        responsible for personal items left inside the vehicle.
                    </p>

                `

            },


            /* =====================================================
               SYSTEM STATUS
            ===================================================== */

            status: {

                title: 'System Status',

                icon: 'fa-signal',

                updated: 'Checked just now',

                html: `

                    <div class="status-banner">

                        <i class="fa-solid fa-circle-check"></i>

                        All systems operational

                    </div>


                    <div class="status-row">

                        <span>
                            Customer portal
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>


                    <div class="status-row">

                        <span>
                            Online booking
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>


                    <div class="status-row">

                        <span>
                            Payments (Stripe)
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>


                    <div class="status-row">

                        <span>
                            Invoice &amp; PDF generation
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>


                    <div class="status-row">

                        <span>
                            Email notifications
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>


                    <div class="status-row">

                        <span>
                            Admin dashboard
                        </span>

                        <span class="status-pill">

                            <span class="status-dot"></span>

                            Operational

                        </span>

                    </div>

                `

            }

        };



        /* =========================================================
           POPUP ELEMENT
        ========================================================== */

        const overlay =
            document.getElementById('popupOverlay');



        /* =========================================================
           OPEN POPUP
        ========================================================== */

        function openPopup(key) {

            const data = popupData[key];

            if (!data) return;


            document.getElementById('popupTitle')
                .textContent = data.title;


            document.getElementById('popupIcon')
                .className =
                'fa-solid ' + data.icon;


            document.getElementById('popupBody')
                .innerHTML = data.html;


            document.getElementById('popupUpdated')
                .textContent = data.updated;


            overlay.classList.add('show');

        }



        /* =========================================================
           CLOSE POPUP
        ========================================================== */

        function closePopup() {

            overlay.classList.remove('show');

        }



        /* =========================================================
           FOOTER LINK CLICK EVENTS
        ========================================================== */

        document
            .querySelectorAll('[data-popup]')
            .forEach(el => {

                el.addEventListener('click', e => {

                    e.preventDefault();

                    openPopup(el.dataset.popup);

                });

            });



        /* =========================================================
           CLOSE BUTTON
        ========================================================== */

        document
            .getElementById('popupClose')
            .addEventListener(
                'click',
                closePopup
            );



        /* =========================================================
           GOT IT BUTTON
        ========================================================== */

        document
            .getElementById('popupOk')
            .addEventListener(
                'click',
                closePopup
            );



        /* =========================================================
           CLICK OUTSIDE POPUP
        ========================================================== */

        overlay.addEventListener('click', e => {

            if (e.target === overlay) {

                closePopup();

            }

        });



        /* =========================================================
           ESC KEY
        ========================================================== */

        document.addEventListener('keydown', e => {

            if (e.key === 'Escape') {

                closePopup();

            }

        });

    </script>


</body>

</html>