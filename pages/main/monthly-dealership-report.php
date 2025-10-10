<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
  header("Location: ../../pages/login.php");
  exit();
}

// Use the database connection from init.php (which uses db_conn.php)
$pdo = $GLOBALS['pdo'] ?? null;

// Check if database connection exists
if (!$pdo) {
  die("Database connection not available. Please check your database configuration.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Monthly Dealership Report - Admin Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../includes/css/common-styles.css" rel="stylesheet">
  <link href="../../includes/css/orders-styles.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <?php include '../../includes/components/sidebar.php'; ?>

  <div class="main">
    <?php include '../../includes/components/topbar.php'; ?>

    <div class="main-content">
      <div class="page-header">
        <h1 class="page-title">
          <i class="fas fa-chart-bar icon-gradient"></i>
          Monthly Dealership Report
        </h1>
        <button class="add-order-btn" id="generateReportBtn" style="padding: 12px 24px; font-size: 1rem;">
          <i class="fas fa-file-export"></i> Export Report
        </button>
      </div>

      <!-- Report Period Selector -->
      <div class="filters-section">
        <div class="filter-row">
          <div class="filter-group">
            <label for="report-year">Report Year</label>
            <select id="report-year" class="filter-select">
              <option value="2024">2024</option>
              <option value="2023">2023</option>
              <option value="2022">2022</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="report-month">Report Month</label>
            <select id="report-month" class="filter-select">
              <option value="all">All Months</option>
              <option value="1">January</option>
              <option value="2">February</option>
              <option value="3">March</option>
              <option value="4">April</option>
              <option value="5">May</option>
              <option value="6">June</option>
              <option value="7">July</option>
              <option value="8">August</option>
              <option value="9">September</option>
              <option value="10">October</option>
              <option value="11">November</option>
              <option value="12">December</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="report-type">Report Type</label>
            <select id="report-type" class="filter-select">
              <option value="summary">Summary Report</option>
              <option value="detailed">Detailed Report</option>
              <option value="comparison">Year Comparison</option>
            </select>
          </div>
          <button class="filter-btn" onclick="generateReport()">Generate Report</button>
        </div>
      </div>

      <!-- KPI Dashboard -->
      <div class="sales-agent-stats">
        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-info">
            <h3>₱45.2M</h3>
            <p>Monthly Revenue</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">
            <i class="fas fa-car"></i>
          </div>
          <div class="stat-info">
            <h3>187</h3>
            <p>Units Sold</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fas fa-warehouse"></i>
          </div>
          <div class="stat-info">
            <h3>342</h3>
            <p>Inventory Units</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">
            <i class="fas fa-percentage"></i>
          </div>
          <div class="stat-info">
            <h3>18.5%</h3>
            <p>Growth Rate</p>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
          <h3 style="margin-bottom: 1rem; color: #2c3e50;">Sales by Model</h3>
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="salesByModelChart"></canvas>
          </div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
          <h3 style="margin-bottom: 1rem; color: #2c3e50;">Monthly Revenue Trend</h3>
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Sales by Model Table -->
      <div class="client-orders-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            <span>Sales Performance by Model</span>
          </h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Vehicle Model</th>
                <th>Units Sold</th>
                <th>Revenue</th>
                <th>Market Share</th>
                <th>Growth</th>
                <th>Inventory Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Montero Sport</span>
                    <span class="vehicle-details">GLS Premium 2024</span>
                  </div>
                </td>
                <td>
                  <div class="order-info">
                    <span class="order-id">67 units</span>
                    <span class="order-date">35.8% of total</span>
                  </div>
                </td>
                <td class="price">₱16.2M</td>
                <td><span class="client-type-badge handled">42.1%</span></td>
                <td style="color: #27ae60; font-weight: bold;">+12.5%</td>
                <td><span class="status-badge confirmed">In Stock (45)</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="View Details" onclick="viewModelDetails('montero-sport')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-small btn-edit" title="Generate Report" onclick="generateModelReport('montero-sport')">
                      <i class="fas fa-file-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Xpander</span>
                    <span class="vehicle-details">GLX AT 2024</span>
                  </div>
                </td>
                <td>
                  <div class="order-info">
                    <span class="order-id">45 units</span>
                    <span class="order-date">24.1% of total</span>
                  </div>
                </td>
                <td class="price">₱5.7M</td>
                <td><span class="client-type-badge handled">18.9%</span></td>
                <td style="color: #27ae60; font-weight: bold;">+8.3%</td>
                <td><span class="status-badge pending">Low Stock (12)</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="View Details" onclick="viewModelDetails('xpander')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-small btn-edit" title="Generate Report" onclick="generateModelReport('xpander')">
                      <i class="fas fa-file-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Strada</span>
                    <span class="vehicle-details">Athlete 2024</span>
                  </div>
                </td>
                <td>
                  <div class="order-info">
                    <span class="order-id">38 units</span>
                    <span class="order-date">20.3% of total</span>
                  </div>
                </td>
                <td class="price">₱6.9M</td>
                <td><span class="client-type-badge urgent">22.8%</span></td>
                <td style="color: #e74c3c; font-weight: bold;">-2.1%</td>
                <td><span class="status-badge confirmed">In Stock (28)</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="View Details" onclick="viewModelDetails('strada')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-small btn-edit" title="Generate Report" onclick="generateModelReport('strada')">
                      <i class="fas fa-file-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Mirage G4</span>
                    <span class="vehicle-details">GLS 2024</span>
                  </div>
                </td>
                <td>
                  <div class="order-info">
                    <span class="order-id">25 units</span>
                    <span class="order-date">13.4% of total</span>
                  </div>
                </td>
                <td class="price">₱2.4M</td>
                <td><span class="client-type-badge handled">7.9%</span></td>
                <td style="color: #27ae60; font-weight: bold;">+15.2%</td>
                <td><span class="status-badge confirmed">In Stock (35)</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="View Details" onclick="viewModelDetails('mirage-g4')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-small btn-edit" title="Generate Report" onclick="generateModelReport('mirage-g4')">
                      <i class="fas fa-file-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Outlander PHEV</span>
                    <span class="vehicle-details">Premium 2024</span>
                  </div>
                </td>
                <td>
                  <div class="order-info">
                    <span class="order-id">12 units</span>
                    <span class="order-date">6.4% of total</span>
                  </div>
                </td>
                <td class="price">₱3.6M</td>
                <td><span class="client-type-badge urgent">11.9%</span></td>
                <td style="color: #27ae60; font-weight: bold;">+22.1%</td>
                <td><span class="status-badge cancelled">Out of Stock</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="View Details" onclick="viewModelDetails('outlander-phev')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-small btn-edit" title="Generate Report" onclick="generateModelReport('outlander-phev')">
                      <i class="fas fa-file-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add SweetAlert CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../includes/js/common-scripts.js"></script>
  
  <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      initializeSalesByModelChart();
      initializeRevenueChart();
      
      // Set current month as default
      const currentMonth = new Date().getMonth() + 1;
      document.getElementById('report-month').value = currentMonth;
    });

    function initializeSalesByModelChart() {
      const ctx = document.getElementById('salesByModelChart').getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Montero Sport', 'Xpander', 'Strada', 'Mirage G4', 'Outlander PHEV'],
          datasets: [{
            data: [67, 45, 38, 25, 12],
            backgroundColor: [
              '#3498db',
              '#27ae60',
              '#f39c12',
              '#9b59b6',
              '#e74c3c'
            ],
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 15
              }
            }
          }
        }
      });
    }

    function initializeRevenueChart() {
      const ctx = document.getElementById('revenueChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [{
            label: 'Revenue (Millions)',
            data: [38.2, 42.1, 39.8, 45.2, 48.7, 52.1],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return '₱' + value + 'M';
                }
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }

    function generateReport() {
      const year = document.getElementById('report-year').value;
      const month = document.getElementById('report-month').value;
      const type = document.getElementById('report-type').value;
      
      Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we compile your report data',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      setTimeout(() => {
        Swal.fire({
          title: 'Report Generated!',
          html: `
            <div style="text-align: center;">
              <h3 style="color: #27ae60; margin: 10px 0;">Monthly Dealership Report</h3>
              <p><strong>Period:</strong> ${month === 'all' ? 'Full Year' : 'Month ' + month} ${year}</p>
              <p><strong>Type:</strong> ${type.charAt(0).toUpperCase() + type.slice(1)} Report</p>
              <p><strong>Generated:</strong> ${new Date().toLocaleDateString()}</p>
            </div>
          `,
          icon: 'success',
          confirmButtonColor: '#27ae60'
        });
      }, 2000);
    }

    function viewModelDetails(model) {
      Swal.fire({
        title: 'Model Performance Details',
        html: `
          <div style="text-align: left;">
            <h4>${model.charAt(0).toUpperCase() + model.slice(1)} Performance</h4>
            <p><strong>Top Selling Variant:</strong> GLS Premium</p>
            <p><strong>Average Sale Price:</strong> ₱2,398,000</p>
            <p><strong>Best Sales Month:</strong> February 2024</p>
            <p><strong>Customer Satisfaction:</strong> 4.8/5</p>
            <p><strong>Inventory Turnover:</strong> 12.5 days</p>
          </div>
        `,
        icon: 'info',
        confirmButtonColor: '#3498db'
      });
    }

    function generateModelReport(model) {
      Swal.fire({
        title: 'Generate Model Report',
        text: `Generate detailed report for ${model}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3498db',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Generate Report',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Report Generated!',
            text: `Detailed report for ${model} has been generated and will be downloaded shortly.`,
            icon: 'success',
            confirmButtonColor: '#27ae60'
          });
        }
      });
    }

    // Export report functionality
    document.getElementById('generateReportBtn')?.addEventListener('click', function() {
      Swal.fire({
        title: 'Export Report',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3498db',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Export PDF',
        cancelButtonText: 'Export Excel',
        showDenyButton: true,
        denyButtonText: 'Export CSV',
        denyButtonColor: '#f39c12'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Exporting...',
            text: 'Generating PDF report...',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
          });
        } else if (result.isDenied) {
          Swal.fire({
            title: 'Exporting...',
            text: 'Generating CSV report...',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
          });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          Swal.fire({
            title: 'Exporting...',
            text: 'Generating Excel report...',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
          });
        }
      });
    });
  </script>
</body>
</html>
