<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$deliveryId = (int)$_POST['id'];
$status = trim($_POST['status']);

// Validate input
if ($deliveryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid delivery ID']);
    exit;
}

// Validate status
$allowedStatuses = ['pending', 'completed', 'canceled'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
    exit;
}

try {
    // Check if delivery exists
    $checkSql = "SELECT id, status FROM deliveries WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $deliveryId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Delivery not found']);
        exit;
    }
    
    $delivery = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $oldStatus = $delivery['status'];
    
    // Skip update if status is the same
    if ($oldStatus === $status) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Status already set to ' . $status,
            'delivery_id' => $deliveryId
        ]);
        exit;
    }
    
    // Update delivery status
    $sql = "UPDATE deliveries SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $deliveryId);
    $stmt->execute();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Delivery status updated successfully',
        'delivery_id' => $deliveryId,
        'new_status' => $status
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
