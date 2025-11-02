<?php
include 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require("php-mailer/PHPMailer.php");
require("php-mailer/SMTP.php");
require("php-mailer/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle AJAX actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Step 1: Send OTP when email/phone changed
    if ($_POST['action'] === 'send_otp') {
        $method = $_POST['method'];
        $value = trim($_POST['value']);
        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        if ($method === 'email') {
            $sql = $conn->prepare("UPDATE users SET otp_email=?, otp_expires=? WHERE user_id=?");
            $sql->bind_param("ssi", $otp, $otp_expiry, $user_id);
            $sql->execute();

            // Send Email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'mail.aadarshsenapati.in';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'contact@aadarshsenapati.in';
                $mail->Password   = 'Rishi@2005'; // ⚠️ Replace with secure password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('contact@aadarshsenapati.in', 'Smart Parking Verification');
                $mail->addAddress($value);
                $mail->isHTML(true);
                $mail->Subject = 'Verify your new email';
                $mail->Body    = "<h3>Your verification OTP is <b>$otp</b></h3>";
                $mail->send();

                echo json_encode(["success" => true, "message" => "📧 OTP sent to your new email."]);
            } catch (Exception $e) {
                echo json_encode(["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo]);
            }

        } else {
            if (strpos($value, '+91') !== 0) {
                $value = '+91' . preg_replace('/[^0-9]/', '', $value);
            }

            $sql = $conn->prepare("UPDATE users SET otp_whatsapp=?, otp_expires=? WHERE user_id=?");
            $sql->bind_param("ssi", $otp, $otp_expiry, $user_id);
            $sql->execute();

            // Send WhatsApp OTP
            $payload = json_encode(["OTP" => $otp, "phone" => $value]);
            $ch = curl_init("https://whatsapp-bot-edr6.onrender.com/send_otp");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);

            echo json_encode(["success" => true, "message" => "📱 OTP sent to your new WhatsApp number."]);
        }
        exit;
    }

    // Step 2: Verify OTP and update info
    if ($_POST['action'] === 'verify_otp') {
        $method = $_POST['method'];
        $value = trim($_POST['value']);
        $otp = $_POST['otp'];

        if ($method === 'email') {
            $q = $conn->prepare("SELECT * FROM users WHERE user_id=? AND otp_email=? AND otp_expires>NOW()");
        } else {
            $q = $conn->prepare("SELECT * FROM users WHERE user_id=? AND otp_whatsapp=? AND otp_expires>NOW()");
        }

        $q->bind_param("is", $user_id, $otp);
        $q->execute();
        $res = $q->get_result();

        if ($res->num_rows > 0) {
            if ($method === 'email') {
                $update = $conn->prepare("UPDATE users SET email=?, otp_email=NULL, otp_expires=NULL WHERE user_id=?");
            } else {
                $update = $conn->prepare("UPDATE users SET phone=?, otp_whatsapp=NULL, otp_expires=NULL WHERE user_id=?");
            }
            $update->bind_param("si", $value, $user_id);
            $update->execute();

            echo json_encode(["success" => true, "message" => "✅ Updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "❌ Invalid or expired OTP."]);
        }
        exit;
    }

    // Step 3: Update name only
    if ($_POST['action'] === 'update_name') {
        $name = trim($_POST['name']);
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE user_id=?");
        $stmt->bind_param("si", $name, $user_id);
        $stmt->execute();
        $_SESSION['name'] = $name;
        echo json_encode(["success" => true, "message" => "✅ Name updated successfully!"]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #191782;
            --primary-main: #4607f4;
            --primary-light: #6a40f7;
            --primary-lighter: #8f73f9;
            --gradient: linear-gradient(135deg, #191782 0%, #4607f4 100%);
            --gradient-light: linear-gradient(135deg, #4607f4 0%, #6a40f7 100%);
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }

        .header {
            background: var(--gradient);
            box-shadow: 0 4px 20px rgba(25, 23, 130, 0.3);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 5px;
        }

        .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }

        .profile-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .profile-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 4px solid var(--primary-main);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-main);
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-main);
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-main);
            box-shadow: 0 0 0 3px rgba(70, 7, 244, 0.1);
        }

        .btn-primary-custom {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--primary-main);
            color: var(--primary-main);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-outline-custom:hover {
            background: var(--primary-main);
            color: white;
            transform: translateY(-2px);
        }

        .status-message {
            padding: 0.8rem;
            border-radius: 10px;
            margin: 1rem 0;
            text-align: center;
            font-size: 0.9rem;
        }

        .status-success {
            background: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .status-error {
            background: rgba(244, 67, 54, 0.1);
            color: #c62828;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .hidden {
            display: none;
        }

        .otp-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            margin-top: 1rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer {
            background: var(--gradient);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer h5 {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: white;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem 0.5rem;
            }
            
            .profile-card {
                padding: 2rem 1.5rem;
            }
            
            .form-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-parking me-2"></i>
                    Smart Parking
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="booking.php"><i class="fas fa-map-marker-alt me-1"></i> Book Slot</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pending_payments.php"><i class="fas fa-tachometer-alt me-1"></i> Payments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h1 class="profile-title">Profile Settings</h1>
                <p class="profile-subtitle">Manage your account information and preferences</p>
            </div>

            <!-- Name Update Section -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-user-edit"></i> Personal Information
                </h3>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="name" class="form-input" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Enter your full name">
                </div>
                <button class="btn btn-primary-custom" onclick="updateName()">
                    <i class="fas fa-save me-2"></i> Update Name
                </button>
                <div id="nameStatus" class="status-message"></div>
            </div>

            <!-- Email Update Section -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-envelope"></i> Email Address
                </h3>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Enter your email address">
                </div>
                <button class="btn btn-outline-custom" onclick="sendEmailOTP()">
                    <i class="fas fa-sync-alt me-2"></i> Change Email
                </button>
                <div id="emailStatus" class="status-message"></div>

                <div id="emailOTPSection" class="otp-section hidden">
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" id="emailOtp" class="form-input" placeholder="Enter 6-digit OTP">
                    </div>
                    <button class="btn btn-primary-custom" onclick="verifyEmailOTP()">
                        <i class="fas fa-check-circle me-2"></i> Verify Email OTP
                    </button>
                </div>
            </div>

            <!-- WhatsApp Update Section -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fab fa-whatsapp"></i> WhatsApp Number
                </h3>
                <div class="input-group">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="text" id="phone" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Enter your WhatsApp number">
                </div>
                <button class="btn btn-outline-custom" onclick="sendPhoneOTP()">
                    <i class="fas fa-sync-alt me-2"></i> Change WhatsApp
                </button>
                <div id="phoneStatus" class="status-message"></div>

                <div id="phoneOTPSection" class="otp-section hidden">
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" id="phoneOtp" class="form-input" placeholder="Enter 6-digit OTP">
                    </div>
                    <button class="btn btn-primary-custom" onclick="verifyPhoneOTP()">
                        <i class="fas fa-check-circle me-2"></i> Verify WhatsApp OTP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center pt-2">
                <p>&copy; 2023 Smart Parking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showStatus(elementId, message, type) {
        const element = document.getElementById(elementId);
        element.innerText = message;
        element.className = 'status-message';
        
        if (type === 'success') {
            element.classList.add('status-success');
        } else if (type === 'error') {
            element.classList.add('status-error');
        }
    }

    async function updateName() {
        let name = document.getElementById('name').value.trim();
        if (!name) {
            showStatus('nameStatus', 'Please enter your name', 'error');
            return;
        }

        let form = new FormData();
        form.append('action', 'update_name');
        form.append('name', name);
        
        try {
            let res = await fetch('profile.php', {method: 'POST', body: form});
            let data = await res.json();
            showStatus('nameStatus', data.message, data.success ? 'success' : 'error');
        } catch (error) {
            showStatus('nameStatus', 'Network error. Please try again.', 'error');
        }
    }

    async function sendEmailOTP() {
        let email = document.getElementById('email').value.trim();
        if (!email) {
            showStatus('emailStatus', 'Please enter a valid email address', 'error');
            return;
        }

        let form = new FormData();
        form.append('action', 'send_otp');
        form.append('method', 'email');
        form.append('value', email);
        
        try {
            let res = await fetch('profile.php', {method: 'POST', body: form});
            let data = await res.json();
            showStatus('emailStatus', data.message, data.success ? 'success' : 'error');
            if (data.success) {
                document.getElementById('emailOTPSection').classList.remove('hidden');
            }
        } catch (error) {
            showStatus('emailStatus', 'Network error. Please try again.', 'error');
        }
    }

    async function verifyEmailOTP() {
        let email = document.getElementById('email').value.trim();
        let otp = document.getElementById('emailOtp').value.trim();
        
        if (!otp || otp.length !== 6) {
            showStatus('emailStatus', 'Please enter a valid 6-digit OTP', 'error');
            return;
        }

        let form = new FormData();
        form.append('action', 'verify_otp');
        form.append('method', 'email');
        form.append('value', email);
        form.append('otp', otp);
        
        try {
            let res = await fetch('profile.php', {method: 'POST', body: form});
            let data = await res.json();
            showStatus('emailStatus', data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                document.getElementById('emailOTPSection').classList.add('hidden');
                document.getElementById('emailOtp').value = '';
            }
        } catch (error) {
            showStatus('emailStatus', 'Network error. Please try again.', 'error');
        }
    }

    async function sendPhoneOTP() {
        let phone = document.getElementById('phone').value.trim();
        if (!phone) {
            showStatus('phoneStatus', 'Please enter a valid WhatsApp number', 'error');
            return;
        }

        let form = new FormData();
        form.append('action', 'send_otp');
        form.append('method', 'whatsapp');
        form.append('value', phone);
        
        try {
            let res = await fetch('profile.php', {method: 'POST', body: form});
            let data = await res.json();
            showStatus('phoneStatus', data.message, data.success ? 'success' : 'error');
            if (data.success) {
                document.getElementById('phoneOTPSection').classList.remove('hidden');
            }
        } catch (error) {
            showStatus('phoneStatus', 'Network error. Please try again.', 'error');
        }
    }

    async function verifyPhoneOTP() {
        let phone = document.getElementById('phone').value.trim();
        let otp = document.getElementById('phoneOtp').value.trim();
        
        if (!otp || otp.length !== 6) {
            showStatus('phoneStatus', 'Please enter a valid 6-digit OTP', 'error');
            return;
        }

        let form = new FormData();
        form.append('action', 'verify_otp');
        form.append('method', 'whatsapp');
        form.append('value', phone);
        form.append('otp', otp);
        
        try {
            let res = await fetch('profile.php', {method: 'POST', body: form});
            let data = await res.json();
            showStatus('phoneStatus', data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                document.getElementById('phoneOTPSection').classList.add('hidden');
                document.getElementById('phoneOtp').value = '';
            }
        } catch (error) {
            showStatus('phoneStatus', 'Network error. Please try again.', 'error');
        }
    }
    </script>
</body>
</html>