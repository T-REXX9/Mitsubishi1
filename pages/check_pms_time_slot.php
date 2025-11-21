<?php
/**
 * Check PMS Time Slot Availability
 * Returns JSON response with availability status
 */

session_start();
require_once '../includes/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pms_date = $_POST['pms_date'] ?? '';
        $pms_time = $_POST['pms_time'] ?? '';

        if (empty($pms_date) || empty($pms_time)) {
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        // Check how many bookings exist for this date and time
        $stmt = $connect->prepare("
            SELECT COUNT(*) as count 
            FROM car_pms_records 
            WHERE pms_date = ? AND pms_time = ? 
            AND request_status IN ('Pending', 'Approved', 'Scheduled')
        ");
        $stmt->execute([$pms_date, $pms_time]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];

        // Max 3 bookings per time slot
        $available = $count < 3;

        echo json_encode([
            'available' => $available,
            'count' => $count,
            'max' => 3,
            'remaining' => max(0, 3 - $count)
        ]);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

