<?php
session_start();
require_once '../includes/connection.php';

header('Content-Type: application/json');

// Check if request is POST and has required parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['date']) || !isset($_POST['time'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }
    
    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        echo json_encode(['error' => 'Invalid time format']);
        exit;
    }
    
    // Check for existing bookings at the same date and time
    $stmt = $connect->prepare("SELECT COUNT(*) FROM test_drive_requests WHERE selected_date = ? AND selected_time_slot = ? AND status IN ('Pending', 'Approved')");
    $stmt->execute([$date, $time]);
    $count = $stmt->fetchColumn();
    
    // Return conflict status
    echo json_encode([
        'conflict' => $count > 0,
        'existing_bookings' => (int)$count
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in check_availability.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Error in check_availability.php: " . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
?>