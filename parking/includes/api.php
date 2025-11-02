<?php
include 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {

    // Register user
    case 'register_user':
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $data['name'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';

        if (!$name || !$phone) {
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            exit;
        }

        $sql = "INSERT INTO users (name, phone, email)
                VALUES ('$name', '$phone', '$email')
                ON DUPLICATE KEY UPDATE name='$name', email='$email'";

        if (mysqli_query($conn, $sql))
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        else
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        break;


    // Get parking lots
    case 'get_parking_lots':
        $result = mysqli_query($conn, "SELECT * FROM parking_lots");
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
        echo json_encode(["status" => "success", "data" => $data]);
        break;


    // Book slot
    case 'book_slot':
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'] ?? 0;
        $slot_id = $data['slot_id'] ?? 0;
        $start_time = $data['start_time'] ?? '';
        $end_time = $data['end_time'] ?? '';
        $total_amount = $data['total_amount'] ?? 0;

        if (!$user_id || !$slot_id) {
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            exit;
        }

        $booking_uid = strtoupper(substr(md5(uniqid()), 0, 8));
        $sql = "INSERT INTO bookings (user_id, slot_id, booking_uid, start_time, end_time, total_amount)
                VALUES ('$user_id', '$slot_id', '$booking_uid', '$start_time', '$end_time', '$total_amount')";

        if (mysqli_query($conn, $sql))
            echo json_encode(["status" => "success", "booking_uid" => $booking_uid]);
        else
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        break;


    // Update payment
    case 'update_payment':
        $data = json_decode(file_get_contents("php://input"), true);
        $booking_uid = $data['booking_uid'] ?? '';
        $status = $data['status'] ?? 'Pending';
        $transaction_id = $data['transaction_id'] ?? '';
        $amount = $data['amount'] ?? 0;

        $sql = "UPDATE bookings SET payment_status='$status', total_amount='$amount' WHERE booking_uid='$booking_uid'";
        if (mysqli_query($conn, $sql))
            echo json_encode(["status" => "success", "message" => "Payment updated"]);
        else
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        break;


    // Get booking status
    case 'get_booking_status':
        $booking_uid = $_GET['booking_uid'] ?? '';
        $result = mysqli_query($conn, "SELECT * FROM bookings WHERE booking_uid='$booking_uid'");
        $data = mysqli_fetch_assoc($result);
        echo json_encode(["status" => "success", "data" => $data]);
        break;


    // Cancel booking
    case 'cancel_booking':
        $data = json_decode(file_get_contents("php://input"), true);
        $booking_uid = $data['booking_uid'] ?? '';
        $sql = "UPDATE bookings SET status='Cancelled' WHERE booking_uid='$booking_uid'";
        if (mysqli_query($conn, $sql))
            echo json_encode(["status" => "success", "message" => "Booking cancelled"]);
        else
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        break;


    // Add feedback
    case 'add_feedback':
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'] ?? 0;
        $booking_id = $data['booking_id'] ?? 0;
        $rating = $data['rating'] ?? 0;
        $comments = $data['comments'] ?? '';
        $sql = "INSERT INTO feedback (user_id, booking_id, rating, comments)
                VALUES ('$user_id', '$booking_id', '$rating', '$comments')";
        if (mysqli_query($conn, $sql))
            echo json_encode(["status" => "success", "message" => "Feedback added"]);
        else
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        break;
    // Get user by phone
    case 'get_user_by_phone':
        $phone = $_GET['phone'] ?? '';
        if (!$phone) {
            echo json_encode(["status" => "error", "message" => "Phone number required"]);
            exit;
        }
    
        $result = mysqli_query($conn, "SELECT * FROM users WHERE phone='$phone'");
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            echo json_encode(["status" => "success", "data" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "User not found"]);
        }
        break;
    // REGISTER USER WITH OTP
    case 'register_user_otp':
        $input = json_decode(file_get_contents("php://input"), true);
        $name = $input["name"];
        $phone = $input["phone"];
        $email = $input["email"];
        $otp = $input["otp"];
        $otp_expires = $input["otp_expires"];
        $registered_on = date("Y-m-d H:i:s");
    
        $stmt = $conn->prepare("INSERT INTO users (name, phone, email, otp, otp_expires, registered_on) 
                                VALUES (?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE 
                                name = VALUES(name), email = VALUES(email), otp = VALUES(otp), otp_expires = VALUES(otp_expires)");
        $stmt->bind_param("ssssss", $name, $phone, $email, $otp, $otp_expires, $registered_on);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User registered with OTP"]);
        } else {
            echo json_encode(["status" => "error", "message" => "DB error"]);
        }
        $stmt->close();
        exit;

    // Default fallback
    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
}
?>
