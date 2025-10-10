<?php
// Simple test for DeepSeek API functionality
session_start();

// Set up a test session
$_SESSION['user_role'] = 'Customer';
$_SESSION['user_id'] = 1;

echo "<h1>DeepSeek API Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}</style>";

echo "<h2>1. Testing API Key</h2>";
$apiKeyFile = 'apikey.txt';
if (file_exists($apiKeyFile)) {
    $apiKey = trim(file_get_contents($apiKeyFile));
    echo "<p class='ok'>✓ API key found: " . substr($apiKey, 0, 10) . "...</p>";
} else {
    echo "<p class='error'>✗ API key file not found</p>";
    exit();
}

echo "<h2>2. Testing Database Connection</h2>";
include_once('includes/database/db_conn.php');
if (isset($connect) && $connect) {
    echo "<p class='ok'>✓ Database connection successful</p>";
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
    exit();
}

echo "<h2>3. Testing DeepSeek API Direct Call</h2>";
function testDeepSeekAPI($message, $apiKey) {
    $apiEndpoint = 'https://api.deepseek.com/chat/completions';
    
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a helpful Mitsubishi Motors assistant for customers. Provide information about vehicles, pricing, and dealership services."
            ],
            [
                "role" => "user", 
                "content" => $message
            ]
        ],
        "temperature" => 0.7,
        "max_tokens" => 500
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

$testMessage = "Do you have a Montero Sport available?";
echo "<p class='info'>Testing message: '$testMessage'</p>";

$result = testDeepSeekAPI($testMessage, $apiKey);

echo "<h3>API Response:</h3>";
echo "<p>HTTP Code: " . $result['http_code'] . "</p>";

if ($result['error']) {
    echo "<p class='error'>cURL Error: " . $result['error'] . "</p>";
} else {
    echo "<p class='ok'>✓ No cURL errors</p>";
}

if ($result['response']) {
    $decodedResponse = json_decode($result['response'], true);
    
    if ($decodedResponse && isset($decodedResponse['choices'][0]['message']['content'])) {
        echo "<p class='ok'>✓ API response successful!</p>";
        echo "<h4>AI Response:</h4>";
        echo "<div style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
        echo nl2br(htmlspecialchars($decodedResponse['choices'][0]['message']['content']));
        echo "</div>";
    } else {
        echo "<p class='error'>✗ Invalid API response format</p>";
        echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ No response from API</p>";
}

echo "<h2>4. Testing Internal API</h2>";
echo "<p class='info'>Testing includes/api/deepseek_chatbot.php...</p>";

// Test the internal API
$internalApiPath = 'includes/api/deepseek_chatbot.php';
if (file_exists($internalApiPath)) {
    echo "<p class='ok'>✓ Internal API file exists</p>";
    
    // Simulate POST request to internal API
    $_POST['message'] = $testMessage;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    ob_start();
    try {
        include($internalApiPath);
        $output = ob_get_contents();
    } catch (Exception $e) {
        $output = "Error: " . $e->getMessage();
    }
    ob_end_clean();
    
    echo "<h4>Internal API Output:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} else {
    echo "<p class='error'>✗ Internal API file not found</p>";
}

echo "<h2>Test Complete</h2>";
?>