<?php
/**
 * Test script to diagnose customer information retrieval issues
 * 
 * Usage: 
 * 1. Access this file in your browser: http://localhost/Mitsubishi/test_customer_retrieval.php
 * 2. Add ?account_id=X to the URL where X is the account ID you want to test
 * 
 * Example: http://localhost/Mitsubishi/test_customer_retrieval.php?account_id=5
 */

// Include dependencies
include_once __DIR__ . '/includes/init.php';
include_once __DIR__ . '/includes/database/accounts_operations.php';
include_once __DIR__ . '/includes/database/customer_operations.php';

// Get account ID from query parameter
$accountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Information Retrieval Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d60000;
            border-bottom: 2px solid #d60000;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .info-box {
            background: #f9f9f9;
            border-left: 4px solid #d60000;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .form-group {
            margin: 20px 0;
        }
        input[type="number"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        button {
            background: #d60000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #b00000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Customer Information Retrieval Test</h1>
        
        <div class="form-group">
            <form method="GET">
                <label for="account_id"><strong>Enter Account ID to test:</strong></label><br>
                <input type="number" id="account_id" name="account_id" value="<?php echo $accountId; ?>" placeholder="Enter account ID" required>
                <button type="submit">Test Retrieval</button>
            </form>
        </div>

        <?php if ($accountId > 0): ?>
            <?php
            $accountsOp = new AccountsOperations();
            $customerOp = new CustomerOperations();
            
            echo "<h2>Test Results for Account ID: {$accountId}</h2>";
            
            // Test 1: Check if account exists
            echo "<h3>1. Account Information</h3>";
            $account = $accountsOp->getAccountById($accountId);
            if ($account) {
                echo '<div class="info-box success">✓ Account found in accounts table</div>';
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th></tr>';
                foreach ($account as $key => $value) {
                    echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info-box error">✗ Account NOT found in accounts table</div>';
            }
            
            // Test 2: Check customer_information table directly
            echo "<h3>2. Customer Information (Direct Query)</h3>";
            try {
                global $connect;
                $stmt = $connect->prepare("SELECT * FROM customer_information WHERE account_id = :account_id");
                $stmt->execute([':account_id' => $accountId]);
                $directResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($directResult) {
                    echo '<div class="info-box success">✓ Customer information found in customer_information table</div>';
                    echo '<table>';
                    echo '<tr><th>Field</th><th>Value</th></tr>';
                    foreach ($directResult as $key => $value) {
                        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info-box warning">⚠ No customer_information record found for this account_id</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="info-box error">✗ Error querying customer_information: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Test 3: Test the CustomerOperations method
            echo "<h3>3. CustomerOperations::getCustomerByAccountId() Result</h3>";
            $customer = $customerOp->getCustomerByAccountId($accountId);
            
            if ($customer) {
                echo '<div class="info-box success">✓ getCustomerByAccountId() returned data</div>';
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th></tr>';
                foreach ($customer as $key => $value) {
                    echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
                }
                echo '</table>';
                
                // Check for expected fields
                echo "<h4>Field Validation:</h4>";
                $expectedFields = ['firstname', 'lastname', 'middlename', 'birthday', 'age', 'gender', 
                                   'civil_status', 'nationality', 'mobile_number', 'employment_status', 
                                   'company_name', 'position', 'monthly_income', 'Status', 'account_id'];
                
                echo '<table>';
                echo '<tr><th>Expected Field</th><th>Present?</th><th>Value</th></tr>';
                foreach ($expectedFields as $field) {
                    $present = array_key_exists($field, $customer);
                    $value = $present ? ($customer[$field] ?? 'NULL') : 'N/A';
                    $status = $present ? '✓' : '✗';
                    $class = $present ? 'success' : 'error';
                    echo '<tr style="background: ' . ($present ? '#d4edda' : '#f8d7da') . '">';
                    echo '<td>' . htmlspecialchars($field) . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td>' . htmlspecialchars($value) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info-box error">✗ getCustomerByAccountId() returned FALSE/NULL</div>';
            }
            
            // Test 4: Check error logs
            echo "<h3>4. Recent Error Logs</h3>";
            echo '<div class="info-box">Check your PHP error log file for detailed debugging information. The enhanced logging will show:</div>';
            echo '<ul>';
            echo '<li>Query execution details</li>';
            echo '<li>Field names returned from database</li>';
            echo '<li>Sample data values</li>';
            echo '<li>Any SQL errors or exceptions</li>';
            echo '</ul>';
            
            // Test 5: Show the SQL query being used
            echo "<h3>5. SQL Query Being Used</h3>";
            echo '<div class="code">';
            echo 'SELECT ci.*, a.Username, a.Email, a.Role<br>';
            echo 'FROM customer_information ci<br>';
            echo 'LEFT JOIN accounts a ON ci.account_id = a.Id<br>';
            echo 'WHERE ci.account_id = ' . $accountId;
            echo '</div>';
            
            ?>
        <?php else: ?>
            <div class="info-box warning">
                ⚠ Please enter an account ID to test
            </div>
        <?php endif; ?>
        
        <h2>📋 Troubleshooting Guide</h2>
        <div class="info-box">
            <h4>Common Issues:</h4>
            <ol>
                <li><strong>Account exists but no customer_information:</strong> The customer hasn't completed their profile yet.</li>
                <li><strong>customer_information exists but getCustomerByAccountId() returns false:</strong> Check the JOIN condition - the account_id field might have incorrect data type or value.</li>
                <li><strong>Data returned but fields are NULL:</strong> Check if the column names in the database match what the code expects (case-sensitive).</li>
                <li><strong>No data at all:</strong> Verify the account_id value in customer_information table matches the Id in accounts table.</li>
            </ol>
        </div>
    </div>
</body>
</html>

