<?php
include 'includes/connection.php';
require_once 'Razorpay/Razorpay.php';
use Razorpay\Api\Api;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$api = new Api("rzp_test_RZLZ20TK4E40zJ", "L4ZVCPr880o6F7vxQhjqI2f3");

if (isset($_GET['action'])) {

    /* ---------- FETCH PARKING LOTS ---------- */
    if ($_GET['action'] === 'fetch_lots') {
        $filter = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $is_special = isset($_GET['is_special']) && $_GET['is_special'] == 'Yes' ? 'Yes' : 'No';
        $sql = "SELECT * FROM parking_lots WHERE is_special='$is_special'";
        if ($filter !== '') $sql .= " AND (lot_name LIKE '%$filter%' OR keyword LIKE '%$filter%')";
        $lots = $conn->query($sql);

        $data = [];
        while ($lot = $lots->fetch_assoc()) {
            $lot_id = $lot['lot_id'];
            $lot['slots_2w'] = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE lot_id=$lot_id AND vehicle_type='2-wheeler'")->fetch_assoc()['c'];
            $lot['slots_4w'] = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE lot_id=$lot_id AND vehicle_type='4-wheeler'")->fetch_assoc()['c'];
            $lot['available'] = $conn->query("SELECT COUNT(*) AS c FROM parking_slots WHERE lot_id=$lot_id AND is_available=1")->fetch_assoc()['c'];
            $data[] = $lot;
        }
        echo json_encode($data);
        exit;
    }

    /* ---------- FETCH SLOTS FOR A LOT ---------- */
    if ($_GET['action'] === 'fetch_slots' && isset($_GET['lot_id'])) {
        $lot_id = intval($_GET['lot_id']);
        $slots = $conn->query("SELECT * FROM parking_slots WHERE lot_id=$lot_id");
        $data = [];
        while ($s = $slots->fetch_assoc()) $data[] = $s;
        echo json_encode($data);
        exit;
    }

    /* ---------- SPECIAL BOOKING: SEARCH BY KEYWORD ---------- */
    if ($_GET['action'] === 'search_special_slots' && isset($_GET['keyword'])) {
        $keyword = trim($_GET['keyword']);
        if ($keyword === '') {
            echo json_encode([]);
            exit;
        }

        $sql = "SELECT lot_id, lot_name FROM parking_lots WHERE keyword = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $lot_ids = [];
        while ($row = $result->fetch_assoc()) {
            $lot_ids[] = $row['lot_id'];
        }

        if (empty($lot_ids)) {
            echo json_encode([]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($lot_ids), '?'));
        $sql2 = "SELECT ps.slot_id, pl.lot_name, ps.slot_number, ps.vehicle_type, ps.hourly_rate, ps.is_available
                 FROM parking_slots ps
                 JOIN parking_lots pl ON ps.lot_id = pl.lot_id
                 WHERE ps.lot_id IN ($placeholders)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param(str_repeat('i', count($lot_ids)), ...$lot_ids);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        $slots = [];
        while ($row = $result2->fetch_assoc()) $slots[] = $row;
        echo json_encode($slots);
        exit;
    }

    /* ---------- RAZORPAY ORDER CREATION ---------- */
    if ($_GET['action'] === 'create_order' && isset($_POST['amount'])) {
        $amount = floatval($_POST['amount']);
        if ($amount <= 0) {
            echo json_encode(['error' => 'Invalid amount']);
            exit;
        }
        $order = $api->order->create([
            'amount' => intval(round($amount * 100)),
            'currency' => 'INR',
            'receipt' => 'receipt_' . uniqid(),
        ]);
        echo json_encode($order);
        exit;
    }

    /* ---------- VERIFY PAYMENT AND SAVE BOOKING ---------- */
if ($_GET['action'] === 'verify_payment' && isset($_POST['razorpay_payment_id'])) {
    $payment_id = $_POST['razorpay_payment_id'];
    $slot_id = intval($_POST['slot_id']);
    $hours = intval($_POST['hours']);
    $total_amount = floatval($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    $booking_uid = 'BOOK_' . strtoupper(substr(md5(uniqid()), 0, 8));
    $start_time = date("Y-m-d H:i:s");
    $end_time = date("Y-m-d H:i:s", strtotime("+$hours hours"));

    // ✅ Insert booking record — mark as Paid and reset total_amount = 0
    $stmt = $conn->prepare("INSERT INTO bookings 
        (user_id, slot_id, booking_uid, start_time, end_time, status, payment_status, total_amount)
        VALUES (?, ?, ?, ?, ?, 'Active', 'Paid', 0)");
    $stmt->bind_param("iisss", $user_id, $slot_id, $booking_uid, $start_time, $end_time);
    $stmt->execute();
    $booking_id = $conn->insert_id;

    // ✅ Mark slot unavailable
    $conn->query("UPDATE parking_slots SET is_available = 0 WHERE slot_id = $slot_id");

    // ✅ Record payment info
    $stmt2 = $conn->prepare("INSERT INTO payment 
        (booking_id, payment_mode, transaction_id, amount, payment_status, payment_time)
        VALUES (?, 'Razorpay', ?, ?, 'Paid', NOW())");
    $stmt2->bind_param("isd", $booking_id, $payment_id, $total_amount);
    $stmt2->execute();

    echo json_encode([
        'status' => 'success',
        'booking_uid' => $booking_uid,
        'paid' => true,
        'remaining_amount' => 0
    ]);
    exit;
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Parking Spot | Smart Parking</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.2rem;
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

        .search-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .search-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .toggle-container {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .form-check-input:checked {
            background-color: var(--primary-main);
            border-color: var(--primary-main);
        }

        .search-box {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-main);
        }

        .search-input {
            padding-left: 45px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            height: 50px;
            font-size: 1rem;
        }

        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(70, 7, 244, 0.1);
        }

        .btn-primary-custom {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            height: 50px;
        }

        .btn-primary-custom:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
            color: white;
        }

        .lots-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .lot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(25, 23, 130, 0.08);
            padding: 1.5rem;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid var(--primary-main);
        }

        .lot-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(25, 23, 130, 0.15);
        }

        .lot-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .lot-address {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .lot-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stat-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .bg-2w { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
        .bg-4w { background: linear-gradient(135deg, #6c757d, #495057); color: white; }
        .bg-available { background: linear-gradient(135deg, #28a745, #20c997); color: white; }

        .slots-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            min-height: 200px;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .slot {
            aspect-ratio: 1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .slot.available {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            border-color: #4caf50;
            color: #2e7d32;
            cursor: pointer;
        }

        .slot.unavailable {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            border-color: #9e9e9e;
            color: #757575;
        }

        .slot.selected {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-main));
            border-color: var(--primary-dark);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
        }

        .slot-type {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .booking-cart {
            position: fixed;
            bottom: -400px;
            right: 30px;
            width: 350px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            padding: 1.5rem;
            transition: bottom 0.4s ease;
            z-index: 1000;
            border: 3px solid var(--primary-light);
        }

        .booking-cart.active {
            bottom: 30px;
        }

        .cart-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .cart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
        }

        .cart-content {
            margin-bottom: 1.5rem;
        }

        .cart-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .duration-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 1rem 0;
        }

        .duration-input {
            width: 80px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 8px;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-align: center;
            margin: 1rem 0;
        }

        .btn-pay {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-pay:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
        }

        .btn-deselect {
            background: transparent;
            border: 2px solid #dc3545;
            color: #dc3545;
            padding: 10px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-deselect:hover {
            background: #dc3545;
            color: white;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-lighter);
        }

        .no-results {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
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
            .main-container {
                padding: 1rem 0.5rem;
            }
            
            .lots-container {
                grid-template-columns: 1fr;
            }
            
            .booking-cart {
                width: calc(100% - 2rem);
                right: 1rem;
                left: 1rem;
            }
            
            .slots-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
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
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Book Your Parking Spot</h1>
            <p class="page-subtitle">Find and reserve the perfect parking space for your vehicle</p>
        </div>

        <!-- Search Section -->
        <div class="search-card">
            <div class="toggle-container">
                <div class="form-check form-switch me-3">
                    <input class="form-check-input" type="checkbox" id="specialToggle" style="transform: scale(1.2);">
                    <label class="form-check-label fw-medium" for="specialToggle">Special Booking</label>
                </div>
                <span class="text-muted">Enable for reserved or premium parking spots</span>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="keyword" class="form-control search-input" placeholder="Search parking lots or enter special keyword...">
                    </div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary-custom w-100" id="searchBtn">
                        <i class="fas fa-search me-2"></i> Search Parking
                    </button>
                </div>
            </div>
        </div>

        <!-- Parking Lots Section -->
        <div class="search-card">
            <h3 class="section-title">
                <i class="fas fa-map-marker-alt me-2"></i> Available Parking Lots
            </h3>
            <div id="lots" class="lots-container"></div>
        </div>

        <!-- Parking Slots Section -->
        <div class="slots-container">
            <h3 class="section-title">
                <i class="fas fa-car me-2"></i> Available Parking Slots
            </h3>
            <div id="slots" class="slots-grid"></div>
        </div>
    </div>

    <!-- Booking Cart -->
    <div id="cart" class="booking-cart">
        <div class="cart-header">
            <h5 class="cart-title">Booking Summary</h5>
            <i class="fas fa-shopping-cart text-primary"></i>
        </div>
        
        <div class="cart-content">
            <div class="cart-item">
                <div id="slotInfo" class="text-muted">No slot selected</div>
            </div>
            
            <div class="duration-control">
                <label class="fw-medium">Duration (hours):</label>
                <input type="number" id="hours" value="1" min="1" class="duration-input">
            </div>
            
            <div class="total-amount">
                Total: ₹<span id="total">0</span>
            </div>
        </div>
        
        <button id="payBtn" class="btn-pay">
            <i class="fas fa-credit-card me-2"></i> Pay Now
        </button>
        <button id="deselectBtn" class="btn-deselect">
            <i class="fas fa-times me-2"></i> Deselect Slot
        </button>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-parking me-2"></i> Smart Parking</h5>
                    <p>Revolutionizing urban parking with smart technology and seamless user experience.</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="booking.php">Book Slot</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@smartparking.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Parking St, City</li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center pt-2">
                <p>&copy; 2023 Smart Parking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let selectedSlot = null, rate = 0;

    function fetchLots() {
        let keyword = $('#keyword').val();
        let is_special = $('#specialToggle').is(':checked') ? 'Yes' : 'No';
        $.getJSON('booking.php?action=fetch_lots', { keyword: keyword, is_special: is_special }, function(data) {
            $('#lots').empty();
            $('#slots').empty();
            if (data.length === 0) {
                $('#lots').html(`
                    <div class="no-results w-100">
                        <i class="fas fa-map-marker-alt"></i>
                        <h5>No Parking Lots Found</h5>
                        <p>Try adjusting your search criteria or special booking filter</p>
                    </div>
                `);
                return;
            }
            data.forEach(lot => {
                $('#lots').append(`
                    <div class="lot-card" onclick="fetchSlots(${lot.lot_id})">
                        <div class="lot-name">${lot.lot_name}</div>
                        <div class="lot-address">${lot.address || 'No address provided'}</div>
                        <div class="lot-stats">
                            <span class="stat-badge bg-2w">2W: ${lot.slots_2w}</span>
                            <span class="stat-badge bg-4w">4W: ${lot.slots_4w}</span>
                            <span class="stat-badge bg-available">Available: ${lot.available}</span>
                        </div>
                    </div>
                `);
            });
        });
    }

    function fetchSlots(lot_id) {
        $.getJSON('booking.php?action=fetch_slots&lot_id=' + lot_id, function(data) {
            $('#slots').empty();
            if (data.length === 0) {
                $('#slots').html(`
                    <div class="no-results w-100">
                        <i class="fas fa-car"></i>
                        <h5>No Slots Available</h5>
                        <p>This parking lot doesn't have any slots configured</p>
                    </div>
                `);
                return;
            }
            data.forEach(slot => {
                let cls = slot.is_available == 1 ? 'available' : 'unavailable';
                let typeIcon = slot.vehicle_type === '2-wheeler' ? 'fa-motorcycle' : 'fa-car';
                $('#slots').append(`
                    <div class="slot ${cls}" onclick="${slot.is_available == 1 ? `selectSlot(${slot.slot_id}, ${slot.hourly_rate}, '${slot.vehicle_type}', '${slot.slot_number}')` : ''}">
                        ${slot.slot_number}
                        <span class="slot-type"><i class="fas ${typeIcon}"></i></span>
                    </div>
                `);
            });
        });
    }

    /* ---- SPECIAL BOOKING BY KEYWORD ---- */
    $('#searchBtn').click(() => {
        const keyword = $('#keyword').val().trim();
        if (!$('#specialToggle').is(':checked')) return fetchLots();
        if (keyword === '') return alert('Please enter a keyword for special booking!');
        
        $.getJSON('booking.php?action=search_special_slots', { keyword: keyword }, function(data) {
            $('#lots').empty();
            $('#slots').empty();
            if (data.length === 0) {
                $('#slots').html(`
                    <div class="no-results w-100">
                        <i class="fas fa-search"></i>
                        <h5>No Special Slots Found</h5>
                        <p>No parking slots found for the keyword: "${keyword}"</p>
                    </div>
                `);
                return;
            }
            data.forEach(slot => {
                let cls = slot.is_available == 1 ? 'available' : 'unavailable';
                let typeIcon = slot.vehicle_type === '2-wheeler' ? 'fa-motorcycle' : 'fa-car';
                $('#slots').append(`
                    <div class="slot ${cls}" onclick="${slot.is_available == 1 ? `selectSlot(${slot.slot_id}, ${slot.hourly_rate}, '${slot.vehicle_type}', '${slot.slot_number}')` : ''}">
                        ${slot.lot_name}<br><small>${slot.slot_number}</small>
                        <span class="slot-type"><i class="fas ${typeIcon}"></i></span>
                    </div>
                `);
            });
        });
    });

    function selectSlot(slot_id, hourly_rate, type, slot_number) {
        $('.slot').removeClass('selected');
        event.target.classList.add('selected');
        selectedSlot = slot_id; 
        rate = hourly_rate;
        
        $('#slotInfo').html(`
            <strong>Slot:</strong> ${slot_number}<br>
            <strong>Type:</strong> ${type}<br>
            <strong>Rate:</strong> ₹${hourly_rate}/hour
        `);
        
        $('#total').text(hourly_rate);
        $('#cart').addClass('active');
    }

    $('#deselectBtn').click(() => {
        selectedSlot = null; 
        rate = 0;
        $('.slot').removeClass('selected');
        $('#slotInfo').html('No slot selected');
        $('#total').text('0');
        $('#cart').removeClass('active');
    });

    $('#hours').on('input', function() { 
        $('#total').text($(this).val() * rate); 
    });

    $('#payBtn').click(function() {
        if (!selectedSlot) return alert("Please select a parking slot first!");
        let hours = parseInt($('#hours').val());
        let amount = hours * rate;
        
        $.post('booking.php?action=create_order', { amount: amount }, function(order) {
            if (order.error) return alert(order.error);
            
            var options = {
                key: "rzp_test_RZLZ20TK4E40zJ",
                amount: order.amount,
                currency: "INR",
                name: "Smart Parking System",
                description: "Parking Slot Booking",
                order_id: order.id,
                handler: function (response) {
                    $.post('booking.php?action=verify_payment', {
                        razorpay_payment_id: response.razorpay_payment_id,
                        slot_id: selectedSlot,
                        hours: hours,
                        amount: amount
                    }, function(res) {
                        if (res.status === 'success') {
                            alert("✅ Payment Successful! Your parking spot is now reserved.");
                            window.location.href = "dashboard.php?payment=success";
                        } else {
                            alert("Payment verification failed! Please contact support.");
                        }
                    }, 'json');
                },
                theme: { color: "#4607f4" },
                modal: {
                    ondismiss: function() {
                        console.log('Payment modal closed');
                    }
                }
            };
            
            var rzp = new Razorpay(options);
            rzp.open();
        }, 'json');
    });

    $('#specialToggle').on('change', fetchLots);
    $(document).ready(fetchLots);
    </script>
</body>
</html>