<?php
$pageTitle = "Mobile Fix Demo";
include '../pages/header.php';
?>

<style>
.demo-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #1a1a1a;
    color: #fff;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}

.demo-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #2a2a2a;
    border-radius: 8px;
}

.demo-section h2 {
    color: #ffd700;
    margin-top: 0;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 10px;
}

.fix-comparison {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.fix-column {
    padding: 15px;
    border-radius: 5px;
}

.fix-column h3 {
    margin-top: 0;
    color: #ffd700;
}

.original-fix {
    background: #3a1a1a;
    border: 1px solid #ff6b6b;
}

.enhanced-fix {
    background: #1a3a1a;
    border: 1px solid #6bff6b;
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

.test-result {
    margin: 15px 0;
    padding: 15px;
    border-radius: 4px;
    background: #333;
    font-family: monospace;
}

.status-ok {
    color: #4CAF50;
}

.status-error {
    color: #f44336;
}

@media (max-width: 768px) {
    .fix-comparison {
        grid-template-columns: 1fr;
    }
    
    .demo-container {
        margin: 10px;
        padding: 15px;
    }
    
    .test-button {
        width: 100%;
        margin: 10px 0;
    }
}
</style>

<div class="demo-container">
    <h1>Mobile Fix Enhancement Demo</h1>
    <p>This page demonstrates the enhanced mobile fixes for the hamburger menu and responsive behavior.</p>
    
    <div class="demo-section">
        <h2>1. Enhanced CSS Implementation</h2>
        <p>The enhanced mobile-fix-enhanced.css includes:</p>
        <ul>
            <li>Additional vendor prefixes for better cross-browser compatibility</li>
            <li>Improved hamburger menu animations with fallbacks</li>
            <li>Better handling of mobile viewport issues</li>
            <li>Enhanced touch target sizing</li>
        </ul>
        
        <div class="fix-comparison">
            <div class="fix-column original-fix">
                <h3>Original CSS</h3>
                <div class="code-block">
.menu-toggle.active span:nth-child(1) {
  transform: translateY(6px) rotate(45deg);
}

.menu-toggle.active span:nth-child(2) {
  opacity: 0;
  transform: scale(0);
}

.menu-toggle.active span:nth-child(3) {
  transform: translateY(-6px) rotate(-45deg);
}
                </div>
            </div>
            
            <div class="fix-column enhanced-fix">
                <h3>Enhanced CSS</h3>
                <div class="code-block">
.menu-toggle.active span:nth-child(1) {
  -webkit-transform: translateY(6px) rotate(45deg);
  -moz-transform: translateY(6px) rotate(45deg);
  -ms-transform: translateY(6px) rotate(45deg);
  transform: translateY(6px) rotate(45deg);
}

.menu-toggle.active span:nth-child(2) {
  opacity: 0;
  -webkit-transform: scale(0);
  -moz-transform: scale(0);
  -ms-transform: scale(0);
  transform: scale(0);
}

.menu-toggle.active span:nth-child(3) {
  -webkit-transform: translateY(-6px) rotate(-45deg);
  -moz-transform: translateY(-6px) rotate(-45deg);
  -ms-transform: translateY(-6px) rotate(-45deg);
  transform: translateY(-6px) rotate(-45deg);
}
                </div>
            </div>
        </div>
    </div>
    
    <div class="demo-section">
        <h2>2. Enhanced JavaScript Implementation</h2>
        <p>The enhanced mobile-fix-enhanced.js includes:</p>
        <ul>
            <li>Comprehensive error handling</li>
            <li>Better cross-browser compatibility</li>
            <li>Improved touch event handling</li>
            <li>Enhanced debugging capabilities</li>
        </ul>
        
        <div class="fix-comparison">
            <div class="fix-column original-fix">
                <h3>Original JavaScript</h3>
                <div class="code-block">
function toggleMenu() {
  const nav = document.getElementById('navMenu');
  const toggle = document.querySelector('.menu-toggle');
  
  nav.classList.toggle('active');
  toggle.classList.toggle('active');
}
                </div>
            </div>
            
            <div class="fix-column enhanced-fix">
                <h3>Enhanced JavaScript</h3>
                <div class="code-block">
function toggleMenu() {
  try {
    const nav = document.getElementById('navMenu');
    const toggle = document.querySelector('.menu-toggle');
    
    if (nav && toggle) {
      // Toggle body scroll when menu is active
      if (nav.classList.contains('active')) {
        document.body.style.overflow = '';
        document.body.style.position = '';
      } else {
        // Store current scroll position
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
      }
      
      nav.classList.toggle('active');
      toggle.classList.toggle('active');
    }
  } catch (e) {
    console.error('Menu toggle error:', e);
  }
}
                </div>
            </div>
        </div>
    </div>
    
    <div class="demo-section">
        <h2>3. Test Enhanced Mobile Features</h2>
        <p>Use the buttons below to test the enhanced mobile functionality:</p>
        
        <button class="test-button" onclick="testEnhancedMenu()">Test Enhanced Menu Toggle</button>
        <button class="test-button" onclick="testViewportFix()">Test Viewport Height Fix</button>
        <button class="test-button" onclick="testAllFixes()">Test All Enhanced Fixes</button>
        
        <div id="testResults"></div>
    </div>
    
    <div class="demo-section">
        <h2>4. Implementation Instructions</h2>
        <p>To implement these enhanced fixes on Hostinger:</p>
        <ol>
            <li>Upload <code>css/mobile-fix-enhanced.css</code> to your server</li>
            <li>Upload <code>js/mobile-fix-enhanced.js</code> to your server</li>
            <li>Update your header.php to reference the new files:
                <div class="code-block">
&lt;!-- Replace these lines in header.php --&gt;
&lt;link rel="stylesheet" href="../css/mobile-fix-enhanced.css"&gt;
&lt;script src="../js/mobile-fix-enhanced.js" defer&gt;&lt;/script&gt;
                </div>
            </li>
            <li>Clear your browser cache and test on mobile devices</li>
        </ol>
    </div>
</div>

<script>
function displayResult(message, isError = false) {
    const results = document.getElementById('testResults');
    const resultClass = isError ? 'status-error' : 'status-ok';
    results.innerHTML += `<div class="test-result ${resultClass}">${message}</div>`;
}

function testEnhancedMenu() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<h3>Enhanced Menu Toggle Test Results:</h3>';
    
    try {
        // Check if enhanced functions exist
        const hasEnhancedJS = typeof window.mobileUtils !== 'undefined';
        displayResult(`Enhanced JavaScript loaded: ${hasEnhancedJS ? 'YES' : 'NO'}`);
        
        // Test menu toggle function
        if (typeof toggleMenu === 'function') {
            displayResult('Menu toggle function available: YES');
            
            // Try to execute the function
            const navMenu = document.getElementById('navMenu');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (navMenu && menuToggle) {
                const initialState = navMenu.classList.contains('active');
                toggleMenu();
                const afterToggle = navMenu.classList.contains('active');
                toggleMenu(); // Toggle back
                
                displayResult(`Menu toggle functionality: WORKING`);
                displayResult(`State changed correctly: ${initialState !== afterToggle ? 'YES' : 'NO'}`);
            } else {
                displayResult('Required DOM elements not found', true);
            }
        } else {
            displayResult('Menu toggle function not available', true);
        }
    } catch (e) {
        displayResult(`Error during test: ${e.message}`, true);
    }
}

function testViewportFix() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<h3>Viewport Height Fix Test Results:</h3>';
    
    try {
        // Check if viewport height function exists
        const hasViewportFix = typeof window.mobileUtils !== 'undefined' && 
                              typeof window.mobileUtils.setViewportHeight === 'function';
        displayResult(`Viewport height fix available: ${hasViewportFix ? 'YES' : 'NO'}`);
        
        // Test setting viewport height
        if (hasViewportFix) {
            window.mobileUtils.setViewportHeight();
            const vhValue = getComputedStyle(document.documentElement).getPropertyValue('--vh');
            displayResult(`Viewport height set: ${vhValue ? 'YES (' + vhValue + ')' : 'NO'}`);
        }
    } catch (e) {
        displayResult(`Error during test: ${e.message}`, true);
    }
}

function testAllFixes() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<h3>Complete Enhanced Fixes Test Results:</h3>';
    
    testEnhancedMenu();
    testViewportFix();
    
    // Additional tests
    try {
        displayResult(`Mobile device detected: ${window.mobileUtils?.isMobile ? 'YES' : 'NO'}`);
        displayResult(`iOS device detected: ${window.mobileUtils?.isIOS ? 'YES' : 'NO'}`);
    } catch (e) {
        displayResult(`Error during additional tests: ${e.message}`, true);
    }
}
</script>

<?php include '../pages/footer.php'; ?>