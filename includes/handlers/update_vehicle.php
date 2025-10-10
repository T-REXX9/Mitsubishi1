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
    // First check if vehicle exists
    $checkSql = "SELECT id FROM vehicles WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $vehicleId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle not found']);
        exit;
    }
    
    // Process uploaded images if any
    $mainImage = null;
    $additionalImages = null;
    $view360Images = null;
    $imageUpdates = [];
    
    // Check and process main image
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $mainImage = file_get_contents($_FILES['main_image']['tmp_name']);
        $imageUpdates[] = "main_image = :main_image";
    }
    
    // Process additional images (simple implementation)
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        // Check if at least one file was uploaded correctly
        $hasValidAdditionalImage = false;
        foreach ($_FILES['additional_images']['error'] as $error) {
            if ($error === UPLOAD_ERR_OK) {
                $hasValidAdditionalImage = true;
                break;
            }
        }
        
        if ($hasValidAdditionalImage) {
            // For simplicity, we're just taking the first valid image
            for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $additionalImages = file_get_contents($_FILES['additional_images']['tmp_name'][$i]);
                    $imageUpdates[] = "additional_images = :additional_images";
                    break; // Just use the first valid image for now
                }
            }
        }
    }
    
    // Process 360 view images (simple implementation)
    if (isset($_FILES['view_360_images']) && is_array($_FILES['view_360_images']['name'])) {
        // Check if at least one file was uploaded correctly
        $hasValid360Image = false;
        foreach ($_FILES['view_360_images']['error'] as $error) {
            if ($error === UPLOAD_ERR_OK) {
                $hasValid360Image = true;
                break;
            }
        }
        
        if ($hasValid360Image) {
            // For simplicity, we're just taking the first valid image
            for ($i = 0; $i < count($_FILES['view_360_images']['name']); $i++) {
                if ($_FILES['view_360_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $view360Images = file_get_contents($_FILES['view_360_images']['tmp_name'][$i]);
                    $imageUpdates[] = "view_360_images = :view_360_images";
                    break; // Just use the first valid image for now
                }
            }
        }
    }
    
    // Prepare data for update
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
    
    // Build SQL to update vehicle
    $sql = "UPDATE vehicles SET 
            model_name = :model_name,
            variant = :variant,
            year_model = :year_model,
            category = :category,
            engine_type = :engine_type,
            transmission = :transmission,
            fuel_type = :fuel_type,
            seating_capacity = :seating_capacity,
            key_features = :key_features,
            base_price = :base_price,
            promotional_price = :promotional_price,
            min_downpayment_percentage = :min_downpayment_percentage,
            financing_terms = :financing_terms,
            color_options = :color_options,
            popular_color = :popular_color,
            stock_quantity = :stock_quantity,
            min_stock_alert = :min_stock_alert,
            availability_status = :availability_status,
            expected_delivery_time = :expected_delivery_time";
    
    // Add image updates if any
    if (!empty($imageUpdates)) {
        $sql .= ", " . implode(", ", $imageUpdates);
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $connect->prepare($sql);
    
    // Bind basic parameters
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
    $stmt->bindParam(':stock_quantity', $stockQuantity);
    $stmt->bindParam(':min_stock_alert', $minStockAlert);
    $stmt->bindParam(':availability_status', $availabilityStatus);
    $stmt->bindParam(':expected_delivery_time', $expectedDeliveryTime);
    $stmt->bindParam(':id', $vehicleId);
    
    // Bind image parameters if needed
    if ($mainImage !== null) {
        $stmt->bindParam(':main_image', $mainImage, PDO::PARAM_LOB);
    }
    
    if ($additionalImages !== null) {
        $stmt->bindParam(':additional_images', $additionalImages, PDO::PARAM_LOB);
    }
    
    if ($view360Images !== null) {
        $stmt->bindParam(':view_360_images', $view360Images, PDO::PARAM_LOB);
    }
    
    // Execute the update
    $stmt->execute();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Vehicle updated successfully',
        'vehicle_id' => $vehicleId,
        'vehicle_name' => "$modelName $variant $yearModel",
        'images_updated' => !empty($imageUpdates)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
