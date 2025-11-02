<?php
include 'includes/connection.php';

// Ensure DB connection
if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}

$owner_id = $_SESSION['owner_id'] ?? 1;

// === AJAX HANDLERS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Fetch Lots
    if (isset($_POST['fetch_lots'])) {
        $vehicle_type = $conn->real_escape_string($_POST['vehicle_type']);
        $lots = $conn->query("SELECT lot_id, lot_name FROM parking_lots WHERE owner_id = $owner_id");
        $data = [];
        while ($row = $lots->fetch_assoc()) {
            $count = $conn->query("SELECT COUNT(*) AS total FROM parking_slots WHERE lot_id={$row['lot_id']} AND vehicle_type='$vehicle_type' AND is_available=1")->fetch_assoc();
            if ($count['total'] > 0) $data[] = $row;
        }
        echo json_encode($data);
        exit;
    }

    // Fetch Slots
    if (isset($_POST['fetch_slots'])) {
        $lot_id = intval($_POST['lot_id']);
        $vehicle_type = $conn->real_escape_string($_POST['vehicle_type']);
        $slots = $conn->query("SELECT slot_id, slot_number, hourly_rate FROM parking_slots WHERE lot_id=$lot_id AND vehicle_type='$vehicle_type' AND is_available=1");
        $data = [];
        while ($r = $slots->fetch_assoc()) $data[] = $r;
        echo json_encode($data);
        exit;
    }

    // Calculate Amount
    if (isset($_POST['calculate_amount'])) {
        $slot_id = intval($_POST['slot_id']);
        $duration = floatval($_POST['duration']);
        $slot = $conn->query("SELECT hourly_rate FROM parking_slots WHERE slot_id=$slot_id")->fetch_assoc();
        if (!$slot) {
            echo json_encode(["status" => "error", "message" => "Slot not found."]);
            exit;
        }
        $total = $slot['hourly_rate'] * $duration;
        echo json_encode(['amount' => $total, 'rate' => $slot['hourly_rate']]);
        exit;
    }

    // Create Booking
    if (isset($_POST['create_booking'])) {
        $vehicle_number = $conn->real_escape_string($_POST['vehicle_number']);
        $slot_id = intval($_POST['slot_id']);
        $duration = floatval($_POST['duration']);
        $entry_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime("+$duration hour"));

        $slot = $conn->query("SELECT hourly_rate, lot_id FROM parking_slots WHERE slot_id=$slot_id")->fetch_assoc();
        if (!$slot) {
            echo json_encode(['status' => 'error', 'message' => 'Slot not found']);
            exit;
        }

        $hourly_rate = $slot['hourly_rate'];
        $total_amount = $hourly_rate * $duration;
        $status = 'Active';
        $payment_status = 'Paid';
        $user_id = 3; // Owner-created booking

        // Generate booking UID like OBK33773131
        $booking_uid = 'OBK' . mt_rand(10000000, 99999999);

        $stmt = $conn->prepare("INSERT INTO bookings (booking_uid, user_id, slot_id, start_time, end_time, status, payment_status, total_amount, created_on) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("siissssd", $booking_uid, $user_id, $slot_id, $entry_time, $end_time, $status, $payment_status, $total_amount);
        $stmt->execute();

        $conn->query("UPDATE parking_slots SET is_available = 0 WHERE slot_id = $slot_id");

        echo json_encode([
            'status' => 'success',
            'booking_uid' => $booking_uid,
            'vehicle_number' => $vehicle_number,
            'entry_time' => $entry_time,
            'end_time' => $end_time,
            'duration' => $duration,
            'total_amount' => $total_amount
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>On-Site Booking | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
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

        .booking-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .booking-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .booking-card::before {
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

        .form-group {
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

        .form-select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            appearance: none;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-main);
            box-shadow: 0 0 0 3px rgba(70, 7, 244, 0.1);
        }

        .select-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-main);
            pointer-events: none;
        }

        .amount-box {
            background: linear-gradient(135deg, #f8f9ff, #f0f2ff);
            border: 2px solid var(--primary-lighter);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: center;
        }

        .amount-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #e9ecef;
        }

        .amount-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .amount-label {
            font-weight: 500;
            color: #6c757d;
        }

        .amount-value {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .total-amount {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-main);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-success-custom:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .btn-success-custom:disabled {
            background: #cccccc;
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: none;
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
            .booking-container {
                padding: 1rem 0.5rem;
            }
            
            .booking-card {
                padding: 2rem 1.5rem;
            }
            
            .page-title {
                font-size: 1.8rem;
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
                            <a class="nav-link" href="entry_exit.php"><i class="fas fa-car me-1"></i> Entry/Exit</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-1"></i> Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_slots.php"><i class="fas fa-plus-circle me-1"></i> Manage Slots</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="owner_onsite_booking.php"><i class="fas fa-star me-1"></i> On-Site Booking</a>
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
    <div class="booking-container">
        <div class="booking-card">
            <div class="page-header">
                <div class="page-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h1 class="page-title">On-Site Booking</h1>
                <p class="page-subtitle">Create instant parking bookings for walk-in customers</p>
            </div>

            <form id="onsiteForm">
                <!-- Vehicle Number -->
                <div class="form-group">
                    <label class="form-label fw-medium">Vehicle Number</label>
                    <div class="position-relative">
                        <i class="fas fa-car input-icon"></i>
                        <input type="text" name="vehicle_number" class="form-input" placeholder="Enter vehicle number (e.g., AP09AB1234)" required>
                    </div>
                </div>

                <!-- Vehicle Type -->
                <div class="form-group">
                    <label class="form-label fw-medium">Vehicle Type</label>
                    <div class="position-relative">
                        <i class="fas fa-vehicle input-icon"></i>
                        <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                            <option value="">-- Select Vehicle Type --</option>
                            <option value="2-wheeler">2-Wheeler</option>
                            <option value="4-wheeler">4-Wheeler</option>
                            <option value="EV">Electric Vehicle (EV)</option>
                            <option value="Bus">Bus</option>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>
                </div>

                <!-- Parking Lot -->
                <div class="form-group">
                    <label class="form-label fw-medium">Parking Lot</label>
                    <div class="position-relative">
                        <i class="fas fa-map-marker-alt input-icon"></i>
                        <select name="lot_id" id="lot_id" class="form-select" required>
                            <option value="">-- Select Parking Lot --</option>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>
                </div>

                <!-- Parking Slot -->
                <div class="form-group">
                    <label class="form-label fw-medium">Parking Slot</label>
                    <div class="position-relative">
                        <i class="fas fa-parking input-icon"></i>
                        <select name="slot_id" id="slot_id" class="form-select" required>
                            <option value="">-- Select Parking Slot --</option>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>
                </div>

                <!-- Entry Time -->
                <div class="form-group">
                    <label class="form-label fw-medium">Entry Time</label>
                    <div class="position-relative">
                        <i class="fas fa-clock input-icon"></i>
                        <input type="text" id="entry_time" name="entry_time" class="form-input" readonly>
                    </div>
                </div>

                <!-- Duration -->
                <div class="form-group">
                    <label class="form-label fw-medium">Duration (hours)</label>
                    <div class="position-relative">
                        <i class="fas fa-hourglass-half input-icon"></i>
                        <input type="number" name="duration" id="duration" class="form-input" min="1" placeholder="Enter parking duration in hours" required>
                    </div>
                </div>

                <!-- Amount Calculation -->
                <div class="amount-box">
                    <div class="amount-item">
                        <span class="amount-label">Hourly Rate:</span>
                        <span class="amount-value">₹<span id="rate">0.00</span></span>
                    </div>
                    <div class="amount-item">
                        <span class="amount-label">Total Amount:</span>
                        <span class="amount-value total-amount">₹<span id="amount">0.00</span></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="collectBtn" class="btn btn-success-custom">
                    <i class="fas fa-money-bill-wave me-2"></i> Collect Cash & Generate Bill
                    <span class="loading-spinner ms-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>
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
        $(document).ready(function(){
            // Set current time
            $('#entry_time').val(new Date().toLocaleString());

            // Fetch lots based on vehicle type
            $('#vehicle_type').on('change', function(){
                let vehicle_type = $(this).val();
                if (!vehicle_type) return;
                
                $('#lot_id').html('<option value="">Loading available lots...</option>');
                $('#slot_id').html('<option value="">-- Select Parking Slot --</option>');
                
                $.post('owner_onsite_booking.php', {fetch_lots: 1, vehicle_type}, function(data){
                    let opts = '<option value="">-- Select Parking Lot --</option>';
                    data.forEach(l => opts += `<option value="${l.lot_id}">${l.lot_name}</option>`);
                    $('#lot_id').html(opts);
                }, 'json').fail(() => alert("⚠️ Failed to fetch available parking lots."));
            });

            // Fetch slots based on selected lot
            $('#lot_id').on('change', function(){
                let lot_id = $(this).val();
                let vehicle_type = $('#vehicle_type').val();
                if (!lot_id || !vehicle_type) return;
                
                $('#slot_id').html('<option value="">Loading available slots...</option>');
                
                $.post('owner_onsite_booking.php', {fetch_slots: 1, lot_id, vehicle_type}, function(data){
                    let opts = '<option value="">-- Select Parking Slot --</option>';
                    data.forEach(s => opts += `<option value="${s.slot_id}">Slot ${s.slot_number} (₹${s.hourly_rate}/hr)</option>`);
                    $('#slot_id').html(opts);
                    
                    // Reset amount calculation
                    $('#rate').text('0.00');
                    $('#amount').text('0.00');
                }, 'json').fail(() => alert("⚠️ Failed to fetch available parking slots."));
            });

            // Calculate total amount dynamically
            $('#slot_id, #duration').on('change keyup', function(){
                let slot_id = $('#slot_id').val();
                let duration = $('#duration').val();
                if (slot_id && duration) {
                    $.post('owner_onsite_booking.php', {calculate_amount: 1, slot_id, duration}, function(res){
                        $('#rate').text(parseFloat(res.rate).toFixed(2));
                        $('#amount').text(parseFloat(res.amount).toFixed(2));
                    }, 'json');
                }
            });

            // Handle form submission
            $('#onsiteForm').on('submit', function(e){
                e.preventDefault();
                
                const submitBtn = $('#collectBtn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Processing Booking...');
                
                let formData = $(this).serialize() + '&create_booking=1';
                
                $.post('owner_onsite_booking.php', formData, function(res){
                    if(res.status === 'success'){
                        alert(`✅ Booking Successful!\n\nBooking ID: ${res.booking_uid}\nVehicle: ${res.vehicle_number}\nDuration: ${res.duration} hours\nAmount Paid: ₹${res.total_amount}\n\nGenerating receipt...`);
                        window.location.href = "pdf.php?booking_uid=" + res.booking_uid;
                    } else {
                        alert('❌ Booking failed: ' + (res.message || 'Please try again.'));
                    }
                }, 'json').fail(xhr => {
                    alert('🚫 Error: Could not reach server.\nPlease check your connection and try again.');
                    console.error(xhr.responseText);
                }).always(() => {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                });
            });
        });
    </script>
</body>
</html>