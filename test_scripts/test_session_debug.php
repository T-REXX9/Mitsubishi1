<?php
// Test script to debug session issues with inquiry_actions.php

echo "<h1>Session Debug Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}</style>";

// Start session
session_start();

echo "<h2>Test 1: Current Session State</h2>";
echo "<p class='info'>Session ID: " . session_id() . "</p>";
echo "<p class='info'>Session Data:</p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>Test 2: Set Sales Agent Session</h2>";
$_SESSION['user_role'] = 'Sales Agent';
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Sales Agent';

echo "<p class='ok'>✓ Session variables set:</p>";
echo "<ul>";
echo "<li>user_role: " . $_SESSION['user_role'] . "</li>";
echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
echo "<li>user_name: " . $_SESSION['user_name'] . "</li>";
echo "</ul>";

echo "<h2>Test 3: Authorization Check (Same as inquiry_actions.php)</h2>";
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Sales Agent'])) {
    echo "<p class='error'>✗ Authorization FAILED - This would cause 403 error</p>";
    echo "<p>Reason: ";
    if (!isset($_SESSION['user_role'])) {
        echo "user_role not set in session";
    } else {
        echo "user_role '" . $_SESSION['user_role'] . "' not in allowed roles";
    }
    echo "</p>";
} else {
    echo "<p class='ok'>✓ Authorization PASSED - Sales Agent can access inquiry_actions.php</p>";
}

echo "<h2>Test 4: Test POST Request to inquiry_actions.php</h2>";
echo "<button onclick='testInquiryActions()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Inquiry Actions POST</button>";
echo "<div id='test-result' style='margin-top: 20px;'></div>";

?>

<script>
function testInquiryActions() {
    const resultDiv = document.getElementById('test-result');
    resultDiv.innerHTML = '<p style="color: #007bff;">Testing POST request to inquiry_actions.php...</p>';
    
    const formData = new FormData();
    formData.append('inquiry_id', '1');
    formData.append('response_type', 'general');
    formData.append('response_message', 'This is a test response message to debug the 403 error. This message is longer than 10 characters to pass validation.');
    
    fetch('pages/main/inquiry_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        return response.text().then(text => {
            return {
                status: response.status,
                statusText: response.statusText,
                text: text
            };
        });
    })
    .then(data => {
        let resultHtml = `
            <h3>Response Received:</h3>
            <p><strong>Status:</strong> ${data.status} ${data.statusText}</p>
            <p><strong>Response Body:</strong></p>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;">${data.text}</pre>
        `;
        
        if (data.status === 403) {
            resultHtml += '<p style="color: red;"><strong>403 Forbidden Error Confirmed!</strong> Check the debug information above.</p>';
        } else if (data.status === 200) {
            resultHtml += '<p style="color: green;"><strong>Success!</strong> No 403 error.</p>';
        } else {
            resultHtml += `<p style="color: orange;"><strong>Unexpected status:</strong> ${data.status}</p>`;
        }
        
        resultDiv.innerHTML = resultHtml;
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = `
            <h3 style="color: red;">Error:</h3>
            <p>${error.message}</p>
        `;
    });
}
</script>

<p><a href="pages/main/dashboard.php">Go to Dashboard</a> | <a href="pages/main/inquiries.php">Go to Inquiries</a></p>