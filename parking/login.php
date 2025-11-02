<?php
include 'includes/connection.php';

require("php-mailer/PHPMailer.php");
require("php-mailer/SMTP.php");
require("php-mailer/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Step 1: Send OTP (Email or WhatsApp)
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    header('Content-Type: application/json');
    $method = $_POST['method'];
    $value = trim($_POST['value']);

    if ($method === 'whatsapp') {
        if (strpos($value, '+91') !== 0) {
            $value = '+91' . preg_replace('/[^0-9]/', '', $value);
        }
    }

    // Check if user exists
    if ($method === 'email') {
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    }
    $check->bind_param("s", $value);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "❌ Account not found. Please sign up first."]);
        exit;
    }

    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    if ($method === 'email') {
        // Update Email OTP
        $sql = $conn->prepare("UPDATE users SET otp_email=?, otp_expires=? WHERE email=?");
        $sql->bind_param("sss", $otp, $otp_expiry, $value);
        $sql->execute();

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'mail.aadarshsenapati.in';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'contact@aadarshsenapati.in';
            $mail->Password   = 'Rishi@2005'; // replace with actual password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('contact@aadarshsenapati.in', 'Smart Parking Login');
            $mail->addAddress($value);
            $mail->isHTML(true);
            $mail->Subject = 'Your Smart Parking Login OTP';
            $mail->Body    = "<h3>Your login OTP is <b>$otp</b></h3>";
            $mail->send();

            echo json_encode(["success" => true, "message" => "📧 OTP sent to your email successfully."]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo]);
        }
    } else {
        // Update WhatsApp OTP
        $sql = $conn->prepare("UPDATE users SET otp_whatsapp=?, otp_expires=? WHERE phone=?");
        $sql->bind_param("sss", $otp, $otp_expiry, $value);
        $sql->execute();

        // Send WhatsApp OTP via Flask API
        $payload = json_encode(["OTP" => $otp, "phone" => $value]);
        $ch = curl_init("https://whatsapp-bot-edr6.onrender.com/send_otp");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        echo json_encode(["success" => true, "message" => "📱 OTP sent to your WhatsApp number."]);
    }
    exit;
}

// 🧠 Step 2: Verify OTP
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    header('Content-Type: application/json');
    $method = $_POST['method'];
    $value = trim($_POST['value']);
    $otp = $_POST['otp'];

    if ($method === 'whatsapp' && strpos($value, '+91') !== 0) {
        $value = '+91' . preg_replace('/[^0-9]/', '', $value);
    }

    if ($method === 'email') {
        $query = $conn->prepare("SELECT * FROM users WHERE email=? AND otp_email=? AND otp_expires > NOW()");
    } else {
        $query = $conn->prepare("SELECT * FROM users WHERE phone=? AND otp_whatsapp=? AND otp_expires > NOW()");
    }
    $query->bind_param("ss", $value, $otp);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];

        if ($method === 'email') {
            $clear = $conn->prepare("UPDATE users SET otp_email=NULL, otp_expires=NULL WHERE email=?");
        } else {
            $clear = $conn->prepare("UPDATE users SET otp_whatsapp=NULL, otp_expires=NULL WHERE phone=?");
        }
        $clear->bind_param("s", $value);
        $clear->execute();

        echo json_encode(["success" => true, "message" => "✅ Login successful! Redirecting..."]);
    } else {
        echo json_encode(["success" => false, "message" => "❌ Invalid or expired OTP!"]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #191782ff 0%, #4607f4ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            flex: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #191782ff, #4607f4ff);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .method-selector {
            display: flex;
            background: #f0f2f5;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 1.5rem;
        }

        .method-btn {
            flex: 1;
            padding: 0.8rem;
            text-align: center;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .method-btn.active {
            background: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            color: #191782ff;
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
            color: #999;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e1e5eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #191782ff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #191782ff 0%, #4607f4ff 100%);
            color: white;
        }

        .btn-primary:hover:not([disabled]) {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn[disabled] {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .success-container {
            text-align: center;
            padding: 2rem 0;
        }

        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 1.5rem;
        }

        .success-container h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .success-container p {
            color: #666;
        }

        .footer {
            background: rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer p {
            margin-bottom: 0.5rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .footer-links a:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-parking"></i>
                <span class="logo-text">Smart Parking</span>
            </a>
            <nav class="nav-links">
                <a href="signup.php"><i class="fas fa-info-circle"></i> Signup</a>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <div class="method-selector">
                <button id="emailBtn" class="method-btn active" onclick="selectMethod('email')">
                    <i class="fas fa-envelope"></i> Email
                </button>
                <button id="whatsappBtn" class="method-btn" onclick="selectMethod('whatsapp')">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
            </div>

            <div id="login-section">
                <div class="input-group">
                    <i class="fas fa-envelope input-icon" id="input-icon"></i>
                    <input type="text" id="value" class="form-input" placeholder="Enter your email address">
                </div>

                <button id="sendBtn" class="btn btn-primary" onclick="sendOTP()">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
                
                <div id="status1" class="status-message"></div>

                <div id="otp-section" class="hidden">
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" id="otp" class="form-input" placeholder="Enter 6-digit OTP">
                    </div>
                    
                    <button class="btn btn-primary" onclick="verifyOTP()">
                        <i class="fas fa-check-circle"></i> Verify OTP
                    </button>
                    
                    <div id="status2" class="status-message"></div>
                </div>
            </div>

            <div id="success" class="hidden">
                <div class="success-container">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h3>Login Successful!</h3>
                    <p>Redirecting to your dashboard...</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2025 Smart Parking System. All rights reserved.</p>
            
        </div>
    </footer>

    <script>
        let method = 'email';

        function selectMethod(m) {
            method = m;
            document.getElementById('emailBtn').classList.remove('active');
            document.getElementById('whatsappBtn').classList.remove('active');

            const input = document.getElementById('value');
            const inputIcon = document.getElementById('input-icon');
            
            if (m === 'email') {
                document.getElementById('emailBtn').classList.add('active');
                input.placeholder = 'Enter your email address';
                inputIcon.className = 'fas fa-envelope input-icon';
                input.value = '';
            } else {
                document.getElementById('whatsappBtn').classList.add('active');
                input.placeholder = 'Enter your WhatsApp number';
                inputIcon.className = 'fab fa-whatsapp input-icon';
                input.value = '+91';
            }
            
            // Reset status messages
            document.getElementById('status1').innerText = '';
            document.getElementById('status1').className = 'status-message';
            document.getElementById('otp-section').classList.add('hidden');
            document.getElementById('sendBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
            document.getElementById('sendBtn').disabled = false;
        }

        async function sendOTP() {
            const sendBtn = document.getElementById('sendBtn');
            let val = document.getElementById('value').value.trim();
            
            if (!val) {
                showStatus('status1', 'Please enter your email or phone.', 'error');
                return;
            }

            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            let form = new FormData();
            form.append('action', 'send_otp');
            form.append('method', method);
            form.append('value', val);

            try {
                let res = await fetch('login.php', {method: 'POST', body: form});
                let data = await res.json();
                
                if (data.success) {
                    showStatus('status1', data.message, 'success');
                    document.getElementById('otp-section').classList.remove('hidden');
                    sendBtn.innerHTML = '<i class="fas fa-check"></i> OTP Sent';
                    
                    // Re-enable after 60 seconds
                    setTimeout(() => {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
                    }, 60000);
                } else {
                    showStatus('status1', data.message, 'error');
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
                }
            } catch (error) {
                showStatus('status1', 'Network error. Please try again.', 'error');
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
            }
        }

        async function verifyOTP() {
            const otp = document.getElementById('otp').value.trim();
            
            if (!otp || otp.length !== 6) {
                showStatus('status2', 'Please enter a valid 6-digit OTP.', 'error');
                return;
            }

            let form = new FormData();
            form.append('action', 'verify_otp');
            form.append('method', method);
            form.append('value', document.getElementById('value').value.trim());
            form.append('otp', otp);

            try {
                let res = await fetch('login.php', {method: 'POST', body: form});
                let data = await res.json();
                
                if (data.success) {
                    showStatus('status2', data.message, 'success');
                    document.getElementById('login-section').classList.add('hidden');
                    document.getElementById('success').classList.remove('hidden');
                    setTimeout(() => window.location.href = 'dashboard.php', 2000);
                } else {
                    showStatus('status2', data.message, 'error');
                }
            } catch (error) {
                showStatus('status2', 'Network error. Please try again.', 'error');
            }
        }

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
    </script>
</body>
</html>