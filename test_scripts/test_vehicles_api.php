<?php
// Test script to verify vehicles API endpoints

echo "<h2>Testing Vehicles API Endpoints</h2>\n";

// Test 1: Main vehicles endpoint
echo "<h3>1. Testing main vehicles endpoint</h3>\n";
$url1 = "http://localhost/Mitsubishi/api/vehicles.php";
$response1 = file_get_contents($url1);
echo "<strong>URL:</strong> $url1<br>\n";
echo "<strong>Response:</strong> <pre>" . htmlspecialchars($response1) . "</pre>\n";

// Test 2: Categories endpoint
echo "<h3>2. Testing categories endpoint</h3>\n";
$url2 = "http://localhost/Mitsubishi/api/vehicles.php?categories=1";
$response2 = file_get_contents($url2);
echo "<strong>URL:</strong> $url2<br>\n";
echo "<strong>Response:</strong> <pre>" . htmlspecialchars($response2) . "</pre>\n";

// Test 3: Stats endpoint
echo "<h3>3. Testing stats endpoint</h3>\n";
$url3 = "http://localhost/Mitsubishi/api/vehicles.php?stats=1";
$response3 = file_get_contents($url3);
echo "<strong>URL:</strong> $url3<br>\n";
echo "<strong>Response:</strong> <pre>" . htmlspecialchars($response3) . "</pre>\n";

// Check if responses are valid JSON
echo "<h3>JSON Validation Results:</h3>\n";
echo "Main vehicles: " . (json_decode($response1) !== null ? "Valid JSON" : "Invalid JSON") . "<br>\n";
echo "Categories: " . (json_decode($response2) !== null ? "Valid JSON" : "Invalid JSON") . "<br>\n";
echo "Stats: " . (json_decode($response3) !== null ? "Valid JSON" : "Invalid JSON") . "<br>\n";
?>