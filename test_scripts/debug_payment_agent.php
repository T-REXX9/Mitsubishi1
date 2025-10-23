<?php
// Debug script to check payment and agent assignment
session_start();
require_once dirname(__DIR__) . '/includes/database/db_conn.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Agent Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #dc143c; color: white; }
        .info { background-color: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196f3; }
        .warning { background-color: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
        .error { background-color: #f8d7da; padding: 10px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .success { background-color: #d4edda; padding: 10px; margin: 10px 0; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <h1>Payment Agent Assignment Debug</h1>

    <?php
    // Check session
    echo "<div class='info'>";
    echo "<h2>Current Session</h2>";
    echo "<strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "<strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'Not set') . "<br>";
    echo "<strong>Username:</strong> " . ($_SESSION['username'] ?? 'Not set') . "<br>";
    echo "</div>";

    $current_user_id = $_SESSION['user_id'] ?? null;

    // Get all pending payments
    echo "<h2>All Pending Payments</h2>";
    $sql = "SELECT ph.id, ph.payment_number, ph.customer_id, ph.order_id, ph.amount_paid, ph.status,
                   o.order_number,
                   a.Id as account_id, a.Username, a.Email,
                   ci.cusID, ci.account_id as ci_account_id, ci.agent_id, ci.firstname, ci.lastname,
                   agent.Username as agent_username, agent.FirstName as agent_fname, agent.LastName as agent_lname
            FROM payment_history ph
            LEFT JOIN orders o ON ph.order_id = o.order_id
            LEFT JOIN accounts a ON ph.customer_id = a.Id
            LEFT JOIN customer_information ci ON a.Id = ci.account_id
            LEFT JOIN accounts agent ON ci.agent_id = agent.Id
            WHERE ph.status = 'Pending'
            ORDER BY ph.id DESC
            LIMIT 10";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($payments) > 0) {
        echo "<table>";
        echo "<tr>
                <th>Payment ID</th>
                <th>Payment #</th>
                <th>Customer ID</th>
                <th>Account ID</th>
                <th>Customer Name</th>
                <th>CI Account ID</th>
                <th>Agent ID</th>
                <th>Agent Name</th>
                <th>Amount</th>
                <th>Match?</th>
              </tr>";
        
        foreach ($payments as $payment) {
            $customer_name = trim(($payment['firstname'] ?? '') . ' ' . ($payment['lastname'] ?? ''));
            if (empty($customer_name)) {
                $customer_name = $payment['Username'] ?? $payment['Email'] ?? 'N/A';
            }
            
            $agent_name = trim(($payment['agent_fname'] ?? '') . ' ' . ($payment['agent_lname'] ?? ''));
            if (empty($agent_name)) {
                $agent_name = $payment['agent_username'] ?? 'N/A';
            }
            
            $matches = ($payment['agent_id'] == $current_user_id);
            $row_class = $matches ? 'success' : '';
            
            echo "<tr style='background-color: " . ($matches ? '#d4edda' : 'white') . "'>";
            echo "<td>" . $payment['id'] . "</td>";
            echo "<td>" . ($payment['payment_number'] ?? 'N/A') . "</td>";
            echo "<td>" . $payment['customer_id'] . "</td>";
            echo "<td>" . ($payment['account_id'] ?? 'NULL') . "</td>";
            echo "<td>" . $customer_name . "</td>";
            echo "<td>" . ($payment['ci_account_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($payment['agent_id'] ?? 'NULL') . "</td>";
            echo "<td>" . $agent_name . "</td>";
            echo "<td>₱" . number_format($payment['amount_paid'], 2) . "</td>";
            echo "<td>" . ($matches ? '✅ YES' : '❌ NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for issues
        $no_ci = array_filter($payments, fn($p) => empty($p['cusID']));
        $no_agent = array_filter($payments, fn($p) => empty($p['agent_id']));
        
        if (count($no_ci) > 0) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Warning:</strong> " . count($no_ci) . " payment(s) have customers with no customer_information record.";
            echo "</div>";
        }
        
        if (count($no_agent) > 0) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Warning:</strong> " . count($no_agent) . " payment(s) have customers with no assigned agent (agent_id is NULL).";
            echo "</div>";
        }
        
    } else {
        echo "<div class='info'>No pending payments found.</div>";
    }

    // Check customer_information table structure
    echo "<h2>Customer Information Records</h2>";
    $sql = "SELECT ci.cusID, ci.account_id, ci.agent_id, ci.firstname, ci.lastname,
                   a.Username, a.Email,
                   agent.Username as agent_username
            FROM customer_information ci
            LEFT JOIN accounts a ON ci.account_id = a.Id
            LEFT JOIN accounts agent ON ci.agent_id = agent.Id
            LIMIT 10";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($customers) > 0) {
        echo "<table>";
        echo "<tr>
                <th>CusID</th>
                <th>Account ID</th>
                <th>Agent ID</th>
                <th>Customer Name</th>
                <th>Account Email</th>
                <th>Agent Username</th>
              </tr>";
        
        foreach ($customers as $customer) {
            echo "<tr>";
            echo "<td>" . $customer['cusID'] . "</td>";
            echo "<td>" . ($customer['account_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($customer['agent_id'] ?? 'NULL') . "</td>";
            echo "<td>" . trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? '')) . "</td>";
            echo "<td>" . ($customer['Email'] ?? 'N/A') . "</td>";
            echo "<td>" . ($customer['agent_username'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>No customer_information records found.</div>";
    }

    // Show the exact query being used
    echo "<h2>Query Being Used in approvePayment()</h2>";
    echo "<div class='info'>";
    echo "<pre>";
    echo "SELECT ph.*, o.order_id, ci.agent_id
FROM payment_history ph
JOIN orders o ON ph.order_id = o.order_id
JOIN accounts a ON ph.customer_id = a.Id
LEFT JOIN customer_information ci ON a.Id = ci.account_id
WHERE ph.id = ?";
    echo "</pre>";
    echo "<p><strong>Note:</strong> This query requires:</p>";
    echo "<ul>";
    echo "<li>payment_history.customer_id must match accounts.Id</li>";
    echo "<li>accounts.Id must match customer_information.account_id</li>";
    echo "<li>customer_information.agent_id must match the logged-in sales agent's ID</li>";
    echo "</ul>";
    echo "</div>";
    ?>

</body>
</html>

