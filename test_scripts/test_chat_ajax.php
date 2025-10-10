<?php
// Simple test for chat AJAX functionality
session_start();

// Set up test session
$_SESSION['user_role'] = 'Customer';
$_SESSION['user_id'] = 1;

echo "<h1>Chat AJAX Debug Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}</style>";

echo "<h2>1. Testing POST Request to chat_support.php</h2>";

// Simulate the POST request that the frontend makes
$_POST['action'] = 'send_message';
$_POST['message_text'] = 'Hello, do you have a Montero Sport available?';

echo "<p class='info'>Simulating POST request with:</p>";
echo "<ul>";
echo "<li>action: " . $_POST['action'] . "</li>";
echo "<li>message_text: " . $_POST['message_text'] . "</li>";
echo "<li>user_role: " . $_SESSION['user_role'] . "</li>";
echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
echo "</ul>";

echo "<h3>Response from chat_support.php:</h3>";

// Capture the output
ob_start();

try {
    // Include the chat support file to test the AJAX handling
    include('pages/chat_support.php');
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
} catch (ParseError $e) {
    $output = "Parse Error: " . $e->getMessage();
} catch (Error $e) {
    $output = "Fatal Error: " . $e->getMessage();
}

ob_end_clean();

echo "<div style='background:#f0f0f0;padding:10px;border:1px solid #ccc;max-height:300px;overflow:auto;'>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";
echo "</div>";

// Check if it's valid JSON
if (!empty($output)) {
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "<p class='ok'>✓ Valid JSON response</p>";
        echo "<p>Decoded response: " . print_r($json, true) . "</p>";
    } else {
        echo "<p class='error'>✗ Invalid JSON response</p>";
        echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        
        // Check if it looks like HTML
        if (strpos($output, '<!DOCTYPE') === 0 || strpos($output, '<html') === 0) {
            echo "<p class='error'>Response appears to be HTML, not JSON</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Empty response</p>";
}

echo "<h2>2. Testing Direct API Call</h2>";

// Test if we can make a direct curl request
$url = 'http://localhost/Mitsubishi/pages/chat_support.php';
$postData = [
    'action' => 'send_message',
    'message_text' => 'Test message for API'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<h3>cURL Response:</h3>";
echo "<div style='background:#f0f0f0;padding:10px;border:1px solid #ccc;max-height:300px;overflow:auto;'>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "</div>";

if (!empty($response)) {
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "<p class='ok'>✓ cURL returned valid JSON</p>";
    } else {
        echo "<p class='error'>✗ cURL returned invalid JSON</p>";
    }
}

echo "<h2>3. Database Connection Test</h2>";
include_once('includes/database/db_conn.php');
if (isset($connect) && $connect) {
    echo "<p class='ok'>✓ Database connection successful</p>";
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
}

?>