<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['vehicle_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vehicle ID is required']);
    exit;
}

$vehicle_id = intval($_GET['vehicle_id']);

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
    
    // Check if data is JSON (new format with color-model mappings)
    $json_data = @json_decode($view_360_images, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
        // This is the new format with color-model mappings, not actual 360 images
        echo json_encode(['success' => false, 'message' => 'Vehicle uses 3D models, not 360 images']);
        exit;
    }
    
    // Try to unserialize the data (old format)
    $images = @unserialize($view_360_images);
    
    if ($images === false) {
        // If unserialize fails, try to handle as base64 encoded data
        $images = [base64_encode($view_360_images)];
    }
    
    // Ensure we have an array of images
    if (!is_array($images)) {
        $images = [$images];
    }
    
    // Convert binary data to base64 if needed
    $base64_images = [];
    foreach ($images as $image) {
        if (is_string($image)) {
            // Check if it's already base64 encoded
            if (base64_decode($image, true) !== false) {
                $base64_images[] = $image;
            } else {
                // Convert binary to base64
                $base64_images[] = base64_encode($image);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'images' => $base64_images,
        'count' => count($base64_images)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>