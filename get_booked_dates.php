<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['vehicle_id'])) {
    echo json_encode([]);
    exit();
}

$vehicle_id = intval($_GET['vehicle_id']);

// Get all booked date ranges for this vehicle (excluding returned and cancelled bookings)
$stmt = $conn->prepare("SELECT start_date, end_date FROM rentals 
                        WHERE vehicle_id = ? 
                        AND status NOT IN ('returned', 'cancelled')
                        ORDER BY start_date");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

$bookedDates = [];

while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['start_date']);
    $end = new DateTime($row['end_date']);
    
    // Add all dates in the range
    while ($start <= $end) {
        $bookedDates[] = $start->format('Y-m-d');
        $start->modify('+1 day');
    }
}

$stmt->close();
$conn->close();

echo json_encode($bookedDates);
?>
