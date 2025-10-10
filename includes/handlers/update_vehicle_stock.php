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
if (!isset($_POST['id']) || !isset($_POST['stock_quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$id = (int)$_POST['id'];
$stockQuantity = (int)$_POST['stock_quantity'];

// Validate input
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vehicle ID']);
    exit;
}

if ($stockQuantity < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Stock quantity cannot be negative']);
    exit;
}

try {
    // First, check if vehicle exists
    $checkSql = "SELECT id, model_name FROM vehicles WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle not found']);
        exit;
    }
    
    // Update stock quantity
    $updateSql = "UPDATE vehicles SET stock_quantity = :stock_quantity, 
                 availability_status = CASE 
                     WHEN :stock_quantity > 0 THEN 'available' 
                     ELSE 'out-of-stock' 
                 END
                 WHERE id = :id";
    
    $updateStmt = $connect->prepare($updateSql);
    $updateStmt->bindParam(':stock_quantity', $stockQuantity, PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Stock quantity updated successfully',
        'stock_quantity' => $stockQuantity,
        'vehicle_id' => $id
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
