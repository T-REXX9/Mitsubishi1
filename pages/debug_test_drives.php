<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    die("Unauthorized access");
}

echo "<h2>Test Debug - Test Drive Requests</h2>";

try {
    // Check table structure
    $stmt = $connect->query("SHOW COLUMNS FROM test_drive_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Count all rows
    $total = (int)$connect->query("SELECT COUNT(*) FROM test_drive_requests")->fetchColumn();
    echo "<h3>Total rows in test_drive_requests (COUNT(*)): $total</h3>";

    // Fetch recent rows without filter
    $stmtAll = $connect->prepare("SELECT tdr.id, tdr.account_id, tdr.vehicle_id, tdr.gate_pass_number, tdr.customer_name, tdr.mobile_number, tdr.selected_date, tdr.selected_time_slot, tdr.status, tdr.requested_at, v.model_name AS vehicle_model, v.variant AS vehicle_variant
                                  FROM test_drive_requests tdr
                                  LEFT JOIN vehicles v ON v.id = tdr.vehicle_id
                                  ORDER BY tdr.requested_at DESC
                                  LIMIT 10");
    $stmtAll->execute();
    $rowsAll = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Recent 10 (no filter):</h3><pre>";
    print_r($rowsAll);
    echo "</pre>";

    // Try to fetch test drives for current user
    $stmt = $connect->prepare("SELECT * FROM test_drive_requests WHERE account_id = ? ORDER BY requested_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $test_drives = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Test Drives for User ID: " . $_SESSION['user_id'] . "</h3><pre>";
    print_r($test_drives);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>
