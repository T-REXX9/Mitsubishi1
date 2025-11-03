<?php
/**
 * Check for encoding issues in customer_information table
 * 
 * This script checks for invalid UTF-8 characters in the customer_information table
 * that might cause JSON encoding errors.
 * 
 * Usage: http://localhost/Mitsubishi/check_encoding_issues.php?account_id=95
 */

include_once __DIR__ . '/includes/init.php';

$accountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encoding Issues Checker</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .info-box {
            background: #f9f9f9;
            border-left: 4px solid #d60000;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Encoding Issues Checker</h1>
        
        <div class="info-box">
            <strong>Purpose:</strong> This tool checks for invalid UTF-8 characters in customer data that might cause JSON encoding errors.
        </div>

        <form method="GET">
            <label for="account_id"><strong>Enter Account ID:</strong></label>
            <input type="number" id="account_id" name="account_id" value="<?php echo $accountId; ?>" required>
            <button type="submit">Check</button>
        </form>

        <?php if ($accountId > 0): ?>
            <?php
            global $connect;
            
            // Get customer data
            $stmt = $connect->prepare("SELECT * FROM customer_information WHERE account_id = :account_id");
            $stmt->execute([':account_id' => $accountId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                echo '<div class="info-box error">No customer_information record found for account_id ' . $accountId . '</div>';
            } else {
                echo '<h2>Results for Account ID: ' . $accountId . '</h2>';
                
                // Test JSON encoding
                $jsonTest = json_encode($customer);
                if ($jsonTest === false) {
                    echo '<div class="info-box error">';
                    echo '<strong>❌ JSON Encoding FAILED</strong><br>';
                    echo 'Error: ' . json_last_error_msg();
                    echo '</div>';
                } else {
                    echo '<div class="info-box success">';
                    echo '<strong>✅ JSON Encoding SUCCESSFUL</strong>';
                    echo '</div>';
                }
                
                // Check each field
                echo '<h3>Field-by-Field Analysis</h3>';
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th><th>Length</th><th>Encoding</th><th>JSON Test</th><th>Issues</th></tr>';
                
                foreach ($customer as $field => $value) {
                    if (!is_string($value)) {
                        continue; // Skip non-string fields
                    }
                    
                    $length = strlen($value);
                    $mbLength = mb_strlen($value, 'UTF-8');
                    $isUtf8 = mb_check_encoding($value, 'UTF-8');
                    $jsonFieldTest = json_encode($value);
                    $jsonOk = $jsonFieldTest !== false;
                    
                    $issues = [];
                    if (!$isUtf8) {
                        $issues[] = 'Invalid UTF-8';
                    }
                    if ($length !== $mbLength) {
                        $issues[] = 'Multi-byte characters';
                    }
                    if (!$jsonOk) {
                        $issues[] = 'JSON encoding failed: ' . json_last_error_msg();
                    }
                    
                    // Check for control characters
                    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                        $issues[] = 'Contains control characters';
                    }
                    
                    $rowClass = '';
                    if (!empty($issues)) {
                        $rowClass = 'error';
                    }
                    
                    echo '<tr class="' . $rowClass . '">';
                    echo '<td><strong>' . htmlspecialchars($field) . '</strong></td>';
                    echo '<td>' . htmlspecialchars(substr($value, 0, 50)) . ($length > 50 ? '...' : '') . '</td>';
                    echo '<td>' . $length . ' bytes' . ($length !== $mbLength ? ' (' . $mbLength . ' chars)' : '') . '</td>';
                    echo '<td>' . ($isUtf8 ? '✅ UTF-8' : '❌ Invalid') . '</td>';
                    echo '<td>' . ($jsonOk ? '✅ OK' : '❌ Failed') . '</td>';
                    echo '<td>' . (empty($issues) ? '✅ None' : '❌ ' . implode(', ', $issues)) . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                // Show raw data
                echo '<h3>Raw Data (for debugging)</h3>';
                echo '<div class="code">';
                echo htmlspecialchars(print_r($customer, true));
                echo '</div>';
                
                // Show JSON output
                echo '<h3>JSON Output</h3>';
                if ($jsonTest !== false) {
                    echo '<div class="code">';
                    echo htmlspecialchars(json_encode($customer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo '</div>';
                } else {
                    echo '<div class="info-box error">Cannot display JSON - encoding failed</div>';
                }
                
                // Suggest fixes
                if ($jsonTest === false) {
                    echo '<h3>🔧 Suggested Fixes</h3>';
                    echo '<div class="info-box warning">';
                    echo '<p><strong>Option 1: Clean the data in the database</strong></p>';
                    echo '<div class="code">';
                    echo "UPDATE customer_information SET\n";
                    foreach ($customer as $field => $value) {
                        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                            echo "  {$field} = CONVERT(CAST(CONVERT({$field} USING latin1) AS BINARY) USING utf8),\n";
                        }
                    }
                    echo "WHERE account_id = {$accountId};";
                    echo '</div>';
                    
                    echo '<p><strong>Option 2: Use PHP to sanitize</strong></p>';
                    echo '<p>The updated accounts.php now includes automatic UTF-8 sanitization using mb_convert_encoding().</p>';
                    echo '</div>';
                }
            }
            ?>
        <?php else: ?>
            <div class="info-box">
                Please enter an account ID to check for encoding issues.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

