<?php
/**
 * Diagnostic script to check transaction records data
 * This will help identify why the transaction records page shows no data
 */

// Include database connection
include_once(__DIR__ . '/includes/init.php');

echo "<!DOCTYPE html><html><head><title>Transaction Data Diagnostic</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".section{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "h2{color:#e60012;border-bottom:2px solid #e60012;padding-bottom:10px;}";
echo "table{width:100%;border-collapse:collapse;margin:10px 0;}";
echo "th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd;}";
echo "th{background:#f8f8f8;font-weight:bold;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".info{color:blue;}";
echo "pre{background:#f8f8f8;padding:10px;border-radius:4px;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>🔍 Transaction Records Diagnostic Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. Check database connection
    echo "<div class='section'>";
    echo "<h2>1. Database Connection</h2>";
    if ($pdo) {
        echo "<p class='success'>✓ Database connection successful</p>";
        echo "<p>Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
    }
    echo "</div>";

    // 2. Check if orders table exists and has data
    echo "<div class='section'>";
    echo "<h2>2. Orders Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Orders table exists</p>";
        
        // Count total orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total orders: <strong>" . $count . "</strong></p>";
        
        if ($count > 0) {
            // Show sample orders
            echo "<h3>Sample Orders (first 5):</h3>";
            $stmt = $pdo->query("SELECT order_id, order_number, customer_id, vehicle_model, total_price, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Order ID</th><th>Order Number</th><th>Customer ID</th><th>Vehicle</th><th>Total Price</th><th>Status</th><th>Created</th></tr>";
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['order_id'] . "</td>";
                echo "<td>" . $order['order_number'] . "</td>";
                echo "<td>" . $order['customer_id'] . "</td>";
                echo "<td>" . $order['vehicle_model'] . "</td>";
                echo "<td>₱" . number_format($order['total_price'], 2) . "</td>";
                echo "<td>" . $order['order_status'] . "</td>";
                echo "<td>" . $order['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No orders found in database</p>";
        }
    } else {
        echo "<p class='error'>✗ Orders table does not exist</p>";
    }
    echo "</div>";

    // 3. Check payment_history table
    echo "<div class='section'>";
    echo "<h2>3. Payment History Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_history'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Payment history table exists</p>";
        
        // Count total payments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_history");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total payment records: <strong>" . $count . "</strong></p>";
        
        // Count confirmed payments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_history WHERE status = 'Confirmed'");
        $confirmedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Confirmed payments: <strong>" . $confirmedCount . "</strong></p>";
        
        if ($count > 0) {
            // Show sample payments
            echo "<h3>Sample Payments (first 5):</h3>";
            $stmt = $pdo->query("SELECT id, order_id, payment_number, amount_paid, payment_type, status, payment_date FROM payment_history ORDER BY payment_date DESC LIMIT 5");
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Order ID</th><th>Payment #</th><th>Amount</th><th>Type</th><th>Status</th><th>Date</th></tr>";
            foreach ($payments as $payment) {
                echo "<tr>";
                echo "<td>" . $payment['id'] . "</td>";
                echo "<td>" . $payment['order_id'] . "</td>";
                echo "<td>" . $payment['payment_number'] . "</td>";
                echo "<td>₱" . number_format($payment['amount_paid'], 2) . "</td>";
                echo "<td>" . $payment['payment_type'] . "</td>";
                echo "<td>" . $payment['status'] . "</td>";
                echo "<td>" . $payment['payment_date'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ No payment records found</p>";
        }
    } else {
        echo "<p class='error'>✗ Payment history table does not exist</p>";
    }
    echo "</div>";

    // 4. Test the completed transactions query
    echo "<div class='section'>";
    echo "<h2>4. Completed Transactions Query Test</h2>";
    
    $sql = "SELECT COUNT(*) as count FROM orders o
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                FROM payment_history
                WHERE status = 'Confirmed'
                GROUP BY order_id
            ) payments ON o.order_id = payments.order_id
            WHERE (o.total_price - COALESCE(payments.total_paid, 0)) = 0";
    
    $stmt = $pdo->query($sql);
    $completedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Completed transactions (fully paid): <strong>" . $completedCount . "</strong></p>";
    
    if ($completedCount > 0) {
        echo "<p class='success'>✓ Found completed transactions</p>";
    } else {
        echo "<p class='warning'>⚠ No completed transactions found (no orders are fully paid)</p>";
    }
    echo "</div>";

    // 5. Test the pending transactions query
    echo "<div class='section'>";
    echo "<h2>5. Pending Transactions Query Test</h2>";
    
    $sql = "SELECT COUNT(*) as count FROM orders o
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                FROM payment_history
                WHERE status = 'Confirmed'
                GROUP BY order_id
            ) payments ON o.order_id = payments.order_id
            WHERE (o.total_price - COALESCE(payments.total_paid, 0)) > 0";
    
    $stmt = $pdo->query($sql);
    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Pending transactions (not fully paid): <strong>" . $pendingCount . "</strong></p>";
    
    if ($pendingCount > 0) {
        echo "<p class='success'>✓ Found pending transactions</p>";
    } else {
        echo "<p class='warning'>⚠ No pending transactions found</p>";
    }
    echo "</div>";

    // 6. Test actual API query
    echo "<div class='section'>";
    echo "<h2>6. Full API Query Test (Completed)</h2>";
    
    $sql = "SELECT 
                o.order_id,
                o.order_number,
                o.total_price,
                COALESCE(payments.total_paid, 0) as total_paid,
                (o.total_price - COALESCE(payments.total_paid, 0)) as remaining_balance
            FROM orders o
            LEFT JOIN (
                SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                FROM payment_history
                WHERE status = 'Confirmed'
                GROUP BY order_id
            ) payments ON o.order_id = payments.order_id
            WHERE (o.total_price - COALESCE(payments.total_paid, 0)) = 0
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "<p class='success'>✓ Query returned " . count($results) . " results</p>";
        echo "<table>";
        echo "<tr><th>Order ID</th><th>Order Number</th><th>Total Price</th><th>Total Paid</th><th>Remaining</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . $row['order_id'] . "</td>";
            echo "<td>" . $row['order_number'] . "</td>";
            echo "<td>₱" . number_format($row['total_price'], 2) . "</td>";
            echo "<td>₱" . number_format($row['total_paid'], 2) . "</td>";
            echo "<td>₱" . number_format($row['remaining_balance'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ Query returned no results</p>";
    }
    echo "</div>";

    // 7. Check customer_information table
    echo "<div class='section'>";
    echo "<h2>7. Customer Information Table</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer_information");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total customer records: <strong>" . $count . "</strong></p>";
    echo "</div>";

    // 8. Summary and recommendations
    echo "<div class='section'>";
    echo "<h2>8. Summary & Recommendations</h2>";
    
    $issues = [];
    $recommendations = [];
    
    // Check for data issues
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($orderCount == 0) {
        $issues[] = "No orders exist in the database";
        $recommendations[] = "Create test orders or import sample data";
    }
    
    if ($completedCount == 0 && $pendingCount == 0 && $orderCount > 0) {
        $issues[] = "Orders exist but none match the completed/pending criteria";
        $recommendations[] = "Check if orders have payment records or adjust the query logic";
    }
    
    if ($completedCount == 0 && $orderCount > 0) {
        $issues[] = "No completed (fully paid) transactions found";
        $recommendations[] = "Add payment records to mark orders as fully paid, or check if payment calculation is correct";
    }
    
    if (count($issues) > 0) {
        echo "<h3 class='error'>Issues Found:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>" . $issue . "</li>";
        }
        echo "</ul>";
        
        echo "<h3 class='info'>Recommendations:</h3>";
        echo "<ul>";
        foreach ($recommendations as $rec) {
            echo "<li class='info'>" . $rec . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='success'>✓ All checks passed! Data should be displaying correctly.</p>";
        echo "<p class='info'>If the page still shows 'Loading...', check:</p>";
        echo "<ul>";
        echo "<li>Browser console for JavaScript errors</li>";
        echo "<li>Network tab for failed API requests</li>";
        echo "<li>Session/authentication issues</li>";
        echo "</ul>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>An error occurred: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>

