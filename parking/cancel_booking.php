<?php
include 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }
}
?>
