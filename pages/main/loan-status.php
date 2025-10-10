<?php
// Include the session initialization file at the very beginning
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is Sales Agent
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'SalesAgent') {
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
  <title>Loan Status Management - Sales Agent</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../includes/css/common-styles.css" rel="stylesheet">
  <link href="../../includes/css/orders-styles.css" rel="stylesheet">
</head>

<body>
  <?php include '../../includes/components/sidebar.php'; ?>

  <div class="main">
    <?php include '../../includes/components/topbar.php'; ?>

    <div class="main-content">
      <div class="page-header">
        <h1 class="page-title">
          <i class="fas fa-tasks icon-gradient"></i>
          Loan Status Management
        </h1>
      </div>

      <!-- Sales Agent Statistics -->
      <div class="sales-agent-stats">
        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fas fa-hourglass-half"></i>
          </div>
          <div class="stat-info">
            <h3>8</h3>
            <p>In Progress</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-info">
            <h3>15</h3>
            <p>Approved</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">
            <i class="fas fa-times-circle"></i>
          </div>
          <div class="stat-info">
            <h3>3</h3>
            <p>Rejected</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">
            <i class="fas fa-money-check-alt"></i>
          </div>
          <div class="stat-info">
            <h3>23</h3>
            <p>Disbursed</p>
          </div>
        </div>
      </div>

      <div class="filters-section">
        <div class="filter-row">
          <div class="filter-group">
            <label for="searchInput">Search Applications</label>
            <input type="text" class="filter-input" placeholder="Customer name, phone number, or vehicle model" id="searchInput">
            <div class="search-hint">Try searching "John", "+639123456789", or "Montero"</div>
          </div>
          <div class="filter-group">
            <label for="statusFilter">Application Status</label>
            <select class="filter-select" id="statusFilter">
              <option value="all">All Statuses</option>
              <option value="Pending">Pending</option>
              <option value="Under Review">Under Review</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="dateFilter">Date Range</label>
            <select class="filter-select" id="dateFilter">
              <option value="all">All Time</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
          </div>
          <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
          <button class="filter-btn" onclick="refreshData()" style="background: #17a2b8;">
            <i class="fas fa-refresh"></i> Refresh
          </button>
        </div>
      </div>

      <!-- Loan Status Table -->
      <div class="client-orders-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-list"></i>
            <span id="sectionTitle">Loan Status Management</span>
          </h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Loan Amount</th>
                <th>Current Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="loansTableBody">
              <tr>
                <td>
                  <div class="customer-info">
                    <span class="customer-name">Juan Carlos Mendoza</span>
                    <span class="customer-contact">juan.mendoza@email.com</span>
                    <div class="agent-note">Credit assessment stage</div>
                  </div>
                </td>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Montero Sport</span>
                    <span class="vehicle-details">2024 GLS Premium</span>
                  </div>
                </td>
                <td class="price">₱1,850,000</td>
                <td><span class="status-badge processing">In Progress</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="Update Status" onclick="updateStatus('LA-2024-001')">
                      <i class="fas fa-edit"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="customer-info">
                    <span class="customer-name">Maria Elena Santos</span>
                    <span class="customer-contact">maria.santos@email.com</span>
                    <div class="agent-note">Ready for disbursement</div>
                  </div>
                </td>
                <td>
                  <div class="vehicle-info">
                    <span class="vehicle-model">Xpander</span>
                    <span class="vehicle-details">2024 GLX AT</span>
                  </div>
                </td>
                <td class="price">₱1,200,000</td>
                <td><span class="status-badge confirmed">Approved</span></td>
                <td>
                  <div class="order-actions-enhanced">
                    <button class="btn-small btn-view" title="Update Status" onclick="updateStatus('LA-2024-002')">
                      <i class="fas fa-edit"></i>
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

  <!-- Status Update Modal -->
  <div class="modal-overlay" id="statusModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Update Loan Status</h3>
        <button class="modal-close" onclick="closeStatusModal()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form id="statusForm">
        <div class="modal-body">
          <input type="hidden" id="loanId" name="loanId">
          <div class="form-group">
            <label class="form-label">New Status</label>
            <select class="form-control" id="newStatus" required>
              <option value="">Select new status</option>
              <option value="in-progress">In Progress</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="disbursed">Disbursed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status Update Notes</label>
            <textarea class="form-control" id="statusNotes" rows="3" placeholder="Add notes about this status update..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add SweetAlert CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../includes/js/common-scripts.js"></script>

  <script>
    let allLoanStatuses = [];
    let filteredLoanStatuses = [];
    let currentLoanId = null;

    // Load data when page loads
    document.addEventListener('DOMContentLoaded', function() {
      loadStatistics();
      loadLoanStatuses();

      // Add event listeners for real-time filtering
      const searchInput = document.getElementById('searchInput');
      const statusSelect = document.getElementById('statusFilter');
      const dateSelect = document.getElementById('dateFilter');

      let searchTimeout;

      // Real-time search with debouncing
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          loadLoanStatuses();
        }, 300); // Wait 300ms after user stops typing
      });

      // Immediate filter on dropdown change
      statusSelect.addEventListener('change', loadLoanStatuses);
      dateSelect.addEventListener('change', loadLoanStatuses);

      // Handle Enter key in search
      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          clearTimeout(searchTimeout);
          loadLoanStatuses();
        }
      });
    });

    function loadStatistics() {
      fetch('../../api/loan-applications.php?action=statistics')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Map the statistics to match our display
            const inProgress = (data.data['Pending'] || 0) + (data.data['Under Review'] || 0);
            const approved = data.data['Approved'] || 0;
            const rejected = data.data['Rejected'] || 0;
            const disbursed = data.data['Completed'] || 0;

            document.querySelector('.stat-card:nth-child(1) h3').textContent = inProgress;
            document.querySelector('.stat-card:nth-child(2) h3').textContent = approved;
            document.querySelector('.stat-card:nth-child(3) h3').textContent = rejected;
            document.querySelector('.stat-card:nth-child(4) h3').textContent = disbursed;
          }
        })
        .catch(error => {
          console.error('Error loading statistics:', error);
          showError('Failed to load statistics');
        });
    }

    function loadLoanStatuses() {
      const search = document.getElementById('searchInput').value.trim();
      const status = document.getElementById('statusFilter').value;
      const dateRange = document.getElementById('dateFilter').value;

      // Show loading state
      const tbody = document.getElementById('loansTableBody');
      tbody.innerHTML = '<tr><td colspan="5" class="text-center">Searching for applications...</td></tr>';

      const params = new URLSearchParams({
        action: 'applications',
        search: search,
        status: status,
        date_range: dateRange
      });

      fetch(`../../api/loan-applications.php?${params}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            allLoanStatuses = data.data;
            filteredLoanStatuses = data.data;
            displayLoanStatuses(data.data);

            // Update section title with more natural language
            updateSectionTitle(data.data.length, search, status, dateRange);
          } else {
            showError('Failed to load applications: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error loading loan statuses:', error);
          showError('Failed to load loan applications');
        });
    }

    function updateSectionTitle(count, search, status, dateRange) {
      const sectionTitle = document.getElementById('sectionTitle');

      if (count === 0) {
        if (search || status !== 'all' || dateRange !== 'all') {
          sectionTitle.textContent = 'No applications found';
        } else {
          sectionTitle.textContent = 'No loan applications yet';
        }
        return;
      }

      // Create a more natural title
      let title = '';

      if (count === 1) {
        title = 'Found 1 application';
      } else {
        title = `Found ${count} applications`;
      }

      // Add filter context in natural language
      const filters = [];
      if (search) {
        if (search.match(/^\+?\d+/)) {
          filters.push(`with phone "${search}"`);
        } else if (search.match(/^\d+$/)) {
          filters.push(`#${search}`);
        } else {
          filters.push(`for "${search}"`);
        }
      }

      if (status !== 'all') {
        filters.push(`with ${status.toLowerCase()} status`);
      }

      if (dateRange !== 'all') {
        const timeFrames = {
          'today': 'from today',
          'week': 'from this week',
          'month': 'from this month'
        };
        filters.push(timeFrames[dateRange]);
      }

      if (filters.length > 0) {
        if (filters.length === 1) {
          title += ` ${filters[0]}`;
        } else if (filters.length === 2) {
          title += ` ${filters[0]} and ${filters[1]}`;
        } else {
          title += ` ${filters.slice(0, -1).join(', ')}, and ${filters[filters.length - 1]}`;
        }
      }

      sectionTitle.textContent = title;
    }

    function displayLoanStatuses(statuses) {
      const tbody = document.getElementById('loansTableBody');

      if (statuses.length === 0) {
        const search = document.getElementById('searchInput').value.trim();
        const status = document.getElementById('statusFilter').value;
        const dateRange = document.getElementById('dateFilter').value;

        let noResultsMessage = '';

        if (search || status !== 'all' || dateRange !== 'all') {
          noResultsMessage = 'No applications match your search';
          noResultsMessage += '<br><small style="color: #666;">Try different keywords or clear some filters</small>';
        } else {
          noResultsMessage = 'No loan applications submitted yet';
          noResultsMessage += '<br><small style="color: #666;">Applications will appear here once customers submit them</small>';
        }

        tbody.innerHTML = `<tr><td colspan="5" class="text-center">${noResultsMessage}</td></tr>`;
        return;
      }

      tbody.innerHTML = statuses.map(loan => {
        const statusClass = getStatusClass(loan.status);
        const statusNote = getStatusNote(loan);

        return `
          <tr>
            <td>
              <div class="customer-info">
                <span class="customer-name">${loan.customer_name || 'Unknown Customer'}</span>
                <span class="customer-contact">${loan.customer_email || 'N/A'}</span>
                ${loan.mobile_number && loan.mobile_number !== 'N/A' ? `<div class="agent-note">${loan.mobile_number}</div>` : ''}
              </div>
            </td>
            <td>
              <div class="vehicle-info">
                <span class="vehicle-model">${loan.vehicle_name || 'Unknown Vehicle'}</span>
                <div class="vehicle-meta">
                  <span><i class="fas fa-cog"></i> ${loan.vehicle_engine_type || 'N/A'} | ${loan.vehicle_transmission || 'N/A'}</span>
                  <span><i class="fas fa-gas-pump"></i> ${loan.vehicle_fuel_type || 'N/A'} | ${loan.vehicle_seating_capacity || 'N/A'} seats</span>
                </div>
              </div>
            </td>
            <td class="price">₱${formatPrice(loan.base_price || loan.loan_amount || 0)}</td>
            <td>
              <span class="status-badge ${statusClass}">${loan.status}</span>
              ${statusNote ? `<div class="agent-note" style="margin-top: 5px; font-size: 0.8rem;">${statusNote}</div>` : ''}
            </td>
            <td>
              <div class="order-actions-enhanced">
                <button class="btn-small btn-view" title="Update Status" onclick="updateStatus(${loan.id})">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function getStatusNote(loan) {
      if (loan.reviewed_at && loan.reviewer_name) {
        return `Reviewed by ${loan.reviewer_name}`;
      }
      if (loan.status === 'Pending') {
        return 'Awaiting review';
      }
      if (loan.status === 'Under Review') {
        return 'Currently being processed';
      }
      return '';
    }

    function updateStatus(loanId) {
      currentLoanId = loanId;

      // Find the current loan data
      const loan = allLoanStatuses.find(l => l.id === loanId);
      if (loan) {
        // Pre-populate form with current status
        document.getElementById('newStatus').value = getStatusSelectValue(loan.status);
      }

      document.getElementById('statusModal').classList.add('active');
    }

    function getStatusSelectValue(dbStatus) {
      // Map database status to form select values
      const statusMap = {
        'Pending': 'in-progress',
        'Under Review': 'in-progress',
        'Approved': 'approved',
        'Rejected': 'rejected',
        'Completed': 'disbursed'
      };
      return statusMap[dbStatus] || 'in-progress';
    }

    function applyFilters() {
      loadLoanStatuses();
    }

    function refreshData() {
      // Clear all filters
      document.getElementById('searchInput').value = '';
      document.getElementById('statusFilter').value = 'all';
      document.getElementById('dateFilter').value = 'all';

      // Reset section title
      document.getElementById('sectionTitle').textContent = 'Loan Status Management';

      // Reload data
      loadStatistics();
      loadLoanStatuses();
    }

    // Handle status form submission
    document.getElementById('statusForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const newStatus = document.getElementById('newStatus').value;
      const notes = document.getElementById('statusNotes').value;

      if (newStatus && currentLoanId) {
        // Map form values to database status values
        const statusMap = {
          'in-progress': 'Under Review',
          'approved': 'Approved',
          'rejected': 'Rejected',
          'disbursed': 'Completed'
        };

        const dbStatus = statusMap[newStatus] || newStatus;

        fetch('../../api/loan-applications.php?action=update_status', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              id: currentLoanId,
              status: dbStatus
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                title: 'Status Updated!',
                text: 'Application status updated successfully',
                icon: 'success',
                confirmButtonColor: '#3498db'
              });
              closeStatusModal();
              loadLoanStatuses();
              loadStatistics();
            } else {
              showError('Failed to update status: ' + (data.error || 'Unknown error'));
            }
          })
          .catch(error => {
            console.error('Error updating status:', error);
            showError('Network error while updating status');
          });
      }
    });

    // Helper functions
    function getStatusClass(status) {
      switch (status) {
        case 'Pending':
        case 'Under Review':
          return 'processing';
        case 'Approved':
          return 'confirmed';
        case 'Rejected':
          return 'cancelled';
        case 'Completed':
          return 'delivered';
        default:
          return 'pending';
      }
    }

    function formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }

    function formatPrice(price) {
      return new Intl.NumberFormat('en-US').format(price);
    }

    function formatNumber(number) {
      return new Intl.NumberFormat('en-US').format(number);
    }

    function showError(message) {
      Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        confirmButtonColor: '#d60000'
      });
    }
  </script>

  <style>
    .filter-input:focus,
    .filter-select:focus {
      outline: none;
      border-color: #b80000;
      box-shadow: 0 0 0 2px rgba(184, 0, 0, 0.2);
    }

    .search-hint {
      font-size: 0.8rem;
      color: #666;
      margin-top: 5px;
      font-style: italic;
    }

    .vehicle-meta {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .vehicle-meta span {
      font-size: 0.8rem;
      color: #666;
    }
  </style>
</body>

</html>