<?php
include 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$userQuery = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$name = htmlspecialchars($user['name']);

// Fetch active booking
$activeQuery = $conn->prepare("
    SELECT * FROM bookings 
    WHERE user_id = ? AND status = 'Active' 
    ORDER BY start_time DESC LIMIT 1
");
$activeQuery->bind_param("i", $user_id);
$activeQuery->execute();
$activeBooking = $activeQuery->get_result()->fetch_assoc();

// Fetch previous bookings (Completed, Cancelled, Booked, Billing)
$prevQuery = $conn->prepare("
    SELECT * FROM bookings 
    WHERE user_id = ? 
    AND status IN ('Completed', 'Cancelled', 'Booked', 'Billing')
    ORDER BY created_on DESC
");
$prevQuery->bind_param("i", $user_id);
$prevQuery->execute();
$previousBookings = $prevQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Smart Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        .welcome-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .welcome-text {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-lighter);
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(25, 23, 130, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-main);
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(25, 23, 130, 0.15);
        }

        .active-booking {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #fff 0%, #f8fff9 100%);
        }

        .booking-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .booking-id {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        .booking-detail {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .booking-icon {
            width: 24px;
            color: var(--primary-main);
            margin-right: 10px;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .bg-active { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .bg-completed { background: linear-gradient(135deg, #007bff, #17a2b8); color: white; }
        .bg-cancelled { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        .bg-booked { background: linear-gradient(135deg, #ffc107, #fd7e14); color: black; }
        .bg-billing { background: linear-gradient(135deg, #6f42c1, #e83e8c); color: white; }

        .btn-primary-custom {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
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
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: var(--primary-main);
            color: white;
            transform: translateY(-2px);
        }

        .filter-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .filter-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(70, 7, 244, 0.1);
        }

        .no-booking {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .no-booking i {
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

        .stats-card {
            background: var(--gradient);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(25, 23, 130, 0.3);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: var(--gradient);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .qr-code {
            padding: 1rem;
            background: white;
            border-radius: 10px;
            display: inline-block;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem 0.5rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .welcome-text {
                font-size: 1.5rem;
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
    <div class="dashboard-container">
        <div class="welcome-card">
            <h1 class="welcome-text">Welcome back, <?= $name ?>!</h1>
            <p class="text-muted">Here's your parking activity and booking history</p>
            
            <div class="row mt-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $activeBooking ? '1' : '0' ?></div>
                        <div class="stats-label">Active Bookings</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $previousBookings->num_rows ?></div>
                        <div class="stats-label">Total Bookings</div>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- Active Booking Section -->
        <div class="welcome-card">
            <h3 class="section-title">
                <i class="fas fa-play-circle me-2"></i> Current Active Booking
            </h3>
            
            <?php if ($activeBooking): ?>
                <div class="booking-card active-booking">
                    <div class="booking-header">
                        <div>
                            <span class="booking-id">Booking #<?= htmlspecialchars($activeBooking['booking_uid']) ?></span>
                            <span class="badge-status bg-active ms-2"><?= htmlspecialchars($activeBooking['status']) ?></span>
                        </div>
                        <div>
                            <button class="btn btn-outline-custom btn-sm me-2" id="showQRBtn" data-uid="<?= $activeBooking['booking_uid'] ?>">
                                <i class="fas fa-qrcode me-1"></i> Show QR
                            </button>
                            <button class="btn btn-danger btn-sm" id="cancelBookingBtn" data-id="<?= $activeBooking['booking_id'] ?>">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="booking-detail">
                                <i class="fas fa-map-marker-alt booking-icon"></i>
                                <div>
                                    <strong>Parking Slot:</strong> <?= htmlspecialchars($activeBooking['slot_id']) ?>
                                </div>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-clock booking-icon"></i>
                                <div>
                                    <strong>Start Time:</strong> <?= htmlspecialchars($activeBooking['start_time']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="booking-detail">
                                <i class="fas fa-money-bill-wave booking-icon"></i>
                                <div>
                                    <strong>Amount:</strong> ₹<?= htmlspecialchars($activeBooking['total_amount']) ?>
                                </div>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-credit-card booking-icon"></i>
                                <div>
                                    <strong>Payment:</strong> <?= htmlspecialchars($activeBooking['payment_status']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-booking">
                    <i class="fas fa-parking"></i>
                    <h5>No Active Booking</h5>
                    <p class="mb-4">You don't have any active parking bookings at the moment.</p>
                    <a href="booking.php" class="btn btn-primary-custom">
                        <i class="fas fa-plus-circle me-2"></i> Book a Parking Slot
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Previous Bookings Section -->
        <div class="welcome-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="section-title mb-0">
                    <i class="fas fa-history me-2"></i> Booking History
                </h3>
                <select id="statusFilter" class="form-select filter-select" style="width: auto;">
                    <option value="all">All Status</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Booked">Booked</option>
                    <option value="Billing">Billing</option>
                </select>
            </div>

            <div id="previousBookings">
                <?php if ($previousBookings->num_rows > 0): ?>
                    <?php while ($booking = $previousBookings->fetch_assoc()): 
                        $status = htmlspecialchars($booking['status']);
                        $badgeClass = match($status) {
                            'Completed' => 'bg-completed',
                            'Cancelled' => 'bg-cancelled',
                            'Booked' => 'bg-booked',
                            'Billing' => 'bg-billing',
                            default => 'bg-secondary'
                        };
                    ?>
                        <div class="booking-card mb-3 booking-item" data-status="<?= $status ?>">
                            <div class="booking-header">
                                <span class="booking-id">Booking #<?= htmlspecialchars($booking['booking_uid']) ?></span>
                                <span class="badge-status <?= $badgeClass ?>"><?= $status ?></span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="booking-detail">
                                        <i class="fas fa-map-marker-alt booking-icon"></i>
                                        <div>
                                            <strong>Slot:</strong> <?= htmlspecialchars($booking['slot_id']) ?>
                                        </div>
                                    </div>
                                    <div class="booking-detail">
                                        <i class="fas fa-calendar booking-icon"></i>
                                        <div>
                                            <strong>Booked on:</strong> <?= htmlspecialchars($booking['created_on']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="booking-detail">
                                        <i class="fas fa-money-bill-wave booking-icon"></i>
                                        <div>
                                            <strong>Amount:</strong> ₹<?= htmlspecialchars($booking['total_amount']) ?>
                                        </div>
                                    </div>
                                    <?php if ($booking['start_time']): ?>
                                    <div class="booking-detail">
                                        <i class="fas fa-clock booking-icon"></i>
                                        <div>
                                            <strong>Duration:</strong> 
                                            <?= htmlspecialchars($booking['start_time']) ?> 
                                            <?= $booking['end_time'] ? ' - ' . htmlspecialchars($booking['end_time']) : '' ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-booking">
                        <i class="fas fa-clipboard-list"></i>
                        <h5>No Previous Bookings</h5>
                        <p>You haven't made any bookings yet.</p>
                    </div>
                <?php endif; ?>
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

    <!-- QR Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-qrcode me-2"></i> Booking QR Code</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="qr-code">
                        <img id="qrImage" src="" alt="QR Code" style="max-width: 200px;">
                    </div>
                    <p class="text-muted mt-3">Show this QR code at the parking entrance</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Cancel booking with confirmation
        $('#cancelBookingBtn').on('click', function() {
            let bookingId = $(this).data('id');
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                $.ajax({
                    url: 'cancel_booking.php',
                    type: 'POST',
                    data: { booking_id: bookingId },
                    success: function(response) {
                        alert('Booking cancelled successfully.');
                        location.reload();
                    },
                    error: function() {
                        alert('Error cancelling booking. Please try again.');
                    }
                });
            }
        });

        // Show QR Code
        $('#showQRBtn').on('click', function() {
            let bookingUID = $(this).data('uid');
            $('#qrImage').attr('src', 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + bookingUID);
            var qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            qrModal.show();
        });

        // Filter previous bookings
        $('#statusFilter').on('change', function() {
            let filter = $(this).val();
            $('.booking-item').each(function() {
                let status = $(this).data('status');
                if (filter === 'all' || status === filter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
    </script>
</body>
</html>