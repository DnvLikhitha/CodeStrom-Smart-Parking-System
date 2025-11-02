<?php
include 'includes/connection.php';

// =========================
// HANDLE AJAX REQUESTS FIRST
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_lot') {
        $stmt = $conn->prepare("INSERT INTO parking_lots (owner_id, lot_name, latitude, longitude, address, is_special, keyword) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsss", $_SESSION['owner_id'], $_POST['lot_name'], $_POST['latitude'], $_POST['longitude'], $_POST['address'], $_POST['is_special'], $_POST['keyword']);
        $stmt->execute();
        exit;
    }

    if ($action === 'add_slot') {
        $stmt = $conn->prepare("INSERT INTO parking_slots (lot_id, slot_number, vehicle_type, is_available, hourly_rate) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issid", $_POST['lot_id'], $_POST['slot_number'], $_POST['vehicle_type'], $_POST['is_available'], $_POST['hourly_rate']);
        $stmt->execute();
        exit;
    }

    if ($action === 'get_lots') {
        $result = $conn->query("SELECT l.*, COUNT(s.slot_id) AS total_slots 
                                FROM parking_lots l 
                                LEFT JOIN parking_slots s ON l.lot_id = s.lot_id 
                                GROUP BY l.lot_id");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['lot_id']}</td>
                    <td>{$row['lot_name']}</td>
                    <td>{$row['is_special']}</td>
                    <td>{$row['keyword']}</td>
                    <td>{$row['total_slots']}</td>
                    <td><button class='delete-lot' data-id='{$row['lot_id']}'>Delete</button></td>
                  </tr>";
        }
        exit;
    }

    if ($action === 'get_lot_dropdown') {
        $res = $conn->query("SELECT lot_id, lot_name FROM parking_lots");
        echo "<option value=''>-- Select Lot --</option>";
        while ($r = $res->fetch_assoc()) {
            echo "<option value='{$r['lot_id']}'>{$r['lot_name']}</option>";
        }
        exit;
    }

    if ($action === 'get_slots') {
        $res = $conn->query("SELECT s.*, l.lot_name FROM parking_slots s JOIN parking_lots l ON s.lot_id = l.lot_id");
        while ($r = $res->fetch_assoc()) {
            $available = $r['is_available'] ? 'Yes' : 'No';
            echo "<tr>
                    <td>{$r['slot_id']}</td>
                    <td>{$r['lot_name']}</td>
                    <td>{$r['slot_number']}</td>
                    <td>{$r['vehicle_type']}</td>
                    <td>{$r['hourly_rate']}</td>
                    <td>{$available}</td>
                    <td><button class='delete-slot' data-id='{$r['slot_id']}'>Delete</button></td>
                  </tr>";
        }
        exit;
    }

    if ($action === 'delete_lot') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM parking_slots WHERE lot_id = $id");
        $conn->query("DELETE FROM parking_lots WHERE lot_id = $id");
        exit;
    }

    if ($action === 'delete_slot') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM parking_slots WHERE slot_id = $id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Parking | Smart Parking</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOVYRIgupAurZup5y1PRh8Ismb1A3lLao&libraries=places"></script>
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

    .management-container {
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

    .form-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(25, 23, 130, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .form-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--gradient);
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--primary-dark);
        margin-bottom: 0.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
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
        padding: 0.75rem 1rem;
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

    .btn-danger-custom {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-danger-custom:hover {
        background: linear-gradient(135deg, #e83e8c, #dc3545);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        color: white;
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

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .data-table th {
        background: var(--gradient);
        color: white;
        font-weight: 600;
        padding: 1rem;
        text-align: left;
        border: none;
    }

    .data-table th:first-child {
        border-radius: 10px 0 0 0;
    }

    .data-table th:last-child {
        border-radius: 0 10px 0 0;
    }

    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .data-table tbody tr {
        transition: all 0.3s ease;
    }

    .data-table tbody tr:hover {
        background: #f8f9fa;
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    .map-container {
        border: 2px solid var(--primary-light);
        border-radius: 15px;
        overflow: hidden;
        margin-top: 1rem;
    }

    #map {
        height: 300px;
        width: 100%;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.8rem;
    }

    .badge-special {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: black;
    }

    .badge-available {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .badge-unavailable {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        color: white;
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
        .management-container {
            padding: 1rem 0.5rem;
        }
        
        .form-card, .table-container {
            padding: 1.5rem;
        }
        
        .data-table {
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
                            <a class="nav-link active" href="manage_slots.php"><i class="fas fa-plus-circle me-1"></i> Manage Slots</a>
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
    <div class="management-container">
        <div class="page-header">
            <h1 class="page-title">Manage Parking Facilities</h1>
            <p class="page-subtitle">Add and manage parking lots and slots in your system</p>
        </div>

        <!-- Add Parking Lot Form -->
        <div class="form-card">
            <h3 class="card-title">
                <i class="fas fa-map-marker-alt"></i> Add New Parking Lot
            </h3>
            <form id="addLotForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Lot Name</label>
                            <input type="text" name="lot_name" class="form-input" placeholder="Enter parking lot name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Is Special Lot?</label>
                            <select name="is_special" id="is_special" class="form-select">
                                <option value="No">Regular Parking</option>
                                <option value="Yes">Special Parking</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="3" placeholder="Enter complete address" required></textarea>
                </div>

                <div id="keywordDiv" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Special Keyword</label>
                        <input type="text" name="keyword" class="form-input" placeholder="Enter special keyword for this lot">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-input" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Location on Map</label>
                    <div class="map-container">
                        <div id="map"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i> Add Parking Lot
                </button>
            </form>
        </div>

        <!-- Add Parking Slot Form -->
        <div class="form-card">
            <h3 class="card-title">
                <i class="fas fa-parking"></i> Add Parking Slot
            </h3>
            <form id="addSlotForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Select Lot</label>
                            <select name="lot_id" id="lot_id" class="form-select" required>
                                <option value="">-- Loading Parking Lots --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Slot Number</label>
                            <input type="text" name="slot_number" class="form-input" placeholder="Enter slot number" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Vehicle Type</label>
                            <select name="vehicle_type" class="form-select" required>
                                <option value="2-wheeler">2-Wheeler</option>
                                <option value="4-wheeler">4-Wheeler</option>
                                <option value="EV">Electric Vehicle (EV)</option>
                                <option value="Bus">Bus</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Hourly Rate (₹)</label>
                            <input type="number" step="0.01" name="hourly_rate" class="form-input" placeholder="Enter hourly rate" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Available for Booking?</label>
                    <select name="is_available" class="form-select">
                        <option value="1">Yes - Available</option>
                        <option value="0">No - Temporarily Unavailable</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i> Add Parking Slot
                </button>
            </form>
        </div>

        <!-- Existing Lots Table -->
        <div class="table-container">
            <h3 class="card-title">
                <i class="fas fa-list-alt"></i> Existing Parking Lots
            </h3>
            <table class="data-table" id="lotsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lot Name</th>
                        <th>Type</th>
                        <th>Keyword</th>
                        <th>Total Slots</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Existing Slots Table -->
        <div class="table-container">
            <h3 class="card-title">
                <i class="fas fa-list-ol"></i> Existing Parking Slots
            </h3>
            <table class="data-table" id="slotsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Parking Lot</th>
                        <th>Slot Number</th>
                        <th>Vehicle Type</th>
                        <th>Rate (₹/hr)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
        let map, marker;
        function initMap() {
            const def = { lat: 16.289200, lng: 80.439122 };
            map = new google.maps.Map(document.getElementById("map"), { 
                center: def, 
                zoom: 14,
                styles: [
                    {
                        "featureType": "all",
                        "elementType": "geometry.fill",
                        "stylers": [{"weight": "2.00"}]
                    },
                    {
                        "featureType": "all",
                        "elementType": "geometry.stroke",
                        "stylers": [{"color": "#9c9c9c"}]
                    },
                    {
                        "featureType": "all",
                        "elementType": "labels.text",
                        "stylers": [{"visibility": "on"}]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "all",
                        "stylers": [{"color": "#f2f2f2"}]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "landscape.man_made",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "poi",
                        "elementType": "all",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "all",
                        "stylers": [{"saturation": -100}, {"lightness": 45}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#eeeeee"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "labels.text.fill",
                        "stylers": [{"color": "#7b7b7b"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "labels.text.stroke",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "road.highway",
                        "elementType": "all",
                        "stylers": [{"visibility": "simplified"}]
                    },
                    {
                        "featureType": "road.arterial",
                        "elementType": "labels.icon",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "transit",
                        "elementType": "all",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "all",
                        "stylers": [{"color": "#46bcec"}, {"visibility": "on"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#c8d7d4"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "labels.text.fill",
                        "stylers": [{"color": "#070707"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "labels.text.stroke",
                        "stylers": [{"color": "#ffffff"}]
                    }
                ]
            });
            marker = new google.maps.Marker({ 
                position: def, 
                map, 
                draggable: true,
                title: "Drag to set parking lot location",
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="20" cy="20" r="18" fill="#4607f4" stroke="white" stroke-width="2"/>
                            <circle cx="20" cy="20" r="8" fill="white"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(40, 40)
                }
            });
            google.maps.event.addListener(marker, "dragend", (e) => {
                $("#latitude").val(e.latLng.lat().toFixed(6));
                $("#longitude").val(e.latLng.lng().toFixed(6));
            });
        }

        $(function() {
            initMap();
            loadLots();
            loadSlots();

            $("#is_special").change(() => {
                $("#keywordDiv").toggle($("#is_special").val() === "Yes");
            });

            $("#addLotForm").submit(function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Adding...');
                submitBtn.prop('disabled', true);

                $.post("manage_slots.php", $(this).serialize() + "&action=add_lot", function() {
                    loadLots();
                    alert("✅ Parking lot added successfully!");
                    $("#addLotForm")[0].reset();
                }).always(() => {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                });
            });

            $("#addSlotForm").submit(function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Adding...');
                submitBtn.prop('disabled', true);

                $.post("manage_slots.php", $(this).serialize() + "&action=add_slot", function() {
                    loadSlots();
                    alert("✅ Parking slot added successfully!");
                    $("#addSlotForm")[0].reset();
                }).always(() => {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                });
            });

            $(document).on("click", ".delete-lot", function() {
                if (confirm("⚠️ Are you sure you want to delete this parking lot? This will also delete all associated parking slots!")) {
                    $.post("manage_slots.php", { action: "delete_lot", id: $(this).data("id") }, function() {
                        loadLots();
                        loadSlots();
                        alert("🗑️ Parking lot deleted successfully!");
                    });
                }
            });

            $(document).on("click", ".delete-slot", function() {
                if (confirm("⚠️ Are you sure you want to delete this parking slot?")) {
                    $.post("manage_slots.php", { action: "delete_slot", id: $(this).data("id") }, function() {
                        loadSlots();
                        alert("🗑️ Parking slot deleted successfully!");
                    });
                }
            });

            function loadLots() {
                $.post("manage_slots.php", { action: "get_lots" }, data => $("#lotsTable tbody").html(data));
                $.post("manage_slots.php", { action: "get_lot_dropdown" }, data => $("#lot_id").html(data));
            }

            function loadSlots() {
                $.post("manage_slots.php", { action: "get_slots" }, data => $("#slotsTable tbody").html(data));
            }
        });
    </script>
</body>
</html>