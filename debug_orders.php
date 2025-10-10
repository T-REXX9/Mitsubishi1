<?php
require_once 'includes/database/connection.php';

try {
    $stmt = $connect->query('SELECT o.order_id, o.customer_id, o.order_number, ci.cusID, ci.account_id FROM orders o LEFT JOIN customer_information ci ON o.customer_id = ci.cusID ORDER BY o.order_id DESC LIMIT 10');
    
    echo "<h3>Orders and Customer Information Relationship:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Order ID</th><th>Order Customer ID</th><th>Order Number</th><th>CI cusID</th><th>CI account_id</th></tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['customer_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cusID'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['account_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Also check customer_information table
    echo "<h3>Customer Information Table:</h3>";
    $stmt2 = $connect->query('SELECT cusID, account_id, firstname, lastname FROM customer_information ORDER BY cusID');
    echo "<table border='1'>";
    echo "<tr><th>cusID</th><th>account_id</th><th>First Name</th><th>Last Name</th></tr>";
    
    while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['cusID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['account_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['lastname']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>