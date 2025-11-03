<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title>Mobile Responsiveness Test - Mitsubishi Motors</title>
  
  <!-- Load the comprehensive mobile responsive fix -->
  <link rel="stylesheet" href="../css/mobile-responsive-fix.css">
  <script src="../js/mobile-responsive-fix.js" defer></script>
  
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background: #f5f5f5;
    }
    
    .test-container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: #c8102e;
      border-bottom: 3px solid #c8102e;
      padding-bottom: 10px;
    }
    
    .test-section {
      margin: 30px 0;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    .test-section h2 {
      color: #333;
      margin-top: 0;
    }
    
    /* Test cases */
    .long-text-test {
      background: #f9f9f9;
      padding: 15px;
      margin: 10px 0;
    }
    
    .wide-table-container {
      overflow-x: auto;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
    }
    
    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }
    
    th {
      background: #c8102e;
      color: white;
    }
    
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }
    
    .card {
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .form-test {
      margin: 20px 0;
    }
    
    .form-group {
      margin: 15px 0;
    }
    
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    
    input, select, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
    }
    
    .button-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 20px 0;
    }
    
    button {
      padding: 12px 24px;
      background: #c8102e;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }
    
    button:hover {
      background: #a00d25;
    }
    
    .status-info {
      background: #e3f2fd;
      padding: 15px;
      border-left: 4px solid #2196F3;
      margin: 20px 0;
    }
    
    .error-box {
      background: #ffebee;
      padding: 15px;
      border-left: 4px solid #f44336;
      margin: 20px 0;
    }
    
    .success-box {
      background: #e8f5e9;
      padding: 15px;
      border-left: 4px solid #4caf50;
      margin: 20px 0;
    }
    
    code {
      background: #f5f5f5;
      padding: 2px 6px;
      border-radius: 3px;
      font-family: 'Courier New', monospace;
    }
  </style>
</head>
<body>
  <div class="test-container">
    <h1>📱 Mobile Responsiveness Test Page</h1>
    
    <div class="status-info">
      <strong>Test Instructions:</strong>
      <ul>
        <li>View this page on a mobile device (phone or tablet)</li>
        <li>Scroll horizontally - there should be NO horizontal scrolling</li>
        <li>Check that all text is readable and not cut off</li>
        <li>Verify that tables, cards, and forms display properly</li>
        <li>Test on both portrait and landscape orientations</li>
      </ul>
    </div>

    <!-- Test 1: Long Text -->
    <div class="test-section">
      <h2>Test 1: Long Text Without Breaks</h2>
      <div class="long-text-test">
        <p><strong>This is a very long text string without spaces to test word breaking:</strong></p>
        <p>ThisIsAVeryLongTextStringWithoutSpacesToTestIfTheTextWillWrapProperlyOnMobileDevicesAndNotCauseHorizontalScrollingOrTextTruncationIssues</p>
        <p><strong>Long URL test:</strong></p>
        <p>https://www.example.com/very/long/url/path/that/might/cause/overflow/issues/on/mobile/devices/testing/responsiveness</p>
      </div>
    </div>

    <!-- Test 2: Wide Table -->
    <div class="test-section">
      <h2>Test 2: Wide Table</h2>
      <div class="wide-table-container">
        <table>
          <thead>
            <tr>
              <th>Vehicle Model</th>
              <th>Category</th>
              <th>Price</th>
              <th>Year</th>
              <th>Status</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td data-label="Vehicle Model">Mitsubishi Xpander</td>
              <td data-label="Category">SUV</td>
              <td data-label="Price">₱1,100,000</td>
              <td data-label="Year">2025</td>
              <td data-label="Status">Available</td>
              <td data-label="Description">This is a very long description of the Mitsubishi Xpander that should wrap properly on mobile devices without being cut off or causing horizontal scrolling issues. It features a fuel-efficient 1.5L MIVEC DOHC gasoline engine.</td>
            </tr>
            <tr>
              <td data-label="Vehicle Model">Mitsubishi Montero Sport</td>
              <td data-label="Category">SUV</td>
              <td data-label="Price">₱1,850,000</td>
              <td data-label="Year">2025</td>
              <td data-label="Status">Available</td>
              <td data-label="Description">Premium SUV with advanced safety features and luxurious interior. Perfect for family adventures and long road trips with comfort and style.</td>
            </tr>
            <tr>
              <td data-label="Vehicle Model">Mitsubishi Mirage G4</td>
              <td data-label="Category">Sedan</td>
              <td data-label="Price">₱759,000</td>
              <td data-label="Year">2025</td>
              <td data-label="Status">Available</td>
              <td data-label="Description">Compact and fuel-efficient sedan perfect for city driving. Features modern technology and excellent fuel economy for daily commuting.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Test 3: Card Grid -->
    <div class="test-section">
      <h2>Test 3: Card Grid Layout</h2>
      <div class="card-grid">
        <div class="card">
          <h3>Card 1</h3>
          <p>This is a test card with some content. The text should wrap properly and not be truncated on mobile devices.</p>
          <button>Action Button</button>
        </div>
        <div class="card">
          <h3>Card 2 with Long Title That Should Wrap</h3>
          <p>Another test card with longer content to verify that everything displays correctly without causing layout issues or horizontal scrolling problems.</p>
          <button>Another Action</button>
        </div>
        <div class="card">
          <h3>Card 3</h3>
          <p>Short content card.</p>
          <button>Click Me</button>
        </div>
      </div>
    </div>

    <!-- Test 4: Form Elements -->
    <div class="test-section">
      <h2>Test 4: Form Elements</h2>
      <div class="form-test">
        <div class="form-group">
          <label for="name">Full Name:</label>
          <input type="text" id="name" placeholder="Enter your full name">
        </div>
        <div class="form-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" placeholder="your.email@example.com">
        </div>
        <div class="form-group">
          <label for="vehicle">Select Vehicle:</label>
          <select id="vehicle">
            <option>Mitsubishi Xpander</option>
            <option>Mitsubishi Montero Sport</option>
            <option>Mitsubishi Mirage G4</option>
            <option>Mitsubishi L300</option>
          </select>
        </div>
        <div class="form-group">
          <label for="comments">Comments:</label>
          <textarea id="comments" rows="4" placeholder="Enter your comments here..."></textarea>
        </div>
        <div class="button-group">
          <button>Submit Form</button>
          <button>Reset</button>
          <button>Cancel</button>
        </div>
      </div>
    </div>

    <!-- Test 5: Long Content -->
    <div class="test-section">
      <h2>Test 5: Long Paragraph Content</h2>
      <p>
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
        Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
      </p>
      <p>
        Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. 
        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, 
        eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
      </p>
    </div>

    <!-- Test 6: Nested Elements -->
    <div class="test-section">
      <h2>Test 6: Nested Elements</h2>
      <div style="padding: 15px; background: #f9f9f9;">
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd;">
          <h3>Nested Container</h3>
          <p>This is content inside nested containers. It should not cause overflow issues even with multiple levels of nesting.</p>
          <div style="padding: 10px; background: #e9e9e9;">
            <p>Third level nesting with some text content that should wrap properly on all devices.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Results -->
    <div class="test-section">
      <h2>📊 Test Results</h2>
      <div id="test-results">
        <div class="success-box">
          <strong>✓ Mobile Fix Loaded</strong>
          <p>The mobile responsive fix has been applied to this page.</p>
        </div>
        <div class="status-info">
          <strong>Device Information:</strong>
          <ul id="device-info">
            <li>Loading device information...</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Instructions -->
    <div class="test-section">
      <h2>📋 Implementation Instructions</h2>
      <div class="success-box">
        <h3>How to Apply to Your Pages:</h3>
        <p><strong>Method 1: Using the Include File (Recommended)</strong></p>
        <p>Add this line in the <code>&lt;head&gt;</code> section of your PHP pages:</p>
        <code>&lt;?php include '../includes/components/mobile-responsive-include.php'; ?&gt;</code>
        
        <p style="margin-top: 20px;"><strong>Method 2: Manual Include</strong></p>
        <p>Add these lines in the <code>&lt;head&gt;</code> section:</p>
        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;">
&lt;link rel="stylesheet" href="../css/mobile-responsive-fix.css"&gt;
&lt;script src="../js/mobile-responsive-fix.js" defer&gt;&lt;/script&gt;</pre>
        
        <p style="margin-top: 20px;"><strong>For Admin Pages (pages/main/):</strong></p>
        <code>&lt;link rel="stylesheet" href="../../css/mobile-responsive-fix.css"&gt;</code><br>
        <code>&lt;script src="../../js/mobile-responsive-fix.js" defer&gt;&lt;/script&gt;</code>
      </div>
    </div>
  </div>

  <!-- Test Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Display device information
      const deviceInfo = document.getElementById('device-info');
      if (deviceInfo) {
        deviceInfo.innerHTML = `
          <li>Screen Width: ${window.innerWidth}px</li>
          <li>Screen Height: ${window.innerHeight}px</li>
          <li>User Agent: ${navigator.userAgent}</li>
          <li>Mobile Device: ${window.mobileResponsiveFix ? (window.mobileResponsiveFix.isMobile ? 'Yes' : 'No') : 'Checking...'}</li>
          <li>Tablet Device: ${window.mobileResponsiveFix ? (window.mobileResponsiveFix.isTablet ? 'Yes' : 'No') : 'Checking...'}</li>
          <li>iOS Device: ${window.mobileResponsiveFix ? (window.mobileResponsiveFix.isIOS ? 'Yes' : 'No') : 'Checking...'}</li>
          <li>Android Device: ${window.mobileResponsiveFix ? (window.mobileResponsiveFix.isAndroid ? 'Yes' : 'No') : 'Checking...'}</li>
        `;
      }

      // Check for horizontal scroll
      setTimeout(function() {
        const hasHorizontalScroll = document.documentElement.scrollWidth > document.documentElement.clientWidth;
        const scrollInfo = document.createElement('div');
        
        if (hasHorizontalScroll) {
          scrollInfo.className = 'error-box';
          scrollInfo.innerHTML = `
            <strong>⚠️ Horizontal Scroll Detected</strong>
            <p>Document width: ${document.documentElement.scrollWidth}px</p>
            <p>Viewport width: ${document.documentElement.clientWidth}px</p>
            <p>Please report this to the development team.</p>
          `;
        } else {
          scrollInfo.className = 'success-box';
          scrollInfo.innerHTML = `
            <strong>✓ No Horizontal Scroll Detected</strong>
            <p>All content fits within the viewport properly.</p>
          `;
        }
        
        document.getElementById('test-results').appendChild(scrollInfo);
      }, 1000);
    });
  </script>
</body>
</html>
