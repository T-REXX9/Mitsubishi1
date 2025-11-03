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
  <title>Sold Units - Mitsubishi</title>
  
  <?php
  // Mobile Responsiveness Fix
  $css_path = '../../css/';
  $js_path = '../../js/';
  include '../../includes/components/mobile-responsive-include.php';
  ?>
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../includes/css/common-styles.css" rel="stylesheet">
  <style>
    /* Override global overflow to allow page scrolling */
    html, body {
      overflow: visible !important;
      height: auto !important;
      scroll-behavior: smooth;
    }

    /* REMOVED zoom: 85% - causes mobile layout issues, not supported by Firefox */

    .main {
      height: auto !important;
      min-height: 100vh;
    }

    .main-content {
      height: auto !important;
      max-height: none !important;
      overflow-y: visible !important;
      padding-bottom: 40px;
    }
    
    /* Admin Solved Units Record Specific Styles */
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

    .page-title i {
      color: var(--primary-red);
    }

    .table-container {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: var(--shadow-light);
      margin-bottom: 20px;
    }

    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background: white;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: var(--shadow-light);
    }

    .pagination-info {
      color: var(--text-light);
      font-size: 14px;
    }

    .pagination-controls {
      display: flex;
      gap: 10px;
    }

    .pagination-controls button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .info-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      transition: var(--transition);
      box-shadow: var(--shadow-light);
      border-left: 4px solid var(--primary-red);
      position: relative;
      overflow: hidden;
    }

    .info-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-red), #b91c3c);
    }

    .info-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-medium);
    }

    .info-card-title {
      font-size: 13px;
      color: var(--text-light);
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }

    .info-card-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary-dark);
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .filter-bar {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-light);
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: center;
    }

    .search-input {
      flex: 1;
      min-width: 250px;
      position: relative;
    }

    .search-input input {
      width: 100%;
      padding: 12px 15px 12px 45px;
      border: 2px solid var(--border-light);
      border-radius: 8px;
      font-size: 14px;
      transition: var(--transition);
    }

    .search-input input:focus {
      outline: none;
      border-color: var(--primary-red);
      box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
    }

    .search-input i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 16px;
    }

    .filter-select {
      padding: 12px 15px;
      border: 2px solid var(--border-light);
      border-radius: 8px;
      background-color: white;
      min-width: 180px;
      font-size: 14px;
      transition: var(--transition);
      cursor: pointer;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--primary-red);
      box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
    }

    .data-table th {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 16px 20px;
      text-align: left;
      font-weight: 700;
      color: var(--primary-dark);
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--primary-red);
    }

    .data-table td {
      padding: 16px 20px;
      text-align: left;
      border-bottom: 1px solid var(--border-light);
      color: var(--text-dark);
      font-size: 14px;
    }

    .data-table tbody tr {
      transition: var(--transition);
    }

    .data-table tbody tr:hover {
      background-color: #f8f9fa;
      transform: scale(1.01);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .data-table tbody tr:last-child td {
      border-bottom: none;
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

    .status.overdue {
      background-color: #fce8e8;
      color: #b91c1c;
    }

    .table-actions {
      display: flex;
      gap: 8px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-medium);
    }

    .btn-small {
      padding: 6px 12px;
      font-size: 12px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      color: white;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #b91c3c, var(--primary-red));
    }

    .btn-outline {
      background: white;
      border: 2px solid var(--border-light);
      color: var(--text-dark);
    }

    .btn-outline:hover {
      border-color: var(--primary-red);
      color: var(--primary-red);
    }

    .btn-secondary {
      background: var(--accent-blue);
      color: white;
    }

    .btn-secondary:hover {
      background: #0056b3;
    }

    .action-area {
      display: flex;
      gap: 15px;
      margin-top: 24px;
      padding: 20px 24px;
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      flex-wrap: wrap;
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

    /* Sales Performance Stat Cards */
    .stat-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .stat-card {
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      border-left: 5px solid;
      box-shadow: var(--shadow-light);
    }

    .stat-card.rank-1 {
      border-left-color: #ffd700; /* Gold */
    }

    .stat-card.rank-2 {
      border-left-color: #c0c0c0; /* Silver */
    }

    .stat-card.rank-3 {
      border-left-color: #cd7f32; /* Bronze */
    }

    .stat-card.rank-other {
      border-left-color: #6c757d; /* Grey for other ranks */
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .stat-label {
      color: var(--text-light);
      font-size: 14px;
    }

    /* Performance Chart */
    .chart-container {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow-light);
      height: 400px;
    }

    /* Progress indicators */
    .progress-bar {
      height: 10px;
      background: var(--border-light);
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 10px;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      border-radius: 5px;
    }

    /* Top performers leaderboard */
    .leaderboard {
      background: white;
      border-radius: 10px;
      padding: 20px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow-light);
      margin-bottom: 30px;
    }
    
    .leaderboard-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--text-dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .leaderboard-title i {
      color: var(--primary-red);
    }
    
    .leaderboard-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid var(--border-light);
    }
    
    .leaderboard-rank {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-right: 15px;
    }
    
    .rank-1 .leaderboard-rank {
      background: #fef9d7;
      color: #d4ac0d;
      border: 2px solid #ffd700;
    }
    
    .rank-2 .leaderboard-rank {
      background: #f8f9fa;
      color: #6c757d;
      border: 2px solid #c0c0c0;
    }
    
    .rank-3 .leaderboard-rank {
      background: #f8f0e5;
      color: #b06500;
      border: 2px solid #cd7f32;
    }
    
    .leaderboard-info {
      flex-grow: 1;
    }
    
    .leaderboard-name {
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .leaderboard-stats {
      font-size: 13px;
      color: var(--text-light);
    }
    
    .leaderboard-score {
      font-weight: 700;
      font-size: 18px;
      color: var(--primary-dark);
    }



    /* Responsive Design */
    @media (max-width: 575px) {
      .info-cards {
        grid-template-columns: 1fr;
      }
      .filter-bar {
        flex-direction: column;
        align-items: stretch;
      }
      .action-area {
        flex-direction: column;
      }
      .pagination-container {
        flex-direction: column;
        gap: 15px;
      }
      .data-table {
        font-size: 12px;
      }
      .data-table th, .data-table td {
        padding: 8px 10px;
      }
    }

    @media (min-width: 576px) and (max-width: 767px) {
      .info-cards {
        grid-template-columns: repeat(2, 1fr);
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
          <i class="fas fa-check-square"></i>
          Sold Units
        </h1>
      </div>

      <!-- Summary Cards -->
      <div class="info-cards" id="summaryCards">
        <div class="info-card">
          <div class="info-card-title">Total Units Sold</div>
          <div class="info-card-value" id="totalUnitsSold">-</div>
        </div>
        <div class="info-card">
          <div class="info-card-title">Total Revenue</div>
          <div class="info-card-value" id="totalRevenue">-</div>
        </div>
        <div class="info-card">
          <div class="info-card-title">Total Agents</div>
          <div class="info-card-value" id="totalAgents">-</div>
        </div>
        <div class="info-card">
          <div class="info-card-title">Vehicle Models</div>
          <div class="info-card-value" id="totalModels">-</div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <div class="search-input">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search by agent or vehicle name...">
        </div>
        <select class="filter-select" id="monthFilter">
          <option value="">All Months</option>
        </select>
        <select class="filter-select" id="vehicleFilter">
          <option value="">All Vehicles</option>
        </select>
        <select class="filter-select" id="agentFilter">
          <option value="">All Agents</option>
        </select>
        <button class="btn btn-primary" id="applyFilters">
          <i class="fas fa-filter"></i> Apply Filters
        </button>
        <button class="btn btn-outline" id="resetFilters">
          <i class="fas fa-redo"></i> Reset
        </button>
      </div>

      <!-- Data Table -->
      <div class="table-container">
        <table class="data-table" id="soldUnitsTable">
          <thead>
            <tr>
              <th>Month</th>
              <th>Agent Name</th>
              <th>Unit/Vehicle Name</th>
              <th>Total Units Sold</th>
              <th>Total Value</th>
            </tr>
          </thead>
          <tbody id="soldUnitsTableBody">
            <tr>
              <td colspan="5" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--text-light);"></i>
                <p style="margin-top: 10px; color: var(--text-light);">Loading sold units data...</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-container" id="paginationContainer" style="display: none;">
        <div class="pagination-info">
          Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalRecords">0</span> records
        </div>
        <div class="pagination-controls">
          <button class="btn btn-outline btn-small" id="prevPage" disabled>
            <i class="fas fa-chevron-left"></i> Previous
          </button>
          <button class="btn btn-outline btn-small" id="nextPage">
            Next <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <div class="action-area">
        <button class="btn btn-primary" id="exportData">
          <i class="fas fa-download"></i> Export to CSV
        </button>
      </div>

    </div>
  </div>

  <script src="../../includes/js/common-scripts.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      let currentPage = 0;
      const limit = 50;
      let filterOptions = {};

      // Load filter options on page load
      loadFilterOptions();

      // Load initial data
      loadSoldUnits();

      // Event listeners
      document.getElementById('applyFilters').addEventListener('click', function() {
        currentPage = 0;
        loadSoldUnits();
      });

      document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('monthFilter').value = '';
        document.getElementById('vehicleFilter').value = '';
        document.getElementById('agentFilter').value = '';
        currentPage = 0;
        loadSoldUnits();
      });

      document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          currentPage = 0;
          loadSoldUnits();
        }
      });

      document.getElementById('prevPage').addEventListener('click', function() {
        if (currentPage > 0) {
          currentPage--;
          loadSoldUnits();
        }
      });

      document.getElementById('nextPage').addEventListener('click', function() {
        currentPage++;
        loadSoldUnits();
      });

      document.getElementById('exportData').addEventListener('click', function() {
        exportToCSV();
      });

      // Load filter options from API
      function loadFilterOptions() {
        fetch('../../api/sold-units.php?action=filters')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              filterOptions = data;
              populateFilters(data);
            }
          })
          .catch(error => {
            console.error('Error loading filter options:', error);
          });
      }

      // Populate filter dropdowns
      function populateFilters(data) {
        const monthFilter = document.getElementById('monthFilter');
        const vehicleFilter = document.getElementById('vehicleFilter');
        const agentFilter = document.getElementById('agentFilter');

        // Populate months
        data.months.forEach(month => {
          const option = document.createElement('option');
          option.value = month.value;
          option.textContent = month.label;
          monthFilter.appendChild(option);
        });

        // Populate vehicles
        data.vehicles.forEach(vehicle => {
          const option = document.createElement('option');
          option.value = vehicle.value;
          option.textContent = vehicle.label;
          vehicleFilter.appendChild(option);
        });

        // Populate agents
        data.agents.forEach(agent => {
          const option = document.createElement('option');
          option.value = agent.value;
          option.textContent = agent.label;
          agentFilter.appendChild(option);
        });
      }

      // Load sold units data
      function loadSoldUnits() {
        const search = document.getElementById('searchInput').value;
        const month = document.getElementById('monthFilter').value;
        const vehicle = document.getElementById('vehicleFilter').value;
        const agent = document.getElementById('agentFilter').value;
        const offset = currentPage * limit;

        const params = new URLSearchParams({
          action: 'list',
          search: search,
          month: month,
          vehicle: vehicle,
          agent: agent,
          limit: limit,
          offset: offset
        });

        fetch(`../../api/sold-units.php?${params}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              displaySoldUnits(data.data);
              updatePagination(data.total, offset);
              loadSummary(month, vehicle, agent);
            } else {
              showError(data.error || 'Failed to load data');
            }
          })
          .catch(error => {
            console.error('Error loading sold units:', error);
            showError('Failed to load sold units data');
          });
      }

      // Load summary statistics
      function loadSummary(month, vehicle, agent) {
        const params = new URLSearchParams({
          action: 'summary',
          month: month,
          vehicle: vehicle,
          agent: agent
        });

        fetch(`../../api/sold-units.php?${params}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateSummaryCards(data.summary);
            }
          })
          .catch(error => {
            console.error('Error loading summary:', error);
          });
      }

      // Update summary cards
      function updateSummaryCards(summary) {
        document.getElementById('totalUnitsSold').textContent = summary.total_units_sold;
        document.getElementById('totalRevenue').textContent = summary.formatted_revenue;
        document.getElementById('totalAgents').textContent = summary.total_agents;
        document.getElementById('totalModels').textContent = summary.total_models;
      }

      // Display sold units in table
      function displaySoldUnits(data) {
        const tbody = document.getElementById('soldUnitsTableBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="5" style="text-align: center; padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-light); margin-bottom: 10px;"></i>
                <p style="color: var(--text-light);">No sold units found</p>
              </td>
            </tr>
          `;
          return;
        }

        data.forEach(row => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${row.month_display}</td>
            <td>${row.agent_name || 'N/A'}</td>
            <td>${row.vehicle_full_name}</td>
            <td><strong>${row.units_sold}</strong></td>
            <td>${row.formatted_value}</td>
          `;
          tbody.appendChild(tr);
        });
      }

      // Update pagination controls
      function updatePagination(total, offset) {
        const paginationContainer = document.getElementById('paginationContainer');
        const showingFrom = document.getElementById('showingFrom');
        const showingTo = document.getElementById('showingTo');
        const totalRecords = document.getElementById('totalRecords');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');

        if (total > 0) {
          paginationContainer.style.display = 'flex';
          showingFrom.textContent = offset + 1;
          showingTo.textContent = Math.min(offset + limit, total);
          totalRecords.textContent = total;

          prevBtn.disabled = currentPage === 0;
          nextBtn.disabled = offset + limit >= total;
        } else {
          paginationContainer.style.display = 'none';
        }
      }

      // Show error message
      function showError(message) {
        const tbody = document.getElementById('soldUnitsTableBody');
        tbody.innerHTML = `
          <tr>
            <td colspan="5" style="text-align: center; padding: 40px;">
              <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--primary-red); margin-bottom: 10px;"></i>
              <p style="color: var(--text-dark);">${message}</p>
            </td>
          </tr>
        `;
      }

      // Export to CSV
      function exportToCSV() {
        const search = document.getElementById('searchInput').value;
        const month = document.getElementById('monthFilter').value;
        const vehicle = document.getElementById('vehicleFilter').value;
        const agent = document.getElementById('agentFilter').value;

        const params = new URLSearchParams({
          action: 'list',
          search: search,
          month: month,
          vehicle: vehicle,
          agent: agent,
          limit: 10000,
          offset: 0
        });

        fetch(`../../api/sold-units.php?${params}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.data.length > 0) {
              let csv = 'Month,Agent Name,Vehicle Name,Units Sold,Total Value\n';

              data.data.forEach(row => {
                csv += `"${row.month_display}","${row.agent_name || 'N/A'}","${row.vehicle_full_name}",${row.units_sold},"${row.formatted_value}"\n`;
              });

              const blob = new Blob([csv], { type: 'text/csv' });
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = `sold-units-${new Date().toISOString().split('T')[0]}.csv`;
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
              window.URL.revokeObjectURL(url);
            } else {
              alert('No data to export');
            }
          })
          .catch(error => {
            console.error('Error exporting data:', error);
            alert('Failed to export data');
          });
      }
    });
  </script>
</body>
</html>
