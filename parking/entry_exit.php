<?php
include 'includes/connection.php';

if (!isset($_SESSION['owner_logged_in'])) {
    header("Location: owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Entry & Exit | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
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

        .entry-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-icon {
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

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .input-group-custom {
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

        .form-input-custom {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input-custom:focus {
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

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
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

        .btn-danger-custom:hover {
            background: linear-gradient(135deg, #e83e8c, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }

        .qr-scanner {
            border: 3px dashed var(--primary-light);
            border-radius: 15px;
            padding: 1.5rem;
            background: #f8f9fa;
            margin: 1.5rem 0;
            text-align: center;
        }

        .qr-placeholder {
            padding: 2rem;
            color: #6c757d;
        }

        .booking-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-main);
            display: none;
        }

        .details-header {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-label {
            font-weight: 500;
            color: #6c757d;
        }

        .detail-value {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .status-booked { background: linear-gradient(135deg, #ffc107, #fd7e14); color: black; }
        .status-active { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .status-completed { background: linear-gradient(135deg, #6c757d, #495057); color: white; }

        .action-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e9ecef;
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
            .entry-container {
                padding: 1rem 0.5rem;
            }
            
            .main-card {
                padding: 2rem 1.5rem;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="owner_dashboard.php">
                    <i class="fas fa-parking me-2"></i>
                    Smart Parking
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="owner_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="entry_exit.php"><i class="fas fa-car me-1"></i> Entry/Exit</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-1"></i> Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_slots.php"><i class="fas fa-plus-circle me-1"></i> Manage Slots</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="owner_onsite_booking.php"><i class="fas fa-star me-1"></i> On-Site Booking</a>
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
    <div class="entry-container">
        <div class="main-card">
            <div class="page-header">
                <div class="page-icon">
                    <i class="fas fa-car"></i>
                </div>
                <h1 class="page-title">Vehicle Entry & Exit</h1>
                <p class="page-subtitle">Manage vehicle arrivals and departures with QR scanning</p>
            </div>

            <!-- Search Section -->
            <div class="input-group-custom">
                <div class="position-relative">
                    <i class="fas fa-search input-icon"></i>
                    <input type="text" id="booking_id" class="form-input-custom" placeholder="Enter booking ID or scan QR code">
                </div>
                <button id="fetchBookingBtn" class="btn btn-primary-custom w-100 mt-2">
                    <i class="fas fa-search me-2"></i> Fetch Booking Details
                </button>
            </div>

            <!-- QR Scanner Section -->
            <div class="qr-scanner">
                <h5 class="text-center mb-3">
                    <i class="fas fa-qrcode me-2"></i> QR Code Scanner
                </h5>
                <div id="qr-reader"></div>
                <div id="qr-placeholder" class="qr-placeholder">
                    <i class="fas fa-camera fa-2x mb-2"></i>
                    <p>QR scanner will appear here</p>
                </div>
            </div>

            <!-- Booking Details Section -->
            <div id="bookingDetails" class="booking-details">
                <h5 class="details-header">
                    <i class="fas fa-file-alt"></i> Booking Details
                </h5>
                
                <div class="detail-item">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value" id="user_name">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Parking Slot:</span>
                    <span class="detail-value" id="slot_number">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value" id="duration">-</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span id="status" class="status-badge">-</span>
                    </span>
                </div>

                <!-- Vehicle Entry Section -->
                <div id="vehicleEntryFields" class="action-section" style="display:none;">
                    <h6 class="mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Vehicle Entry
                    </h6>
                    <div class="input-group-custom">
                        <div class="position-relative">
                            <i class="fas fa-car input-icon"></i>
                            <input type="text" id="vehicle_number" class="form-input-custom" placeholder="Enter vehicle number (e.g. AP16AB1234)">
                        </div>
                    </div>
                    <button id="confirmEntry" class="btn btn-success-custom">
                        <i class="fas fa-check-circle me-2"></i> Confirm Vehicle Entry
                    </button>
                </div>

                <!-- Vehicle Exit Section -->
                <div id="vehicleExitFields" class="action-section" style="display:none;">
                    <h6 class="mb-3">
                        <i class="fas fa-sign-out-alt me-2"></i> Vehicle Exit
                    </h6>
                    <button id="confirmExit" class="btn btn-danger-custom">
                        <i class="fas fa-door-open me-2"></i> Confirm Vehicle Exit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center pt-2">
                <p>&copy; 2025 Smart Parking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form
        function resetForm() {
            document.getElementById("booking_id").value = "";
            document.getElementById("bookingDetails").style.display = "none";
            document.getElementById("vehicle_number").value = "";
        }

        // Fetch booking info
        document.getElementById("fetchBookingBtn").addEventListener("click", fetchBooking);

        function fetchBooking() {
            const id = document.getElementById("booking_id").value.trim();
            if (!id) {
                alert("Please enter Booking ID first.");
                return;
            }

            // Show loading state
            const fetchBtn = document.getElementById("fetchBookingBtn");
            const originalText = fetchBtn.innerHTML;
            fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Fetching...';
            fetchBtn.disabled = true;

            fetch("owner_fetch_booking.php?booking_id=" + id)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("bookingDetails").style.display = "block";
                    document.getElementById("user_name").innerText = data.user;
                    document.getElementById("slot_number").innerText = data.slot;
                    document.getElementById("duration").innerText = data.duration + " hour(s)";
                    
                    // Set status with appropriate badge class
                    const statusElement = document.getElementById("status");
                    statusElement.innerText = data.status;
                    statusElement.className = "status-badge status-" + data.status.toLowerCase();

                    const entryFields = document.getElementById("vehicleEntryFields");
                    const exitFields = document.getElementById("vehicleExitFields");
                    entryFields.style.display = "none";
                    exitFields.style.display = "none";

                    // Decide automatically which mode to show
                    if (data.status === "Booked") {
                        entryFields.style.display = "block";
                    } 
                    else if (data.status === "Active") {
                        exitFields.style.display = "block";
                    } 
                    else if (data.status === "Completed" || data.status === "Cancelled") {
                        alert("⚠️ This booking is already " + data.status + ". No further actions allowed.");
                        document.getElementById("bookingDetails").style.display = "none";
                    } 
                    else {
                        alert("Unknown booking status: " + data.status);
                    }

                } else {
                    alert(data.message || "Booking not found!");
                    document.getElementById("bookingDetails").style.display = "none";
                }
            })
            .catch(() => {
                alert("Failed to fetch booking details. Please check your connection.");
                document.getElementById("bookingDetails").style.display = "none";
            })
            .finally(() => {
                fetchBtn.innerHTML = originalText;
                fetchBtn.disabled = false;
            });
        }

        // ---- Confirm Entry ----
        document.getElementById("confirmEntry").addEventListener("click", () => {
            const booking_id = document.getElementById("booking_id").value.trim();
            const vehicle_number = document.getElementById("vehicle_number").value.trim();
            if (!vehicle_number) {
                alert("Please enter vehicle number first.");
                return;
            }

            const confirmBtn = document.getElementById("confirmEntry");
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            confirmBtn.disabled = true;

            fetch("owner_confirm_entry_exit.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "mode=entry&booking_id=" + encodeURIComponent(booking_id) +
                      "&vehicle_number=" + encodeURIComponent(vehicle_number)
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                resetForm();
            })
            .finally(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        });

        // ---- Confirm Exit ----
        document.getElementById("confirmExit").addEventListener("click", () => {
            const booking_id = document.getElementById("booking_id").value.trim();

            const confirmBtn = document.getElementById("confirmExit");
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            confirmBtn.disabled = true;

            fetch("owner_confirm_entry_exit.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "mode=exit&booking_id=" + encodeURIComponent(booking_id)
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                resetForm();
            })
            .finally(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        });

        // QR Code Scanner (optional enhancement)
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById("booking_id").value = decodedText;
            fetchBooking();
        }

        function onScanFailure(error) {
            // Handle scan failure, usually ignored
        }

        // Initialize QR Scanner
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { 
                fps: 10, 
                qrbox: { width: 250, height: 250 } 
            }, false);
        
        // You can choose to start the scanner automatically or let user start it
        // html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
</body>
</html>