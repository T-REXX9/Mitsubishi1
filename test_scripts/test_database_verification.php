<?php
// Database verification script
header('Content-Type: text/plain');

echo "=== DATABASE VERIFICATION ===\n\n";

try {
    // Include database connection
    include_once('../includes/database/db_conn.php');
    
    if (!$connect) {
        throw new Exception("Database connection failed");
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Check customer_information table
    echo "📋 Checking customer_information table:\n";
    $stmt = $connect->query("SELECT COUNT(*) as count FROM customer_information");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total customers: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   Sample customer records:\n";
        $stmt = $connect->query("SELECT cusID, account_id, firstname, lastname FROM customer_information LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - cusID: {$row['cusID']}, account_id: {$row['account_id']}, name: {$row['firstname']} {$row['lastname']}\n";
        }
    }
    
    echo "\n📋 Checking orders table:\n";
    $stmt = $connect->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total orders: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   Sample order records:\n";
        $stmt = $connect->query("SELECT order_id, customer_id, vehicle_model, total_price, order_status FROM orders LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - ID: {$row['order_id']}, customer: {$row['customer_id']}, model: {$row['vehicle_model']}, status: {$row['order_status']}\n";
        }
    }
    
    echo "\n📋 Checking vehicles table:\n";
    $stmt = $connect->query("SELECT COUNT(*) as count FROM vehicles");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total vehicles: " . $result['count'] . "\n";
    
    echo "\n📋 Checking accounts table:\n";
    $stmt = $connect->query("SELECT COUNT(*) as count FROM accounts");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total accounts: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   Sample account records:\n";
        $stmt = $connect->query("SELECT Id, Username, Role FROM accounts LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - ID: {$row['Id']}, username: {$row['Username']}, role: {$row['Role']}\n";
        }
    }
    
    echo "\n✅ Database verification completed successfully\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>