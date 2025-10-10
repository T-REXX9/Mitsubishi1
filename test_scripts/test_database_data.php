<?php
// Quick test to check database data for chatbot
include_once('includes/database/db_conn.php');

echo "<h1>Database Data Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f2f2f2;}</style>";

echo "<h2>1. Testing Database Connection</h2>";
if (isset($connect) && $connect) {
    echo "<p class='ok'>✓ Database connection successful</p>";
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
    exit();
}

echo "<h2>2. Testing Vehicles Table</h2>";
try {
    $stmt = $connect->query("SELECT COUNT(*) as count FROM vehicles");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p class='info'>Total vehicles in database: $count</p>";
    
    if ($count > 0) {
        echo "<p class='ok'>✓ Vehicles table has data</p>";
        
        // Show sample vehicles
        $stmt = $connect->query("SELECT model_name, variant, base_price, promotional_price, stock_quantity, availability_status FROM vehicles LIMIT 5");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Vehicles:</h3>";
        echo "<table>";
        echo "<tr><th>Model</th><th>Variant</th><th>Base Price</th><th>Promo Price</th><th>Stock</th><th>Status</th></tr>";
        foreach ($vehicles as $vehicle) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($vehicle['model_name']) . "</td>";
            echo "<td>" . htmlspecialchars($vehicle['variant']) . "</td>";
            echo "<td>₱" . number_format($vehicle['base_price']) . "</td>";
            echo "<td>₱" . number_format($vehicle['promotional_price'] ?? 0) . "</td>";
            echo "<td>" . $vehicle['stock_quantity'] . "</td>";
            echo "<td>" . htmlspecialchars($vehicle['availability_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No vehicles found - need to populate sample data</p>";
        echo "<p class='info'>Run this to add sample data: <code>mysql -u root mitsubishi < sample_vehicles.sql</code></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error querying vehicles table: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testing Orders Table</h2>";
try {
    $stmt = $connect->query("SELECT COUNT(*) as count FROM orders");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p class='info'>Total orders in database: $count</p>";
    
    if ($count > 0) {
        echo "<p class='ok'>✓ Orders table has data</p>";
    } else {
        echo "<p class='error'>✗ No orders found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error querying orders table: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testing Accounts Table</h2>";
try {
    $stmt = $connect->query("SELECT COUNT(*) as count FROM accounts WHERE Role = 'Customer'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p class='info'>Total customers in database: $count</p>";
    
    if ($count > 0) {
        echo "<p class='ok'>✓ Customer accounts exist</p>";
    } else {
        echo "<p class='error'>✗ No customer accounts found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error querying accounts table: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Test Complete</h2>";
echo "<p class='info'>If vehicles table is empty, populate it with: <strong>sample_vehicles.sql</strong></p>";
?>