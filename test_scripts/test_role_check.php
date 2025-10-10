<?php
// Quick session role test
session_start();

echo "<h1>Role Check Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}</style>";

// Set up test session for Sales Agent
$_SESSION['user_role'] = 'SalesAgent';  // No space - this is how it's stored in the database
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['username'] = 'test_sales_agent';

echo "<h2>Current Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>Role Authorization Tests:</h2>";

// Test 1: Exact role check
echo "<h3>Test 1: Check with 'Sales Agent' (with space)</h3>";
$allowed_roles = ['Admin', 'Sales Agent'];
if (in_array($_SESSION['user_role'], $allowed_roles)) {
    echo "<p class='ok'>✓ 'Sales Agent' (with space) is ALLOWED</p>";
} else {
    echo "<p class='error'>✗ 'Sales Agent' (with space) is DENIED</p>";
}

// Test 2: Check with SalesAgent (no space)
echo "<h3>Test 2: Check with 'SalesAgent' (no space)</h3>";
$allowed_roles2 = ['Admin', 'SalesAgent'];
if (in_array($_SESSION['user_role'], $allowed_roles2)) {
    echo "<p class='ok'>✓ Current role matches 'SalesAgent'</p>";
} else {
    echo "<p class='error'>✗ Current role does NOT match 'SalesAgent'</p>";
}

// Test 3: Show what we need to use
echo "<h3>Conclusion:</h3>";
echo "<p class='info'>Role in session: '<strong>" . $_SESSION['user_role'] . "</strong>'</p>";
echo "<p class='info'>For inquiry_actions.php, we need to allow: <strong>['Admin', 'Sales Agent']</strong></p>";

// Test the actual inquiry_actions.php authorization
echo "<h2>Test inquiry_actions.php Authorization:</h2>";
if (!isset($_SESSION['user_role'])) {
    echo "<p class='error'>✗ NO_ROLE: user_role not set</p>";
} elseif (!in_array($_SESSION['user_role'], ['Admin', 'SalesAgent'])) {
    echo "<p class='error'>✗ INVALID_ROLE: Role '" . $_SESSION['user_role'] . "' not in allowed roles</p>";
} else {
    echo "<p class='ok'>✓ AUTHORIZED: Role '" . $_SESSION['user_role'] . "' is allowed</p>";
}

// Now test a POST request to inquiry_actions.php
echo "<h2>Test POST Request:</h2>";
echo "<button onclick='testPost()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;'>Test inquiry_actions.php POST</button>";
echo "<div id='result' style='margin-top: 15px;'></div>";
?>

<script>
function testPost() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p style="color: blue;">Testing POST request...</p>';
    
    const formData = new FormData();
    formData.append('inquiry_id', '1');
    formData.append('response_type', 'general');
    formData.append('response_message', 'This is a test message from the role check test. Testing if the 403 error is resolved.');
    
    fetch('pages/main/inquiry_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => ({
            status: response.status,
            statusText: response.statusText,
            body: text
        }));
    })
    .then(data => {
        let color = 'green';
        if (data.status === 403) color = 'red';
        else if (data.status !== 200) color = 'orange';
        
        resultDiv.innerHTML = `
            <p style="color: ${color};"><strong>Status:</strong> ${data.status} ${data.statusText}</p>
            <p><strong>Response:</strong></p>
            <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">${data.body}</pre>
        `;
    })
    .catch(error => {
        resultDiv.innerHTML = `<p style="color: red;"><strong>Error:</strong> ${error.message}</p>`;
    });
}
</script>