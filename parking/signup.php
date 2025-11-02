<?php
include 'includes/connection.php'; 

require("php-mailer/PHPMailer.php");
require("php-mailer/SMTP.php");
require("php-mailer/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'send_otps') {
        $name  = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($email) || empty($phone)) {
            echo json_encode(["success" => false, "message" => "Please fill all fields."]);
            exit;
        }

        // ✅ Check duplicates
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' OR phone='$phone'");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(["success" => false, "message" => "❌ Email or phone already registered!"]);
            exit;
        }

        $otp_email     = rand(100000, 999999);
        $otp_whatsapp  = rand(100000, 999999);
        $otp_expires   = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $sql = "INSERT INTO users (name, email, phone, otp_email, otp_whatsapp, otp_expires, registered_on)
                VALUES ('$name','$email','$phone','$otp_email','$otp_whatsapp','$otp_expires', NOW())";
        mysqli_query($conn, $sql);

        // --- Send Email OTP ---
        $mail_status = false;
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'mail.aadarshsenapati.in';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'contact@aadarshsenapati.in';
            $mail->Password   = 'Rishi@2005'; // replace with actual password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('contact@aadarshsenapati.in', 'Smart Parking Verification');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Your Smart Parking Email OTP';
            $mail->Body    = "<h3>Hello $name,</h3><p>Your Email OTP is <b>$otp_email</b></p>";
            $mail->send();
            $mail_status = true;
        } catch (Exception $e) {
            $mail_status = false;
        }

        // --- Send WhatsApp OTP ---
        $payload = json_encode(["OTP" => $otp_whatsapp, "phone" => $phone]);
        $ch = curl_init("https://whatsapp-bot-edr6.onrender.com/send_otp");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        echo json_encode([
            "success" => true,
            "message" => "📧 Email and 📱 WhatsApp OTPs sent successfully!"
        ]);
        exit;
    }

    if ($_POST['action'] === 'verify_both_otps') {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $otp_email = $_POST['otp_email'];
        $otp_whatsapp = $_POST['otp_whatsapp'];

        $query = mysqli_query($conn, "SELECT * FROM users 
            WHERE email='$email' AND phone='$phone' 
            AND otp_email='$otp_email' AND otp_whatsapp='$otp_whatsapp' 
            AND otp_expires > NOW()");

        if (mysqli_num_rows($query) > 0) {
            mysqli_query($conn, "UPDATE users 
                SET otp_email=NULL, otp_whatsapp=NULL, otp_expires=NULL 
                WHERE email='$email'");
            echo json_encode(["success" => true, "message" => "🎉 Account verified successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "❌ Invalid or expired OTPs!"]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Smart Parking</title>
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

        .signup-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .signup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .signup-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .signup-icon {
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

        .signup-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .signup-subtitle {
            color: #6c757d;
            font-size: 1rem;
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
            z-index: 3;
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

        .phone-input-group {
            display: flex;
            align-items: center;
        }

        .phone-prefix {
            background: #f8f9fa;
            border: 2px solid #e1e5eb;
            border-right: none;
            padding: 1rem;
            border-radius: 12px 0 0 12px;
            font-weight: 500;
            color: var(--primary-dark);
        }

        .phone-input {
            border-radius: 0 12px 12px 0 !important;
            border-left: none;
            padding-left: 1rem;
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
            width: 100%;
        }

        .btn-primary-custom:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
            color: white;
        }

        .btn-primary-custom:disabled {
            background: #cccccc;
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
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
            background: #f8f9fa;
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

        .success-section {
            text-align: center;
            padding: 2rem 0;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
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
            .signup-container {
                padding: 1rem 0.5rem;
            }
            
            .signup-card {
                padding: 2rem 1.5rem;
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
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="signup.php"><i class="fas fa-user-plus me-1"></i> Sign Up</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <div class="signup-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="signup-title">Create Account</h1>
                <p class="signup-subtitle">Join Smart Parking for seamless parking experience</p>
            </div>

            <!-- Registration Form Section -->
            <div id="form-section">
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="name" class="form-input" placeholder="Enter your full name" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" class="form-input" placeholder="Enter your email address" required>
                </div>

                <div class="input-group">
                    <div class="phone-input-group">
                        <span class="phone-prefix">+91</span>
                        <input type="text" id="phone" class="form-input phone-input" placeholder="WhatsApp number" maxlength="10" required>
                    </div>
                </div>

                <button id="send-btn" class="btn-primary-custom" onclick="sendOTPs()">
                    <i class="fas fa-paper-plane me-2"></i> Send Verification OTPs
                </button>
                
                <div id="send-status" class="status-message"></div>

                <!-- OTP Verification Section -->
                <div id="verify-section" class="otp-section hidden">
                    <h5 class="text-center mb-3">
                        <i class="fas fa-shield-alt me-2"></i> Enter Verification Codes
                    </h5>
                    <p class="text-muted text-center mb-3">Check your email and WhatsApp for OTPs</p>
                    
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="text" id="otp_email" class="form-input" placeholder="Enter email OTP" maxlength="6">
                    </div>
                    
                    <div class="input-group">
                        <i class="fab fa-whatsapp input-icon"></i>
                        <input type="text" id="otp_whatsapp" class="form-input" placeholder="Enter WhatsApp OTP" maxlength="6">
                    </div>
                    
                    <button class="btn-primary-custom" onclick="verifyBothOTPs()">
                        <i class="fas fa-check-circle me-2"></i> Verify & Create Account
                    </button>
                    
                    <div id="verify-status" class="status-message"></div>
                </div>
            </div>

            <!-- Success Section -->
            <div id="success-section" class="success-section hidden">
                <i class="fas fa-check-circle success-icon"></i>
                <h3 class="mb-3">Account Verified Successfully!</h3>
                <p class="text-muted mb-4">Your account has been created and verified successfully.</p>
                <a href="login.php" class="btn-primary-custom">
                    <i class="fas fa-sign-in-alt me-2"></i> Proceed to Login
                </a>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted">Already have an account? 
                    <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--primary-main);">Login here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center pt-2">
                <p>&copy; 2025 Smart Parking System. All rights reserved.</p>
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

    async function sendOTPs(){
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const sendBtn = document.getElementById('send-btn');
        const status = document.getElementById('send-status');

        if(!name || !email || !phone){ 
            showStatus('send-status', 'Please fill all fields.', 'error');
            return; 
        }

        if(phone.length !== 10) {
            showStatus('send-status', 'Please enter a valid 10-digit phone number.', 'error');
            return;
        }

        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending OTPs...';

        const form = new FormData();
        form.append('action','send_otps');
        form.append('name',name);
        form.append('email',email);
        form.append('phone',phone);

        try {
            let res = await fetch('signup.php',{method:'POST',body:form});
            let data = await res.json();
            showStatus('send-status', data.message, data.success ? 'success' : 'error');
            
            if(data.success){
                document.getElementById('verify-section').classList.remove('hidden');
                sendBtn.innerHTML = '<i class="fas fa-redo me-2"></i> Resend OTPs';
            }
        } catch (err){
            showStatus('send-status', '⚠️ Network error. Please try again.', 'error');
        } finally {
            sendBtn.disabled = false;
        }
    }

    async function verifyBothOTPs(){
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const otp_email = document.getElementById('otp_email').value.trim();
        const otp_whatsapp = document.getElementById('otp_whatsapp').value.trim();
        const status = document.getElementById('verify-status');

        if(!otp_email || !otp_whatsapp) {
            showStatus('verify-status', 'Please enter both OTPs.', 'error');
            return;
        }

        const form = new FormData();
        form.append('action','verify_both_otps');
        form.append('email',email);
        form.append('phone','+91' + phone);
        form.append('otp_email',otp_email);
        form.append('otp_whatsapp',otp_whatsapp);

        showStatus('verify-status', 'Verifying your account...', 'success');
        
        try {
            let res = await fetch('signup.php',{method:'POST',body:form});
            let data = await res.json();
            showStatus('verify-status', data.message, data.success ? 'success' : 'error');
            
            if(data.success){
                document.getElementById('form-section').classList.add('hidden');
                document.getElementById('success-section').classList.remove('hidden');
            }
        } catch (err) {
            showStatus('verify-status', '⚠️ Network error. Please try again.', 'error');
        }
    }

    // Add input validation
    document.getElementById('phone').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('otp_email').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('otp_whatsapp').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    </script>
</body>
</html>