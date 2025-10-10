<?php
include_once(__DIR__ . '/includes/init.php');

echo "<h2>Database Connection Test</h2>\n";

if ($pdo) {
    echo "<p>✓ Database connection successful</p>\n";
    
    // Check if vehicles table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'vehicles'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            echo "<p>✓ Vehicles table exists</p>\n";
            
            // Count records
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicles");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>Records in vehicles table: $count</p>\n";
            
            if ($count > 0) {
                // Show first few records
                $stmt = $pdo->query("SELECT id, model_name, variant, category, stock_quantity FROM vehicles LIMIT 3");
                $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Sample records:</h3>\n";
                foreach ($vehicles as $vehicle) {
                    echo "<p>ID: {$vehicle['id']}, Model: {$vehicle['model_name']} {$vehicle['variant']}, Category: {$vehicle['category']}, Stock: {$vehicle['stock_quantity']}</p>\n";
                }
            }
            
            // Test categories
            $stmt = $pdo->query("SELECT DISTINCT category FROM vehicles WHERE category IS NOT NULL AND category != ''");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<h3>Available categories:</h3>\n";
            echo "<p>" . implode(', ', $categories) . "</p>\n";
            
        } else {
            echo "<p>✗ Vehicles table does not exist</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>✗ Database error: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>✗ Database connection failed</p>\n";
}
?>