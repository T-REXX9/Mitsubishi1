<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once(dirname(__DIR__) . '/includes/init.php');

// Check if user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Use the database connection from init.php
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit();
}

// Get request parameters
$action = $_GET['action'] ?? 'list';
$search = $_GET['search'] ?? '';
$month = $_GET['month'] ?? '';
$vehicle = $_GET['vehicle'] ?? '';
$agent = $_GET['agent'] ?? '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

try {
    switch ($action) {
        case 'list':
            echo json_encode(getSoldUnitsList($pdo, $search, $month, $vehicle, $agent, $limit, $offset));
            break;
        case 'filters':
            echo json_encode(getFilterOptions($pdo));
            break;
        case 'summary':
            echo json_encode(getSoldUnitsSummary($pdo, $month, $vehicle, $agent));
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Get list of sold units with filtering
 */
function getSoldUnitsList($pdo, $search, $month, $vehicle, $agent, $limit, $offset) {
    // Build the WHERE clause
    $where = ["o.order_status IN ('Completed', 'Delivered')"];
    $params = [];
    
    // Search filter (agent name or vehicle name)
    if (!empty($search)) {
        $where[] = "(CONCAT(a.FirstName, ' ', a.LastName) LIKE :search OR o.vehicle_model LIKE :search OR o.vehicle_variant LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Month filter
    if (!empty($month)) {
        $where[] = "DATE_FORMAT(o.created_at, '%Y-%m') = :month";
        $params[':month'] = $month;
    }
    
    // Vehicle filter
    if (!empty($vehicle)) {
        $where[] = "o.vehicle_model = :vehicle";
        $params[':vehicle'] = $vehicle;
    }
    
    // Agent filter
    if (!empty($agent)) {
        $where[] = "o.sales_agent_id = :agent";
        $params[':agent'] = $agent;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM orders o
                 LEFT JOIN accounts a ON o.sales_agent_id = a.Id
                 WHERE $whereClause";
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get the data
    $sql = "SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as sale_month,
                DATE_FORMAT(o.created_at, '%M %Y') as month_display,
                CONCAT(a.FirstName, ' ', a.LastName) as agent_name,
                a.Id as agent_id,
                o.vehicle_model,
                o.vehicle_variant,
                CONCAT(o.vehicle_model, ' ', o.vehicle_variant) as vehicle_full_name,
                COUNT(*) as units_sold,
                SUM(o.total_price) as total_value,
                MIN(o.created_at) as first_sale_date,
                MAX(o.created_at) as last_sale_date
            FROM orders o
            LEFT JOIN accounts a ON o.sales_agent_id = a.Id
            WHERE $whereClause
            GROUP BY sale_month, agent_name, a.Id, o.vehicle_model, o.vehicle_variant
            ORDER BY sale_month DESC, units_sold DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results
    foreach ($results as &$row) {
        $row['units_sold'] = intval($row['units_sold']);
        $row['total_value'] = floatval($row['total_value']);
        $row['formatted_value'] = '₱' . number_format($row['total_value'], 2);
    }
    
    return [
        'success' => true,
        'data' => $results,
        'total' => intval($total),
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'search' => $search,
            'month' => $month,
            'vehicle' => $vehicle,
            'agent' => $agent
        ]
    ];
}

/**
 * Get filter options for dropdowns
 */
function getFilterOptions($pdo) {
    // Get available months
    $monthsSql = "SELECT DISTINCT 
                    DATE_FORMAT(created_at, '%Y-%m') as value,
                    DATE_FORMAT(created_at, '%M %Y') as label
                  FROM orders
                  WHERE order_status IN ('Completed', 'Delivered')
                  ORDER BY value DESC
                  LIMIT 24"; // Last 24 months
    
    $stmt = $pdo->prepare($monthsSql);
    $stmt->execute();
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available vehicles
    $vehiclesSql = "SELECT DISTINCT vehicle_model as value, vehicle_model as label
                    FROM orders
                    WHERE order_status IN ('Completed', 'Delivered')
                    AND vehicle_model IS NOT NULL
                    ORDER BY vehicle_model ASC";
    
    $stmt = $pdo->prepare($vehiclesSql);
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available agents
    $agentsSql = "SELECT DISTINCT 
                    a.Id as value,
                    CONCAT(a.FirstName, ' ', a.LastName) as label
                  FROM orders o
                  INNER JOIN accounts a ON o.sales_agent_id = a.Id
                  WHERE o.order_status IN ('Completed', 'Delivered')
                  AND a.Role = 'SalesAgent'
                  ORDER BY label ASC";
    
    $stmt = $pdo->prepare($agentsSql);
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'months' => $months,
        'vehicles' => $vehicles,
        'agents' => $agents
    ];
}

/**
 * Get summary statistics for sold units
 */
function getSoldUnitsSummary($pdo, $month, $vehicle, $agent) {
    // Build the WHERE clause
    $where = ["o.order_status IN ('Completed', 'Delivered')"];
    $params = [];
    
    if (!empty($month)) {
        $where[] = "DATE_FORMAT(o.created_at, '%Y-%m') = :month";
        $params[':month'] = $month;
    }
    
    if (!empty($vehicle)) {
        $where[] = "o.vehicle_model = :vehicle";
        $params[':vehicle'] = $vehicle;
    }
    
    if (!empty($agent)) {
        $where[] = "o.sales_agent_id = :agent";
        $params[':agent'] = $agent;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get summary statistics
    $sql = "SELECT 
                COUNT(*) as total_units_sold,
                COUNT(DISTINCT o.sales_agent_id) as total_agents,
                COUNT(DISTINCT o.vehicle_model) as total_models,
                SUM(o.total_price) as total_revenue,
                AVG(o.total_price) as avg_sale_value,
                MIN(o.created_at) as earliest_sale,
                MAX(o.created_at) as latest_sale
            FROM orders o
            WHERE $whereClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the results
    $summary['total_units_sold'] = intval($summary['total_units_sold']);
    $summary['total_agents'] = intval($summary['total_agents']);
    $summary['total_models'] = intval($summary['total_models']);
    $summary['total_revenue'] = floatval($summary['total_revenue']);
    $summary['avg_sale_value'] = floatval($summary['avg_sale_value']);
    $summary['formatted_revenue'] = '₱' . number_format($summary['total_revenue'], 2);
    $summary['formatted_avg'] = '₱' . number_format($summary['avg_sale_value'], 2);
    
    return [
        'success' => true,
        'summary' => $summary
    ];
}

