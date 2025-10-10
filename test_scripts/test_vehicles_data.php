<?php
include_once(dirname(__FILE__) . '/includes/init.php');

// Use the database connection from init.php
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    die("Database connection not available. Please check your database configuration.");
}

echo "<h2>Vehicle Table Test</h2>";

try {
    // Test 1: Check if vehicles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'vehicles'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h3>Test 1: Table Existence</h3>";
    echo "Vehicles table exists: " . ($tableExists ? "YES" : "NO") . "<br><br>";
    
    if ($tableExists) {
        // Test 2: Check table structure
        echo "<h3>Test 2: Table Structure</h3>";
        $stmt = $pdo->query("DESCRIBE vehicles");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Test 3: Check data count
        echo "<h3>Test 3: Data Count</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicles");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Total vehicles in database: " . $count . "<br><br>";
        
        // Test 4: Show first 5 records
        echo "<h3>Test 4: Sample Records</h3>";
        $stmt = $pdo->query("SELECT * FROM vehicles LIMIT 5");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($vehicles);
        echo "</pre>";
        
        // Test 5: Show ALL vehicle IDs and basic info
        echo "<h3>Test 5: All Available Vehicle IDs</h3>";
        $stmt = $pdo->query("SELECT id, model_name, variant, base_price, promotional_price FROM vehicles ORDER BY id");
        $allVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Available vehicles:<br>";
        foreach ($allVehicles as $v) {
            $effectivePrice = ($v['promotional_price'] && $v['promotional_price'] > 0) ? $v['promotional_price'] : $v['base_price'];
            echo "<strong>ID: {$v['id']}</strong> - {$v['model_name']} {$v['variant']} - ₱" . number_format($effectivePrice, 2) . "<br>";
        }
        
        // Test 6: Check for specific ID 18 (the one that failed)
        echo "<h3>Test 6: Check Vehicle ID 18</h3>";
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([18]);
        $vehicle18 = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle18) {
            echo "Vehicle ID 18 found:<br>";
            echo "<pre>";
            print_r($vehicle18);
            echo "</pre>";
        } else {
            echo "<strong style='color: red;'>Vehicle ID 18 NOT FOUND</strong><br>";
            echo "This explains why the vehicle selection is failing.<br><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>