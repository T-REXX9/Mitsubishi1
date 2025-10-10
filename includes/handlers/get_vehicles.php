<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Get filter parameters
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build SQL query with filters
    $sql = "SELECT id, model_name, variant, year_model, category, base_price, 
                   stock_quantity, availability_status, updated_at 
            FROM vehicles 
            WHERE 1=1";
    
    $params = [];
    
    // Add filters if provided
    if (!empty($category)) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($status)) {
        $sql .= " AND availability_status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($search)) {
        $sql .= " AND (model_name LIKE :search OR variant LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Count total records for pagination
    $countStmt = $connect->prepare(str_replace("SELECT id, model_name, variant, year_model, category, base_price, 
                   stock_quantity, availability_status, updated_at", "SELECT COUNT(*)", $sql));
    
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Add pagination to the main query
    $sql .= " ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $connect->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total pages
    $totalPages = ceil($totalRecords / $limit);
    
    // Return response
    echo json_encode([
        'status' => 'success',
        'vehicles' => $vehicles,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
