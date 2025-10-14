<?php
// Include the init file which sets up database connection
require_once '../includes/init.php';

header('Content-Type: application/json');

if (!isset($_GET['vehicle_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vehicle ID is required']);
    exit;
}

$vehicle_id = intval($_GET['vehicle_id']);

// Get PDO connection from globals (set by init.php)
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    error_log("get_360_images.php: Database connection not available");
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT view_360_images FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
        exit;
    }
    
    $view_360_images = $result['view_360_images'];
    
    if (empty($view_360_images)) {
        echo json_encode(['success' => false, 'message' => 'No 360 images available']);
        exit;
    }
    
    // Try to decode as JSON first (new formats)
    $json_data = @json_decode($view_360_images, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
        // Check if this is color-model mapping format
        $hasColorModelMapping = false;
        foreach ($json_data as $item) {
            if (is_array($item) && isset($item['color']) && isset($item['model'])) {
                $hasColorModelMapping = true;
                break;
            }
        }
        
        if ($hasColorModelMapping) {
            // This is color-model mapping format - these are 3D models, not 360 images
            echo json_encode(['success' => false, 'message' => 'Vehicle uses 3D models, not 360 images']);
            exit;
        }
        
        // Check if array contains file paths (new format for images)
        $hasImagePaths = false;
        foreach ($json_data as $item) {
            if (is_string($item) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $item)) {
                $hasImagePaths = true;
                break;
            }
        }
        
        if ($hasImagePaths) {
            // Return the image paths directly - frontend will convert to full URLs
            echo json_encode([
                'success' => true,
                'images' => $json_data,
                'format' => 'paths',
                'count' => count($json_data)
            ]);
            exit;
        }
        
        // Check if array contains 3D model paths
        foreach ($json_data as $item) {
            if (is_string($item) && preg_match('/\.(glb|gltf)$/i', $item)) {
                echo json_encode(['success' => false, 'message' => 'Vehicle uses 3D models, not 360 images']);
                exit;
            }
        }
    }
    
    // Try to unserialize the data (legacy format with binary/serialized data)
    $images = @unserialize($view_360_images);
    
    if ($images === false) {
        // If unserialize fails, try to handle as raw base64 or binary data
        $images = [base64_encode($view_360_images)];
    }
    
    // Ensure we have an array of images
    if (!is_array($images)) {
        $images = [$images];
    }
    
    // Convert binary data to base64 if needed (legacy format)
    $base64_images = [];
    foreach ($images as $image) {
        if (is_string($image)) {
            // Check if it's already base64 encoded
            if (base64_decode($image, true) !== false && !preg_match('/[^A-Za-z0-9+\/=]/', $image)) {
                $base64_images[] = $image;
            } else {
                // Convert binary to base64
                $base64_images[] = base64_encode($image);
            }
        }
    }
    
    if (empty($base64_images)) {
        echo json_encode(['success' => false, 'message' => 'No valid images found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'images' => $base64_images,
        'format' => 'base64',
        'count' => count($base64_images)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>