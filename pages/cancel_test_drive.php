<?php
/**
 * Cancel Test Drive Request
 * Allows customers to cancel their own test drive bookings
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header early
header('Content-Type: application/json');

// Include database connection
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to cancel a test drive'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get the test drive ID
    $test_drive_id = intval($_POST['test_drive_id'] ?? 0);

    if (empty($test_drive_id)) {
        throw new Exception('Test drive ID is required');
    }

    // Check database connection
    if (!isset($connect) || !$connect) {
        throw new Exception('Database connection not available');
    }

    // Verify that this test drive belongs to the current user
    $stmt = $connect->prepare("
        SELECT id, account_id, status, selected_date, selected_time_slot, vehicle_id
        FROM test_drive_requests
        WHERE id = ? AND account_id = ?
    ");
    $stmt->execute([$test_drive_id, $_SESSION['user_id']]);
    $test_drive = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_drive) {
        throw new Exception('Test drive not found or you do not have permission to cancel it');
    }

    // Check if the test drive can be cancelled (only Pending or Approved status)
    if (!in_array($test_drive['status'], ['Pending', 'Approved'])) {
        throw new Exception('This test drive cannot be cancelled. Status: ' . $test_drive['status']);
    }

    // Check if the scheduled date/time has already passed
    $scheduled_datetime = $test_drive['selected_date'] . ' ' . $test_drive['selected_time_slot'];
    if (strtotime($scheduled_datetime) <= time()) {
        throw new Exception('Cannot cancel a test drive that has already passed');
    }

    // Update the test drive status to 'Cancelled'
    // We'll use 'Rejected' status with a [CUSTOMER_CANCELLED] prefix in notes
    $cancellation_note = '[CUSTOMER_CANCELLED] Customer cancelled the test drive booking on ' . date('Y-m-d H:i:s');

    $updateStmt = $connect->prepare("
        UPDATE test_drive_requests
        SET status = 'Rejected',
            notes = CONCAT(?, '\n---\n', COALESCE(notes, ''))
        WHERE id = ? AND account_id = ?
    ");

    $result = $updateStmt->execute([$cancellation_note, $test_drive_id, $_SESSION['user_id']]);

    if (!$result) {
        throw new Exception('Failed to cancel test drive');
    }
    
    // Send notification to admins and sales agents
    require_once dirname(__DIR__) . '/includes/api/notification_api.php';

    // Notify admins
    createNotification(
        null,
        'Admin',
        'Test Drive Cancelled by Customer',
        'Customer cancelled test drive request (ID: ' . $test_drive_id . ') scheduled for ' . date('F j, Y', strtotime($test_drive['selected_date'])),
        'test_drive',
        $test_drive_id
    );

    // Notify sales agents
    createNotification(
        null,
        'SalesAgent',
        'Test Drive Cancelled by Customer',
        'Customer cancelled test drive request (ID: ' . $test_drive_id . ') scheduled for ' . date('F j, Y', strtotime($test_drive['selected_date'])),
        'test_drive',
        $test_drive_id
    );
    
    // Log the cancellation
    error_log("Test drive ID {$test_drive_id} cancelled by customer (User ID: {$_SESSION['user_id']})");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Test drive has been cancelled successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error cancelling test drive: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Throwable $e) {
    error_log("Fatal error cancelling test drive: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred: ' . $e->getMessage()
    ]);
}

