
# Vehicle Service Management System 🚗⚙️

A full-stack, enterprise-grade web application built using PHP, MySQL, and the Stripe API. It digitalizes the day-to-day operations of a vehicle service center, allowing customers to book appointments online, pay via Stripe, and track their service status in real-time.

## Tech Stack
* **Frontend:** HTML5, CSS3 (Flexbox/Grid), JavaScript (Fetch API)
* **Backend:** PHP 8 (Procedural & PDO)
* **Database:** MySQL (torquepoint_db)
* **Integrations:** Stripe API, Google OAuth, PHPMailer, Dompdf, Chart.js.

## Features
- Secure Dual Authentication (Google Login + Email/Password with Bcrypt & OTP verification)
- Role-Based Access Control (RBAC) for Admin, Mechanic, and Customer portals
- Dynamic visual booking calendar preventing double-booking and blocking holidays
- Real Stripe Payment Gateway integration with automated PDF Invoice generation
- Automated Mechanic Queue Management (auto-assigning available mechanics)
- Admin Dashboard with Chart.js analytics and CSV export
- Smart maintenance mileage reminder emails

---

## ⚠️ IMPORTANT SETUP INSTRUCTIONS (Please Read Carefully)

To ensure the system runs perfectly on your local machine, please follow these steps exactly. Skipping the XAMPP configuration step will cause the PDF generation to fail!

### Step 1: Clone to the Correct Folder
Clone this repository **directly** into your XAMPP `htdocs` folder. 
The application is hardcoded to run inside a folder named `Vehicle-Service-Management-System`. 

Open your terminal/command prompt, navigate to your XAMPP htdocs folder, and run:
```bash
cd C:\xampp\htdocs
git clone https://github.com/YOUR_USERNAME/Vehicle-Service-Management-System.git Vehicle-Service-Management-System
```
*Your final path must look exactly like this: `C:\xampp\htdocs\Vehicle-Service-Management-System\`*

### Step 2: Configure XAMPP (Enable GD Extension) 🛠️
The system generates PDF invoices using the `Dompdf` library. To process images (like your logo) inside the PDF, PHP requires the **GD Extension**, which is disabled by default in XAMPP. If you do not enable this, you will get a `Fatal error: Uncaught Exception: The GD extension is required`.

1. Open your **XAMPP Control Panel**.
2. Next to **Apache**, click the **Config** button and select **`php.ini`**.
3. A notepad file will open. Press `Ctrl + F` and search for: `extension=gd`
4. You will see a line that looks like this: `;extension=gd` (or `;extension=php_gd.dll`).
5. **Remove the semicolon `;`** at the beginning of the line. It must look exactly like this:
   ```ini
   extension=gd
   ```
6. Save the file (`Ctrl + S`) and close Notepad.
7. Go back to the XAMPP Control Panel, click **Stop** next to Apache, wait 3 seconds, and click **Start** to restart the server with the new configuration.

### Step 3: Database Setup
1. Open `phpMyAdmin` (`http://localhost/phpmyadmin`).
2. Click **New** on the left sidebar and create a database named `torquepoint_db`.
3. Select the `torquepoint_db` database, click the **Import** tab at the top.
4. Choose the `database_schema.sql` file included in this repository and click **Go**. This will create all 6 required tables and insert the default mechanics/services.

### Step 4: Connect the Database
1. In the main project folder, find the file named `db_connect.example.php`.
2. Rename it to `db_connect.php`.
3. Open it and ensure the credentials match your local XAMPP setup (usually `root` with an empty password):
   ```php
   <?php
   $host = 'localhost';
   $dbname = 'torquepoint_db';
   $username = 'root'; 
   $password = '';     
   // ... rest of the PDO connection code ...
   ?>
   ```

### Step 5: Install Third-Party Libraries
Because GitHub ignores heavy library folders, you need to download two libraries and place them in the main project root folder (`C:\xampp\htdocs\Vehicle-Service-Management-System\`):

1. **Dompdf (For PDF Generation):** Download the latest release from [Dompdf GitHub](https://github.com/dompdf/dompdf/releases). Extract it, rename the folder to `dompdf`, and place it in the project root.
2. **PHPMailer (For Emails):** Download it from [PHPMailer GitHub](https://github.com/PHPMailer/PHPMailer). Extract it, rename the folder to `PHPMailer`, and place it in the project root.

*Your final folder structure should have `dompdf/` and `PHPMailer/` sitting right next to `db_connect.php`.*

---

## Run the Application
Once the setup is complete, open your browser and go to:
### `http://localhost/Vehicle-Service-Management-System/home/home.php`

### Test Payment Credentials
To test the Stripe Payment Gateway, use the following Stripe Test Card:
* **Card Number:** `4242 4242 4242 4242`
* **Expiry Date:** Any future date (e.g., `12/34`)
* **CVC:** Any 3 digits (e.g., `123`)
* **ZIP/Postal Code:** Any 5 digits (e.g., `12345`)
```

***

### Database SQL codes:
-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS torquepoint_db;
USE torquepoint_db;

-- Drop existing tables if they exist (for a clean slate)
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS blocked_dates;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS mechanics;
DROP TABLE IF EXISTS service_packages;
DROP TABLE IF EXISTS users;

-- 2. Create the Users Table (Customers, Admins, Mechanics)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    login_type VARCHAR(50) DEFAULT 'email',
    is_admin TINYINT(1) DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    verification_pin VARCHAR(10) DEFAULT NULL,
    role VARCHAR(20) DEFAULT 'customer',
    mechanic_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Create the Mechanics Table (The 4 workers)
CREATE TABLE mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_available TINYINT(1) DEFAULT 1
);

-- 4. Create the Bookings Table (Service Orders)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    vehicle_details VARCHAR(255) NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    est_time VARCHAR(100) DEFAULT NULL,
    price VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending Payment',
    assigned_mechanic_id INT DEFAULT NULL,
    vehicle_mileage INT DEFAULT 0,
    booking_date DATE DEFAULT NULL,
    booking_time VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Create the Service Packages Table (Dynamic Pricing)
CREATE TABLE service_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_type VARCHAR(50) NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    description TEXT,
    price VARCHAR(100) NOT NULL,
    time VARCHAR(100) NOT NULL
);

-- 6. Create the Ratings Table (Customer Feedback)
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNIQUE NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    rating INT NOT NULL,
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Create the Blocked Dates Table (Admin Calendar Management)
CREATE TABLE blocked_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    reason VARCHAR(255) DEFAULT 'Holiday/Closed'
);

-- ==========================================
-- INSERT DEFAULT DATA
-- ==========================================

-- Insert 4 Default Mechanics
INSERT INTO mechanics (name, is_available) VALUES 
('Saman', 1),
('Kasun', 1),
('Nimal', 1),
('Sunil', 1);

-- Insert Default Service Packages
INSERT INTO service_packages (vehicle_type, service_name, description, price, time) VALUES
('car', 'Oil Change', 'Engine oil replacement and basic inspection.', 'Rs. 5,000', '1 Hour'),
('car', 'Brake Service', 'Brake inspection and brake pad checking.', 'Rs. 8,500', '2 Hours'),
('car', 'Full Vehicle Service', 'Complete vehicle inspection and maintenance.', 'Rs. 25,000', '3 - 4 Hours'),
('car', 'AC Service', 'Air conditioning inspection and maintenance.', 'Rs. 7,500', '2 Hours'),
('suv', 'Oil Change', 'Engine oil replacement.', 'Rs. 7,000', '1 Hour'),
('suv', 'Full Service', 'Complete SUV maintenance.', 'Rs. 30,000', '4 Hours'),
('van', 'Oil Change', 'Engine oil replacement.', 'Rs. 7,000', '1 Hour'),
('van', 'Full Service', 'Complete van maintenance.', 'Rs. 30,000', '4 Hours');

CREATE TABLE chat_sessions (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  token          CHAR(32)     NOT NULL,                 
  first_name     VARCHAR(60)  NOT NULL,
  last_name      VARCHAR(60)  NOT NULL,
  email          VARCHAR(150) NOT NULL,
  status         ENUM('open','closed') NOT NULL DEFAULT 'open',
  user_last_seen DATETIME NULL,                         
  last_activity  DATETIME NOT NULL,
  created_at     DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_token (token),
  KEY idx_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chat_messages (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id  INT UNSIGNED NOT NULL,
  sender      ENUM('user','admin') NOT NULL,
  message     TEXT NOT NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,           
  created_at  DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_session_id (session_id, id),
  CONSTRAINT fk_msg_session FOREIGN KEY (session_id)
    REFERENCES chat_sessions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Now, anyone who downloads this and read the README.md will know exactly how to set up XAMPP and enable the GD extension, thier SQL database and they won't get stuck!
