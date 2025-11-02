<?php
$server = "localhost";
$uname = "bmezdlbk_aadarshsenapati";
$pass = "Rishi@2005";

// Connect to MySQL server
$conn = mysqli_connect($server, $uname, $pass);
if (!$conn) {
    die("Unable to connect");
}

// Select the database
mysqli_select_db($conn, "bmezdlbk_parking");

date_default_timezone_set("Asia/Kolkata");
// Start session
session_start();
?>
