<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Test 1: Check if init.php can be included
    include_once(dirname(__DIR__) . '/includes/init.php');
    echo json_encode(['step' => 'init_included', 'success' => true, 'message' => 'init.php included successfully']);
} catch (Exception $e) {
    echo json_encode(['step' => 'init_error', 'success' => false, 'message' => 'Failed to include init.php: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 2: Check session
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $sessionInfo = [
        'session_id' => session_id(),
        'user_role' => $_SESSION['user_role'] ?? 'not_set',
        'user_id' => $_SESSION['user_id'] ?? 'not_set'
    ];
    
    echo json_encode(['step' => 'session_check', 'success' => true, 'session_info' => $sessionInfo]);
} catch (Exception $e) {
    echo json_encode(['step' => 'session_error', 'success' => false, 'message' => 'Session error: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 3: Check database connection
    $pdo = $GLOBALS['pdo'] ?? null;
    
    if (!$pdo) {
        echo json_encode(['step' => 'db_connection', 'success' => false, 'message' => 'Database connection not available in GLOBALS']);
        exit();
    }
    
    echo json_encode(['step' => 'db_connection', 'success' => true, 'message' => 'Database connection available']);
} catch (Exception $e) {
    echo json_encode(['step' => 'db_connection_error', 'success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 4: Check if deliveries table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'deliveries'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode(['step' => 'table_check', 'success' => false, 'message' => 'Deliveries table does not exist']);
        exit();
    }
    
    echo json_encode(['step' => 'table_check', 'success' => true, 'message' => 'Deliveries table exists']);
} catch (Exception $e) {
    echo json_encode(['step' => 'table_check_error', 'success' => false, 'message' => 'Table check error: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 5: Check if vehicles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'vehicles'");
    $vehicleTableExists = $stmt->rowCount() > 0;
    
    echo json_encode(['step' => 'vehicle_table_check', 'success' => true, 'vehicle_table_exists' => $vehicleTableExists]);
} catch (Exception $e) {
    echo json_encode(['step' => 'vehicle_table_error', 'success' => false, 'message' => 'Vehicle table check error: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 6: Try a simple query on deliveries table
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM deliveries");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode(['step' => 'simple_query', 'success' => true, 'message' => "Found $count deliveries in table"]);
} catch (Exception $e) {
    echo json_encode(['step' => 'simple_query_error', 'success' => false, 'message' => 'Simple query error: ' . $e->getMessage()]);
    exit();
}

try {
    // Test 7: Try the LEFT JOIN query
    $sql = "SELECT d.*, v.year_model, v.color_options, v.popular_color, v.base_price, v.promotional_price, v.stock_quantity
            FROM deliveries d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['step' => 'join_query', 'success' => true, 'message' => 'LEFT JOIN query successful', 'sample_result' => $result]);
} catch (Exception $e) {
    echo json_encode(['step' => 'join_query_error', 'success' => false, 'message' => 'LEFT JOIN query error: ' . $e->getMessage()]);
    exit();
}

echo json_encode(['step' => 'all_tests_passed', 'success' => true, 'message' => 'All diagnostic tests passed successfully!']);
?>