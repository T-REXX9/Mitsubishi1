<?php
// Start session and simulate logged-in admin user
session_start();
$_SESSION['user_role'] = 'Admin';
$_SESSION['user_id'] = 1; // Simulate a user ID

include_once(__DIR__ . '/includes/init.php');

echo "<h2>Testing Vehicles API with Admin Session</h2>\n";
echo "<p>Simulated session: user_role = Admin, user_id = 1</p>\n";

// Function to make internal API calls
function makeAPICall($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n",
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return $response;
}

// Test 1: Main vehicles endpoint
echo "<h3>1. Testing main vehicles endpoint</h3>\n";
$response1 = makeAPICall("http://localhost/Mitsubishi/api/vehicles.php");
echo "<strong>Response:</strong><br><pre>" . htmlspecialchars($response1) . "</pre>\n";
echo "<strong>Valid JSON:</strong> " . (json_decode($response1) !== null ? "Yes" : "No") . "<br>\n";

// Test 2: Categories endpoint
echo "<h3>2. Testing categories endpoint</h3>\n";
$response2 = makeAPICall("http://localhost/Mitsubishi/api/vehicles.php?categories=1");
echo "<strong>Response:</strong><br><pre>" . htmlspecialchars($response2) . "</pre>\n";
echo "<strong>Valid JSON:</strong> " . (json_decode($response2) !== null ? "Yes" : "No") . "<br>\n";

// Test 3: Stats endpoint
echo "<h3>3. Testing stats endpoint</h3>\n";
$response3 = makeAPICall("http://localhost/Mitsubishi/api/vehicles.php?stats=1");
echo "<strong>Response:</strong><br><pre>" . htmlspecialchars($response3) . "</pre>\n";
echo "<strong>Valid JSON:</strong> " . (json_decode($response3) !== null ? "Yes" : "No") . "<br>\n";

// Direct function tests (bypassing HTTP)
echo "<h2>Direct Function Tests</h2>\n";

if ($pdo) {
    echo "<h3>Direct database queries:</h3>\n";
    
    // Test vehicles query
    try {
        $sql = "SELECT * FROM vehicles ORDER BY model_name, variant";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✓ Vehicles query: Found " . count($vehicles) . " vehicles</p>\n";
    } catch (Exception $e) {
        echo "<p>✗ Vehicles query failed: " . $e->getMessage() . "</p>\n";
    }
    
    // Test categories query
    try {
        $sql = "SELECT DISTINCT category FROM vehicles WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✓ Categories query: Found " . count($categories) . " categories: " . implode(', ', $categories) . "</p>\n";
    } catch (Exception $e) {
        echo "<p>✗ Categories query failed: " . $e->getMessage() . "</p>\n";
    }
    
    // Test stats query
    try {
        $sql = "SELECT 
                    COUNT(*) as total_units,
                    COUNT(DISTINCT CONCAT(model_name, variant)) as models_in_stock,
                    COUNT(CASE WHEN stock_quantity <= min_stock_alert THEN 1 END) as low_stock_alerts,
                    SUM(base_price * stock_quantity) as total_value
                FROM vehicles
                WHERE availability_status != 'discontinued'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Stats query: Total Units: {$stats['total_units']}, Models: {$stats['models_in_stock']}, Low Stock: {$stats['low_stock_alerts']}, Total Value: {$stats['total_value']}</p>\n";
    } catch (Exception $e) {
        echo "<p>✗ Stats query failed: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>✗ No database connection available</p>\n";
}
?>