<?php
// Moved to the very top so session_start() runs before any HTML is sent.
session_start();
require_once '../db_connect.php';

$is_logged_in = isset($_SESSION['user_email']);
$userName = 'User';
$initials = 'U';

// Pre-fill values for the contact form (only filled when the customer is logged in)
$prefill_first = '';
$prefill_last  = '';
$prefill_email = '';

// Determine where the "Get Started" button should go
$get_started_link = $is_logged_in ? '../services/services.php' : '../loginPage/login.html';

if ($is_logged_in) {
    $email = $_SESSION['user_email'];
    $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $userName = $user['name'] ?? $email;
    $initials = strtoupper(substr($userName, 0, 1));
    if (strpos($userName, ' ') !== false) {
        $parts = explode(' ', $userName);
        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }

    // Split the saved full name into first / last name for the contact form
    $nameParts     = explode(' ', trim($userName), 2);
    $prefill_first = $nameParts[0] ?? '';
    $prefill_last  = $nameParts[1] ?? '';
    $prefill_email = $email;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Torque Point | Vehicle Service Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Updated Fonts: Montserrat, Inter, JetBrains Mono -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="home.css">
<link rel="stylesheet" href="home-chat.css">
<link rel="icon" type="image/png" href="../logo.png">
</head>
<body>

<!-- ============ NAVBAR ============ -->
<header class="navbar" id="navbar">
    <a href="home.php" class="logo">
      <img src="../logo.png" alt="Torque Point Logo" class="logo-img">
      Torque<span>Point</span>
    </a>

    <nav class="nav-links" id="navLinks">
        <a href="home.php" class="nav-link" data-target="home">Home</a>
        <a href="<?php echo $get_started_link; ?>" class="nav-link">Get Started</a>
        <a href="#about" class="nav-link" data-target="about">About&nbsp;Us</a>
        <a href="#contact" class="nav-link" data-target="contact">Contact&nbsp;Us</a>
        
        <?php if ($is_logged_in): ?>
            <!-- AVATAR DROPDOWN -->
            <div class="avatar-dropdown">
                <div class="header-avatar" onclick="toggleDropdown()"><?php echo $initials; ?></div>
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="../services/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                    <a href="../services/history.php"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
                    <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <!-- LOGIN LINK -->
            <a href="../loginPage/login.html" class="nav-link" id="loginLink">Login</a>
        <?php endif; ?>
    </nav>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu">
      <span></span><span></span><span></span>
    </button>
</header>

  <!-- ============ HERO ============ -->
  <section class="hero" id="home">
    <div class="blueprint"></div>

    <div class="hero-content">
      <p class="eyebrow">Vehicle Service Management System</p>
      <h1>Every Vehicle Has a <span class="accent">Torque Point.</span></h1>
      <p class="tagline">Book, track and manage every service — from a routine oil change to a full engine overhaul — through one connected system built for precision.</p>
      <div class="hero-actions">
        <a href="<?php echo $get_started_link; ?>" class="btn btn-primary">Get Started</a>
        <a href="#about" class="btn btn-ghost">Learn More</a>
      </div>
    </div>

    <div class="scroll-cue"><span class="line"></span>Scroll</div>
  </section>

  <!-- ============ ABOUT ============ -->
  <section class="about" id="about">
    <div class="section-head reveal">
      <p class="eyebrow">About the System</p>
      <h2>Built to Run a Service Center Like Clockwork</h2>
      <p>A single dashboard that connects customers, service bays and records — so nothing falls through the cracks.</p>
    </div>

    <div class="about-grid">
      <div class="about-copy reveal">
        <h3>One System, Every Step of the Service</h3>
        <p>Torque Point replaces scattered paperwork and phone calls with a connected platform: customers book online, technicians track jobs bay-by-bay, and managers see the whole workshop at a glance — from intake to invoice.</p>

        <div class="feature-list">
          <div class="feature">
            <div class="feature-icon">
              <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div>
              <h4>Online Booking</h4>
              <p>Customers pick a service, a bay and a time slot without a single phone call.</p>
            </div>
          </div>

          <div class="feature">
            <div class="feature-icon">
              <i class="fa-solid fa-gauge-high"></i>
            </div>
            <div>
              <h4>Live Job Tracking</h4>
              <p>Every vehicle's status updates in real time, from check-in to final inspection.</p>
            </div>
          </div>

          <div class="feature">
            <div class="feature-icon">
              <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
              <h4>Digital Service History</h4>
              <p>Full repair and maintenance records stored per vehicle, ready whenever needed.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Replaced illustration with a clean contact card -->
      <div class="reveal" style="display:flex; align-items:center;">
        <div style="background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 32px; width: 100%; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
          <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding-bottom:24px; border-bottom:1px solid var(--border-color);">
            <div style="width:56px; height:56px; background:var(--primary); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--surface); font-size:24px;">
              <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <div>
              <h4 style="font-family:'Montserrat'; font-size:18px; color:var(--text-main); margin-bottom:4px;">Service Center Dashboard</h4>
              <p style="color:var(--text-muted); font-size:14px;">Real-time overview of shop operations</p>
            </div>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div style="background:var(--bg-main); padding:16px; border-radius:8px;">
              <div style="font-family:'JetBrains Mono'; font-size:13px; color:var(--text-muted); margin-bottom:4px;">VEHICLES TODAY</div>
              <div style="font-family:'Montserrat'; font-size:24px; font-weight:600; color:var(--text-main);">12</div>
            </div>
            <div style="background:var(--bg-main); padding:16px; border-radius:8px;">
              <div style="font-family:'JetBrains Mono'; font-size:13px; color:var(--text-muted); margin-bottom:4px;">ACTIVE JOBS</div>
              <div style="font-family:'Montserrat'; font-size:24px; font-weight:600; color:var(--text-main);">5</div>
            </div>
          </div>
          
          <div style="margin-top:16px; background:var(--bg-main); padding:16px; border-radius:8px;">
            <div style="font-family:'JetBrains Mono'; font-size:13px; color:var(--text-muted); margin-bottom:8px;">BAY STATUS</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <span class="status-badge status-success">Bay 1: Completed</span>
              <span class="status-badge status-warning">Bay 2: In Progress</span>
              <span class="status-badge" style="background:rgba(100, 116, 139, 0.1); color:var(--text-muted);">Bay 3: Idle</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ CONTACT / FOOTER ============ -->
  <footer class="contact-footer" id="contact">
    <div class="contact-grid">
      <div class="contact-info reveal">
        <p class="eyebrow">Get in Touch</p>
        <h2>Bring Your Vehicle to Torque Point</h2>
        <p>Have a question about a booking or need service today? Send us a message and chat with our team live.</p>

        <div class="info-row">
          <div class="feature-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div><h4>Visit the Workshop</h4><p>124 Galle Road, Colombo, Sri Lanka</p></div>
        </div>

        <div class="info-row">
          <div class="feature-icon"><i class="fa-solid fa-phone"></i></div>
          <div><h4>Call the Service Desk</h4><a href="tel:+94112345678">+94 11 234 5678</a></div>
        </div>

        <div class="info-row">
          <div class="feature-icon"><i class="fa-solid fa-envelope"></i></div>
          <div><h4>Email Us</h4><a href="mailto:pointtorque@gmail.com">pointtorque@gmail.com</a></div>
        </div>

        <div class="social-row">
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
        </div>
      </div>

      <div class="contact-side reveal">

        <!-- 1) Contact form: sends the first message and opens the chat -->
        <form class="contact-form" id="contactForm" novalidate>
          <div class="form-row">
            <div class="field"><label for="fname">First name</label><input id="fname" name="first_name" type="text" maxlength="60" placeholder="FirstName" value="<?php echo htmlspecialchars($prefill_first); ?>" required></div>
            <div class="field"><label for="lname">Last name</label><input id="lname" name="last_name" type="text" maxlength="60" placeholder="SecondName" value="<?php echo htmlspecialchars($prefill_last); ?>" required></div>
          </div>
          <div class="form-row">
            <div class="field full"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="150" placeholder="Enter your email" value="<?php echo htmlspecialchars($prefill_email); ?>" required></div>
          </div>
          <div class="form-row">
            <div class="field full"><label for="msg">Message</label><textarea id="msg" name="message" maxlength="2000" placeholder="Tell us about your vehicle or the service you need..." required></textarea></div>
          </div>
          <input class="hp-field" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
          <p class="form-error" id="formError" role="alert"></p>
          <button type="submit" class="btn btn-primary" id="sendBtn">Send Message</button>
        </form>

        <!-- 2) Live chat: replaces the form after the first message is sent -->
        <div class="contact-form chat-panel" id="chatPanel" hidden>
          <div class="chat-head">
            <div>
              <h3>Your conversation</h3>
              <span class="chat-state" id="chatState">Connected</span>
            </div>
            <button type="button" class="chat-new" id="chatNew">New message</button>
          </div>
          <div class="chat-log" id="chatLog" aria-live="polite"></div>
          <form class="chat-composer" id="chatForm">
            <input id="chatInput" type="text" maxlength="2000" placeholder="Type a message..." autocomplete="off">
            <button type="submit" class="btn btn-primary" id="chatSend">Send</button>
          </form>
          <p class="chat-note" id="chatClosed" hidden>This conversation has been closed. Use "New message" to contact us again.</p>
        </div>

      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <span id="year"></span> Torque Point</span>
      <span>Group Project — Vehicle Service Management System</span>
    </div>
  </footer>

<script>
  // Path from this page to the chat api.php. Change it if your folder has a different name.
  window.CHAT_API = '../admin/api.php';
</script>
<script src="home.js"></script>
<script src="home-chat.js"></script>
</body>
</html>
