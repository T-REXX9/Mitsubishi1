<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Query to get inventory summary
    $querySummary = "SELECT 
        SUM(stock_quantity) as total_units,
        COUNT(DISTINCT model_name) as total_models,
        COUNT(*) as total_variants,
        SUM(CASE WHEN stock_quantity <= min_stock_alert THEN 1 ELSE 0 END) as low_stock_alerts,
        SUM(stock_quantity * base_price) as total_inventory_value
    FROM vehicles
    WHERE availability_status != 'discontinued'";
    
    $stmtSummary = $connect->prepare($querySummary);
    $stmtSummary->execute();
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);
    
    // Query to get models by category
    $queryByCategory = "SELECT 
        category,
        COUNT(*) as model_count,
        SUM(stock_quantity) as category_units
    FROM vehicles
    WHERE availability_status != 'discontinued'
    GROUP BY category
    ORDER BY category_units DESC";
    
    $stmtByCategory = $connect->prepare($queryByCategory);
    $stmtByCategory->execute();
    $categorySummary = $stmtByCategory->fetchAll(PDO::FETCH_ASSOC);
    
    // Query to get recent deliveries
    $queryRecentDeliveries = "SELECT 
        SUM(units_delivered) as recent_units_delivered,
        COUNT(*) as recent_delivery_count,
        MAX(delivery_date) as last_delivery_date
    FROM deliveries
    WHERE delivery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
    
    $stmtRecentDeliveries = $connect->prepare($queryRecentDeliveries);
    $stmtRecentDeliveries->execute();
    $recentDeliveries = $stmtRecentDeliveries->fetch(PDO::FETCH_ASSOC);
    
    // Query to get low stock items
    $queryLowStock = "SELECT 
        id,
        model_name,
        variant,
        stock_quantity,
        min_stock_alert,
        base_price
    FROM vehicles
    WHERE stock_quantity <= min_stock_alert 
        AND min_stock_alert > 0
        AND availability_status != 'discontinued'
    ORDER BY (stock_quantity / min_stock_alert) ASC
    LIMIT 5";
    
    $stmtLowStock = $connect->prepare($queryLowStock);
    $stmtLowStock->execute();
    $lowStockItems = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);
    
    // Return all data
    echo json_encode([
        'status' => 'success',
        'summary' => $summary,
        'by_category' => $categorySummary,
        'recent_deliveries' => $recentDeliveries,
        'low_stock_items' => $lowStockItems
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
