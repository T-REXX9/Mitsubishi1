<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

// Check if model_name is provided
if (!isset($_GET['model'])) {
    echo json_encode(['status' => 'error', 'message' => 'Model name is required']);
    exit;
}

$modelName = $_GET['model'];

try {
    // Query to get unique variants for the given model
    $query = "SELECT DISTINCT variant, id FROM vehicles 
              WHERE model_name = :model_name 
              ORDER BY variant";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':model_name', $modelName);
    $stmt->execute();
    
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'variants' => $variants
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
