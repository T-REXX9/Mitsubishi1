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
    // Query to fetch vehicle details
    $query = "SELECT id, model_name, variant, year_model, category, engine_type, 
              transmission, fuel_type, seating_capacity, key_features, base_price, 
              promotional_price, min_downpayment_percentage, financing_terms, 
              color_options, popular_color, 
              IF(main_image IS NOT NULL, 1, 0) AS has_main_image,
              IF(additional_images IS NOT NULL, 1, 0) AS has_additional_images,
              IF(view_360_images IS NOT NULL, 1, 0) AS has_view_360_images,
              stock_quantity, min_stock_alert, availability_status, expected_delivery_time,
              created_at, updated_at
              FROM vehicles 
              WHERE id = :id";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':id', $vehicleId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Check if vehicle exists
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle not found']);
        exit;
    }
    
    // Fetch vehicle data
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'vehicle' => $vehicle
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
