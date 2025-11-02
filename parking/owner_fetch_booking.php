<?php
include 'includes/connection.php';
if (!isset($_SESSION['owner_logged_in'])) exit();

$booking_id = $_GET['booking_id'] ?? '';
$response = ["success" => false];

$q = $conn->prepare("SELECT b.*, ps.slot_number, u.name AS user_name 
                     FROM bookings b 
                     JOIN parking_slots ps ON b.slot_id = ps.slot_id 
                     JOIN users u ON b.user_id = u.user_id 
                     WHERE (b.booking_id = ? OR b.booking_uid = ?)");
$q->bind_param("ss", $booking_id, $booking_id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows > 0) {
    $data = $res->fetch_assoc();
    $response = [
        "success" => true,
        "user" => $data['user_name'],
        "slot" => $data['slot_number'],
        "duration" => $data['slot_duration'],
        "status" => $data['status']
    ];
} else {
    $response['message'] = "No booking found.";
}

echo json_encode($response);
?>
