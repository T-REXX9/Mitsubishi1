<?php
/**
 * Check the status of "Completed" orders vs fully paid orders
 */

include_once(__DIR__ . '/includes/init.php');

echo "<!DOCTYPE html><html><head><title>Completed Orders Analysis</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;background:white;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#e60012;color:white;}";
echo ".fully-paid{background:#d4edda;}";
echo ".partially-paid{background:#fff3cd;}";
echo ".not-paid{background:#f8d7da;}";
echo "h1{color:#e60012;}";
echo "</style></head><body>";

echo "<h1>Completed Orders Analysis</h1>";

try {
    // Get orders with order_status = 'Completed'
    $sql = "SELECT 
                o.order_id,
                o.order_number,
                o.order_status,
                o.total_price,
                CONCAT(ci.firstname, ' ', ci.lastname) as customer_name,
                o.vehicle_model,
                COALESCE(payments.total_paid, 0) as total_paid,
                (o.total_price - COALESCE(payments.total_paid, 0)) as remaining_balance,
                CASE 
                    WHEN (o.total_price - COALESCE(payments.total_paid, 0)) = 0 THEN 'Fully Paid'
                    WHEN COALESCE(payments.total_paid, 0) > 0 THEN 'Partially Paid'
                    ELSE 'Not Paid'
                END as payment_status
            FROM orders o
            LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                FROM payment_history
                WHERE status = 'Confirmed'
                GROUP BY order_id
            ) payments ON o.order_id = payments.order_id
            WHERE o.order_status = 'Completed'
            ORDER BY o.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Orders with order_status = 'Completed' (" . count($completed_orders) . " orders)</h2>";
    
    if (count($completed_orders) > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>Order #</th>";
        echo "<th>Customer</th>";
        echo "<th>Vehicle</th>";
        echo "<th>Total Price</th>";
        echo "<th>Total Paid</th>";
        echo "<th>Remaining</th>";
        echo "<th>Payment Status</th>";
        echo "</tr>";
        
        $fully_paid_count = 0;
        $partially_paid_count = 0;
        $not_paid_count = 0;
        
        foreach ($completed_orders as $order) {
            $row_class = '';
            if ($order['payment_status'] == 'Fully Paid') {
                $row_class = 'fully-paid';
                $fully_paid_count++;
            } elseif ($order['payment_status'] == 'Partially Paid') {
                $row_class = 'partially-paid';
                $partially_paid_count++;
            } else {
                $row_class = 'not-paid';
                $not_paid_count++;
            }
            
            echo "<tr class='$row_class'>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['customer_name'] . "</td>";
            echo "<td>" . $order['vehicle_model'] . "</td>";
            echo "<td>₱" . number_format($order['total_price'], 2) . "</td>";
            echo "<td>₱" . number_format($order['total_paid'], 2) . "</td>";
            echo "<td>₱" . number_format($order['remaining_balance'], 2) . "</td>";
            echo "<td><strong>" . $order['payment_status'] . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Summary:</h3>";
        echo "<ul>";
        echo "<li><strong>Fully Paid:</strong> $fully_paid_count orders (should show in Transaction Records)</li>";
        echo "<li><strong>Partially Paid:</strong> $partially_paid_count orders</li>";
        echo "<li><strong>Not Paid:</strong> $not_paid_count orders</li>";
        echo "</ul>";
        
        echo "<h3>Recommendation:</h3>";
        if ($fully_paid_count == 0) {
            echo "<p style='color:red;'><strong>Issue:</strong> None of the 'Completed' orders are fully paid!</p>";
            echo "<p>You should either:</p>";
            echo "<ol>";
            echo "<li><strong>Change Transaction Records to use order_status</strong> (like Sales Report does), OR</li>";
            echo "<li><strong>Add payment records</strong> to mark these orders as fully paid, OR</li>";
            echo "<li><strong>Update order_status</strong> to only mark orders as 'Completed' when they're fully paid</li>";
            echo "</ol>";
        } else {
            echo "<p style='color:green;'>Good! $fully_paid_count orders are fully paid and should appear in Transaction Records.</p>";
        }
    } else {
        echo "<p>No orders found with order_status = 'Completed'</p>";
    }
    
    // Also check all orders
    echo "<hr>";
    echo "<h2>All Orders Summary</h2>";
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN order_status = 'Completed' THEN 1 END) as completed_status_count,
                COUNT(CASE WHEN (o.total_price - COALESCE(payments.total_paid, 0)) = 0 THEN 1 END) as fully_paid_count,
                COUNT(CASE WHEN (o.total_price - COALESCE(payments.total_paid, 0)) > 0 THEN 1 END) as pending_payment_count
            FROM orders o
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                FROM payment_history
                WHERE status = 'Confirmed'
                GROUP BY order_id
            ) payments ON o.order_id = payments.order_id";
    
    $stmt = $pdo->query($sql);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Total Orders</td><td>" . $summary['total_orders'] . "</td></tr>";
    echo "<tr><td>Orders with status = 'Completed'</td><td>" . $summary['completed_status_count'] . "</td></tr>";
    echo "<tr><td>Orders Fully Paid (Transaction Records logic)</td><td>" . $summary['fully_paid_count'] . "</td></tr>";
    echo "<tr><td>Orders with Pending Payments</td><td>" . $summary['pending_payment_count'] . "</td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>

