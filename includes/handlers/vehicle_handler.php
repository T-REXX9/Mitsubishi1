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

// Validation array for errors
$errors = [];
$requiredFields = [
    'model_name' => 'Model name',
    'year_model' => 'Year model',
    'category' => 'Category',
    'engine_type' => 'Engine type',
    'transmission' => 'Transmission',
    'fuel_type' => 'Fuel type',
    'seating_capacity' => 'Seating capacity',
    'base_price' => 'Base price',
    'color_options' => 'Available colors',
    'stock_quantity' => 'Stock quantity',
    'availability_status' => 'Availability status'
];

// Check required fields
foreach ($requiredFields as $field => $label) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = "$label is required";
    }
}

// If main_image is required, check it too
if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Main vehicle image is required";
}

// Check if there are validation errors
if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Validation errors occurred',
        'errors' => $errors
    ]);
    exit;
}

try {
    // Process main image 
    $mainImage = null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $mainImage = file_get_contents($_FILES['main_image']['tmp_name']);
    }
    
    // Process additional images (optional)
    $additionalImages = null;
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        // For simplicity, we're not handling multiple files in this example
        // A real implementation would need to process each file and combine them
        $additionalImages = null;
    }
    
    // Process 360 view images (optional)
    $view360Images = null;
    if (isset($_FILES['view_360_images']) && is_array($_FILES['view_360_images']['name'])) {
        // For simplicity, we're not handling multiple files in this example
        $view360Images = null;
    }
    
    // Prepare data for insertion
    $modelName = trim($_POST['model_name']);
    $variant = isset($_POST['variant']) ? trim($_POST['variant']) : null;
    $yearModel = (int)$_POST['year_model'];
    $category = trim($_POST['category']);
    $engineType = trim($_POST['engine_type']);
    $transmission = trim($_POST['transmission']);
    $fuelType = trim($_POST['fuel_type']);
    $seatingCapacity = (int)$_POST['seating_capacity'];
    $keyFeatures = isset($_POST['key_features']) ? trim($_POST['key_features']) : null;
    $basePrice = (float)$_POST['base_price'];
    $promotionalPrice = isset($_POST['promotional_price']) && !empty($_POST['promotional_price']) ? 
        (float)$_POST['promotional_price'] : null;
    $minDownpaymentPercentage = isset($_POST['min_downpayment_percentage']) && !empty($_POST['min_downpayment_percentage']) ? 
        (int)$_POST['min_downpayment_percentage'] : null;
    $financingTerms = isset($_POST['financing_terms']) ? trim($_POST['financing_terms']) : null;
    $colorOptions = trim($_POST['color_options']);
    $popularColor = isset($_POST['popular_color']) ? trim($_POST['popular_color']) : null;
    $stockQuantity = (int)$_POST['stock_quantity'];
    $minStockAlert = isset($_POST['min_stock_alert']) && !empty($_POST['min_stock_alert']) ? 
        (int)$_POST['min_stock_alert'] : null;
    $availabilityStatus = trim($_POST['availability_status']);
    $expectedDeliveryTime = isset($_POST['expected_delivery_time']) ? trim($_POST['expected_delivery_time']) : null;
    
    // SQL to insert vehicle into database
    $query = "INSERT INTO vehicles (
                model_name, variant, year_model, category, engine_type, transmission, 
                fuel_type, seating_capacity, key_features, base_price, promotional_price, 
                min_downpayment_percentage, financing_terms, color_options, popular_color, 
                main_image, additional_images, view_360_images, stock_quantity, min_stock_alert, 
                availability_status, expected_delivery_time
              ) VALUES (
                :model_name, :variant, :year_model, :category, :engine_type, :transmission, 
                :fuel_type, :seating_capacity, :key_features, :base_price, :promotional_price, 
                :min_downpayment_percentage, :financing_terms, :color_options, :popular_color, 
                :main_image, :additional_images, :view_360_images, :stock_quantity, :min_stock_alert, 
                :availability_status, :expected_delivery_time
              )";
    
    $stmt = $connect->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':model_name', $modelName);
    $stmt->bindParam(':variant', $variant);
    $stmt->bindParam(':year_model', $yearModel);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':engine_type', $engineType);
    $stmt->bindParam(':transmission', $transmission);
    $stmt->bindParam(':fuel_type', $fuelType);
    $stmt->bindParam(':seating_capacity', $seatingCapacity);
    $stmt->bindParam(':key_features', $keyFeatures);
    $stmt->bindParam(':base_price', $basePrice);
    $stmt->bindParam(':promotional_price', $promotionalPrice);
    $stmt->bindParam(':min_downpayment_percentage', $minDownpaymentPercentage);
    $stmt->bindParam(':financing_terms', $financingTerms);
    $stmt->bindParam(':color_options', $colorOptions);
    $stmt->bindParam(':popular_color', $popularColor);
    $stmt->bindParam(':main_image', $mainImage, PDO::PARAM_LOB);
    $stmt->bindParam(':additional_images', $additionalImages, PDO::PARAM_LOB);
    $stmt->bindParam(':view_360_images', $view360Images, PDO::PARAM_LOB);
    $stmt->bindParam(':stock_quantity', $stockQuantity);
    $stmt->bindParam(':min_stock_alert', $minStockAlert);
    $stmt->bindParam(':availability_status', $availabilityStatus);
    $stmt->bindParam(':expected_delivery_time', $expectedDeliveryTime);
    
    // Execute the statement
    $stmt->execute();
    
    // Get the inserted vehicle ID
    $vehicleId = $connect->lastInsertId();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Vehicle added successfully',
        'vehicle_id' => $vehicleId,
        'vehicle_name' => "$modelName $variant $yearModel"
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
