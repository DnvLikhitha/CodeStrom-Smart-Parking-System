<?php
require('fpdf186/fpdf.php');
include 'includes/connection.php';

if (!isset($_GET['booking_uid'])) {
    die("Booking ID not provided.");
}

$booking_id = intval($_GET['booking_uid']);

$query = $conn->query("
    SELECT 
        b.booking_id, 
        b.start_time AS entry_time, 
        b.end_time, 
        b.total_amount, 
        b.payment_status, 
        b.status, 
        ps.slot_number, 
        ps.vehicle_type, 
        ps.hourly_rate, 
        pl.lot_name 
    FROM bookings b
    JOIN parking_slots ps ON b.slot_id = ps.slot_id
    JOIN parking_lots pl ON ps.lot_id = pl.lot_id
    WHERE b.booking_uid = $booking_id
");

if ($query->num_rows == 0) {
    die("Invalid Booking ID.");
}

$data = $query->fetch_assoc();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'PARKING RECEIPT', 0, 1, 'C');
$pdf->Ln(5);

// Details
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Booking ID:', 0, 0);
$pdf->Cell(50, 10, $data['booking_id'], 0, 1);

$pdf->Cell(50, 10, 'Parking Lot:', 0, 0);
$pdf->Cell(50, 10, $data['lot_name'], 0, 1);

$pdf->Cell(50, 10, 'Slot Number:', 0, 0);
$pdf->Cell(50, 10, $data['slot_number'], 0, 1);

$pdf->Cell(50, 10, 'Vehicle Type:', 0, 0);
$pdf->Cell(50, 10, $data['vehicle_type'], 0, 1);

$pdf->Cell(50, 10, 'Hourly Rate (₹):', 0, 0);
$pdf->Cell(50, 10, $data['hourly_rate'], 0, 1);

$pdf->Cell(50, 10, 'Entry Time:', 0, 0);
$pdf->Cell(50, 10, $data['entry_time'], 0, 1);

$pdf->Cell(50, 10, 'Exit Time:', 0, 0);
$pdf->Cell(50, 10, $data['end_time'], 0, 1);

$pdf->Cell(50, 10, 'Payment Mode:', 0, 0);
$pdf->Cell(50, 10, 'Cash', 0, 1);

$pdf->Cell(50, 10, 'Payment Status:', 0, 0);
$pdf->Cell(50, 10, $data['payment_status'], 0, 1);

$pdf->Cell(50, 10, 'Total Amount (₹):', 0, 0);
$pdf->Cell(50, 10, number_format($data['total_amount'], 2), 0, 1);

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 11);
$pdf->Cell(0, 10, 'Thank you for choosing our parking service!', 0, 1, 'C');

// Output PDF
$pdf->Output('D', 'OnsiteBooking_' . $data['booking_id'] . '.pdf');
exit;
?>
