<?php
// Test script to debug order backend API
session_start();

// Simulate a logged-in user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a test user ID
    echo "Setting test user_id = 1 for debugging...\n";
}

echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
echo "Testing order backend API...\n\n";

// Test the API directly
$url = 'http://localhost/mitsubishi/includes/backend/order_backend.php?action=get_customer_orders';

echo "Making request to: $url\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

echo "Raw response:\n";
echo "Length: " . strlen($response) . " bytes\n";
echo "Content: '" . $response . "'\n";

if (empty($response)) {
    echo "\nERROR: Empty response received!\n";
} else {
    echo "\nAttempting to decode JSON...\n";
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "JSON decoded successfully:\n";
        print_r($decoded);
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
}

// Also test database connection
echo "\n\nTesting database connection...\n";
try {
    include_once('../includes/database/db_conn.php');
    echo "Database connection: " . ($connect ? "Success" : "Failed") . "\n";
    
    if ($connect) {
        // Test customer query
        $stmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = ?");
        $stmt->execute([1]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Customer found: " . ($customer ? "Yes (cusID: {$customer['cusID']})" : "No") . "\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>