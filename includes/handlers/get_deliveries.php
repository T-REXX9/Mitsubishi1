<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Check if the deliveries table exists
    $checkTableQuery = "SHOW TABLES LIKE 'deliveries'";
    $checkTableStmt = $connect->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() === 0) {
        // Table doesn't exist, return empty data
        echo json_encode([
            'status' => 'success',
            'message' => 'Deliveries table does not exist yet',
            'deliveries' => [],
            'currentPage' => 1,
            'totalPages' => 0,
            'totalRecords' => 0,
            'totals' => [
                'total_units' => 0,
                'total_value' => 0,
                'delivery_days' => 0,
                'total_deliveries' => 0
            ]
        ]);
        exit;
    }

    // Get pagination and filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $model = isset($_GET['model']) ? $_GET['model'] : '';
    $supplier = isset($_GET['supplier']) ? $_GET['supplier'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $receivedBy = isset($_GET['received_by']) ? $_GET['received_by'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Sorting parameters
    $sortField = 'delivery_date';
    $sortOrder = 'DESC';
    
    if (isset($_GET['sort'])) {
        $sortParams = explode('|', $_GET['sort']);
        if (count($sortParams) == 2) {
            $allowedFields = ['delivery_date', 'units_delivered', 'total_value', 'supplier_dealer'];
            $allowedOrders = ['asc', 'desc'];
            
            if (in_array($sortParams[0], $allowedFields) && in_array(strtolower($sortParams[1]), $allowedOrders)) {
                $sortField = $sortParams[0];
                $sortOrder = strtoupper($sortParams[1]);
            }
        }
    }
    
    // Build SQL query with filters
    $sql = "SELECT * FROM deliveries WHERE 1=1";
    $params = [];
    
    // Add filters if provided
    if (!empty($startDate)) {
        $sql .= " AND delivery_date >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND delivery_date <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    if (!empty($model)) {
        $sql .= " AND model_name = :model";
        $params[':model'] = $model;
    }
    
    if (!empty($supplier)) {
        $sql .= " AND supplier_dealer = :supplier";
        $params[':supplier'] = $supplier;
    }
    
    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($receivedBy)) {
        $sql .= " AND received_by LIKE :received_by";
        $params[':received_by'] = "%$receivedBy%";
    }
    
    if (!empty($search)) {
        $sql .= " AND (delivery_reference LIKE :search OR supplier_dealer LIKE :search OR model_name LIKE :search OR variant LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Count total records for pagination
    $countStmt = $connect->prepare(str_replace("SELECT *", "SELECT COUNT(*)", $sql));
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Add sorting and pagination
    $sql .= " ORDER BY $sortField $sortOrder LIMIT :limit OFFSET :offset";
    
    $stmt = $connect->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalsSql = "SELECT 
                    SUM(units_delivered) as total_units,
                    SUM(total_value) as total_value,
                    COUNT(DISTINCT DATE(delivery_date)) as delivery_days,
                    COUNT(*) as total_deliveries
                  FROM deliveries WHERE 1=1";
    
    // Apply same filters for totals
    if (!empty($startDate)) {
        $totalsSql .= " AND delivery_date >= :start_date";
    }
    if (!empty($endDate)) {
        $totalsSql .= " AND delivery_date <= :end_date";
    }
    if (!empty($model)) {
        $totalsSql .= " AND model_name = :model";
    }
    if (!empty($supplier)) {
        $totalsSql .= " AND supplier_dealer = :supplier";
    }
    if (!empty($status)) {
        $totalsSql .= " AND status = :status";
    }
    if (!empty($receivedBy)) {
        $totalsSql .= " AND received_by LIKE :received_by";
    }
    if (!empty($search)) {
        $totalsSql .= " AND (delivery_reference LIKE :search OR supplier_dealer LIKE :search OR model_name LIKE :search OR variant LIKE :search)";
    }
    
    $totalsStmt = $connect->prepare($totalsSql);
    foreach ($params as $key => $value) {
        if (strpos($totalsSql, $key) !== false) {
            $totalsStmt->bindValue($key, $value);
        }
    }
    $totalsStmt->execute();
    $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total pages
    $totalPages = ceil($totalRecords / $limit);
    
    // Return response
    echo json_encode([
        'status' => 'success',
        'deliveries' => $deliveries,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords,
        'totals' => $totals
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
