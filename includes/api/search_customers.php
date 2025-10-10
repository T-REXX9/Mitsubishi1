<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(dirname(__DIR__)) . '/includes/database/db_conn.php';
// Ensure session for role/agent context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $search = $_GET['search'] ?? '';
    
    if (strlen($search) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Build dynamic SQL with optional agent filter for Sales Agents
    $baseSql = "\n        SELECT \n            ci.cusID,\n            ci.firstname,\n            ci.lastname,\n            ci.middlename,\n            ci.mobile_number,\n            ci.employment_status,\n            ci.monthly_income,\n            a.Email as email\n        FROM customer_information ci\n        LEFT JOIN accounts a ON ci.account_id = a.Id\n        WHERE (\n            CONCAT(ci.firstname, ' ', ci.lastname) LIKE ? OR\n            ci.firstname LIKE ? OR\n            ci.lastname LIKE ? OR\n            ci.mobile_number LIKE ? OR\n            a.Email LIKE ?\n        ) AND ci.Status = 'Approved'\n    ";

    $params = [];
    $searchParam = "%{$search}%";
    // 5 params for the LIKEs
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);

    $isSalesAgent = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['SalesAgent', 'Sales Agent']);
    if ($isSalesAgent) {
        $agentId = $_SESSION['user_id'] ?? null;
        if ($agentId) {
            $baseSql .= " AND ci.agent_id = ? ";
            $params[] = $agentId;
        }
    }

    $baseSql .= " ORDER BY ci.firstname, ci.lastname LIMIT 10 ";

    $stmt = $connect->prepare($baseSql);
    $stmt->execute($params);
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $customers
    ]);
    
} catch (Exception $e) {
    error_log("Customer search error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Search failed'
    ]);
}
?>
