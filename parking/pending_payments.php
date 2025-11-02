<?php
include 'includes/connection.php';

// Assuming user is logged in
$user_id = $_SESSION['user_id'] ?? 3; // fallback for testing

// Fetch only pending payments
$sql = "SELECT b.*, l.lot_name, s.slot_number 
        FROM bookings b
        JOIN parking_slots s ON b.slot_id = s.slot_id
        JOIN parking_lots l ON s.lot_id = l.lot_id
        WHERE b.user_id = ? AND b.payment_status = 'Pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments | Smart Parking</title>
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

        .content-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
            padding: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
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

        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-success-custom:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .payment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
        }

        .payment-table thead {
            background: var(--gradient);
        }

        .payment-table th {
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border: none;
        }

        .payment-table th:first-child {
            border-radius: 12px 0 0 0;
        }

        .payment-table th:last-child {
            border-radius: 0 12px 0 0;
        }

        .payment-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .payment-table tbody tr {
            transition: all 0.3s ease;
        }

        .payment-table tbody tr:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .payment-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .bg-pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: black;
        }

        .amount-cell {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        .no-payments {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .no-payments i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #28a745;
        }

        .booking-id {
            font-weight: 600;
            color: var(--primary-dark);
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
            
            .content-card {
                padding: 1.5rem;
            }
            
            .payment-table {
                display: block;
                overflow-x: auto;
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
                            <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="pending_payments.php"><i class="fas fa-credit-card me-1"></i> Payments</a>
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
            <h1 class="page-title">Pending Payments</h1>
            <p class="page-subtitle">Review and complete your outstanding parking payments</p>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="dashboard.php" class="btn btn-primary-custom">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
                
                <div class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= $result->num_rows ?> pending payment<?= $result->num_rows != 1 ? 's' : '' ?>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Booking Details</th>
                                <th>Parking Information</th>
                                <th>Timing</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="booking-id"><?= htmlspecialchars($row['booking_uid']) ?></div>
                                    <?php if (!empty($row['vehicle_no'])): ?>
                                        <small class="text-muted">Vehicle: <?= htmlspecialchars($row['vehicle_no']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><strong><?= htmlspecialchars($row['lot_name']) ?></strong></div>
                                    <small class="text-muted">Slot: <?= htmlspecialchars($row['slot_number']) ?></small>
                                </td>
                                <td>
                                    <div><small><strong>Entry:</strong> <?= htmlspecialchars($row['start_time']) ?></small></div>
                                    <?php if (!empty($row['end_time'])): ?>
                                        <div><small><strong>Exit:</strong> <?= htmlspecialchars($row['end_time']) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td class="amount-cell">
                                    ₹<?= number_format($row['total_amount'], 2) ?>
                                </td>
                                <td>
                                    <span class="badge-status bg-pending">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= htmlspecialchars($row['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="pay_now.php?booking_uid=<?= urlencode($row['booking_uid']) ?>&amt=<?= urlencode($row['total_amount']) ?>" 
                                       class="btn btn-success-custom">
                                        <i class="fas fa-credit-card me-1"></i> Pay Now
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-payments">
                    <i class="fas fa-check-circle"></i>
                    <h4>All Payments Cleared!</h4>
                    <p class="mb-4">You don't have any pending payments at the moment.</p>
                    <a href="booking.php" class="btn btn-primary-custom">
                        <i class="fas fa-plus-circle me-2"></i> Book New Parking
                    </a>
                </div>
            <?php endif; ?>
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
</body>
</html>