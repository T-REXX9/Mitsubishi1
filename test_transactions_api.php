<?php
/**
 * Test script to diagnose the transactions_backend.php API
 * This will show the exact error that's causing the 500 error
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
session_start();

// Set up a test admin session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'Admin';
    echo "Setting test session: user_id=1, role=Admin<br><br>";
}

echo "<h1>Testing Transactions Backend API</h1>";
echo "<hr>";

// Test 1: Check if init.php loads correctly
echo "<h2>Test 1: Loading init.php</h2>";
try {
    include_once(__DIR__ . '/includes/init.php');
    echo "✓ init.php loaded successfully<br>";
    
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']) {
        echo "✓ Database connection available<br>";
    } else {
        echo "✗ Database connection NOT available<br>";
        echo "PDO object: ";
        var_dump($GLOBALS['pdo']);
        echo "<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading init.php: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 2: Simulate the get_transactions API call
echo "<h2>Test 2: Simulating get_transactions API call</h2>";

// Set up POST data
$_POST['action'] = 'get_transactions';
$_POST['status'] = 'completed';
$_POST['page'] = '1';
$_POST['limit'] = '10';

echo "POST data:<br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Capture output
ob_start();

try {
    // Include the backend file
    include(__DIR__ . '/includes/backend/transactions_backend.php');
    
    $output = ob_get_clean();
    
    echo "<h3>API Response:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to decode JSON
    $json = json_decode($output, true);
    if ($json) {
        echo "<h3>Decoded JSON:</h3>";
        echo "<pre>";
        print_r($json);
        echo "</pre>";
        
        if (isset($json['success']) && $json['success']) {
            echo "<p style='color:green;font-weight:bold;'>✓ API call successful!</p>";
            if (isset($json['data']['transactions'])) {
                echo "<p>Number of transactions returned: " . count($json['data']['transactions']) . "</p>";
            }
        } else {
            echo "<p style='color:red;font-weight:bold;'>✗ API call failed</p>";
            if (isset($json['error'])) {
                echo "<p>Error: " . $json['error'] . "</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>✗ Response is not valid JSON</p>";
        echo "<p>JSON decode error: " . json_last_error_msg() . "</p>";
    }
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red;font-weight:bold;'>✗ Exception caught:</p>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " (Line " . $e->getLine() . ")</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// Test 3: Test get_stats API call
echo "<h2>Test 3: Simulating get_stats API call</h2>";

// Reset POST data
$_POST = [];
$_POST['action'] = 'get_stats';
$_POST['status'] = 'completed';

echo "POST data:<br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Capture output
ob_start();

try {
    // We need to re-include, but PHP won't let us include the same file twice
    // So let's just call the function directly if it exists
    
    if (function_exists('getStats')) {
        getStats();
        $output = ob_get_clean();
        
        echo "<h3>API Response:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        
        $json = json_decode($output, true);
        if ($json && isset($json['success']) && $json['success']) {
            echo "<p style='color:green;font-weight:bold;'>✓ get_stats successful!</p>";
        }
    } else {
        ob_end_clean();
        echo "<p style='color:orange;'>Function getStats() not available (already executed in Test 2)</p>";
    }
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red;font-weight:bold;'>✗ Exception caught:</p>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// Test 4: Direct database query test
echo "<h2>Test 4: Direct Database Query Test</h2>";

if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']) {
    $pdo = $GLOBALS['pdo'];
    
    try {
        // Test the exact query from getTransactions
        $sql = "SELECT COUNT(*) AS total FROM orders o
                LEFT JOIN customer_information ci ON o.customer_id = ci.cusID
                LEFT JOIN accounts acc ON ci.account_id = acc.Id
                LEFT JOIN vehicles v ON o.vehicle_id = v.id
                LEFT JOIN (
                    SELECT order_id, COALESCE(SUM(amount_paid), 0) as total_paid
                    FROM payment_history
                    WHERE status = 'Confirmed'
                    GROUP BY order_id
                ) payments ON o.order_id = payments.order_id
                WHERE (o.total_price - COALESCE(payments.total_paid, 0)) = 0";
        
        echo "Executing query:<br>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p style='color:green;'>✓ Query executed successfully</p>";
        echo "<p>Total completed transactions: <strong>" . $result['total'] . "</strong></p>";
        
        if ($result['total'] == 0) {
            echo "<p style='color:orange;'>⚠ No completed transactions found. This is why the page shows no data!</p>";
            
            // Check if there are any orders at all
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
            $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>Total orders in database: <strong>" . $orderCount . "</strong></p>";
            
            if ($orderCount > 0) {
                // Check payment status
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_history WHERE status = 'Confirmed'");
                $paymentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>Total confirmed payments: <strong>" . $paymentCount . "</strong></p>";
                
                echo "<h3>Diagnosis:</h3>";
                if ($paymentCount == 0) {
                    echo "<p style='color:red;'>✗ No confirmed payments exist. Orders cannot be marked as 'completed' without confirmed payments.</p>";
                } else {
                    echo "<p style='color:orange;'>⚠ Payments exist but no orders are fully paid yet.</p>";
                }
            } else {
                echo "<p style='color:red;'>✗ No orders exist in the database at all!</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✗ Database query failed:</p>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p style='color:red;'>✗ Database connection not available</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Check the results above to identify the issue.</p>";
echo "<p>Common issues:</p>";
echo "<ul>";
echo "<li>Database connection failure</li>";
echo "<li>No data in the database</li>";
echo "<li>SQL syntax errors</li>";
echo "<li>Missing tables or columns</li>";
echo "<li>Session/authentication issues</li>";
echo "</ul>";
?>

