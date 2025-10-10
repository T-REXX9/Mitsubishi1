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
if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing delivery ID']);
    exit;
}

$deliveryId = (int)$_POST['id'];

// Validate input
if ($deliveryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid delivery ID']);
    exit;
}

try {
    // First, check if delivery exists and get details for stock adjustment
    $checkSql = "SELECT id, units_delivered, vehicle_id, delivery_reference FROM deliveries WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $deliveryId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Delivery not found']);
        exit;
    }
    
    $deliveryInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete the delivery
    $deleteSql = "DELETE FROM deliveries WHERE id = :id";
    $deleteStmt = $connect->prepare($deleteSql);
    $deleteStmt->bindParam(':id', $deliveryId, PDO::PARAM_INT);
    $deleteStmt->execute();
    
    // Adjust vehicle stock if vehicle_id exists
    if ($deliveryInfo['vehicle_id']) {
        $updateStockQuery = "UPDATE vehicles SET stock_quantity = stock_quantity - :units WHERE id = :vehicle_id";
        $updateStmt = $connect->prepare($updateStockQuery);
        $updateStmt->bindParam(':units', $deliveryInfo['units_delivered']);
        $updateStmt->bindParam(':vehicle_id', $deliveryInfo['vehicle_id']);
        $updateStmt->execute();
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Delivery deleted successfully',
        'delivery_id' => $deliveryId,
        'delivery_reference' => $deliveryInfo['delivery_reference']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
