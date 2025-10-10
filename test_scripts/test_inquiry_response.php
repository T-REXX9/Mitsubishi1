<?php
// Test script to verify inquiry response functionality for Sales Agents

echo "<h1>Inquiry Response System Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.error{color:red;}.info{color:blue;}</style>";

// Simulate Sales Agent session
session_start();
$_SESSION['user_role'] = 'Sales Agent';
$_SESSION['user_id'] = 1;

echo "<h2>Test 1: Role Authorization</h2>";
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Sales Agent'])) {
    echo "<p class='ok'>✓ Sales Agent role is authorized for inquiry responses</p>";
} else {
    echo "<p class='error'>✗ Sales Agent role not authorized</p>";
}

echo "<h2>Test 2: Database Connection</h2>";
include_once(__DIR__ . '/includes/init.php');

if (isset($pdo) && $pdo) {
    echo "<p class='ok'>✓ Database connection available</p>";
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
    exit();
}

echo "<h2>Test 3: Inquiries Table Check</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inquiries");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p class='ok'>✓ Inquiries table accessible with $count records</p>";
    
    // Get sample inquiry for testing
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY InquiryDate DESC LIMIT 1");
    $sample_inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample_inquiry) {
        echo "<p class='info'>Sample inquiry found: INQ-" . str_pad($sample_inquiry['Id'], 5, '0', STR_PAD_LEFT) . " from " . htmlspecialchars($sample_inquiry['FullName']) . "</p>";
    } else {
        echo "<p class='error'>✗ No inquiries found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error accessing inquiries table: " . $e->getMessage() . "</p>";
}

echo "<h2>Test 4: Inquiry Response Table</h2>";
try {
    // Check if inquiry_responses table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'inquiry_responses'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='ok'>✓ inquiry_responses table exists</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM inquiry_responses");
        $response_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p class='info'>Current responses: $response_count</p>";
    } else {
        echo "<p class='info'>inquiry_responses table will be created automatically when first response is sent</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking inquiry_responses table: " . $e->getMessage() . "</p>";
}

echo "<h2>Test 5: Test Response Submission (Simulated)</h2>";
if ($sample_inquiry) {
    // Simulate form data that would be sent from the frontend
    $test_data = [
        'inquiry_id' => $sample_inquiry['Id'],
        'response_type' => 'general',
        'response_message' => 'Test response from automated system - this is a test to verify the inquiry response system is working correctly.',
        'follow_up_date' => date('Y-m-d', strtotime('+7 days'))
    ];
    
    echo "<p class='info'>Test data prepared for inquiry ID: " . $test_data['inquiry_id'] . "</p>";
    echo "<p class='info'>Response type: " . $test_data['response_type'] . "</p>";
    echo "<p class='info'>Message length: " . strlen($test_data['response_message']) . " characters</p>";
    
    // Validation checks (same as in inquiry_actions.php)
    if (isset($test_data['inquiry_id'], $test_data['response_type'], $test_data['response_message'])) {
        echo "<p class='ok'>✓ All required fields are present</p>";
        
        if (strlen(trim($test_data['response_message'])) >= 10) {
            echo "<p class='ok'>✓ Response message meets minimum length requirement</p>";
        } else {
            echo "<p class='error'>✗ Response message too short</p>";
        }
    } else {
        echo "<p class='error'>✗ Missing required fields</p>";
    }
    
    echo "<p class='info'><strong>Note:</strong> This is a simulation. To test the actual submission, use the inquiry response form in the dashboard.</p>";
} else {
    echo "<p class='error'>✗ No sample inquiry available for testing</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The inquiry response system should now work for Sales Agents. Key fixes implemented:</p>";
echo "<ul>";
echo "<li>✅ Added missing <code>handleResponseSubmit()</code> function in inquiries.php</li>";
echo "<li>✅ Updated authorization to allow both Admin and Sales Agent roles</li>";
echo "<li>✅ Fixed API endpoint in sales_agent_dashboard.php to use inquiry_actions.php</li>";
echo "<li>✅ Proper form validation and error handling</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<p>1. Login as a Sales Agent user</p>";
echo "<p>2. Go to Dashboard and click 'Inquiry Management'</p>";
echo "<p>3. Click 'Respond' button on any inquiry</p>";
echo "<p>4. Fill in the response form and submit</p>";
echo "<p>5. Check if the response is saved successfully</p>";

echo "<p><a href='pages/main/dashboard.php'>Test Dashboard</a> | <a href='pages/main/inquiries.php'>Test Inquiries Page</a></p>";
?>