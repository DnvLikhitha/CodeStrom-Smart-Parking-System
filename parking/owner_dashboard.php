<?php
// owner_dashboard.php
include 'includes/connection.php'; // make sure this connects to your database

// Fetch summary data
$totalLots = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM parking_lots"))['total'];
$totalSpots = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM parking_slots"))['total'];
$availableSpots = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM parking_slots WHERE is_available='1'"))['total'];
$occupiedSpots = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM parking_slots WHERE is_available='0'"))['total'];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM payments"))['total'];
$totalRevenue = $totalRevenue ? $totalRevenue : 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | Smart Parking System</title>
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
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

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
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

        .icon-lots { background: linear-gradient(135deg, #667eea, #764ba2); }
        .icon-spots { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .icon-available { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .icon-occupied { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .icon-revenue { background: linear-gradient(135deg, #fa709a, #fee140); }
        .icon-bookings { background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333 !important; }
        .icon-users { background: linear-gradient(135deg, #d299c2, #fef9d7); color: #333 !important; }

        .stat-value {
            font-size: 2.2rem;
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

        .actions-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
        }

        .actions-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            background: white;
            border-color: var(--primary-main);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(70, 7, 244, 0.1);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.8rem;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .action-description {
            color: #6c757d;
            font-size: 0.9rem;
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
            .dashboard-container {
                padding: 1rem 0.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-title {
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
                            <a class="nav-link active" href="owner_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
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
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Owner Dashboard</h1>
            <p class="dashboard-subtitle">Manage your parking facilities and monitor performance</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <!-- Total Lots -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Lots</h3>
                    <div class="stat-icon icon-lots">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
                <p class="stat-value"><?= $totalLots ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-chart-line"></i>
                    All parking facilities
                </div>
            </div>

            <!-- Total Spots -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Spots</h3>
                    <div class="stat-icon icon-spots">
                        <i class="fas fa-parking"></i>
                    </div>
                </div>
                <p class="stat-value"><?= $totalSpots ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-layer-group"></i>
                    Total parking capacity
                </div>
            </div>

            <!-- Available Spots -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Available Spots</h3>
                    <div class="stat-icon icon-available">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <p class="stat-value text-success"><?= $availableSpots ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    Ready for booking
                </div>
            </div>

            <!-- Occupied Spots -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Occupied Spots</h3>
                    <div class="stat-icon icon-occupied">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <p class="stat-value text-danger"><?= $occupiedSpots ?></p>
                <div class="stat-change negative">
                    <i class="fas fa-car"></i>
                    Currently in use
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Revenue</h3>
                    <div class="stat-icon icon-revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <p class="stat-value text-warning">₹<?= number_format($totalRevenue, 2) ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-rupee-sign"></i>
                    Lifetime earnings
                </div>
            </div>

            <!-- Total Bookings -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Total Bookings</h3>
                    <div class="stat-icon icon-bookings">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <p class="stat-value"><?= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings"))['total']; ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-history"></i>
                    All-time bookings
                </div>
            </div>

            <!-- Active Users -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">Active Users</h3>
                    <div class="stat-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <p class="stat-value"><?= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS total FROM bookings WHERE status='active'"))['total']; ?></p>
                <div class="stat-change positive">
                    <i class="fas fa-user-check"></i>
                    Currently parking
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="actions-section">
            <h3 class="section-title">
                <i class="fas fa-bolt me-2"></i> Quick Actions
            </h3>
            <div class="actions-grid">
                <a href="entry_exit.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h4 class="action-title">Vehicle Entry / Exit</h4>
                    <p class="action-description">Manage vehicle arrivals and departures in real-time</p>
                </a>

                <a href="analytics.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4 class="action-title">Detailed Analytics</h4>
                    <p class="action-description">View comprehensive reports and performance metrics</p>
                </a>

                <a href="manage_slots.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h4 class="action-title">Manage Parking Slots</h4>
                    <p class="action-description">Add, remove or modify parking slots</p>
                </a>

                <a href="owner_onsite_booking.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="action-title">On-Site Booking</h4>
                    <p class="action-description">Handle direct parking bookings at your facility</p>
                </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>