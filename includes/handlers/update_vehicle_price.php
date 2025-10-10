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
if (!isset($_POST['id']) || !isset($_POST['price'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$id = (int)$_POST['id'];
$price = (float)$_POST['price'];
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// Validate input
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vehicle ID']);
    exit;
}

if ($price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Price must be greater than zero']);
    exit;
}

try {
    // Begin transaction
    $connect->beginTransaction();
    
    // First, get the current price to save in history
    $getQuery = "SELECT id, base_price FROM vehicles WHERE id = :id";
    $getStmt = $connect->prepare($getQuery);
    $getStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $getStmt->execute();
    
    $vehicle = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        $connect->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Vehicle not found']);
        exit;
    }
    
    $oldPrice = $vehicle['base_price'];
    
    // First, save the old price in price_history table if it exists
    // If price_history table does not exist, skip this part
    $tableExistsQuery = "SHOW TABLES LIKE 'price_history'";
    $tableExistsStmt = $connect->prepare($tableExistsQuery);
    $tableExistsStmt->execute();
    
    if ($tableExistsStmt->rowCount() > 0) {
        $historyQuery = "INSERT INTO price_history (vehicle_id, old_price, new_price, change_date, change_note, changed_by)
                          VALUES (:vehicle_id, :old_price, :new_price, NOW(), :note, :user)";
        $historyStmt = $connect->prepare($historyQuery);
        $historyStmt->bindParam(':vehicle_id', $id, PDO::PARAM_INT);
        $historyStmt->bindParam(':old_price', $oldPrice);
        $historyStmt->bindParam(':new_price', $price);
        $historyStmt->bindParam(':note', $note);
        $historyStmt->bindParam(':user', $_SESSION['user_name'] ?? 'Admin');
        $historyStmt->execute();
    }
    
    // Update the vehicle price
    $updateQuery = "UPDATE vehicles SET base_price = :price, updated_at = NOW() WHERE id = :id";
    $updateStmt = $connect->prepare($updateQuery);
    $updateStmt->bindParam(':price', $price);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Price updated successfully',
        'old_price' => $oldPrice,
        'new_price' => $price
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $connect->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
