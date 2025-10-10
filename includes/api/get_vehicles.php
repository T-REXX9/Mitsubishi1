<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(dirname(__DIR__)) . '/includes/init.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = $GLOBALS['pdo'] ?? null;
    
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }

    $search = $_GET['search'] ?? '';
    
    // Build the SQL query
    $sql = "SELECT id, model_name, variant, year_model, category, engine_type, 
                   transmission, fuel_type, seating_capacity, base_price, 
                   promotional_price, stock_quantity, min_stock_alert, 
                   availability_status, main_image, key_features, color_options 
            FROM vehicles 
            WHERE availability_status = 'available'";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (model_name LIKE ? OR variant LIKE ? OR category LIKE ? OR fuel_type LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    $sql .= " ORDER BY model_name ASC, variant ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle main_image - check if it's a file path or binary data
    foreach ($vehicles as &$vehicle) {
        if ($vehicle['main_image']) {
            // If it looks like a file path, keep it as is
            if (strpos($vehicle['main_image'], 'uploads') !== false || strpos($vehicle['main_image'], '.png') !== false || strpos($vehicle['main_image'], '.jpg') !== false || strpos($vehicle['main_image'], '.jpeg') !== false) {
                // It's a file path, keep as is
                $vehicle['main_image'] = $vehicle['main_image'];
            } else {
                // It's binary data, convert to base64
                $vehicle['main_image'] = base64_encode($vehicle['main_image']);
            }
        }
        
        // Ensure numeric fields are properly formatted
        $vehicle['base_price'] = floatval($vehicle['base_price']);
        $vehicle['promotional_price'] = floatval($vehicle['promotional_price']);
        $vehicle['stock_quantity'] = intval($vehicle['stock_quantity']);
        $vehicle['min_stock_alert'] = intval($vehicle['min_stock_alert']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $vehicles,
        'count' => count($vehicles)
    ]);

} catch (Exception $e) {
    error_log("Error in get_vehicles.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch vehicles',
        'error' => $e->getMessage()
    ]);
}
?>
