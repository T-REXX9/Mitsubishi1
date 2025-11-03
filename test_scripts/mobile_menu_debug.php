<?php
$pageTitle = "Mobile Menu Debug";
include '../pages/header.php';
?>

<style>
.debug-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #1a1a1a;
    color: #fff;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}

.debug-section {
    margin-bottom: 30px;
    padding: 15px;
    background: #2a2a2a;
    border-radius: 5px;
}

.debug-section h2 {
    color: #ffd700;
    margin-top: 0;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-ok {
    background-color: #4CAF50;
}

.status-error {
    background-color: #f44336;
}

.test-result {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
    background: #333;
}

.test-button {
    background: #ffd700;
    color: #111;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin: 5px;
}

.test-button:hover {
    background: #e6c200;
}

.code-block {
    background: #000;
    padding: 15px;
    border-radius: 4px;
    font-family: monospace;
    white-space: pre-wrap;
    overflow-x: auto;
}

@media (max-width: 768px) {
    .debug-container {
        margin: 10px;
        padding: 15px;
    }
}
</style>

<div class="debug-container">
    <h1>Mobile Menu Debug Tool</h1>
    <p>This tool helps diagnose issues with the mobile hamburger menu on Hostinger deployment.</p>
    
    <div class="debug-section">
        <h2>1. Environment Detection</h2>
        <div class="test-result">
            <span class="status-indicator <?php echo ($_SERVER['SERVER_NAME'] == 'localhost') ? 'status-ok' : 'status-error'; ?>"></span>
            <strong>Server:</strong> <?php echo $_SERVER['SERVER_NAME']; ?>
        </div>
        <div class="test-result">
            <span class="status-indicator status-ok"></span>
            <strong>User Agent:</strong> <?php echo $_SERVER['HTTP_USER_AGENT']; ?>
        </div>
    </div>
    
    <div class="debug-section">
        <h2>2. Asset Loading Test</h2>
        <button class="test-button" onclick="testAssets()">Test CSS/JS Loading</button>
        <div id="assetTestResults"></div>
    </div>
    
    <div class="debug-section">
        <h2>3. DOM Element Check</h2>
        <button class="test-button" onclick="testDOMElements()">Check Required Elements</button>
        <div id="domTestResults"></div>
    </div>
    
    <div class="debug-section">
        <h2>4. Event Listener Test</h2>
        <button class="test-button" onclick="testEventListeners()">Test Menu Events</button>
        <div id="eventTestResults"></div>
    </div>
    
    <div class="debug-section">
        <h2>5. CSS Animation Support</h2>
        <button class="test-button" onclick="testCSSSupport()">Check CSS Support</button>
        <div id="cssTestResults"></div>
    </div>
    
    <div class="debug-section">
        <h2>6. Manual Menu Toggle Test</h2>
        <p>Click the button below to manually trigger the menu toggle function:</p>
        <button class="test-button" onclick="manualToggleTest()">Toggle Menu Manually</button>
        <div id="manualToggleResults"></div>
    </div>
</div>

<script>
// Test asset loading
function testAssets() {
    const results = document.getElementById('assetTestResults');
    results.innerHTML = '<p>Testing asset loading...</p>';
    
    // Check if mobile-fix.css is loaded
    let cssLoaded = false;
    for (let i = 0; i < document.styleSheets.length; i++) {
        const href = document.styleSheets[i].href;
        if (href && href.includes('mobile-fix.css')) {
            cssLoaded = true;
            break;
        }
    }
    
    // Check if mobile-fix.js is loaded
    const jsLoaded = typeof toggleMenu !== 'undefined';
    
    results.innerHTML = `
        <div class="test-result">
            <span class="status-indicator ${cssLoaded ? 'status-ok' : 'status-error'}"></span>
            mobile-fix.css: ${cssLoaded ? 'Loaded' : 'Not Loaded'}
        </div>
        <div class="test-result">
            <span class="status-indicator ${jsLoaded ? 'status-ok' : 'status-error'}"></span>
            mobile-fix.js: ${jsLoaded ? 'Loaded' : 'Not Loaded'}
        </div>
    `;
}

// Test DOM elements
function testDOMElements() {
    const results = document.getElementById('domTestResults');
    results.innerHTML = '<p>Checking DOM elements...</p>';
    
    const navMenu = document.getElementById('navMenu');
    const menuToggle = document.querySelector('.menu-toggle');
    
    results.innerHTML = `
        <div class="test-result">
            <span class="status-indicator ${navMenu ? 'status-ok' : 'status-error'}"></span>
            #navMenu element: ${navMenu ? 'Found' : 'Missing'}
        </div>
        <div class="test-result">
            <span class="status-indicator ${menuToggle ? 'status-ok' : 'status-error'}"></span>
            .menu-toggle element: ${menuToggle ? 'Found' : 'Missing'}
        </div>
        ${navMenu && menuToggle ? 
            '<div class="test-result"><span class="status-indicator status-ok"></span>All required elements found</div>' : 
            '<div class="test-result"><span class="status-indicator status-error"></span>Missing required elements</div>'}
    `;
}

// Test event listeners
function testEventListeners() {
    const results = document.getElementById('eventTestResults');
    results.innerHTML = '<p>Testing event listeners...</p>';
    
    try {
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
            // Try to trigger the toggle function
            const beforeNavClass = document.getElementById('navMenu')?.className || '';
            toggleMenu();
            const afterNavClass = document.getElementById('navMenu')?.className || '';
            
            const toggled = beforeNavClass !== afterNavClass;
            
            // Reset to original state
            if (toggled) {
                toggleMenu();
            }
            
            results.innerHTML = `
                <div class="test-result">
                    <span class="status-indicator ${toggled ? 'status-ok' : 'status-error'}"></span>
                    Menu toggle function: ${toggled ? 'Working' : 'Not Working'}
                </div>
            `;
        } else {
            results.innerHTML = '<div class="test-result"><span class="status-indicator status-error"></span>Menu toggle element not found</div>';
        }
    } catch (e) {
        results.innerHTML = `
            <div class="test-result">
                <span class="status-indicator status-error"></span>
                Error testing event listeners: ${e.message}
            </div>
        `;
    }
}

// Test CSS support
function testCSSSupport() {
    const results = document.getElementById('cssTestResults');
    results.innerHTML = '<p>Checking CSS support...</p>';
    
    // Test transform support
    const transformSupported = typeof document.body.style.transform !== 'undefined';
    const webkitTransformSupported = typeof document.body.style.webkitTransform !== 'undefined';
    
    // Test transition support
    const transitionSupported = typeof document.body.style.transition !== 'undefined';
    
    results.innerHTML = `
        <div class="test-result">
            <span class="status-indicator ${transformSupported ? 'status-ok' : 'status-error'}"></span>
            CSS Transform: ${transformSupported ? 'Supported' : 'Not Supported'}
        </div>
        <div class="test-result">
            <span class="status-indicator ${webkitTransformSupported ? 'status-ok' : 'status-error'}"></span>
            CSS Webkit Transform: ${webkitTransformSupported ? 'Supported' : 'Not Supported'}
        </div>
        <div class="test-result">
            <span class="status-indicator ${transitionSupported ? 'status-ok' : 'status-error'}"></span>
            CSS Transition: ${transitionSupported ? 'Supported' : 'Not Supported'}
        </div>
    `;
}

// Manual toggle test
function manualToggleTest() {
    const results = document.getElementById('manualToggleResults');
    results.innerHTML = '<p>Manually triggering menu toggle...</p>';
    
    try {
        // Log current state
        const navMenu = document.getElementById('navMenu');
        const menuToggle = document.querySelector('.menu-toggle');
        
        const navActive = navMenu?.classList.contains('active');
        const toggleActive = menuToggle?.classList.contains('active');
        
        results.innerHTML += `<p>Before toggle - Nav: ${navActive}, Toggle: ${toggleActive}</p>`;
        
        // Trigger toggle
        toggleMenu();
        
        // Log new state
        const newNavActive = navMenu?.classList.contains('active');
        const newToggleActive = menuToggle?.classList.contains('active');
        
        results.innerHTML += `<p>After toggle - Nav: ${newNavActive}, Toggle: ${newToggleActive}</p>`;
        results.innerHTML += '<div class="test-result"><span class="status-indicator status-ok"></span>Manual toggle executed</div>';
        
        // Toggle back to original state
        setTimeout(() => {
            toggleMenu();
            results.innerHTML += '<p>Menu reset to original state</p>';
        }, 1000);
        
    } catch (e) {
        results.innerHTML += `
            <div class="test-result">
                <span class="status-indicator status-error"></span>
                Error during manual toggle: ${e.message}
            </div>
        `;
    }
}

// Log page load information
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile Menu Debug Page Loaded');
    console.log('Server:', '<?php echo $_SERVER['SERVER_NAME']; ?>');
    console.log('User Agent:', navigator.userAgent);
});
</script>

<?php include '../pages/footer.php'; ?>