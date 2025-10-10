<?php
// Direct test of the order backend functionality
// This bypasses any session or other complications

// Simulate the required environment
$_GET['action'] = 'get_customer_orders';
$_SESSION['user_id'] = 1; // Simulate logged in user

// Capture all output
ob_start();

// Include the backend file
include '../includes/backend/order_backend.php';

// Get the output
$output = ob_get_contents();
ob_end_clean();

// Display the results
header('Content-Type: text/plain');
echo "=== DIRECT BACKEND TEST ===\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Output content:\n";
echo $output;
echo "\n=== END OUTPUT ===\n";

// Test JSON validity
if ($output) {
    $decoded = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nJSON is valid!\n";
        echo "Decoded content: " . print_r($decoded, true);
    } else {
        echo "\nJSON parsing error: " . json_last_error_msg() . "\n";
    }
}
?>