<?php
// Test script to check AJAX endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate a POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'get_messages';

// Start session with dummy data
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Customer';

echo "Testing AJAX endpoint...\n";
echo "Headers will be:\n";

// Capture output
ob_start();

// Include the chat_support.php file (this should trigger the AJAX handler)
include 'pages/chat_support.php';

$output = ob_get_contents();
ob_end_clean();

echo "Raw output: " . var_export($output, true) . "\n";
echo "Output length: " . strlen($output) . "\n";

// Check if it's valid JSON
if (json_decode($output) === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Not valid JSON!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    
    // Show first few characters to identify the issue
    echo "First 200 characters:\n";
    echo substr($output, 0, 200) . "\n";
} else {
    echo "SUCCESS: Valid JSON returned!\n";
    echo "Parsed JSON: " . print_r(json_decode($output, true), true) . "\n";
}
?>