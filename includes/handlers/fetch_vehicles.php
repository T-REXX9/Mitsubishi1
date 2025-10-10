<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Prepare and execute query to get vehicles according to the schema
    $query = "SELECT id, model_name, variant, year_model, category, engine_type, 
              transmission, fuel_type, seating_capacity, base_price, promotional_price, 
              color_options, popular_color, stock_quantity, availability_status, expected_delivery_time
              FROM vehicles 
              ORDER BY created_at DESC";
    $stmt = $connect->prepare($query);
    $stmt->execute();
    
    // Fetch all vehicles
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with vehicles data
    echo json_encode([
        'status' => 'success',
        'vehicles' => $vehicles,
        'count' => count($vehicles)
    ]);
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
