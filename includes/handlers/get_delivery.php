<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing delivery ID']);
    exit;
}

$deliveryId = (int)$_GET['id'];

// Validate input
if ($deliveryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid delivery ID']);
    exit;
}

try {
    // Query to fetch delivery details
    $query = "SELECT * FROM deliveries WHERE id = :id";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':id', $deliveryId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Check if delivery exists
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Delivery not found']);
        exit;
    }
    
    // Fetch delivery data
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'delivery' => $delivery
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
