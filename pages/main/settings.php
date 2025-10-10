<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Settings - Mitsubishi</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../includes/css/common-styles.css" rel="stylesheet">
  <style>
	  html, body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      overflow-y: auto;
      scroll-behavior: smooth;
    }
    
    body {
      zoom: 75%;
    }
    
    /* Custom scrollbar styling */
    ::-webkit-scrollbar {
      width: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 4px;
      transition: background 0.3s ease;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
    .page-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--border-light);
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary-dark);
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .tabs {
      background: white;
      border-radius: 8px;
      padding: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      display: flex;
      gap: 4px;
    }

    .tab-button {
      padding: 12px 20px;
      cursor: pointer;
      font-weight: 600;
      color: var(--text-dark);
      border: none;
      background: transparent;
      border-radius: 6px;
      transition: all 0.3s ease;
      flex: 1;
    }

    .tab-button.active {
      color: white;
      background: var(--primary-red);
      box-shadow: 0 2px 4px rgba(220, 20, 60, 0.3);
    }
    
    .tab-button:hover:not(.active) {
      background: var(--border-light);
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .settings-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      position: relative;
    }

    .settings-card:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
      transform: translateY(-2px);
    }

    .settings-header {
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 2px solid var(--border-light);
      position: relative;
    }

    .settings-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--primary-dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .settings-title::before {
      content: '';
      width: 4px;
      height: 24px;
      background: var(--primary-red);
      border-radius: 2px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      flex: 1;
      min-width: 220px;
    }

    .form-label {
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-dark);
      display: block;
      font-size: 14px;
      letter-spacing: 0.5px;
      transition: color 0.3s ease;
    }

    .form-group:focus-within .form-label {
      color: var(--primary-red);
    }

    .form-input, .form-select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e8ed;
      border-radius: 8px;
      font-size: 16px;
      color: var(--text-dark);
      background: white;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .form-input:hover, .form-select:hover {
      border-color: #c1c8cd;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .form-input:focus, .form-select:focus {
      border-color: var(--primary-red);
      outline: none;
      box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1), 0 4px 12px rgba(0, 0, 0, 0.15);
      transform: translateY(-1px);
    }

    .input-group {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-group .form-input {
      padding-right: 50px;
    }

    .input-suffix {
      position: absolute;
      right: 16px;
      color: var(--text-muted);
      font-weight: 500;
      pointer-events: none;
    }

    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 64px;
      height: 36px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: all 0.3s ease;
      border-radius: 36px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .slider:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15), inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 28px;
      width: 28px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      border-radius: 50%;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    input:checked + .slider {
      background-color: var(--primary-red);
      box-shadow: 0 2px 4px rgba(220, 20, 60, 0.3), inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    input:checked + .slider:before {
      transform: translateX(28px);
    }

    .toggle-switch:focus-within .slider {
      box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.2), 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .action-area {
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border-light);
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-block;
      padding: 12px 24px;
      font-size: 14px;
      font-weight: 700;
      color: white;
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
      position: relative;
      overflow: hidden;
    }

    .btn:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 20, 60, 0.4);
    }

    .btn:hover:before {
      left: 100%;
    }

    .btn:active {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #6c757d, #5a6268);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary-red);
      color: var(--primary-red);
      box-shadow: none;
    }

    .btn-outline:hover {
      background: var(--primary-red);
      color: white;
      box-shadow: 0 6px 20px rgba(220, 20, 60, 0.3);
    }

    .info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }

    .info-card {
      background: var(--bg-card);
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .info-card-title {
      font-size: 16px;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 10px;
    }

    .info-card-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary-dark);
    }
  </style>
</head>
<body>
  <?php include '../../includes/components/sidebar.php'; ?>

  <div class="main">
    <?php include '../../includes/components/topbar.php'; ?>

    <div class="main-content">
      <div class="page-header">
        <h1 class="page-title">
          <i class="fas fa-cog"></i>
          System Settings
        </h1>
      </div>

      <div class="tabs">
        <button class="tab-button active" data-tab="general">General</button>
        <button class="tab-button" data-tab="security">Security</button>
        <button class="tab-button" data-tab="financing">Financing</button>
        <button class="tab-button" data-tab="backup">Backup & Maintenance</button>
      </div>

      <!-- General Settings Tab -->
      <div class="tab-content active" id="general">
        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Company Information</h3>
          </div>
          <form id="companyForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Company Name</label>
                <input type="text" class="form-input" value="Mitsubishi Motors" required>
              </div>
              <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" class="form-input" value="1234 Elm Street" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" class="form-input" value="Tokyo" required>
              </div>
              <div class="form-group">
                <label class="form-label">Postal Code</label>
                <input type="text" class="form-input" value="100-0001" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" class="form-input" value="Japan" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-input" value="+81-3-1234-5678" required>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Business Hours</h3>
          </div>
          <form id="businessHoursForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Monday</label>
                <input type="text" class="form-input" value="9:00 AM - 5:00 PM" required>
              </div>
              <div class="form-group">
                <label class="form-label">Tuesday</label>
                <input type="text" class="form-input" value="9:00 AM - 5:00 PM" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Wednesday</label>
                <input type="text" class="form-input" value="9:00 AM - 5:00 PM" required>
              </div>
              <div class="form-group">
                <label class="form-label">Thursday</label>
                <input type="text" class="form-input" value="9:00 AM - 5:00 PM" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Friday</label>
                <input type="text" class="form-input" value="9:00 AM - 5:00 PM" required>
              </div>
              <div class="form-group">
                <label class="form-label">Saturday</label>
                <input type="text" class="form-input" value="Closed" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Sunday</label>
                <input type="text" class="form-input" value="Closed" required>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Update Hours</button>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">System Preferences</h3>
          </div>
          <form id="systemForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Timezone</label>
                <select class="form-select" required>
                  <option value="utc+9">UTC+9:00 Tokyo, Osaka</option>
                  <option value="utc+8">UTC+8:00 Beijing, Hong Kong</option>
                  <option value="utc+7">UTC+7:00 Bangkok, Hanoi</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Date Format</label>
                <select class="form-select" required>
                  <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                  <option value="dd-mm-yyyy" selected>DD-MM-YYYY</option>
                  <option value="mm-dd-yyyy">MM-DD-YYYY</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Time Format</label>
                <select class="form-select" required>
                  <option value="24h" selected>24-hour format</option>
                  <option value="12h">12-hour format</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Language</label>
                <select class="form-select" required>
                  <option value="english" selected>English</option>
                  <option value="japanese">日本語</option>
                  <option value="chinese">中文</option>
                </select>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Save Preferences</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Security Settings Tab -->
      <div class="tab-content" id="security">
        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Password Policy</h3>
          </div>
          <form id="securityForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Minimum Password Length</label>
                <input type="number" class="form-input" value="8" min="6" max="20" required>
              </div>
              <div class="form-group">
                <label class="form-label">Maximum Password Length</label>
                <input type="number" class="form-input" value="16" min="6" max="20" required>
              </div>
            </div>
            <div style="margin-bottom: 20px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Require uppercase letters</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Require lowercase letters</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Require numbers</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Require special characters</span>
                <label class="toggle-switch">
                  <input type="checkbox">
                  <span class="slider"></span>
                </label>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Update Security Settings</button>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Access Control</h3>
          </div>
          <form id="accessForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Max Login Attempts</label>
                <input type="number" class="form-input" value="5" min="3" max="10" required>
              </div>
              <div class="form-group">
                <label class="form-label">Account Lockout Duration (minutes)</label>
                <input type="number" class="form-input" value="30" min="5" max="120" required>
              </div>
            </div>
            <div style="margin-bottom: 20px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Enable two-factor authentication</span>
                <label class="toggle-switch">
                  <input type="checkbox">
                  <span class="slider"></span>
                </label>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Log all user activities</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Save Access Settings</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Financing Tab -->
      <div class="tab-content" id="financing">
        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Financing Rates</h3>
            <p class="settings-description">Configure interest rates for different loan terms</p>
          </div>
          <form id="financingRatesForm">
            <div id="ratesContainer">
              <!-- Rates will be loaded dynamically -->
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Save Rates
              </button>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Financing Rules</h3>
            <p class="settings-description">Configure financing policies and requirements</p>
          </div>
          <form id="financingRulesForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Minimum Down Payment (%)</label>
                <input type="number" id="minDownPayment" class="form-input" min="0" max="100" step="0.01" required>
              </div>
              <div class="form-group">
                <label class="form-label">Maximum Financing Amount (₱)</label>
                <input type="number" id="maxFinancingAmount" class="form-input" min="0" step="0.01" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Minimum Credit Score (Optional)</label>
                <input type="number" id="minCreditScore" class="form-input" min="300" max="850">
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Save Rules
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Backup & Maintenance Tab -->
      <div class="tab-content" id="backup">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-card-title">Last Backup</div>
            <div class="info-card-value">Mar 23, 2024</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Backup Size</div>
            <div class="info-card-value">2.4 GB</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">System Uptime</div>
            <div class="info-card-value">99.8%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Storage Used</div>
            <div class="info-card-value">68%</div>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">Backup Configuration</h3>
          </div>
          <form id="backupForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Backup Frequency</label>
                <select class="form-select" required>
                  <option value="daily">Daily</option>
                  <option value="weekly" selected>Weekly</option>
                  <option value="monthly">Monthly</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Backup Time</label>
                <input type="time" class="form-input" value="02:00" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Retention Period (days)</label>
                <input type="number" class="form-input" value="30" min="7" max="365" required>
              </div>
              <div class="form-group">
                <label class="form-label">Backup Location</label>
                <select class="form-select" required>
                  <option value="local">Local Storage</option>
                  <option value="cloud">Cloud Storage</option>
                  <option value="both" selected>Both</option>
                </select>
              </div>
            </div>
            <div style="margin-bottom: 20px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Auto-backup enabled</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span>Email backup notifications</span>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Save Backup Settings</button>
              <button type="button" class="btn btn-secondary">Run Backup Now</button>
            </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="settings-header">
            <h3 class="settings-title">System Maintenance</h3>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Database Optimization</label>
              <p style="color: var(--text-light); font-size: 14px; margin-bottom: 10px;">
                Last optimized: Mar 20, 2024
              </p>
              <button class="btn btn-primary">Optimize Database</button>
            </div>
            <div class="form-group">
              <label class="form-label">Clear System Cache</label>
              <p style="color: var(--text-light); font-size: 14px; margin-bottom: 10px;">
                Cache size: 156 MB
              </p>
              <button class="btn btn-secondary">Clear Cache</button>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Log Files Management</label>
              <p style="color: var(--text-light); font-size: 14px; margin-bottom: 10px;">
                Total log size: 89 MB
              </p>
              <button class="btn btn-outline">Archive Old Logs</button>
            </div>
            <div class="form-group">
              <label class="form-label">System Update</label>
              <p style="color: var(--text-light); font-size: 14px; margin-bottom: 10px;">
                Current version: v2.1.4
              </p>
              <button class="btn btn-primary">Check for Updates</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../includes/js/common-scripts.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Tab navigation functionality
      document.querySelectorAll('.tab-button').forEach(function(button) {
        button.addEventListener('click', function() {
          // Remove active class from all buttons
          document.querySelectorAll('.tab-button').forEach(function(btn) {
            btn.classList.remove('active');
          });
          // Add active class to clicked button
          this.classList.add('active');
          
          // Get the target tab content id
          const tabId = this.getAttribute('data-tab');
          // Hide all tab contents
          document.querySelectorAll('.tab-content').forEach(function(tab) {
            tab.classList.remove('active');
          });
          // Show the target tab content
          document.getElementById(tabId).classList.add('active');
        });
      });

      // Form submissions
      document.getElementById('companyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Company information updated successfully!');
      });

      document.getElementById('businessHoursForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Business hours updated successfully!');
      });

      document.getElementById('systemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('System preferences updated successfully!');
      });

      document.getElementById('securityForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Security settings updated successfully!');
      });

      document.getElementById('accessForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Access control settings updated successfully!');
      });

      document.getElementById('backupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Backup configuration saved successfully!');
      });

      // Load financing data when financing tab is clicked
      document.querySelector('[data-tab="financing"]').addEventListener('click', function() {
        loadFinancingRates();
        loadFinancingRules();
      });

      // Financing rates form submission
      document.getElementById('financingRatesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveFinancingRates();
      });

      // Financing rules form submission
      document.getElementById('financingRulesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveFinancingRules();
      });
    });

    // Financing functions
    function loadFinancingRates() {
      fetch('../../includes/backend/financing_settings_backend.php?action=get_financing_rates')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayFinancingRates(data.data);
          } else {
            alert('Error loading financing rates: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Failed to load financing rates');
        });
    }

    function displayFinancingRates(rates) {
      const container = document.getElementById('ratesContainer');
      container.innerHTML = '';
      
      rates.forEach(rate => {
        const rateRow = document.createElement('div');
        rateRow.className = 'form-row';
        rateRow.innerHTML = `
          <div class="form-group">
            <label class="form-label">${rate.term_months} Months</label>
            <div class="input-group">
              <input type="number" name="rate_${rate.term_months}" class="form-input" 
                     value="${rate.annual_rate_percent}" min="0" max="100" step="0.01" required>
              <span class="input-suffix">%</span>
            </div>
          </div>
        `;
        container.appendChild(rateRow);
      });
    }

    function saveFinancingRates() {
      const formData = new FormData();
      formData.append('action', 'update_financing_rates');
      
      const rates = [];
      document.querySelectorAll('#ratesContainer input[name^="rate_"]').forEach(input => {
        const termMonths = input.name.replace('rate_', '');
        rates.push({
          term_months: termMonths,
          annual_rate_percent: parseFloat(input.value)
        });
      });
      
      // Send rates as individual form fields
      rates.forEach((rate, index) => {
        formData.append(`rates[${index}][term_months]`, rate.term_months);
        formData.append(`rates[${index}][annual_rate_percent]`, rate.annual_rate_percent);
      });
      
      fetch('../../includes/backend/financing_settings_backend.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Financing rates updated successfully!');
        } else {
          alert('Error updating financing rates: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to update financing rates');
      });
    }

    function loadFinancingRules() {
      fetch('../../includes/backend/financing_settings_backend.php?action=get_financing_rules')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.data.length > 0) {
            const rule = data.data[0]; // Use first active rule
            document.getElementById('minDownPayment').value = rule.min_down_payment_percent_display;
            document.getElementById('maxFinancingAmount').value = rule.max_financing_amount;
            document.getElementById('minCreditScore').value = rule.min_credit_score || '';
          } else {
            alert('Error loading financing rules: ' + (data.error || 'No rules found'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Failed to load financing rules');
        });
    }

    function saveFinancingRules() {
      const formData = new FormData();
      formData.append('action', 'update_financing_rules');
      formData.append('rule_id', '1'); // Default rule ID
      formData.append('min_down_payment_percent', document.getElementById('minDownPayment').value);
      formData.append('max_financing_amount', document.getElementById('maxFinancingAmount').value);
      formData.append('min_credit_score', document.getElementById('minCreditScore').value);
      
      fetch('../../includes/backend/financing_settings_backend.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Financing rules updated successfully!');
        } else {
          alert('Error updating financing rules: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to update financing rules');
      });
    }
  </script>
</body>
</html>
