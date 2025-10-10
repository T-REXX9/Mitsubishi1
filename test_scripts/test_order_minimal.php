<?php
// Minimal order backend test - bypasses all complications
header('Content-Type: text/plain');
echo "=== MINIMAL ORDER BACKEND TEST ===\n\n";

// Start session
session_start();
echo "1. Session started: " . (session_id() ? "✅" : "❌") . "\n";

// Check for user_id
if (!isset($_SESSION['user_id'])) {
    echo "2. ❌ No user_id in session - this is the problem!\n";
    echo "   Session contents: " . print_r($_SESSION, true) . "\n";
    echo "\n🔧 SOLUTION: You need to log in first.\n";
    echo "   Go to: http://localhost/mitsubishi/pages/login.php\n";
    exit;
}

echo "2. ✅ User ID found: " . $_SESSION['user_id'] . "\n";

// Test database connection
echo "3. Testing database connection...\n";
try {
    include_once('../includes/database/db_conn.php');
    if ($connect) {
        echo "   ✅ Database connected\n";
    } else {
        echo "   ❌ Database connection failed\n";
        exit;
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    exit;
}

// Test customer lookup
echo "4. Testing customer lookup...\n";
try {
    $account_id = $_SESSION['user_id'];
    $cusStmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = ?");
    $cusStmt->execute([$account_id]);
    $customer = $cusStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "   ✅ Customer found: cusID = " . $customer['cusID'] . "\n";
    } else {
        echo "   ⚠️ No customer profile found for account_id: $account_id\n";
        echo "   This means you have an account but no customer profile.\n";
    }
} catch (Exception $e) {
    echo "   ❌ Customer lookup error: " . $e->getMessage() . "\n";
}

// Test orders lookup  
echo "5. Testing orders lookup...\n";
try {
    if (isset($customer['cusID'])) {
        $customer_id = $customer['cusID'];
        $stmt = $connect->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   📊 Orders found: " . $result['count'] . "\n";
    } else {
        echo "   ⏭️ Skipped (no customer profile)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Orders lookup error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "If you see ❌ errors above, those need to be fixed first.\n";
echo "If all tests pass ✅, then the issue is elsewhere.\n";
?>