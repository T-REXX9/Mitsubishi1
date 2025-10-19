<?php
/**
 * Timezone Fix Verification Script
 * 
 * This script tests and verifies that PHP and MySQL timezones are synchronized
 * to prevent timestamp discrepancies (records appearing 1 day late or advance)
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Timezone Fix Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #dc143c;
            border-bottom: 3px solid #dc143c;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            border-left: 4px solid #dc143c;
            padding-left: 10px;
        }
        .test-section {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .error {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }
        .warning {
            background: #fff3e0;
            border-left-color: #ff9800;
            color: #e65100;
        }
        .success {
            background: #e8f5e9;
            border-left-color: #4CAF50;
            color: #2e7d32;
        }
        .info {
            background: #e3f2fd;
            border-left-color: #2196F3;
            color: #1565c0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #dc143c;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .code {
            background: #263238;
            color: #aed581;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #4CAF50;
            color: white;
        }
        .badge-error {
            background: #f44336;
            color: white;
        }
        .badge-warning {
            background: #ff9800;
            color: white;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🕐 Timezone Fix Verification</h1>";
echo "<p><strong>Purpose:</strong> Verify that PHP and MySQL timezones are synchronized to prevent timestamp issues.</p>";

// Include database connection
require_once dirname(__DIR__) . '/includes/database/db_conn.php';

// Test 1: PHP Timezone
echo "<h2>Test 1: PHP Timezone Configuration</h2>";
$php_timezone = date_default_timezone_get();
$php_datetime = date('Y-m-d H:i:s');
$php_offset = date('P');

echo "<div class='test-section success'>";
echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td>PHP Timezone</td><td><strong>$php_timezone</strong></td></tr>";
echo "<tr><td>PHP Current DateTime</td><td><strong>$php_datetime</strong></td></tr>";
echo "<tr><td>PHP Timezone Offset</td><td><strong>$php_offset</strong></td></tr>";
echo "</table>";
echo "</div>";

// Test 2: MySQL Timezone
echo "<h2>Test 2: MySQL Timezone Configuration</h2>";
try {
    $stmt = $connect->query("SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz, NOW() as mysql_now");
    $mysql_tz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='test-section success'>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>MySQL Session Timezone</td><td><strong>{$mysql_tz['session_tz']}</strong></td></tr>";
    echo "<tr><td>MySQL Global Timezone</td><td><strong>{$mysql_tz['global_tz']}</strong></td></tr>";
    echo "<tr><td>MySQL NOW()</td><td><strong>{$mysql_tz['mysql_now']}</strong></td></tr>";
    echo "</table>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div class='test-section error'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Test 3: Timezone Synchronization Check
echo "<h2>Test 3: PHP vs MySQL Synchronization</h2>";
$php_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
$php_timestamp = $php_time->format('Y-m-d H:i:s');

try {
    $stmt = $connect->query("SELECT NOW() as mysql_timestamp");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $mysql_timestamp = $result['mysql_timestamp'];
    
    // Calculate difference in seconds
    $php_dt = new DateTime($php_timestamp);
    $mysql_dt = new DateTime($mysql_timestamp);
    $diff = abs($php_dt->getTimestamp() - $mysql_dt->getTimestamp());
    
    if ($diff <= 2) {
        echo "<div class='test-section success'>";
        echo "<p><span class='badge badge-success'>✓ SYNCHRONIZED</span></p>";
        echo "<table>";
        echo "<tr><th>Source</th><th>Timestamp</th></tr>";
        echo "<tr><td>PHP DateTime</td><td><strong>$php_timestamp</strong></td></tr>";
        echo "<tr><td>MySQL NOW()</td><td><strong>$mysql_timestamp</strong></td></tr>";
        echo "<tr><td>Difference</td><td><strong>{$diff} seconds</strong></td></tr>";
        echo "</table>";
        echo "<p><strong>Status:</strong> PHP and MySQL are properly synchronized! ✓</p>";
        echo "</div>";
    } else {
        echo "<div class='test-section error'>";
        echo "<p><span class='badge badge-error'>✗ NOT SYNCHRONIZED</span></p>";
        echo "<table>";
        echo "<tr><th>Source</th><th>Timestamp</th></tr>";
        echo "<tr><td>PHP DateTime</td><td><strong>$php_timestamp</strong></td></tr>";
        echo "<tr><td>MySQL NOW()</td><td><strong>$mysql_timestamp</strong></td></tr>";
        echo "<tr><td>Difference</td><td><strong>{$diff} seconds (" . round($diff/3600, 2) . " hours)</strong></td></tr>";
        echo "</table>";
        echo "<p><strong>⚠ WARNING:</strong> PHP and MySQL timestamps are out of sync by {$diff} seconds!</p>";
        echo "<p>This can cause records to appear with incorrect timestamps (1 day late or advance).</p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div class='test-section error'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Test 4: Database Insert Test
echo "<h2>Test 4: Database Insert Timestamp Test</h2>";
echo "<p>Testing actual database insert with timestamp...</p>";

try {
    // Create test table if not exists
    $connect->exec("CREATE TABLE IF NOT EXISTS timezone_test (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_message VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at_php VARCHAR(50)
    )");
    
    // Insert test record
    $php_insert_time = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("INSERT INTO timezone_test (test_message, created_at_php) VALUES (?, ?)");
    $stmt->execute(['Timezone test at ' . date('Y-m-d H:i:s'), $php_insert_time]);
    
    // Retrieve the record
    $stmt = $connect->query("SELECT * FROM timezone_test ORDER BY id DESC LIMIT 1");
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='test-section info'>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Test Message</td><td>{$record['test_message']}</td></tr>";
    echo "<tr><td>PHP Timestamp (inserted)</td><td><strong>{$record['created_at_php']}</strong></td></tr>";
    echo "<tr><td>MySQL TIMESTAMP (auto)</td><td><strong>{$record['created_at']}</strong></td></tr>";
    echo "</table>";
    
    // Compare timestamps
    $php_dt = new DateTime($record['created_at_php']);
    $mysql_dt = new DateTime($record['created_at']);
    $insert_diff = abs($php_dt->getTimestamp() - $mysql_dt->getTimestamp());
    
    if ($insert_diff <= 2) {
        echo "<p><span class='badge badge-success'>✓ PASS</span> Timestamps match! (Difference: {$insert_diff} seconds)</p>";
    } else {
        echo "<p><span class='badge badge-error'>✗ FAIL</span> Timestamps don't match! (Difference: {$insert_diff} seconds)</p>";
    }
    echo "</div>";
    
    // Clean up test table
    $connect->exec("DROP TABLE IF EXISTS timezone_test");
    
} catch (PDOException $e) {
    echo "<div class='test-section error'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Summary and Recommendations
echo "<h2>Summary & Recommendations</h2>";
echo "<div class='test-section info'>";
echo "<h3>✓ Fix Applied:</h3>";
echo "<p>The database connection file (<code>includes/database/db_conn.php</code>) has been updated to set MySQL timezone to <strong>+08:00</strong> (Asia/Manila).</p>";
echo "<div class='code'>SET time_zone = '+08:00';</div>";

echo "<h3>📋 What This Fixes:</h3>";
echo "<ul>";
echo "<li>Records created with <code>NOW()</code> or <code>CURRENT_TIMESTAMP</code> will use Philippine time</li>";
echo "<li>Prevents timestamps from appearing 1 day late or 1 day advance</li>";
echo "<li>Ensures consistency between PHP date functions and MySQL date functions</li>";
echo "<li>Fixes issues with date comparisons and filtering</li>";
echo "</ul>";

echo "<h3>🔍 Common Issues This Resolves:</h3>";
echo "<ul>";
echo "<li><strong>Registration dates appearing wrong:</strong> Customer accounts showing future or past dates</li>";
echo "<li><strong>Order timestamps incorrect:</strong> Orders appearing to be placed on wrong dates</li>";
echo "<li><strong>Payment history issues:</strong> Payments showing incorrect submission times</li>";
echo "<li><strong>Report date filtering:</strong> Daily/monthly reports showing wrong data</li>";
echo "</ul>";

echo "<h3>⚠ Important Notes:</h3>";
echo "<ul>";
echo "<li>This fix applies to <strong>new records</strong> created after the fix</li>";
echo "<li>Existing records with incorrect timestamps will need manual correction if critical</li>";
echo "<li>All database connections will now use Asia/Manila timezone (+08:00)</li>";
echo "<li>This matches the PHP timezone setting in <code>includes/init.php</code></li>";
echo "</ul>";
echo "</div>";

echo "</div>
</body>
</html>";
?>

