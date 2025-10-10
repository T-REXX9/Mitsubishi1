<?php
session_start();
require_once 'includes/database/db_conn.php';

// Set up test session (replace with actual customer account ID)
$_SESSION['user_id'] = 1; // Adjust this to match a real customer account ID

echo "<h2>Debug System-Generated Orders</h2>";

// Find system-generated orders
try {
    $sql = "SELECT o.order_id, o.order_number, o.customer_id, o.vehicle_model, 
                   o.down_payment, o.financing_term, o.monthly_payment, o.payment_method,
                   o.total_price, o.order_status, o.created_at,
                   ci.cusID, ci.account_id
            FROM orders o 
            LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
            WHERE o.order_number LIKE 'LOAN-%' 
            ORDER BY o.created_at DESC 
            LIMIT 5";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Found " . count($orders) . " system-generated orders:</h3>";
    
    foreach ($orders as $order) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h4>Order ID: {$order['order_id']} - {$order['order_number']}</h4>";
        echo "<p><strong>Customer ID:</strong> {$order['customer_id']}</p>";
        echo "<p><strong>Account ID:</strong> {$order['account_id']}</p>";
        echo "<p><strong>Vehicle:</strong> {$order['vehicle_model']}</p>";
        echo "<p><strong>Payment Method:</strong> {$order['payment_method']}</p>";
        echo "<p><strong>Down Payment:</strong> " . ($order['down_payment'] ?? 'NULL') . "</p>";
        echo "<p><strong>Financing Term:</strong> " . ($order['financing_term'] ?? 'NULL') . "</p>";
        echo "<p><strong>Monthly Payment:</strong> " . ($order['monthly_payment'] ?? 'NULL') . "</p>";
        echo "<p><strong>Total Price:</strong> {$order['total_price']}</p>";
        echo "<p><strong>Status:</strong> {$order['order_status']}</p>";
        echo "<p><strong>Created:</strong> {$order['created_at']}</p>";
        
        // Test API calls for this order
        if ($order['account_id']) {
            $_SESSION['user_id'] = $order['account_id'];
            echo "<h5>Testing API calls for this order:</h5>";
            
            // Test get_order_details
            echo "<p><strong>Testing get_order_details:</strong></p>";
            $_GET['order_id'] = $order['order_id'];
            $_GET['action'] = 'get_order_details';
            
            ob_start();
            try {
                include 'includes/backend/order_backend.php';
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
            }
            $output = ob_get_clean();
            
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars($output);
            echo "</pre>";
            
            // Check if payment_schedule table exists and has data for this order
            echo "<p><strong>Payment Schedule Check:</strong></p>";
            try {
                $checkTable = $connect->query("SHOW TABLES LIKE 'payment_schedule'");
                if ($checkTable->rowCount() > 0) {
                    $scheduleStmt = $connect->prepare("SELECT COUNT(*) as count FROM payment_schedule WHERE order_id = ?");
                    $scheduleStmt->execute([$order['order_id']]);
                    $scheduleCount = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>Payment schedule entries: {$scheduleCount['count']}</p>";
                } else {
                    echo "<p style='color: orange;'>Payment schedule table does not exist</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error checking payment schedule: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>No account_id found for this order - cannot test API calls</p>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Database Table Structure Check:</h3>";

// Check orders table structure
try {
    $stmt = $connect->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Orders table columns:</h4>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Default']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking orders table: " . $e->getMessage() . "</p>";
}

// Check payment_schedule table
try {
    $checkTable = $connect->query("SHOW TABLES LIKE 'payment_schedule'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $connect->query("DESCRIBE payment_schedule");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Payment_schedule table columns:</h4>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Default']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>Payment_schedule table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking payment_schedule table: " . $e->getMessage() . "</p>";
}
?>