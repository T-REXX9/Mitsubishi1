<?php
// Test file to verify vehicle database queries
require_once '../includes/database/db_conn.php';

// Test database connection
echo "<h2>Testing Vehicle Database Queries</h2>";

try {
    // Test 1: Count vehicles
    $stmt = $connect->prepare("SELECT COUNT(*) as count FROM vehicles");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total vehicles in database:</strong> " . $result['count'] . "</p>";
    
    // Test 2: Show sample vehicles
    $stmt = $connect->prepare("SELECT model_name, variant, base_price, promotional_price, availability_status FROM vehicles LIMIT 5");
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Vehicles:</h3>";
    foreach($vehicles as $vehicle) {
        $price = $vehicle['promotional_price'] ? $vehicle['promotional_price'] : $vehicle['base_price'];
        echo "<p>• {$vehicle['model_name']} {$vehicle['variant']} - ₱" . number_format($price, 0) . " ({$vehicle['availability_status']})</p>";
    }
    
    // Test the functions from chat_support.php
    echo "<h3>Testing Query Functions:</h3>";
    
    // Copy the functions here for testing
    function getVehicleByModel($modelName) {
        global $connect;
        try {
            $stmt = $connect->prepare("SELECT * FROM vehicles WHERE model_name LIKE ? ORDER BY year_model DESC LIMIT 5");
            $searchTerm = '%' . $modelName . '%';
            $stmt->execute([$searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
    function getAllVehicles() {
        global $connect;
        try {
            $stmt = $connect->prepare("SELECT model_name, variant, year_model, category, base_price, promotional_price, fuel_type, seating_capacity, availability_status FROM vehicles WHERE availability_status = 'available' ORDER BY model_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
    function formatVehicleInfo($vehicles) {
        if (empty($vehicles)) {
            return "No vehicles found matching your criteria.";
        }
        
        $info = "Here are the available vehicles:\n\n";
        foreach ($vehicles as $vehicle) {
            $price = $vehicle['promotional_price'] ? $vehicle['promotional_price'] : $vehicle['base_price'];
            $info .= "🚗 **{$vehicle['model_name']} {$vehicle['variant']}** ({$vehicle['year_model']})\n";
            $info .= "   Category: {$vehicle['category']}\n";
            $info .= "   Price: ₱" . number_format($price, 0) . "\n";
            if ($vehicle['promotional_price']) {
                $info .= "   Regular Price: ₱" . number_format($vehicle['base_price'], 0) . " (PROMO PRICE!)\n";
            }
            $info .= "   Fuel: {$vehicle['fuel_type']} | Seats: {$vehicle['seating_capacity']}\n";
            if (!empty($vehicle['key_features'])) {
                $info .= "   Features: {$vehicle['key_features']}\n";
            }
            $info .= "\n";
        }
        return $info;
    }
    
    // Test getAllVehicles function
    $allVehicles = getAllVehicles();
    echo "<p><strong>getAllVehicles() returned:</strong> " . count($allVehicles) . " vehicles</p>";
    
    if (!empty($allVehicles)) {
        echo "<h4>Formatted Vehicle Info:</h4>";
        $formatted = formatVehicleInfo($allVehicles);
        echo "<pre>" . htmlspecialchars($formatted) . "</pre>";
    }
    
    // Test specific model search
    $monteroVehicles = getVehicleByModel('montero');
    echo "<p><strong>getVehicleByModel('montero') returned:</strong> " . count($monteroVehicles) . " vehicles</p>";
    
    // Test message detection logic
    echo "<h3>Testing Message Detection:</h3>";
    $testMessages = [
        'what vehicles are in stock',
        'show me all vehicles',
        'what cars do you have',
        'montero price',
        'available cars'
    ];
    
    foreach($testMessages as $message) {
        $message_lower = strtolower($message);
        $detected = false;
        
        if (strpos($message_lower, 'all vehicles') !== false || strpos($message_lower, 'available cars') !== false || strpos($message_lower, 'what cars') !== false || strpos($message_lower, 'in stock') !== false || strpos($message_lower, 'inventory') !== false || strpos($message_lower, 'what vehicles') !== false || strpos($message_lower, 'available vehicles') !== false) {
            $detected = 'General vehicle list';
        }
        
        $models = ['montero', 'pajero', 'mirage', 'outlander', 'xpander', 'strada', 'lancer', 'eclipse'];
        foreach ($models as $model) {
            if (strpos($message_lower, $model) !== false) {
                $detected = 'Model: ' . $model;
                break;
            }
        }
        
        echo "<p>Message: '<em>{$message}</em>' → Detection: <strong>" . ($detected ? $detected : 'None') . "</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>