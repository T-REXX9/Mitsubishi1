<?php
/**
 * Execute Vehicle Image Path Fix
 * This script runs the SQL to convert absolute Windows paths to relative paths
 */

// Include database connection
require_once dirname(__DIR__) . '/includes/database/db_conn.php';

echo "=== VEHICLE IMAGE PATH FIX SCRIPT ===\n\n";

try {
    // Start transaction
    $connect->beginTransaction();
    
    echo "Step 1: Checking current state...\n";
    
    // Check how many records have absolute paths
    $checkStmt = $connect->query("
        SELECT COUNT(*) as count 
        FROM vehicles 
        WHERE (main_image LIKE '%:%' AND main_image LIKE '%uploads%')
           OR (additional_images LIKE '%:%' AND additional_images LIKE '%uploads%')
           OR (view_360_images LIKE '%:%' AND view_360_images LIKE '%uploads%')
    ");
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    echo "Found {$result['count']} records with absolute paths\n\n";
    
    if ($result['count'] == 0) {
        echo "No records need fixing. All paths are already relative.\n";
        $connect->rollBack();
        exit(0);
    }
    
    echo "Step 2: Fixing main_image paths...\n";
    $stmt1 = $connect->exec("
        UPDATE vehicles 
        SET main_image = CONCAT('uploads/', SUBSTRING_INDEX(main_image, 'uploads/', -1))
        WHERE main_image LIKE '%:%' 
          AND main_image LIKE '%uploads%'
    ");
    echo "Updated $stmt1 main_image records\n\n";
    
    echo "Step 3: Fixing additional_images paths...\n";
    $stmt2 = $connect->exec("
        UPDATE vehicles 
        SET additional_images = CONCAT('uploads/', SUBSTRING_INDEX(additional_images, 'uploads/', -1))
        WHERE additional_images LIKE '%:%' 
          AND additional_images LIKE '%uploads%'
    ");
    echo "Updated $stmt2 additional_images records\n\n";
    
    echo "Step 4: Fixing view_360_images paths...\n";
    $stmt3 = $connect->exec("
        UPDATE vehicles 
        SET view_360_images = CONCAT('uploads/', SUBSTRING_INDEX(view_360_images, 'uploads/', -1))
        WHERE view_360_images LIKE '%:%' 
          AND view_360_images LIKE '%uploads%'
    ");
    echo "Updated $stmt3 view_360_images records\n\n";
    
    echo "Step 5: Verifying changes...\n";
    $verifyStmt = $connect->query("
        SELECT 
            id,
            model_name,
            main_image,
            CASE 
                WHEN main_image LIKE '%:%' THEN 'STILL ABSOLUTE'
                WHEN main_image LIKE 'uploads/%' THEN 'FIXED'
                ELSE 'OTHER'
            END as status
        FROM vehicles
        WHERE main_image IS NOT NULL AND main_image != ''
        ORDER BY id
    ");
    
    $fixed = 0;
    $stillAbsolute = 0;
    $other = 0;
    
    while ($row = $verifyStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] == 'FIXED') {
            $fixed++;
        } elseif ($row['status'] == 'STILL ABSOLUTE') {
            $stillAbsolute++;
            echo "WARNING: Vehicle ID {$row['id']} still has absolute path: {$row['main_image']}\n";
        } else {
            $other++;
        }
    }
    
    echo "\nVerification Results:\n";
    echo "- Fixed (relative paths): $fixed\n";
    echo "- Still absolute: $stillAbsolute\n";
    echo "- Other format: $other\n\n";
    
    if ($stillAbsolute > 0) {
        echo "WARNING: Some records still have absolute paths. Review the output above.\n";
        echo "Do you want to commit these changes? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'yes') {
            $connect->rollBack();
            echo "Changes rolled back.\n";
            exit(1);
        }
    }
    
    // Commit transaction
    $connect->commit();
    echo "\n=== SUCCESS: All changes committed ===\n";
    echo "Total records updated: " . ($stmt1 + $stmt2 + $stmt3) . "\n";
    
} catch (PDOException $e) {
    $connect->rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    exit(1);
}
?>