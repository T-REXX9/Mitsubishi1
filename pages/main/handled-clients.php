<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../pages/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Handled Records - Mitsubishi</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../includes/css/common-styles.css" rel="stylesheet">
  <style>
	  html, body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }
    
    body {
    }
    /* Admin Handled Records Specific Styles */
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

    .tab-navigation {
      display: flex;
      gap: 5px;
      margin-bottom: 25px;
      border-bottom: 1px solid var(--border-light);
      flex-wrap: wrap;
    }

    .tab-button {
      padding: 12px 20px;
      background: none;
      border: none;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      font-weight: 600;
      color: var(--text-light);
      transition: var(--transition);
    }

    .tab-button.active {
      color: var(--primary-red);
      border-bottom-color: var(--primary-red);
    }

    .tab-button:hover:not(.active) {
      color: var(--text-dark);
      background-color: var(--border-light);
    }

    .tab-content {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
      display: block;
    }

    .info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .info-card {
      background-color: white;
      border: 1px solid var(--border-light);
      border-radius: 10px;
      padding: 20px;
      transition: var(--transition);
    }

    .info-card:hover {
      box-shadow: var(--shadow-light);
    }

    .info-card-title {
      font-size: 14px;
      color: var(--text-light);
      margin-bottom: 5px;
    }

    .info-card-value {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
      align-items: center;
    }

    .search-input {
      flex: 1;
      min-width: 200px;
      position: relative;
    }

    .search-input input {
      width: 100%;
      padding: 10px 15px 10px 40px;
      border: 1px solid var(--border-light);
      border-radius: 8px;
      font-size: 14px;
    }

    .search-input i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
    }

    .filter-select {
      padding: 10px 15px;
      border: 1px solid var(--border-light);
      border-radius: 8px;
      background-color: white;
      min-width: 150px;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    .data-table th, .data-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--border-light);
    }

    .data-table th {
      background-color: #f9fafb;
      font-weight: 600;
      color: var(--text-dark);
    }

    .data-table tr:hover {
      background-color: #f9fafb;
    }

    .status {
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      display: inline-block;
    }

    .status.approved {
      background-color: #e6f7ed;
      color: #0e7c42;
    }

    .status.pending {
      background-color: #fff8e6;
      color: #b78105;
    }

    .status.completed {
      background-color: #e6eefb;
      color: #1e62cd;
    }

    .status.overdue {
      background-color: #fce8e8;
      color: #b91c1c;
    }

    .table-actions {
      display: flex;
      gap: 8px;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-small {
      padding: 5px 10px;
      font-size: 11px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      color: white;
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--border-light);
      color: var(--text-dark);
    }

    .btn-secondary {
      background: var(--border-light);
      color: var(--text-dark);
    }

    .action-area {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid var(--border-light);
    }

    /* Workload Status Cards */
    .workload-stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .workload-card {
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      border-left: 5px solid;
      box-shadow: var(--shadow-light);
    }

    .workload-card.high {
      border-left-color: #ef4444;
    }

    .workload-card.medium {
      border-left-color: #f59e0b;
    }

    .workload-card.low {
      border-left-color: #10b981;
    }

    .workload-card.normal {
      border-left-color: #3b82f6;
    }

    .workload-value {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .workload-label {
      color: var(--text-light);
      font-size: 14px;
    }

    /* Assignment form */
    .assignment-form {
      background: white;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 30px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow-light);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border-light);
      border-radius: 8px;
      font-size: 14px;
      transition: var(--transition);
      background: white;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .section-heading {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border-light);
      font-size: 18px;
      color: var(--text-dark);
      font-weight: 600;
    }

    .required {
      color: var(--primary-red);
    }

    /* Responsive Design */
    @media (max-width: 575px) {
      .info-cards, .workload-stats {
        grid-template-columns: 1fr;
      }
      .form-row {
        grid-template-columns: 1fr;
      }
      .filter-bar {
        flex-direction: column;
        align-items: stretch;
      }
      .action-area {
        flex-direction: column;
      }
    }

    @media (min-width: 576px) and (max-width: 767px) {
      .info-cards {
        grid-template-columns: repeat(2, 1fr);
      }
      .form-row {
        grid-template-columns: 1fr;
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
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
          <i class="fas fa-user-friends"></i>
          Admin Handled Records
        </h1>
      </div>

      <div class="tab-navigation">
        <button class="tab-button active" data-tab="handled-clients">Client-Agent Assignments</button>
        <button class="tab-button" data-tab="handled-workload">Agent Workload</button>
        <button class="tab-button" data-tab="handled-reassign">Reassign Clients</button>
        <button class="tab-button" data-tab="handled-performance">Performance Review</button>
      </div>

      <!-- Client-Agent Assignments Tab -->
      <div class="tab-content active" id="handled-clients">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-card-title">Total Client Assignments</div>
            <div class="info-card-value">1,247</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Active Clients</div>
            <div class="info-card-value">1,198</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Successful Conversions</div>
            <div class="info-card-value">89%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Avg. Handle Time</div>
            <div class="info-card-value">14 days</div>
          </div>
        </div>

        <div class="filter-bar">
          <div class="search-input">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search clients or agents...">
          </div>
          <select class="filter-select">
            <option value="">All Agents</option>
            <option value="carlos">Carlos Mendoza</option>
            <option value="ana">Ana Santos</option>
            <option value="juan">Juan Reyes</option>
          </select>
          <select class="filter-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
          </select>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Contact Info</th>
              <th>Vehicle Interest</th>
              <th>Assigned Agent</th>
              <th>Assignment Date</th>
              <th>Status</th>
              <th>Last Activity</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>John Doe<br><small>CL-2024-001</small></td>
              <td>john.doe@email.com<br><small>+63 917 123 4567</small></td>
              <td>Montero Sport GLS<br><small>Interested in financing</small></td>
              <td>Carlos Mendoza<br><small>Senior Agent</small></td>
              <td>Mar 15, 2024</td>
              <td><span class="status approved">Active</span></td>
              <td>Mar 23, 2024</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-primary">Reassign</button>
              </td>
            </tr>
            <tr>
              <td>Maria Santos<br><small>CL-2024-002</small></td>
              <td>maria@email.com<br><small>+63 917 234 5678</small></td>
              <td>Xpander GLS AT<br><small>Test drive completed</small></td>
              <td>Ana Santos<br><small>Sales Agent</small></td>
              <td>Mar 10, 2024</td>
              <td><span class="status completed">Sale Completed</span></td>
              <td>Mar 22, 2024</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-secondary">Archive</button>
              </td>
            </tr>
            <tr>
              <td>Robert Cruz<br><small>CL-2024-003</small></td>
              <td>robert@email.com<br><small>+63 917 345 6789</small></td>
              <td>Mirage G4 GLS<br><small>Price negotiation</small></td>
              <td>Juan Reyes<br><small>Junior Agent</small></td>
              <td>Mar 20, 2024</td>
              <td><span class="status pending">Follow-up Required</span></td>
              <td>Mar 21, 2024</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-primary">Escalate</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Export Client List</button>
          <button class="btn btn-secondary">Generate Assignment Report</button>
        </div>
      </div>

      <!-- Agent Workload Tab -->
      <div class="tab-content" id="handled-workload">
        <div class="workload-stats">
          <div class="workload-card high">
            <div class="workload-value">23</div>
            <div class="workload-label">Carlos - Active Clients</div>
          </div>
          <div class="workload-card medium">
            <div class="workload-value">19</div>
            <div class="workload-label">Ana - Active Clients</div>
          </div>
          <div class="workload-card normal">
            <div class="workload-value">15</div>
            <div class="workload-label">Juan - Active Clients</div>
          </div>
          <div class="workload-card low">
            <div class="workload-value">12</div>
            <div class="workload-label">Maria - Active Clients</div>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Sales Agent</th>
              <th>Active Clients</th>
              <th>Completed This Month</th>
              <th>Average Handle Time</th>
              <th>Success Rate</th>
              <th>Workload Status</th>
              <th>Next Available</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Carlos Mendoza</td>
              <td>23</td>
              <td>18</td>
              <td>12 days</td>
              <td><span class="status approved">92%</span></td>
              <td><span class="status overdue">High</span></td>
              <td>Mar 28, 2024</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Clients</button>
                <button class="btn btn-small btn-secondary">Reduce Load</button>
              </td>
            </tr>
            <tr>
              <td>Ana Santos</td>
              <td>19</td>
              <td>15</td>
              <td>14 days</td>
              <td><span class="status approved">88%</span></td>
              <td><span class="status pending">Medium</span></td>
              <td>Mar 25, 2024</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Clients</button>
                <button class="btn btn-small btn-primary">Assign More</button>
              </td>
            </tr>
            <tr>
              <td>Juan Reyes</td>
              <td>15</td>
              <td>10</td>
              <td>18 days</td>
              <td><span class="status pending">75%</span></td>
              <td><span class="status approved">Normal</span></td>
              <td>Available</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Clients</button>
                <button class="btn btn-small btn-primary">Assign More</button>
              </td>
            </tr>
            <tr>
              <td>Maria Gonzales</td>
              <td>12</td>
              <td>8</td>
              <td>16 days</td>
              <td><span class="status pending">70%</span></td>
              <td><span class="status approved">Low</span></td>
              <td>Available</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Clients</button>
                <button class="btn btn-small btn-primary">Assign More</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Balance Workload</button>
          <button class="btn btn-secondary">Export Workload Report</button>
        </div>
      </div>

      <!-- Reassign Clients Tab -->
      <div class="tab-content" id="handled-reassign">
        <div class="assignment-form">
          <h3 class="section-heading">Reassign Client to Agent</h3>
          <form id="reassignForm">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Select Client <span class="required">*</span></label>
                <select class="form-select" required>
                  <option value="">Choose client to reassign</option>
                  <option value="CL-2024-001">John Doe - Montero Sport Interest</option>
                  <option value="CL-2024-003">Robert Cruz - Mirage G4 Interest</option>
                  <option value="CL-2024-004">Anna Reyes - Strada Interest</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Current Agent</label>
                <input type="text" class="form-input" placeholder="Current assigned agent" readonly>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">New Agent <span class="required">*</span></label>
                <select class="form-select" required>
                  <option value="">Select new agent</option>
                  <option value="carlos">Carlos Mendoza (23 active clients)</option>
                  <option value="ana">Ana Santos (19 active clients)</option>
                  <option value="juan">Juan Reyes (15 active clients)</option>
                  <option value="maria">Maria Gonzales (12 active clients)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Reassignment Reason <span class="required">*</span></label>
                <select class="form-select" required>
                  <option value="">Select reason</option>
                  <option value="workload">Workload Balancing</option>
                  <option value="expertise">Agent Expertise Match</option>
                  <option value="availability">Agent Availability</option>
                  <option value="performance">Performance Issues</option>
                  <option value="request">Client Request</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Additional Notes</label>
              <textarea class="form-textarea" rows="3" placeholder="Enter any additional notes for the reassignment..."></textarea>
            </div>
            <div class="action-area">
              <button type="submit" class="btn btn-primary">Reassign Client</button>
              <button type="button" class="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>

        <h3 class="section-heading">Recent Reassignments</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Client</th>
              <th>From Agent</th>
              <th>To Agent</th>
              <th>Reassignment Date</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Michael Torres</td>
              <td>Maria Gonzales</td>
              <td>Carlos Mendoza</td>
              <td>Mar 22, 2024</td>
              <td>Agent Expertise Match</td>
              <td><span class="status completed">Completed</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
              </td>
            </tr>
            <tr>
              <td>Sarah Johnson</td>
              <td>Juan Reyes</td>
              <td>Ana Santos</td>
              <td>Mar 21, 2024</td>
              <td>Workload Balancing</td>
              <td><span class="status approved">Active</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Performance Review Tab -->
      <div class="tab-content" id="handled-performance">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-card-title">Overall Success Rate</div>
            <div class="info-card-value">84%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Average Handle Time</div>
            <div class="info-card-value">15 days</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Client Satisfaction</div>
            <div class="info-card-value">4.2/5</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Reassignment Rate</div>
            <div class="info-card-value">8%</div>
          </div>
        </div>

        <h3 class="section-heading">Agent Performance Analysis</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Clients Handled</th>
              <th>Success Rate</th>
              <th>Avg. Handle Time</th>
              <th>Client Rating</th>
              <th>Reassignments</th>
              <th>Performance Grade</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Carlos Mendoza</td>
              <td>156</td>
              <td><span class="status approved">92%</span></td>
              <td>12 days</td>
              <td>4.8/5</td>
              <td>2</td>
              <td><span class="status approved">A+</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Detailed Review</button>
                <button class="btn btn-small btn-primary">Commend</button>
              </td>
            </tr>
            <tr>
              <td>Ana Santos</td>
              <td>134</td>
              <td><span class="status approved">88%</span></td>
              <td>14 days</td>
              <td>4.5/5</td>
              <td>5</td>
              <td><span class="status approved">A</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Detailed Review</button>
                <button class="btn btn-small btn-primary">Commend</button>
              </td>
            </tr>
            <tr>
              <td>Juan Reyes</td>
              <td>89</td>
              <td><span class="status pending">75%</span></td>
              <td>18 days</td>
              <td>3.9/5</td>
              <td>12</td>
              <td><span class="status pending">B</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Detailed Review</button>
                <button class="btn btn-small btn-secondary">Training Plan</button>
              </td>
            </tr>
            <tr>
              <td>Maria Gonzales</td>
              <td>76</td>
              <td><span class="status pending">70%</span></td>
              <td>20 days</td>
              <td>3.7/5</td>
              <td>18</td>
              <td><span class="status overdue">C+</span></td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Detailed Review</button>
                <button class="btn btn-small btn-secondary">Performance Plan</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Generate Performance Report</button>
          <button class="btn btn-secondary">Schedule Reviews</button>
          <button class="btn btn-outline">Export Analysis</button>
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

      // Form submission for reassignment
      document.getElementById('reassignForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Client reassignment completed successfully!');
        this.reset();
      });
    });
  </script>
</body>
</html>
