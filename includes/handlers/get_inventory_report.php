<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

// Check if report type is provided
if (!isset($_GET['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Report type is required']);
    exit;
}

$reportType = $_GET['type'];
$allowedTypes = ['low_stock', 'deliveries', 'value'];

if (!in_array($reportType, $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
    exit;
}

try {
    switch ($reportType) {
        case 'low_stock':
            // Get low stock items
            $query = "SELECT 
                        id, model_name, variant, stock_quantity, min_stock_alert,
                        availability_status, updated_at
                      FROM vehicles
                      WHERE (stock_quantity <= min_stock_alert AND min_stock_alert > 0) 
                         OR stock_quantity <= 0
                      ORDER BY (stock_quantity / min_stock_alert) ASC, stock_quantity ASC";
            
            $stmt = $connect->prepare($query);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'report_type' => 'low_stock',
                'items' => $items,
                'count' => count($items)
            ]);
            break;
            
        case 'deliveries':
            // Get recent deliveries (last 30 days)
            $query = "SELECT 
                        id, delivery_date, delivery_reference, model_name, variant,
                        units_delivered, unit_price, total_value, supplier_dealer, status
                      FROM deliveries
                      WHERE delivery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                      ORDER BY delivery_date DESC
                      LIMIT 20";
            
            $stmt = $connect->prepare($query);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get summary info
            $summaryQuery = "SELECT 
                              SUM(units_delivered) as total_units,
                              SUM(total_value) as total_value,
                              COUNT(DISTINCT DATE(delivery_date)) as delivery_days
                            FROM deliveries
                            WHERE delivery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
            
            $summaryStmt = $connect->prepare($summaryQuery);
            $summaryStmt->execute();
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'report_type' => 'deliveries',
                'items' => $items,
                'count' => count($items),
                'summary' => $summary
            ]);
            break;
            
        case 'value':
            // Get inventory value by category
            $query = "SELECT 
                        category, 
                        COUNT(*) as model_count,
                        SUM(stock_quantity) as total_units,
                        ROUND(AVG(base_price), 2) as avg_price,
                        SUM(stock_quantity * base_price) as total_value
                      FROM vehicles
                      GROUP BY category
                      ORDER BY total_value DESC";
            
            $stmt = $connect->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get totals
            $totalsQuery = "SELECT 
                              COUNT(DISTINCT model_name) as total_models,
                              SUM(stock_quantity) as total_units,
                              ROUND(AVG(base_price), 2) as avg_price,
                              SUM(stock_quantity * base_price) as total_value
                            FROM vehicles";
            
            $totalsStmt = $connect->prepare($totalsQuery);
            $totalsStmt->execute();
            $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'report_type' => 'value',
                'categories' => $categories,
                'totals' => $totals
            ]);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
