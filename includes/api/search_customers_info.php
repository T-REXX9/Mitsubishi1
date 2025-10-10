<?php
header('Content-Type: application/json');
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = $GLOBALS['pdo'] ?? null;
    
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }

    $search = $_GET['search'] ?? '';
    
    if (strlen($search) < 2) {
        echo json_encode(['success' => false, 'message' => 'Search term too short']);
        exit();
    }
    
    // Build the SQL query to search customers
    $sql = "SELECT cusID, firstname, lastname, middlename, email, mobile_number, 
                   valid_id_type, valid_id_number, customer_type, employment_status, 
                   company_name, monthly_income 
            FROM customers 
            WHERE firstname LIKE ? 
               OR lastname LIKE ? 
               OR CONCAT(firstname, ' ', lastname) LIKE ? 
               OR mobile_number LIKE ? 
               OR email LIKE ?
               OR valid_id_number LIKE ?
            ORDER BY firstname ASC, lastname ASC
            LIMIT 10";
    
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $customers,
        'count' => count($customers)
    ]);

} catch (Exception $e) {
    error_log("Error in search_customers_info.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to search customers',
        'error' => $e->getMessage()
    ]);
}
?>
?>
