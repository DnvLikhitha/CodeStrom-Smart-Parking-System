<?php
include 'includes/connection.php';


if (!isset($_SESSION['owner_logged_in'])) {
    header("Location: owner_login.php");
    exit();
}

$mode = $_POST['mode'] ?? '';
$booking_id = $_POST['booking_id'] ?? '';
$vehicle_number = $_POST['vehicle_number'] ?? '';

if (!$mode || !$booking_id) {
    exit("⚠️ Invalid request. Missing parameters.");
}

date_default_timezone_set('Asia/Kolkata');
$now = date('Y-m-d H:i:s');

// ---- Fetch Booking & User Details ----
$stmt = $conn->prepare("
    SELECT b.*, u.name AS user_name, u.phone AS user_phone, s.hourly_rate, s.slot_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN parking_slots s ON b.slot_id = s.slot_id
    WHERE b.booking_uid = ? OR b.booking_id = ?
");
$stmt->bind_param("ss", $booking_id, $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    exit("❌ Booking not found!");
}

$booking_uid   = $booking['booking_uid'];
$user_name     = $booking['user_name'];
$user_phone    = $booking['user_phone'];
$slot_id       = $booking['slot_id'];
$slot_number   = $booking['slot_number'];
$rate_per_hour = (float)$booking['hourly_rate'];

// ✅ --- ENTRY MODE ---
if ($mode === 'entry') {

    // Prevent duplicate entry
    if ($booking['status'] === 'Active') {
        exit("⚠️ Vehicle already marked as entered.");
    }

    // Mark booking as Active and store vehicle number
    $update = $conn->prepare("
        UPDATE bookings 
        SET start_time = ?, status = 'Active', vehicle_no = ?
        WHERE booking_uid = ?
    ");
    $update->bind_param("sss", $now, $vehicle_number, $booking_uid);
    $update->execute();


    // WhatsApp Notification: Entry
    $message = [
        "phone"   => $user_phone,
        "message" => "✅ Hello *$user_name*! Your vehicle number *$vehicle_number* entry has been recorded successfully for booking *$booking_uid* at " . date("h:i A") . ".\nSlot: *$slot_number*.\nWelcome to Smart Parking! 🚘"
    ];
    sendWhatsApp($message);

    echo "✅ Entry recorded successfully for vehicle $vehicle_number.";
    exit();
}

// ✅ --- EXIT MODE ---
if ($mode === 'exit') {

    // Ensure start_time exists
    if (empty($booking['start_time'])) {
        exit("⚠️ Start time missing. Entry was not recorded!");
    }

    // Prevent double exit
    if ($booking['status'] === 'Completed') {
        exit("⚠️ This booking has already been completed.");
    }

    $start_time = strtotime($booking['start_time']);
    $end_time   = strtotime($now);
    $hours      = ceil(($end_time - $start_time) / 3600); // Round up to next hour
    $new_total  = $hours * $rate_per_hour;
    $original_total = (float)$booking['total_amount'];

    // Compare payment
    if ($new_total > $original_total) {
        $balance = $new_total - $original_total;
        $payment_status = "Pending";
        $msg_text = "Exit recorded. You parked for *$hours hour(s)*.\nYou paid ₹$original_total. Balance ₹$balance.\nPlease pay ₹$balance using this link:\nhttps://aadarshsenapati.in/api/pay.php?booking_uid=$booking_uid&amt=$balance";
    } else {
        $balance = 0;
        $payment_status = "Paid";
        $msg_text = "Exit recorded. You parked for *$hours hour(s)*.\nYou paid ₹$original_total. No balance due. Thank you! 🙏";
    }

    // Update booking record
    $update = $conn->prepare("
        UPDATE bookings 
        SET end_time = ?, status = 'Completed', total_amount = ?, slot_duration = ?, payment_status = ?
        WHERE booking_uid = ?
    ");
    $update->bind_param("sdiss", $now, $new_total, $hours, $payment_status, $booking_uid);
    $update->execute();

    // Optionally update payments table if exists
    $pay = $conn->prepare("
        INSERT INTO payments (booking_id, payment_mode, amount, payment_status, payment_time)
        VALUES (?, 'Online', ?, ?, ?)
        ON DUPLICATE KEY UPDATE amount=?, payment_status=?, payment_time=?
    ");
    $pay->bind_param("sdssdss", $booking_uid, $new_total, $payment_status, $now, $new_total, $payment_status, $now);
    $pay->execute();

    // WhatsApp Notification: Exit
    $message = [
        "phone"   => $user_phone,
        "message" => "🚗 Hello *$user_name*! $msg_text"
    ];
    sendWhatsApp($message);

    echo "🚗 Exit recorded successfully. $msg_text";
    exit();
}

// ---- WhatsApp API sender ----
function sendWhatsApp($payload) {
    $ch = curl_init("https://whatsapp-bot-edr6.onrender.com/send_message");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
?>
