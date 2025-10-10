<?php
session_start();
require_once 'includes/database/connection.php';

// Simulate a user session - let's use account_id = 5 (from the customer_information data we saw)
$_SESSION['user_id'] = 5;

echo "<h2>Debug Order Backend Issue</h2>";

try {
    $account_id = $_SESSION['user_id'];
    echo "<p><strong>Step 1:</strong> Account ID from session: $account_id</p>";
    
    // Step 1: Get cusID from customer_information
    $cusStmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = ?");
    $cusStmt->execute([$account_id]);
    $customer = $cusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo "<p style='color: red;'><strong>Step 2:</strong> No customer found for account_id $account_id</p>";
        
        // Check if account exists
        $accountStmt = $connect->prepare("SELECT Id, Username, Role FROM accounts WHERE Id = ?");
        $accountStmt->execute([$account_id]);
        $account = $accountStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            echo "<p><strong>Account exists:</strong> " . json_encode($account) . "</p>";
        } else {
            echo "<p style='color: red;'><strong>Account does not exist</strong></p>";
        }
        exit;
    }
    
    $customer_id = $customer['cusID'];
    echo "<p><strong>Step 2:</strong> Found customer cusID: $customer_id</p>";
    
    // Step 2: Check orders for this customer
    $orderStmt = $connect->prepare("SELECT order_id, order_number, customer_id, vehicle_model, total_price, order_status FROM orders WHERE customer_id = ?");
    $orderStmt->execute([$customer_id]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Step 3:</strong> Found " . count($orders) . " orders for customer cusID $customer_id</p>";
    
    if (count($orders) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Order ID</th><th>Order Number</th><th>Customer ID</th><th>Vehicle Model</th><th>Total Price</th><th>Status</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($order['customer_id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['vehicle_model']) . "</td>";
            echo "<td>" . htmlspecialchars($order['total_price']) . "</td>";
            echo "<td>" . htmlspecialchars($order['order_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 3: Test the full query from order_backend.php
    echo "<h3>Step 4: Testing Full Query from order_backend.php</h3>";
    
    // Check if payment_history table exists
    $checkTable = $connect->query("SHOW TABLES LIKE 'payment_history'");
    $tableExists = ($checkTable instanceof PDOStatement) ? ($checkTable->rowCount() > 0) : false;
    echo "<p>Payment history table exists: " . ($tableExists ? 'Yes' : 'No') . "</p>";
    
    if ($tableExists) {
        $sql = "SELECT 
                    o.order_id as id,
                    o.order_number,
                    o.customer_id,
                    o.sales_agent_id as agent_id,
                    o.vehicle_id,
                    o.vehicle_model as model_name,
                    o.vehicle_variant as variant,
                    o.model_year as year_model,
                    o.base_price,
                    o.discount_amount,
                    o.total_price as total_amount,
                    o.payment_method,
                    o.down_payment,
                    o.financing_term,
                    o.monthly_payment,
                    o.order_status as status,
                    o.delivery_date,
                    o.actual_delivery_date,
                    o.created_at,
                    v.main_image,
                    COALESCE(SUM(ph.amount_paid), 0) as total_paid,
                    (o.total_price - COALESCE(SUM(ph.amount_paid), 0)) as remaining_balance,
                    ROUND((COALESCE(SUM(ph.amount_paid), 0) / o.total_price) * 100, 2) as payment_progress
                FROM orders o
                LEFT JOIN vehicles v ON o.vehicle_id = v.id
                LEFT JOIN payment_history ph ON o.order_id = ph.order_id AND ph.status = 'Confirmed'
                WHERE o.customer_id = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC";
    } else {
        $sql = "SELECT 
                    o.order_id as id,
                    o.order_number,
                    o.customer_id,
                    o.sales_agent_id as agent_id,
                    o.vehicle_id,
                    o.vehicle_model as model_name,
                    o.vehicle_variant as variant,
                    o.model_year as year_model,
                    o.base_price,
                    o.discount_amount,
                    o.total_price as total_amount,
                    o.payment_method,
                    o.down_payment,
                    o.financing_term,
                    o.monthly_payment,
                    o.order_status as status,
                    o.delivery_date,
                    o.actual_delivery_date,
                    o.created_at,
                    v.main_image,
                    0 as total_paid,
                    o.total_price as remaining_balance,
                    0 as payment_progress
                FROM orders o
                LEFT JOIN vehicles v ON o.vehicle_id = v.id
                WHERE o.customer_id = ?
                ORDER BY o.created_at DESC";
    }
    
    echo "<p><strong>Executing query...</strong></p>";
    $stmt = $connect->prepare($sql);
    $stmt->execute([$customer_id]);
    $fullOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $errorInfo = $stmt->errorInfo();
    
    if (!empty($errorInfo[1])) {
        echo "<p style='color: red;'><strong>SQL Error:</strong> " . implode(' | ', $errorInfo) . "</p>";
    } else {
        echo "<p style='color: green;'><strong>Query executed successfully!</strong> Found " . count($fullOrders) . " orders</p>";
        
        if (count($fullOrders) > 0) {
            echo "<h4>Full Order Details:</h4>";
            echo "<pre>" . json_encode($fullOrders, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>