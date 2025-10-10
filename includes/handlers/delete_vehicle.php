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
    echo json_encode(['status' => 'error', 'message' => 'Missing vehicle ID']);
    exit;
}

$vehicleId = (int)$_POST['id'];

// Validate input
if ($vehicleId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vehicle ID']);
    exit;
}

try {
    // First, check if vehicle exists
    $checkSql = "SELECT model_name, variant, year_model FROM vehicles WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $vehicleId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle not found']);
        exit;
    }
    
    $vehicleInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete the vehicle
    $deleteSql = "DELETE FROM vehicles WHERE id = :id";
    $deleteStmt = $connect->prepare($deleteSql);
    $deleteStmt->bindParam(':id', $vehicleId, PDO::PARAM_INT);
    $deleteStmt->execute();
    
    // Return success response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Vehicle deleted successfully',
        'vehicle_id' => $vehicleId,
        'vehicle_name' => trim($vehicleInfo['model_name'] . ' ' . $vehicleInfo['variant'] . ' ' . $vehicleInfo['year_model'])
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
