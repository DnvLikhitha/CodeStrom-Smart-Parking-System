<?php
include 'includes/connection.php';
if (!isset($_SESSION['owner_logged_in'])) {
    header("Location: owner_login.php");
    exit();
}
$owner_id = $_SESSION['owner_id'];

// Get filter parameters
$filter_days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$filter_lot = isset($_GET['lot_id']) ? intval($_GET['lot_id']) : 'all';
$filter_type = isset($_GET['vehicle_type']) ? $_GET['vehicle_type'] : 'all';

// Base query conditions
$lot_condition = $filter_lot !== 'all' ? "AND pl.lot_id = $filter_lot" : "";
$type_condition = $filter_type !== 'all' ? "AND ps.vehicle_type = '$filter_type'" : "";

// Total lots and slots statistics
$total_lots = $conn->query("SELECT COUNT(*) AS total FROM parking_lots WHERE owner_id=$owner_id")->fetch_assoc()['total'];
$total_slots = $conn->query("SELECT COUNT(*) AS total FROM parking_slots ps JOIN parking_lots pl ON ps.lot_id=pl.lot_id WHERE pl.owner_id=$owner_id")->fetch_assoc()['total'];
$available_slots = $conn->query("SELECT COUNT(*) AS available FROM parking_slots ps JOIN parking_lots pl ON ps.lot_id=pl.lot_id WHERE pl.owner_id=$owner_id AND ps.is_available=1")->fetch_assoc()['available'];
$occupied_slots = $total_slots - $available_slots;

// Revenue statistics
$total_revenue = $conn->query("SELECT SUM(p.amount) AS total 
                              FROM payments p 
                              JOIN bookings b ON p.booking_id=b.booking_id 
                              JOIN parking_slots ps ON b.slot_id=ps.slot_id 
                              JOIN parking_lots pl ON ps.lot_id=pl.lot_id 
                              WHERE pl.owner_id=$owner_id AND p.payment_status='Success'")->fetch_assoc()['total'] ?? 0;

$monthly_revenue = $conn->query("SELECT SUM(p.amount) AS total 
                                FROM payments p 
                                JOIN bookings b ON p.booking_id=b.booking_id 
                                JOIN parking_slots ps ON b.slot_id=ps.slot_id 
                                JOIN parking_lots pl ON ps.lot_id=pl.lot_id 
                                WHERE pl.owner_id=$owner_id AND p.payment_status='Success' 
                                AND MONTH(p.payment_time) = MONTH(CURRENT_DATE())")->fetch_assoc()['total'] ?? 0;

// Daily income data with filters
$incomeData = [];
$res = $conn->query("SELECT DATE(p.payment_time) AS day, SUM(p.amount) AS total 
                     FROM payments p 
                     JOIN bookings b ON p.booking_id=b.booking_id 
                     JOIN parking_slots ps ON b.slot_id=ps.slot_id 
                     JOIN parking_lots pl ON ps.lot_id=pl.lot_id 
                     WHERE pl.owner_id=$owner_id AND p.payment_status='Success'
                     AND p.payment_time >= DATE_SUB(CURRENT_DATE(), INTERVAL $filter_days DAY)
                     $lot_condition $type_condition
                     GROUP BY DATE(p.payment_time) ORDER BY day");

while($r = $res->fetch_assoc()) { 
    $incomeData[] = $r; 
}

// Vehicle type distribution
$vehicleData = [];
$res = $conn->query("SELECT ps.vehicle_type, COUNT(*) as count, SUM(p.amount) as revenue
                     FROM parking_slots ps 
                     JOIN bookings b ON ps.slot_id = b.slot_id
                     JOIN payments p ON b.booking_id = p.booking_id
                     JOIN parking_lots pl ON ps.lot_id = pl.lot_id
                     WHERE pl.owner_id = $owner_id AND p.payment_status = 'Success'
                     $lot_condition
                     GROUP BY ps.vehicle_type");
while($r = $res->fetch_assoc()) {
    $vehicleData[] = $r;
}

// Peak hours analysis
$hourlyData = [];
$res = $conn->query("SELECT HOUR(b.start_time) as hour, COUNT(*) as bookings, AVG(p.amount) as avg_revenue
                     FROM bookings b
                     JOIN payments p ON b.booking_id = p.booking_id
                     JOIN parking_slots ps ON b.slot_id = ps.slot_id
                     JOIN parking_lots pl ON ps.lot_id = pl.lot_id
                     WHERE pl.owner_id = $owner_id AND p.payment_status = 'Success'
                     $lot_condition $type_condition
                     GROUP BY HOUR(b.start_time) ORDER BY hour");
while($r = $res->fetch_assoc()) {
    $hourlyData[] = $r;
}

// Lot performance
$lotPerformance = [];
$res = $conn->query("SELECT pl.lot_id, pl.lot_name, 
                     COUNT(b.booking_id) as total_bookings,
                     SUM(p.amount) as total_revenue,
                     AVG(p.amount) as avg_revenue
                     FROM parking_lots pl
                     LEFT JOIN parking_slots ps ON pl.lot_id = ps.lot_id
                     LEFT JOIN bookings b ON ps.slot_id = b.slot_id
                     LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.payment_status = 'Success'
                     WHERE pl.owner_id = $owner_id
                     GROUP BY pl.lot_id, pl.lot_name
                     ORDER BY total_revenue DESC");
while($r = $res->fetch_assoc()) {
    $lotPerformance[] = $r;
}

// Get lots for filter dropdown
$lots = $conn->query("SELECT lot_id, lot_name FROM parking_lots WHERE owner_id = $owner_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .analytics-container {
            max-width: 1400px;
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

        .filter-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .filter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(25, 23, 130, 0.08);
            padding: 1.5rem;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-main);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(25, 23, 130, 0.15);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            margin: 0;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-revenue { background: linear-gradient(135deg, #28a745, #20c997); }
        .icon-lots { background: linear-gradient(135deg, #007bff, #17a2b8); }
        .icon-slots { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
        .icon-available { background: linear-gradient(135deg, #ffc107, #fd7e14); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            line-height: 1;
        }

        .stat-change {
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .positive { color: #28a745; }
        .negative { color: #dc3545; }

        .chart-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .performance-table th {
            background: var(--gradient);
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border: none;
        }

        .performance-table th:first-child {
            border-radius: 10px 0 0 0;
        }

        .performance-table th:last-child {
            border-radius: 0 10px 0 0;
        }

        .performance-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .performance-table tbody tr {
            transition: all 0.3s ease;
        }

        .performance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .performance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-primary-custom {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            background: var(--gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.3);
            color: white;
        }

        .form-select-custom {
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select-custom:focus {
            outline: none;
            border-color: var(--primary-main);
            box-shadow: 0 0 0 3px rgba(70, 7, 244, 0.1);
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
            .analytics-container {
                padding: 1rem 0.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container, .table-container {
                padding: 1.5rem;
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
                            <a class="nav-link active" href="analytics.php"><i class="fas fa-chart-bar me-1"></i> Analytics</a>
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
    <div class="analytics-container">
        <div class="page-header">
            <h1 class="page-title">Analytics Dashboard</h1>
            <p class="page-subtitle">Comprehensive insights into your parking business performance</p>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-medium">Time Period</label>
                    <select name="days" class="form-select-custom">
                        <option value="7" <?= $filter_days == 7 ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $filter_days == 30 ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= $filter_days == 90 ? 'selected' : '' ?>>Last 90 Days</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Parking Lot</label>
                    <select name="lot_id" class="form-select-custom">
                        <option value="all" <?= $filter_lot == 'all' ? 'selected' : '' ?>>All Lots</option>
                        <?php while($lot = $lots->fetch_assoc()): ?>
                            <option value="<?= $lot['lot_id'] ?>" <?= $filter_lot == $lot['lot_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lot['lot_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Vehicle Type</label>
                    <select name="vehicle_type" class="form-select-custom">
                        <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>All Vehicles</option>
                        <option value="2-wheeler" <?= $filter_type == '2-wheeler' ? 'selected' : '' ?>>2-Wheeler</option>
                        <option value="4-wheeler" <?= $filter_type == '4-wheeler' ? 'selected' : '' ?>>4-Wheeler</option>
                        <option value="EV" <?= $filter_type == 'EV' ? 'selected' : '' ?>>Electric Vehicle</option>
                        <option value="Bus" <?= $filter_type == 'Bus' ? 'selected' : '' ?>>Bus</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary-custom w-100">
                        <i class="fas fa-filter me-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Revenue</h3>
                    <div class="stat-icon icon-revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <p class="stat-value">₹<?= number_format($total_revenue, 2) ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    ₹<?= number_format($monthly_revenue, 2) ?> this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Parking Lots</h3>
                    <div class="stat-icon icon-lots">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
                <p class="stat-value"><?= $total_lots ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-building"></i>
                    Total facilities
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Slots</h3>
                    <div class="stat-icon icon-slots">
                        <i class="fas fa-parking"></i>
                    </div>
                </div>
                <p class="stat-value"><?= $total_slots ?></p>
                <div class="stat-change">
                    <i class="fas fa-chart-pie"></i>
                    <?= $available_slots ?> available
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Utilization Rate</h3>
                    <div class="stat-icon icon-available">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <p class="stat-value"><?= $total_slots > 0 ? number_format(($occupied_slots / $total_slots) * 100, 1) : 0 ?>%</p>
                <div class="stat-change positive">
                    <i class="fas fa-car"></i>
                    <?= $occupied_slots ?> occupied
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i> Revenue Trend
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-car-side"></i> Vehicle Type Distribution
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="vehicleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-clock"></i> Peak Hours Analysis
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i> Slot Utilization
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="utilizationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Table -->
        <div class="table-container">
            <h3 class="chart-title">
                <i class="fas fa-trophy"></i> Lot Performance Ranking
            </h3>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Lot Name</th>
                        <th>Total Bookings</th>
                        <th>Total Revenue</th>
                        <th>Avg. Revenue</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lotPerformance as $index => $lot): ?>
                    <tr>
                        <td>#<?= $index + 1 ?></td>
                        <td><strong><?= htmlspecialchars($lot['lot_name']) ?></strong></td>
                        <td><?= $lot['total_bookings'] ?? 0 ?></td>
                        <td>₹<?= number_format($lot['total_revenue'] ?? 0, 2) ?></td>
                        <td>₹<?= number_format($lot['avg_revenue'] ?? 0, 2) ?></td>
                        <td>
                            <?php 
                            $performance = $lot['total_revenue'] ? ($lot['total_revenue'] / max(array_column($lotPerformance, 'total_revenue')) * 100) : 0;
                            $color = $performance > 80 ? 'success' : ($performance > 50 ? 'warning' : 'danger');
                            ?>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?= $color ?>" 
                                     style="width: <?= $performance ?>%"
                                     title="<?= number_format($performance, 1) ?>%">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($incomeData, 'day')) ?>,
                datasets: [{
                    label: 'Daily Revenue (₹)',
                    data: <?= json_encode(array_column($incomeData, 'total')) ?>,
                    borderColor: '#4607f4',
                    backgroundColor: 'rgba(70, 7, 244, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Vehicle Type Distribution Chart
        const vehicleCtx = document.getElementById('vehicleChart').getContext('2d');
        new Chart(vehicleCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($vehicleData, 'vehicle_type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($vehicleData, 'revenue')) ?>,
                    backgroundColor: [
                        '#4607f4',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Peak Hours Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function($h) { 
                    return $h['hour'] . ':00'; 
                }, $hourlyData)) ?>,
                datasets: [{
                    label: 'Number of Bookings',
                    data: <?= json_encode(array_column($hourlyData, 'bookings')) ?>,
                    backgroundColor: '#28a745',
                    borderColor: '#1e7e34',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Utilization Chart
        const utilizationCtx = document.getElementById('utilizationChart').getContext('2d');
        new Chart(utilizationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Available Slots', 'Occupied Slots'],
                datasets: [{
                    data: [<?= $available_slots ?>, <?= $occupied_slots ?>],
                    backgroundColor: ['#28a745', '#4607f4'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>