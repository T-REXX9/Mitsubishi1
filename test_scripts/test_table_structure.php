<?php
// Check vehicles table structure
header('Content-Type: text/plain');

try {
    include_once('../includes/database/db_conn.php');
    
    if (!$connect) {
        die("Database connection failed");
    }
    
    echo "=== VEHICLES TABLE STRUCTURE ===\n\n";
    
    // Get table structure
    $stmt = $connect->query("DESCRIBE vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in vehicles table:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - Default: {$column['Default']}\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $stmt = $connect->query("SELECT * FROM vehicles LIMIT 3");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($vehicles)) {
        echo "Sample vehicle records:\n";
        foreach ($vehicles as $i => $vehicle) {
            echo "\nVehicle " . ($i + 1) . ":\n";
            foreach ($vehicle as $key => $value) {
                if (is_string($value) && strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                echo "  $key: $value\n";
            }
        }
    } else {
        echo "No vehicles found in table\n";
    }
    
    echo "\n=== ORDERS TABLE STRUCTURE ===\n\n";
    
    // Get orders table structure
    $stmt = $connect->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in orders table:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - Default: {$column['Default']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>