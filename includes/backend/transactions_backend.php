<?php
// Always respond with JSON
if (!headers_sent()) {
    header('Content-Type: application/json');
}

include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Require Admin or Sales Agent access
if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['user_role'] ?? ''), ['Admin', 'Sales Agent'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_transactions':
            getTransactions();
            break;
        case 'get_stats':
            getStats();
            break;
        case 'get_transaction_details':
            getTransactionDetails();
            break;
        case 'get_filters':
            getFilters();
            break;
        case 'export_transactions':
            exportTransactions();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getTransactions()
{
    global $pdo;

    // Inputs
    $status = trim(strtolower($_POST['status'] ?? $_GET['status'] ?? ''));
    $search = trim($_POST['search'] ?? $_GET['search'] ?? '');
    $model = trim($_POST['model'] ?? $_GET['model'] ?? '');
    $agent_id = trim($_POST['agent_id'] ?? $_GET['agent_id'] ?? '');
    $date_from = trim($_POST['date_from'] ?? $_GET['date_from'] ?? '');
    $date_to = trim($_POST['date_to'] ?? $_GET['date_to'] ?? '');
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    $limit = max(1, min(50, (int)($_POST['limit'] ?? $_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    // Map UI status to order_status
    if ($status !== '') {
        if ($status === 'completed') {
            // Consider commonly used completion states
            $where[] = "o.order_status IN ('completed','delivered','paid','Complete','Completed')";
        } elseif ($status === 'pending') {
            $where[] = "o.order_status IN ('pending','Processing','processing','Pending')";
        } else {
            $where[] = 'o.order_status = ?';
            $params[] = $status;
        }
    }

    if ($search !== '') {
        $where[] = "(o.order_number LIKE ? OR CONCAT(ci.firstname,' ',ci.lastname) LIKE ? OR acc.Email LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }

    if ($model !== '') {
        $where[] = "(o.vehicle_model = ? OR v.model_name = ?)";
        array_push($params, $model, $model);
    }

    // Enforce server-side agent filter for Sales Agent role
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'Sales Agent') {
        $where[] = 'o.sales_agent_id = ?';
        $params[] = $_SESSION['user_id'];
    } elseif ($agent_id !== '') {
        $where[] = 'o.sales_agent_id = ?';
        $params[] = $agent_id;
    }

    if ($date_from !== '') {
        $where[] = 'DATE(o.created_at) >= ?';
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $where[] = 'DATE(o.created_at) <= ?';
        $params[] = $date_to;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $countSql = "SELECT COUNT(*) AS total FROM orders o
                 LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
                 LEFT JOIN accounts acc ON ci.account_id = acc.Id
                 LEFT JOIN vehicles v ON o.vehicle_id = v.id
                 $whereSql";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Data query
    $sql = "SELECT 
                o.order_id,
                o.order_number,
                o.customer_id,
                o.sales_agent_id,
                o.vehicle_id,
                o.vehicle_model,
                o.vehicle_variant,
                o.model_year,
                o.total_price,
                o.order_status,
                o.payment_method,
                o.created_at,
                o.actual_delivery_date,
                ci.firstname, ci.lastname, acc.Email AS email,
                CONCAT(agent.FirstName,' ',agent.LastName) AS agent_name,
                v.model_name AS v_model_name, v.variant AS v_variant
            FROM orders o
            LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
            LEFT JOIN accounts acc ON ci.account_id = acc.Id
            LEFT JOIN accounts agent ON o.sales_agent_id = agent.Id
            LEFT JOIN vehicles v ON o.vehicle_id = v.id
            $whereSql
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?";

    $params2 = $params;
    $params2[] = $limit;
    $params2[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params2);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for UI
    $transactions = array_map(function ($r) {
        $client_name = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));
        return [
            'order_id' => (int)$r['order_id'],
            'transaction_id' => $r['order_number'],
            'client_name' => $client_name ?: 'N/A',
            'email' => $r['email'] ?? '',
            'vehicle_model' => $r['vehicle_model'] ?: ($r['v_model_name'] ?? ''),
            'variant' => $r['vehicle_variant'] ?: ($r['v_variant'] ?? ''),
            'sale_price' => (float)($r['total_price'] ?? 0),
            'agent_name' => $r['agent_name'] ?? '',
            'date_completed' => $r['actual_delivery_date'] ?: $r['created_at'],
            'payment_method' => $r['payment_method'] ?? '',
            'order_status' => $r['order_status'] ?? ''
        ];
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
}

function getStats()
{
    global $pdo;

    $status = trim(strtolower($_POST['status'] ?? $_GET['status'] ?? 'completed'));

    $where = '';
    if ($status === 'completed') {
        $where = "WHERE o.order_status IN ('completed','delivered','paid','Complete','Completed')";
    } elseif ($status === 'pending') {
        $where = "WHERE o.order_status IN ('pending','Processing','processing','Pending')";
    }

    // Sales Agent restriction
    $role = $_SESSION['user_role'] ?? '';
    $params = [];
    if ($role === 'Sales Agent') {
        $where = $where ? ($where . ' AND o.sales_agent_id = ?') : 'WHERE o.sales_agent_id = ?';
        $params[] = $_SESSION['user_id'];
    }

    // Total transactions
    $totalSql = "SELECT COUNT(*) AS cnt FROM orders o $where";
    $stmt = $pdo->prepare($totalSql);
    $stmt->execute($params);
    $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    // This month count
    $monthFilter = "DATE_FORMAT(o.created_at,'%Y-%m') = DATE_FORMAT(CURRENT_DATE(),'%Y-%m')";
    $monthSql = "SELECT COUNT(*) AS cnt FROM orders o " . ($where ? ($where . ' AND ' . $monthFilter) : ('WHERE ' . $monthFilter));
    $stmt = $pdo->prepare($monthSql);
    $stmt->execute($params);
    $month = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    // Total sales and avg
    $sumSql = "SELECT COALESCE(SUM(o.total_price),0) AS total_sales, COALESCE(AVG(o.total_price),0) AS avg_sale FROM orders o $where";
    $stmt = $pdo->prepare($sumSql);
    $stmt->execute($params);
    $sum = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'this_month' => $month,
            'total_sales_value' => (float)($sum['total_sales'] ?? 0),
            'avg_sale' => (float)($sum['avg_sale'] ?? 0)
        ]
    ]);
}

function getTransactionDetails()
{
    global $pdo;

    $order_id = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'order_id is required']);
        return;
    }

    $sql = "SELECT 
                o.*, 
                ci.firstname, ci.lastname, ci.mobile_number,
                acc.Email AS email,
                CONCAT(agent.FirstName,' ',agent.LastName) AS agent_name,
                agent.Email AS agent_email,
                v.model_name, v.variant, v.main_image
            FROM orders o
            LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
            LEFT JOIN accounts acc ON ci.account_id = acc.Id
            LEFT JOIN accounts agent ON o.sales_agent_id = agent.Id
            LEFT JOIN vehicles v ON o.vehicle_id = v.id
            WHERE o.order_id = ?";

    $params = [$order_id];
    // If Sales Agent, ensure the order belongs to them
    if (($_SESSION['user_role'] ?? '') === 'Sales Agent') {
        $sql .= " AND o.sales_agent_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $row]);
}

function getFilters()
{
    global $pdo;
    // Agents (restrict for Sales Agent role)
    $agents = [];
    if (($_SESSION['user_role'] ?? '') === 'Sales Agent') {
        $agents = [['Id' => $_SESSION['user_id'], 'name' => ($_SESSION['user_name'] ?? 'You')]];
    } else {
        $stmtA = $pdo->query("SELECT Id, CONCAT(FirstName,' ',LastName) AS name FROM accounts WHERE Role = 'Sales Agent' ORDER BY FirstName, LastName");
        if ($stmtA) { $agents = $stmtA->fetchAll(PDO::FETCH_ASSOC) ?: []; }
    }

    // Models
    $models = [];
    try {
        if (($_SESSION['user_role'] ?? '') === 'Sales Agent') {
            $stmtM = $pdo->prepare("SELECT DISTINCT COALESCE(o.vehicle_model, v.model_name) AS model
                                     FROM orders o LEFT JOIN vehicles v ON o.vehicle_id = v.id
                                     WHERE o.sales_agent_id = ? AND COALESCE(o.vehicle_model, v.model_name) IS NOT NULL AND COALESCE(o.vehicle_model, v.model_name) <> ''
                                     ORDER BY model");
            $stmtM->execute([$_SESSION['user_id']]);
        } else {
            $stmtM = $pdo->query("SELECT DISTINCT COALESCE(vehicle_model, model_name) AS model FROM orders o LEFT JOIN vehicles v ON o.vehicle_id = v.id WHERE COALESCE(vehicle_model, model_name) IS NOT NULL AND COALESCE(vehicle_model, model_name) <> '' ORDER BY model");
        }
        if ($stmtM) { $models = array_map(function($r){ return $r['model']; }, $stmtM->fetchAll(PDO::FETCH_ASSOC) ?: []); }
    } catch (Throwable $e) {
        // Fallback to vehicles table only
        $stmtM2 = $pdo->query("SELECT DISTINCT model_name AS model FROM vehicles WHERE model_name IS NOT NULL AND model_name <> '' ORDER BY model_name");
        if ($stmtM2) { $models = array_map(function($r){ return $r['model']; }, $stmtM2->fetchAll(PDO::FETCH_ASSOC) ?: []); }
    }

    echo json_encode(['success' => true, 'data' => [ 'agents' => $agents, 'models' => $models ]]);
}

function exportTransactions()
{
    global $pdo;

    // Collect same filters as getTransactions
    $status = trim(strtolower($_POST['status'] ?? $_GET['status'] ?? ''));
    $search = trim($_POST['search'] ?? $_GET['search'] ?? '');
    $model = trim($_POST['model'] ?? $_GET['model'] ?? '');
    $agent_id = trim($_POST['agent_id'] ?? $_GET['agent_id'] ?? '');
    $date_from = trim($_POST['date_from'] ?? $_GET['date_from'] ?? '');
    $date_to = trim($_POST['date_to'] ?? $_GET['date_to'] ?? '');

    $where = [];
    $params = [];
    if ($status !== '') {
        if ($status === 'completed') {
            $where[] = "o.order_status IN ('completed','delivered','paid','Complete','Completed')";
        } elseif ($status === 'pending') {
            $where[] = "o.order_status IN ('pending','Processing','processing','Pending')";
        } else {
            $where[] = 'o.order_status = ?';
            $params[] = $status;
        }
    }
    if ($search !== '') {
        $where[] = "(o.order_number LIKE ? OR CONCAT(ci.firstname,' ',ci.lastname) LIKE ? OR acc.Email LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }
    if ($model !== '') {
        $where[] = "(o.vehicle_model = ? OR v.model_name = ?)";
        array_push($params, $model, $model);
    }
    if ($agent_id !== '') {
        $where[] = 'o.sales_agent_id = ?';
        $params[] = $agent_id;
    }
    if ($date_from !== '') { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $date_from; }
    if ($date_to !== '') { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $date_to; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT 
                o.order_number AS TransactionID,
                CONCAT(ci.firstname,' ',ci.lastname) AS ClientName,
                acc.Email AS Email,
                COALESCE(o.vehicle_model, v.model_name) AS VehicleModel,
                COALESCE(o.vehicle_variant, v.variant) AS Variant,
                o.model_year AS ModelYear,
                o.total_price AS SalePrice,
                CONCAT(agent.FirstName,' ',agent.LastName) AS AgentName,
                o.payment_method AS PaymentMethod,
                o.order_status AS OrderStatus,
                o.created_at AS CreatedAt,
                o.actual_delivery_date AS CompletedAt
            FROM orders o
            LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
            LEFT JOIN accounts acc ON ci.account_id = acc.Id
            LEFT JOIN accounts agent ON o.sales_agent_id = agent.Id
            LEFT JOIN vehicles v ON o.vehicle_id = v.id
            $whereSql
            ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Output CSV
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transactions_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) { fputcsv($out, $r); }
    } else {
        fputcsv($out, ['No data']);
    }
    fclose($out);
    exit;
}
