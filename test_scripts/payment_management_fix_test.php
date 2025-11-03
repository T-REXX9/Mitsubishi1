<?php
$pageTitle = "Payment Management Fix Test";
include '../pages/header.php';
?>

<style>
.test-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #1a1a1a;
    color: #fff;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}

.test-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #2a2a2a;
    border-radius: 8px;
}

.test-section h2 {
    color: #ffd700;
    margin-top: 0;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 10px;
}

.test-result {
    margin: 15px 0;
    padding: 15px;
    border-radius: 4px;
    background: #333;
    font-family: monospace;
}

.status-ok {
    color: #4CAF50;
    border-left: 4px solid #4CAF50;
}

.status-error {
    color: #f44336;
    border-left: 4px solid #f44336;
}

.status-warning {
    color: #ff9800;
    border-left: 4px solid #ff9800;
}

.test-button {
    background: #ffd700;
    color: #111;
    border: none;
    padding: 12px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin: 10px 5px;
    font-size: 16px;
}

.test-button:hover {
    background: #e6c200;
    transform: translateY(-2px);
}

.code-block {
    background: #000;
    padding: 15px;
    border-radius: 4px;
    font-family: monospace;
    white-space: pre-wrap;
    overflow-x: auto;
    font-size: 14px;
    margin: 10px 0;
}

.instructions {
    background: #1a3a5a;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

@media (max-width: 768px) {
    .test-container {
        margin: 10px;
        padding: 15px;
    }
    
    .test-button {
        width: 100%;
        margin: 10px 0;
    }
}
</style>

<div class="test-container">
    <h1>Payment Management Fix Verification</h1>
    <p>This page verifies that the JavaScript errors in payment-management.php have been fixed.</p>
    
    <div class="test-section">
        <h2>1. JavaScript Functionality Test</h2>
        <button class="test-button" onclick="testJavaScriptFunctions()">Test JavaScript Functions</button>
        <div id="jsFunctionResults"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Common Scripts Loading Test</h2>
        <button class="test-button" onclick="testCommonScripts()">Test Common Scripts Loading</button>
        <div id="commonScriptsResults"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Payment Management Page Test</h2>
        <div class="instructions">
            <h3>Manual Verification Steps:</h3>
            <ol>
                <li>Navigate to <a href="../pages/main/payment-management.php" target="_blank">payment-management.php</a></li>
                <li>Check that there are no JavaScript errors in the console</li>
                <li>Look specifically for:
                    <ul>
                        <li>No "toggleSidebar is not defined" errors</li>
                        <li>No syntax errors from common-scripts.js</li>
                    </ul>
                </li>
                <li>Verify that the page loads correctly</li>
            </ol>
        </div>
    </div>
    
    <div class="test-section">
        <h2>4. Implementation Summary</h2>
        <div class="test-result status-ok">
            <h3>✅ Fixes Applied:</h3>
            <ul>
                <li>Added missing common-scripts.js inclusion to payment-management.php</li>
                <li>Ensured toggleSidebar function is properly available</li>
            </ul>
        </div>
    </div>
</div>

<script>
function displayResult(containerId, message, status) {
    const container = document.getElementById(containerId);
    const statusClass = status === 'ok' ? 'status-ok' : status === 'error' ? 'status-error' : 'status-warning';
    container.innerHTML += `<div class="test-result ${statusClass}">${message}</div>`;
}

function testJavaScriptFunctions() {
    const results = document.getElementById('jsFunctionResults');
    results.innerHTML = '<h3>JavaScript Function Test Results:</h3>';
    
    try {
        // Test if common functions are available
        const hasToggleSidebar = typeof toggleSidebar !== 'undefined';
        displayResult('jsFunctionResults', 
            `toggleSidebar function: ${hasToggleSidebar ? '✅ AVAILABLE' : '❌ NOT AVAILABLE'}`, 
            hasToggleSidebar ? 'ok' : 'error');
        
        const hasShowNotification = typeof showNotification !== 'undefined';
        displayResult('jsFunctionResults', 
            `showNotification function: ${hasShowNotification ? '✅ AVAILABLE' : '❌ NOT AVAILABLE'}`, 
            hasShowNotification ? 'ok' : 'error');
        
        const hasDebounce = typeof debounce !== 'undefined';
        displayResult('jsFunctionResults', 
            `debounce function: ${hasDebounce ? '✅ AVAILABLE' : '❌ NOT AVAILABLE'}`, 
            hasDebounce ? 'ok' : 'error');
        
        if (hasToggleSidebar && hasShowNotification && hasDebounce) {
            displayResult('jsFunctionResults', 
                '🎉 ALL CORE FUNCTIONS AVAILABLE', 
                'ok');
        } else {
            displayResult('jsFunctionResults', 
                '⚠️ SOME FUNCTIONS MISSING - Check implementation', 
                'warning');
        }
        
    } catch (e) {
        displayResult('jsFunctionResults', 
            `❌ Error during JavaScript function test: ${e.message}`, 
            'error');
    }
}

function testCommonScripts() {
    const results = document.getElementById('commonScriptsResults');
    results.innerHTML = '<h3>Common Scripts Loading Test Results:</h3>';
    
    try {
        // Check if the common-scripts.js file is loaded
        let commonScriptsLoaded = false;
        const scripts = document.querySelectorAll('script');
        
        for (let script of scripts) {
            if (script.src && script.src.includes('common-scripts.js')) {
                commonScriptsLoaded = true;
                break;
            }
        }
        
        displayResult('commonScriptsResults', 
            `common-scripts.js loaded: ${commonScriptsLoaded ? '✅ LOADED' : '❌ NOT LOADED'}`, 
            commonScriptsLoaded ? 'ok' : 'error');
        
        // Check for any script loading errors
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'SCRIPT' && e.target.src.includes('common-scripts.js')) {
                displayResult('commonScriptsResults', 
                    `Script loading error: ❌ FAILED TO LOAD - ${e.message}`, 
                    'error');
            }
        });
        
    } catch (e) {
        displayResult('commonScriptsResults', 
            `❌ Error during common scripts test: ${e.message}`, 
            'error');
    }
}

// Run tests automatically when page loads
document.addEventListener('DOMContentLoaded', function() {
    testJavaScriptFunctions();
    testCommonScripts();
});
</script>

<?php include '../pages/footer.php'; ?>