<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Search for booking
if (isset($_POST['search_booking_ajax'])) {
    $search_type = $_POST['search_type'];
    $search_value = trim($_POST['search_value']);
    
    if ($search_type == 'booking_id') {
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE r.id = ?");
        $stmt->bind_param("i", $search_value);
    } elseif ($search_type == 'customer_email') {
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE u.email = ?
                                ORDER BY r.id DESC LIMIT 1");
        $stmt->bind_param("s", $search_value);
    } elseif ($search_type == 'vehicle_number') {
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE v.vehicle_number LIKE ?
                                ORDER BY r.id DESC LIMIT 1");
        $search_pattern = "%{$search_value}%";
        $stmt->bind_param("s", $search_pattern);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode(['success' => true, 'booking' => $booking]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No booking found']);
    }
    $stmt->close();
    exit();
}

// Update booking status
if (isset($_POST['update_booking_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    
    // Validate status
    if (!in_array($status, ['pending', 'active', 'returned'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE rentals SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update status']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
