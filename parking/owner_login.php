<?php
include 'includes/connection.php';
require("php-mailer/PHPMailer.php");
require("php-mailer/SMTP.php");
require("php-mailer/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$showOtpBox = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Request OTP
    if (isset($_POST['send_otp'])) {
        $email = trim($_POST['email']);

        // Check if owner exists
        $check = $conn->prepare("SELECT * FROM owners WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $owner = $result->fetch_assoc();
            $name = $owner['name'];

            // Generate OTP and store in session
            $otp_email = rand(100000, 999999);
            $_SESSION['otp_email'] = $otp_email;
            $_SESSION['owner_email'] = $email;
            $_SESSION['owner_name'] = $name;
            $_SESSION['otp_expiry'] = time() + 300; // 5 min expiry

            // ✅ Initialize PHPMailer correctly
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'mail.aadarshsenapati.in';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'contact@aadarshsenapati.in';
                $mail->Password   = 'Rishi@2005'; // ⚠️ Replace with your actual password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('contact@aadarshsenapati.in', 'Smart Parking Verification');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your Smart Parking Email OTP';
                $mail->Body    = "<h3>Hello $name,</h3><p>Your verification OTP is <b>$otp_email</b>.</p><p>This OTP will expire in 5 minutes.</p>";

                $mail->send();
                $message = "<div class='alert alert-success'>✅ OTP sent successfully to $email</div>";
                $showOtpBox = true;
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>❌ Failed to send OTP. Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>⚠️ No owner account found for this email.</div>";
        }
    }

    // Step 2: Verify OTP
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp']);

        if (
            isset($_SESSION['otp_email']) &&
            $entered_otp == $_SESSION['otp_email'] &&
            time() <= $_SESSION['otp_expiry']
        ) {
            $_SESSION['owner_logged_in'] = true;

            // Fetch owner_id for session
            $email = $_SESSION['owner_email'];
            $q = $conn->prepare("SELECT owner_id FROM owners WHERE email = ?");
            $q->bind_param("s", $email);
            $q->execute();
            $res = $q->get_result()->fetch_assoc();
            $_SESSION['owner_id'] = $res['owner_id'];

            // Clear OTP after successful login
            unset($_SESSION['otp_email'], $_SESSION['otp_expiry']);

            header("Location: owner_dashboard.php");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>❌ Invalid or expired OTP. Please try again.</div>";
            $showOtpBox = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login | Smart Parking</title>
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

        .login-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-icon {
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

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
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
            width: 100%;
        }

        .btn-primary-custom:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
            color: white;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
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

        .btn-success-custom:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            color: #c62828;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.1);
            color: #ef6c00;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .resend-link {
            text-align: center;
            margin-top: 1rem;
        }

        .resend-link a {
            color: var(--primary-main);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .resend-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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
            .login-container {
                padding: 1rem 0.5rem;
            }
            
            .login-card {
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
                            <a class="nav-link" href="login.php"><i class="fas fa-user me-1"></i> User Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="owner_login.php"><i class="fas fa-building me-1"></i> Owner Login</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h1 class="login-title">Owner Portal</h1>
                <p class="login-subtitle">Access your parking lot management dashboard</p>
            </div>

            <?= $message ?>

            <?php if (!$showOtpBox): ?>
                <!-- Email Input Form -->
                <form method="POST">
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" placeholder="Enter your registered email" required>
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary-custom">
                        <i class="fas fa-paper-plane me-2"></i> Send Verification OTP
                    </button>
                </form>
            <?php else: ?>
                <!-- OTP Verification Form -->
                <form method="POST">
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="number" name="otp" class="form-input" placeholder="Enter 6-digit OTP" required>
                    </div>
                    <button type="submit" name="verify_otp" class="btn btn-success-custom">
                        <i class="fas fa-check-circle me-2"></i> Verify & Login
                    </button>
                    <div class="resend-link">
                        <a href="owner_login.php">
                            <i class="fas fa-redo me-1"></i> Resend OTP
                        </a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Need help? <a href="contact.php" class="text-decoration-none fw-bold" style="color: var(--primary-main);">Contact support</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            
                <p>&copy; 2025 Smart Parking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add input validation for OTP field
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.querySelector('input[name="otp"]');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    // Limit to 6 digits
                    if (this.value.length > 6) {
                        this.value = this.value.slice(0, 6);
                    }
                });
            }
        });
    </script>
</body>
</html>