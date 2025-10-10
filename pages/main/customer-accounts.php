<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is Sales Agent
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'SalesAgent') {
    header("Location: ../../pages/login.php");
    exit();
}

// Get sales agent ID
$sales_agent_id = $_SESSION['user_id'];

// Fetch customer statistics
try {
    // Total customers count
    $stmt = $connect->prepare("
        SELECT COUNT(DISTINCT ci.cusID) as total_customers
        FROM customer_information ci
        INNER JOIN accounts a ON ci.account_id = a.Id
        WHERE a.Role = 'Customer' AND ci.agent_id = :sales_agent_id
    ");
    $stmt->bindParam(':sales_agent_id', $sales_agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_customers = $stats['total_customers'] ?? 0;

    // Approved customers
    $stmt = $connect->prepare("
        SELECT COUNT(DISTINCT ci.cusID) as approved_customers
        FROM customer_information ci
        INNER JOIN accounts a ON ci.account_id = a.Id
        WHERE a.Role = 'Customer' AND ci.Status = 'Approved' AND ci.agent_id = :sales_agent_id
    ");
    $stmt->bindParam(':sales_agent_id', $sales_agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $approved_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $approved_customers = $approved_stats['approved_customers'] ?? 0;

    // Pending customers
    $stmt = $connect->prepare("
        SELECT COUNT(DISTINCT ci.cusID) as pending_customers
        FROM customer_information ci
        INNER JOIN accounts a ON ci.account_id = a.Id
        WHERE a.Role = 'Customer' AND ci.Status = 'Pending' AND ci.agent_id = :sales_agent_id
    ");
    $stmt->bindParam(':sales_agent_id', $sales_agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $pending_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_customers = $pending_stats['pending_customers'] ?? 0;

    // Fetch all customers with their information
    $stmt = $connect->prepare("
        SELECT 
            ci.*,
            a.Username,
            a.Email,
            a.Status as AccountStatus,
            a.LastLoginAt,
            CONCAT(ci.firstname, ' ', ci.lastname) as full_name
        FROM customer_information ci
        INNER JOIN accounts a ON ci.account_id = a.Id
        WHERE a.Role = 'Customer' AND ci.agent_id = :sales_agent_id
        ORDER BY ci.created_at DESC
    ");
    $stmt->bindParam(':sales_agent_id', $sales_agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching customer data: " . $e->getMessage());
    $customers = [];
    $total_customers = 0;
    $approved_customers = 0;
    $pending_customers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Accounts - Mitsubishi</title>
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
      zoom: 80%;
    }
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .page-header h1 {
      font-size: 2rem;
      color: var(--text-dark);
      font-weight: 700;
    }

    .add-btn {
      background: linear-gradient(135deg, var(--primary-red), #b91c3c);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-medium);
    }

    .customer-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      text-align: center;
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-red);
      margin-bottom: 8px;
    }

    .stat-label {
      color: var(--text-light);
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .filters-section {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      margin-bottom: 25px;
    }

    .filter-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      align-items: end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .filter-group label {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .filter-input, .filter-select {
      padding: 10px 12px;
      border: 1px solid var(--border-light);
      border-radius: 6px;
      font-size: 14px;
    }

    .filter-btn {
      padding: 10px 20px;
      background: var(--accent-blue);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      height: fit-content;
    }

    .customers-table {
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      overflow: hidden;
      margin-top: 25px;
    }

    .table-header {
      padding: 20px 25px;
      background: var(--primary-light);
      border-bottom: 1px solid var(--border-light);
      display: flex;
      justify-content: between;
      align-items: center;
    }

    .table-header h2 {
      font-size: 1.3rem;
      color: var(--text-dark);
    }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th,
    .table td {
      padding: 15px 25px;
      text-align: left;
      border-bottom: 1px solid var(--border-light);
    }

    .table th {
      background: var(--primary-light);
      font-weight: 600;
      color: var(--text-dark);
    }

    .table tbody tr:hover {
      background: #f8f9fa;
    }

    .customer-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .customer-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary-red);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }

    .customer-details h4 {
      font-size: 14px;
      color: var(--text-dark);
      margin-bottom: 2px;
    }

    .customer-details p {
      font-size: 12px;
      color: var(--text-light);
    }

    .status-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-badge.approved {
      background: #d4edda;
      color: #155724;
    }

    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge.rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn-small {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      transition: var(--transition);
    }

    .btn-view {
      background: var(--success-green);
      color: white;
    }

    .btn-edit {
      background: var(--accent-blue);
      color: white;
    }

    .btn-delete {
      background: var(--primary-red);
      color: white;
    }

    .btn-small:hover {
      transform: translateY(-1px);
    }

    /* Responsive Design */
    @media (max-width: 575px) {
      .page-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
      }

      .customer-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .filter-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .table-responsive {
        overflow-x: auto;
      }

      .table {
        min-width: 700px;
      }

      .table th,
      .table td {
        padding: 10px 15px;
        font-size: 14px;
      }
    }

    @media (min-width: 576px) and (max-width: 767px) {
      .customer-stats {
        grid-template-columns: repeat(2, 1fr);
      }

      .filter-row {
        grid-template-columns: repeat(2, 1fr);
      }

      .table th,
      .table td {
        padding: 12px 20px;
      }
    }

    @media (min-width: 768px) and (max-width: 991px) {
      .customer-stats {
        grid-template-columns: repeat(4, 1fr);
      }

      .filter-row {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (min-width: 992px) and (max-width: 1199px) {
      .filter-row {
        grid-template-columns: repeat(4, 1fr);
      }
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

    /* Modal Styles from inventory.php */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      padding: 10px;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal {
      background: white;
      border-radius: 12px;
      width: 95%;
      max-width: 1300px; /* Increased from 700px */
      max-height: 95vh; /* Changed back to 95vh for better responsiveness */
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.3s ease-out;
      display: flex;
      flex-direction: column;
    }

    .modal-header {
      padding: 20px 25px;
      border-bottom: 1px solid var(--border-light);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 1.5rem;
      color: var(--text-dark);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-light);
      transition: var(--transition);
    }

    .modal-close:hover {
      color: var(--primary-red);
    }

    .modal-body {
      max-height: calc(95vh - 140px); /* Adjusted to account for header and footer */
      overflow-y: auto;
      flex: 1;
      padding: 25px;
    }

    .modal-footer {
      padding: 15px 25px;
      border-top: 1px solid var(--border-light);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      flex-shrink: 0;
      background: white;
      border-radius: 0 0 12px 12px;
    }

    /* Button Styles */
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-primary {
      background: var(--primary-red);
      color: white;
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-light);
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Additional styles for the form modal */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-dark);
      font-size: 14px;
    }

    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border-light);
      border-radius: 6px;
      font-size: 14px;
      transition: var(--transition);
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-red);
      box-shadow: 0 0 0 3px rgba(214, 0, 0, 0.1);
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .form-row.three-cols {
      grid-template-columns: repeat(3, 1fr);
    }

    .form-section {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border-light);
    }

    .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }

    .form-section h3 {
      font-size: 1.2rem;
      margin-bottom: 15px;
      color: var(--primary-red);
    }

    .search-account-wrapper {
      position: relative;
    }

    .search-results {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid var(--border-light);
      border-radius: 6px;
      max-height: 200px;
      overflow-y: auto;
      z-index: 100;
      display: none;
      box-shadow: var(--shadow-medium);
    }

    .search-result-item {
      padding: 12px;
      cursor: pointer;
      transition: var(--transition);
      border-bottom: 1px solid var(--border-light);
    }

    .search-result-item:hover {
      background: var(--primary-light);
    }

    .search-result-item:last-child {
      border-bottom: none;
    }

    .account-info-display {
      background: var(--primary-light);
      padding: 15px;
      border-radius: 6px;
      margin-top: 15px;
      display: none;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .info-row:last-child {
      margin-bottom: 0;
    }

    .info-label {
      font-weight: 600;
      color: var(--text-dark);
    }

    .info-value {
      color: var(--text-light);
    }

    /* Responsive modal adjustments */
    @media (max-height: 768px) {
      .modal {
        max-height: 90vh;
      }
      
      .modal-body {
        max-height: calc(90vh - 140px);
      }
    }

    @media (max-height: 600px) {
      .modal {
        max-height: 85vh;
      }
      
      .modal-body {
        max-height: calc(85vh - 140px);
      }
    }

    /* Fix SweetAlert positioning */
    .swal2-container {
      z-index: 10000 !important;
    }

    .swal2-popup {
      font-size: 14px !important;
    }

    .swal2-title {
      font-size: 20px !important;
    }

    .swal2-content {
      font-size: 14px !important;
    }

    .swal2-actions {
      font-size: 14px !important;
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
          <i class="fas fa-users"></i>
          Customer Account Management
        </h1>
        <button class="add-btn" id="addCustomerBtn">
          <i class="fas fa-plus"></i>
          Add Customer Information
        </button>
      </div>

      <div class="customer-stats">
        <div class="stat-card">
          <div class="stat-number"><?php echo $total_customers; ?></div>
          <div class="stat-label">Total Customers</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $approved_customers; ?></div>
          <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $pending_customers; ?></div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $total_customers > 0 ? round(($approved_customers / $total_customers) * 100, 1) : 0; ?>%</div>
          <div class="stat-label">Approval Rate</div>
        </div>
      </div>

      <div class="filters-section">
        <div class="filter-row">
          <div class="filter-group">
            <label for="customer-search">Search Customers</label>
            <input type="text" id="customer-search" class="filter-input" placeholder="Name, email or phone">
          </div>
          <div class="filter-group">
            <label for="customer-status">Status</label>
            <select id="customer-status" class="filter-select">
              <option value="all">All Statuses</option>
              <option value="Approved">Approved</option>
              <option value="Pending">Pending</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
        </div>
      </div>

      <div class="customers-table">
        <div class="table-header">
          <h2>Customer Information Records</h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Customer Name</th>
                <th>Contact Information</th>
                <th>Employment</th>
                <th>Account Status</th>
                <th>Info Status</th>
                <th>Created Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="customersTableBody">
              <?php if (empty($customers)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                  No customer information found. Click "Add Customer Information" to create a new record.
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <tr>
                  <td>
                    <div class="customer-info">
                      <div class="customer-avatar">
                        <?php 
                          $fi = isset($customer['firstname']) && $customer['firstname'] !== null ? substr(trim($customer['firstname']), 0, 1) : '';
                          $li = isset($customer['lastname']) && $customer['lastname'] !== null ? substr(trim($customer['lastname']), 0, 1) : '';
                          $initials = strtoupper(($fi . $li) ?: '?');
                          echo $initials;
                        ?>
                      </div>
                      <div class="customer-details">
                        <h4><?php echo htmlspecialchars($customer['full_name'] ?? ''); ?></h4>
                        <p><?php echo htmlspecialchars($customer['Username'] ?? ''); ?></p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <p><?php echo htmlspecialchars($customer['Email'] ?? ''); ?></p>
                    <p><?php echo htmlspecialchars($customer['mobile_number'] ?? 'N/A'); ?></p>
                    <p><?php echo htmlspecialchars($customer['complete_address'] ?? 'N/A'); ?></p>
                  </td>
                  <td>
                    <p><?php echo htmlspecialchars($customer['company_name'] ?? 'N/A'); ?></p>
                    <p><?php echo htmlspecialchars($customer['position'] ?? 'N/A'); ?></p>
                  </td>
                  <td>
                    <span class="status-badge <?php echo strtolower($customer['AccountStatus'] ?? ''); ?>">
                      <?php echo htmlspecialchars($customer['AccountStatus'] ?? ''); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge <?php echo strtolower($customer['Status'] ?? ''); ?>">
                      <?php echo htmlspecialchars($customer['Status'] ?? ''); ?>
                    </span>
                  </td>
                  <td><?php echo !empty($customer['created_at']) ? date('M d, Y', strtotime($customer['created_at'])) : 'N/A'; ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-small btn-edit" onclick="editCustomer(<?php echo $customer['cusID']; ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-small btn-delete" onclick="deleteCustomer(<?php echo $customer['cusID']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Customer Form Modal -->
      <div class="modal-overlay" id="customerFormModal">
        <div class="modal">
          <div class="modal-header">
            <h3 id="modalTitle">Add Customer Information</h3>
            <button class="modal-close" onclick="closeCustomerModal()">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <form id="customerForm">
            <div class="modal-body">
              <input type="hidden" id="cusID" name="cusID">
              
              <div class="form-section">
                <h3>Customer Type</h3>
                <div class="form-group">
                  <label for="customer_type">Customer Type <span style="color: red;">*</span></label>
                  <select id="customer_type" name="customer_type" class="form-control" required onchange="toggleCustomerType()">
                    <option value="">Select Customer Type</option>
                    <option value="Handled">Handled Client</option>
                    <option value="Walk In">Walk-in Client</option>
                  </select>
                </div>
              </div>

              <div class="form-section" id="handledClientSection" style="display: none;">
                <h3>Account Selection</h3>
                <div class="form-group">
                  <label for="account_search">Search Customer Account <span style="color: red;">*</span></label>
                  <div class="search-account-wrapper" id="search-account-wrapper">
                    <input type="text" id="account_search" class="form-control" 
                           placeholder="Search by username or email...">
                    <input type="hidden" id="account_id" name="account_id">
                    <div class="search-results" id="searchResults"></div>
                  </div>
                  <div class="account-info-display" id="accountInfoDisplay">
                    <div class="info-row">
                      <span class="info-label">Username:</span>
                      <span class="info-value" id="display_username"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Email:</span>
                      <span class="info-value" id="display_email"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Account Status:</span>
                      <span class="info-value" id="display_status"></span>
                    </div>
                  </div>
                  
                  <!-- New display area for personal information for Handled Clients -->
                  <div class="account-info-display" id="handledClientPersonalInfoDisplay" style="margin-top: 10px; display: none;">
                    <h4 style="margin-bottom: 10px; color: var(--primary-red);">Personal Information (from Account)</h4>
                    <div class="info-row">
                      <span class="info-label">First Name:</span>
                      <span class="info-value" id="display_handled_firstname"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Middle Name:</span>
                      <span class="info-value" id="display_handled_middlename"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Last Name:</span>
                      <span class="info-value" id="display_handled_lastname"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Suffix:</span>
                      <span class="info-value" id="display_handled_suffix"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Birthday:</span>
                      <span class="info-value" id="display_handled_birthday"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Age:</span>
                      <span class="info-value" id="display_handled_age"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Gender:</span>
                      <span class="info-value" id="display_handled_gender"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Nationality:</span>
                      <span class="info-value" id="display_handled_nationality"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Civil Status:</span>
                      <span class="info-value" id="display_handled_civil_status"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Mobile Number:</span>
                      <span class="info-value" id="display_handled_mobile_number"></span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Complete Address:</span>
                      <span class="info-value" id="display_handled_complete_address"></span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-section" id="walkInClientSection" style="display: none;">
                <h3>Profile Image</h3>
                <div class="form-group">
                  <label for="profile_image">Profile Image</label>
                  <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*" onchange="previewProfileImage(this)">
                  <div id="profileImagePreview" style="margin-top: 10px; display: none;">
                    <img id="profilePreviewImg" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid var(--border-light);">
                  </div>
                </div>
              </div>

              <div class="form-section" id="personalInfoSection" style="display: none;">
                <h3>Personal Information</h3>
                <div class="form-row">
                  <div class="form-group">
                    <label for="firstname">First Name <span style="color: red;">*</span></label>
                    <input type="text" id="firstname" name="firstname" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="lastname">Last Name <span style="color: red;">*</span></label>
                    <input type="text" id="lastname" name="lastname" class="form-control" required>
                  </div>
                </div>
                
                <div class="form-row three-cols">
                  <div class="form-group">
                    <label for="middlename">Middle Name</label>
                    <input type="text" id="middlename" name="middlename" class="form-control">
                  </div>
                  <div class="form-group">
                    <label for="suffix">Suffix</label>
                    <input type="text" id="suffix" name="suffix" class="form-control" placeholder="Jr., Sr., III">
                  </div>
                  <div class="form-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" class="form-control" value="Filipino">
                  </div>
                </div>

                <div class="form-row three-cols">
                  <div class="form-group">
                    <label for="birthday">Birthday <span style="color: red;">*</span></label>
                    <input type="date" id="birthday" name="birthday" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" class="form-control" readonly>
                  </div>
                  <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                      <option value="">Select Gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="civil_status">Civil Status</label>
                    <select id="civil_status" name="civil_status" class="form-control">
                      <option value="">Select Status</option>
                      <option value="Single">Single</option>
                      <option value="Married">Married</option>
                      <option value="Widowed">Widowed</option>
                      <option value="Divorced">Divorced</option>
                      <option value="Separated">Separated</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" id="mobile_number" name="mobile_number" class="form-control" 
                           placeholder="+63 9XX XXX XXXX">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="complete_address">Complete Address</label>
                    <input type="text" id="complete_address" name="complete_address" class="form-control" placeholder="House No., Street, Barangay, City/Municipality, Province, Zip Code">
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h3>Employment & Financial Information</h3>
                <div class="form-row">
                  <div class="form-group">
                    <label for="employment_status">Employment Status</label>
                    <select id="employment_status" name="employment_status" class="form-control">
                      <option value="">Select Status</option>
                      <option value="Employed">Employed</option>
                      <option value="Self-Employed">Self-Employed</option>
                      <option value="Business Owner">Business Owner</option>
                      <option value="Unemployed">Unemployed</option>
                      <option value="Retired">Retired</option>
                      <option value="Student">Student</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="position">Position/Job Title</label>
                    <input type="text" id="position" name="position" class="form-control">
                  </div>
                  <div class="form-group">
                    <label for="monthly_income">Monthly Income</label>
                    <input type="number" id="monthly_income" name="monthly_income" class="form-control" 
                           step="0.01" placeholder="₱0.00">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="valid_id_type">Valid ID Type <span style="color: red;">*</span></label>
                    <select id="valid_id_type" name="valid_id_type" class="form-control" required>
                      <option value="">Select ID Type</option>
                      <option value="Driver's License">Driver's License</option>
                      <option value="Passport">Passport</option>
                      <option value="SSS ID">SSS ID</option>
                      <option value="UMID">UMID</option>
                      <option value="PhilHealth ID">PhilHealth ID</option>
                      <option value="TIN ID">TIN ID</option>
                      <option value="Postal ID">Postal ID</option>
                      <option value="Voter's ID">Voter's ID</option>
                      <option value="PRC ID">PRC ID</option>
                      <option value="Senior Citizen ID">Senior Citizen ID</option>
                      <option value="Student ID">Student ID</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="valid_id_number">Valid ID Number <span style="color: red;">*</span></label>
                    <input type="text" id="valid_id_number" name="valid_id_number" class="form-control" 
                           placeholder="Enter ID number" required>
                  </div>
                </div>

                <div class="form-group">
                  <label for="valid_id_image">Valid ID Image <span style="color: red;">*</span></label>
                  <input type="file" id="valid_id_image" name="valid_id_image" class="form-control" 
                         accept="image/*" onchange="previewValidIdImage(this)" required>
                  <small style="color: #666; font-size: 12px;">Please upload a clear photo of your valid ID (front side only)</small>
                  <div id="validIdImagePreview" style="margin-top: 10px; display: none;">
                    <img id="validIdPreviewImg" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid var(--border-light);">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <span id="submitBtnText">Save Customer Information</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add SweetAlert CDN -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

      <script src="../../includes/js/common-scripts.js"></script>
      <script>
        // Modal functions updated to match inventory.php style
        function openCustomerModal() {
          document.getElementById('customerFormModal').classList.add('active');
        }

        function closeCustomerModal() {
          document.getElementById('customerFormModal').classList.remove('active');
          document.getElementById('customerForm').reset();
          document.getElementById('accountInfoDisplay').style.display = 'none';
          document.getElementById('handledClientPersonalInfoDisplay').style.display = 'none'; 
          // Clear text content of handledClientPersonalInfoDisplay spans
          const handledInfoSpans = document.querySelectorAll('#handledClientPersonalInfoDisplay .info-value');
          handledInfoSpans.forEach(span => span.textContent = '');
          
          document.getElementById('profileImagePreview').style.display = 'none';
          document.getElementById('validIdImagePreview').style.display = 'none';
          // Ensure sections are reset to default visibility
          document.getElementById('handledClientSection').style.display = 'none';
          document.getElementById('walkInClientSection').style.display = 'none';
          document.getElementById('personalInfoSection').style.display = 'none';
          // Re-enable customer_type select for next operation
          document.getElementById('customer_type').disabled = false;
        }

        document.addEventListener('DOMContentLoaded', function() {
          // Add customer button
          document.getElementById('addCustomerBtn').addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Add Customer Information';
            document.getElementById('submitBtnText').textContent = 'Save Customer Information';
            document.getElementById('customerForm').reset(); 
            document.getElementById('cusID').value = '';
            
            // Ensure customer_type select is enabled for adding and reset its value
            document.getElementById('customer_type').disabled = false;
            document.getElementById('customer_type').value = ''; // Reset selection
            
            // Call toggleCustomerType to ensure sections are correctly hidden/shown based on no selection
            toggleCustomerType(); 

            document.getElementById('accountInfoDisplay').style.display = 'none';
            const handledClientPersonalInfoDisplay = document.getElementById('handledClientPersonalInfoDisplay');
            handledClientPersonalInfoDisplay.style.display = 'none';
            const handledInfoSpans = handledClientPersonalInfoDisplay.querySelectorAll('.info-value');
            handledInfoSpans.forEach(span => span.textContent = '');

            document.getElementById('profileImagePreview').style.display = 'none';
            document.getElementById('profilePreviewImg').src = '';
            document.getElementById('validIdImagePreview').style.display = 'none';
            document.getElementById('validIdPreviewImg').src = '';

            // Reset required attributes for "Add" mode
            // These might be set by toggleCustomerType, but explicit reset here is safer for "Add"
            document.getElementById('valid_id_image').required = true; 
            // Default required for walk-in, will be adjusted by toggleCustomerType if needed
            document.getElementById('firstname').required = true; 
            document.getElementById('lastname').required = true;
            document.getElementById('birthday').required = true;
            document.getElementById('account_search').required = false; // Default for "Add" until type is selected

            openCustomerModal();
          });

          // Form submission
          document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            handleCustomerSubmit();
          });

          // Calculate age when birthday changes
          document.getElementById('birthday').addEventListener('change', function() {
            const birthday = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
              age--;
            }
            
            document.getElementById('age').value = age;
          });

          // Account search functionality
          let searchTimeout;
          document.getElementById('account_search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length < 2) {
              document.getElementById('searchResults').style.display = 'none';
              return;
            }

            searchTimeout = setTimeout(() => {
              fetch(`customer-accounts-ajax.php?action=search_accounts&term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                  const resultsDiv = document.getElementById('searchResults');
                  resultsDiv.innerHTML = '';
                  
                  if (data.success && data.accounts.length > 0) {
                    data.accounts.forEach(account => {
                      const item = document.createElement('div');
                      item.className = 'search-result-item';
                      
                      // Create display name - show full name if available, otherwise username
                      let displayName = account.Username;
                      if (account.FirstName && account.LastName) {
                        displayName = `${account.FirstName} ${account.LastName} (${account.Username})`;
                      } else if (account.FirstName || account.LastName) {
                        displayName = `${account.FirstName || account.LastName} (${account.Username})`;
                      }
                      
                      item.innerHTML = `
                        <strong>${displayName}</strong><br>
                        <small>${account.Email} - ${account.Status}</small>
                      `;
                      item.addEventListener('click', function() {
                        selectAccount(account);
                      });
                      resultsDiv.appendChild(item);
                    });
                    resultsDiv.style.display = 'block';
                  } else {
                    resultsDiv.innerHTML = '<div class="search-result-item">No accounts found</div>';
                    resultsDiv.style.display = 'block';
                  }
                })
                .catch(error => {
                  console.error('Error searching accounts:', error);
                });
            }, 300);
          });

          // Click outside to close search results
          document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-account-wrapper')) {
              document.getElementById('searchResults').style.display = 'none';
            }
          });
        });

        // Handle form submission
        async function handleCustomerSubmit() {
          const form = document.getElementById('customerForm');
          const formData = new FormData(form);
          
          // Validate customer type
          const customerType = formData.get('customer_type');
          if (!customerType) {
            Swal.fire({
              title: 'Error',
              text: 'Please select a customer type',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '400px'
            });
            return;
          }

          // Validate based on customer type
          if (customerType === 'Handled' && !formData.get('account_id')) {
            Swal.fire({
              title: 'Error',
              text: 'Please select a customer account for handled clients',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '400px'
            });
            return;
          }

          // Validate required fields
          if (!formData.get('valid_id_type') || !formData.get('valid_id_number')) {
            Swal.fire({
              title: 'Error',
              text: 'Please fill in valid ID type and number',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '400px'
            });
            return;
          }

          // Validate valid ID image for new customers
          if (!formData.get('cusID') && !formData.get('valid_id_image').name) {
            Swal.fire({
              title: 'Error',
              text: 'Please upload a valid ID image',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '400px'
            });
            return;
          }

          // Add action
          formData.append('action', formData.get('cusID') ? 'update_customer' : 'add_customer');

          try {
            const response = await fetch('customer-accounts-ajax.php', {
              method: 'POST',
              body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
              Swal.fire({
                title: 'Success',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#d60000',
                confirmButtonText: 'OK',
                allowOutsideClick: true,
                allowEscapeKey: true,
                backdrop: true,
                heightAuto: false,
                width: '400px'
              }).then(() => {
                closeCustomerModal();
                location.reload();
              });
            } else {
              Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to save customer',
                icon: 'error',
                confirmButtonColor: '#d60000',
                confirmButtonText: 'OK',
                allowOutsideClick: true,
                allowEscapeKey: true,
                backdrop: true,
                heightAuto: false,
                width: '400px'
              });
            }
          } catch (error) {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error',
              text: 'An error occurred while saving',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '400px'
            });
          }
        }

        // Function to toggle customer type sections
        function toggleCustomerType() {
          const customerType = document.getElementById('customer_type').value;
          const handledSection = document.getElementById('handledClientSection');
          const walkInSection = document.getElementById('walkInClientSection');
          const personalInfoSectionInputs = document.getElementById('personalInfoSection'); // This is the section with INPUTS
          const handledClientPersonalInfoDisplay = document.getElementById('handledClientPersonalInfoDisplay');
          const accountSearchInput = document.getElementById('account_search');
          // const accountId = document.getElementById('account_id'); // Not directly used for visibility logic here

          const searchLabel = document.querySelector('label[for="account_search"]');
          const searchWrapper = document.getElementById('search-account-wrapper');
          
          // Reset relevant parts of the form, but preserve customer_type and cusID
          const currentCusID = document.getElementById('cusID').value;
          const form = document.getElementById('customerForm');
          const elementsToReset = Array.from(form.elements).filter(el => el.id !== 'customer_type' && el.id !== 'cusID');

          elementsToReset.forEach(element => {
            if (element.type === 'file') {
                element.value = ''; // Clear file input
            } else if (element.type === 'select-one') {
                element.selectedIndex = 0;
            } else if (element.type !== 'button' && element.type !== 'submit' && element.type !== 'reset') {
                element.value = '';
            }
            if (element.readOnly) {
                element.readOnly = false;
                element.style.backgroundColor = '';
            }
            // Reset required attributes to a default (e.g., true for walk-in, false for handled initially)
            // This will be fine-tuned below based on customerType
          });
          document.getElementById('cusID').value = currentCusID; // Restore cusID
          document.getElementById('customer_type').value = customerType; // Restore customer_type

          // Hide all dynamic displays initially
          document.getElementById('accountInfoDisplay').style.display = 'none';
          handledClientPersonalInfoDisplay.style.display = 'none';
          document.getElementById('profileImagePreview').style.display = 'none';
          document.getElementById('validIdImagePreview').style.display = 'none';

          // Default state for search elements: hidden. Also ensure account_search is not required by default.
          if (searchLabel) searchLabel.style.display = 'none';
          if (searchWrapper) searchWrapper.style.display = 'none';
          accountSearchInput.required = false;
          
          if (customerType === 'Handled') {
            handledSection.style.display = 'block';
            walkInSection.style.display = 'none';
            personalInfoSectionInputs.style.display = 'none'; // Hide input section
            
            // For 'Handled' type, show search elements by default.
            // editCustomer will hide these if it's an edit operation.
            if (searchLabel) searchLabel.style.display = 'block'; 
            if (searchWrapper) searchWrapper.style.display = 'block';
            
            accountSearchInput.required = true;
            // accountId.required = true; // account_id is hidden, its requirement is implicit via account_search
            
            document.getElementById('profile_image').required = false;
            document.getElementById('firstname').required = false;
            document.getElementById('lastname').required = false;
            document.getElementById('birthday').required = false;
          } else if (customerType === 'Walk In') {
            handledSection.style.display = 'none';
            walkInSection.style.display = 'block';
            personalInfoSectionInputs.style.display = 'block'; // Show input section
            
            // accountSearchInput.required is already false from default
            
            document.getElementById('profile_image').required = false; // Optional for walk-in
            document.getElementById('firstname').required = true;
            document.getElementById('lastname').required = true;
            document.getElementById('birthday').required = true;
          } else {
            // No customer type selected
            handledSection.style.display = 'none';
            walkInSection.style.display = 'none';
            personalInfoSectionInputs.style.display = 'none'; // Hide input section
            
            // accountSearchInput.required is already false from default
            // Set default required for general fields if form can be submitted without type (should not happen with validation)
            document.getElementById('firstname').required = true; 
            document.getElementById('lastname').required = true;
            document.getElementById('birthday').required = true;
          }
        }

        // Function to preview profile image
        function previewProfileImage(input) {
          if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
              document.getElementById('profilePreviewImg').src = e.target.result;
              document.getElementById('profileImagePreview').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
          }
        }

        // Function to preview valid ID image
        function previewValidIdImage(input) {
          if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
              document.getElementById('validIdPreviewImg').src = e.target.result;
              document.getElementById('validIdImagePreview').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
          }
        }

        // Update selectAccount function to populate the new display area
        function selectAccount(account) {
          // Check account status first
          if (account.Status === 'Pending' || account.Status === 'Rejected') {
            let message = '';
            if (account.Status === 'Pending') {
              message = 'This account is currently pending approval by an administrator. Customer information cannot be added until the account is approved.';
            } else { // Rejected
              message = 'This account has been rejected. Customer information cannot be added for a rejected account.';
            }
            Swal.fire({
              title: `Account Status: ${account.Status}`,
              text: message,
              icon: 'warning',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK',
              allowOutsideClick: true,
              allowEscapeKey: true,
              backdrop: true,
              heightAuto: false,
              width: '450px'
            });
            // Clear search and prevent further processing for this account
            document.getElementById('account_search').value = '';
            document.getElementById('account_id').value = '';
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('accountInfoDisplay').style.display = 'none';
            document.getElementById('handledClientPersonalInfoDisplay').style.display = 'none';
            return; // Stop further execution
          }

          document.getElementById('account_id').value = account.Id;
          document.getElementById('account_search').value = account.Username;
          document.getElementById('searchResults').style.display = 'none';
          
          // Show account info (Username, Email, Status)
          document.getElementById('display_username').textContent = account.Username || 'N/A';
          document.getElementById('display_email').textContent = account.Email || 'N/A';
          document.getElementById('display_status').textContent = account.Status || 'N/A';
          document.getElementById('accountInfoDisplay').style.display = 'block';

          // Populate and show handled client personal info display section
          // Fields from 'accounts' table (available directly from 'account' object)
          document.getElementById('display_handled_firstname').textContent = account.FirstName || 'N/A';
          document.getElementById('display_handled_lastname').textContent = account.LastName || 'N/A';
          
          let ageDisplay = 'N/A';
          if (account.DateOfBirth) {
            document.getElementById('display_handled_birthday').textContent = new Date(account.DateOfBirth).toLocaleDateString('en-CA'); // YYYY-MM-DD format
            const birthday = new Date(account.DateOfBirth);
            const today = new Date();
            let age = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
              age--;
            }
            ageDisplay = age;
          } else {
            document.getElementById('display_handled_birthday').textContent = 'N/A';
          }
          document.getElementById('display_handled_age').textContent = ageDisplay;

          // Fields from 'customer_information' (not available from basic account search, set to N/A)
          // These will be populated by editCustomer if a record exists.
          document.getElementById('display_handled_middlename').textContent = 'N/A';
          document.getElementById('display_handled_suffix').textContent = 'N/A';
          document.getElementById('display_handled_gender').textContent = 'N/A';
          document.getElementById('display_handled_nationality').textContent = 'N/A';
          document.getElementById('display_handled_civil_status').textContent = 'N/A';
          document.getElementById('display_handled_mobile_number').textContent = 'N/A';
          document.getElementById('display_handled_complete_address').textContent = 'N/A';
          
          document.getElementById('handledClientPersonalInfoDisplay').style.display = 'block';
          document.getElementById('personalInfoSection').style.display = 'none';
        }

        // Update editCustomer function to properly handle customer types and new display
        async function editCustomer(cusID) {
          try {
            const response = await fetch(`customer-accounts-ajax.php?action=get_customer&id=${cusID}`);
            const data = await response.json();
            console.log('[editCustomer] get_customer payload:', data);
            
            if (data.success) {
              const customer = data.customer; // This object contains joined data
              document.getElementById('modalTitle').textContent = 'Edit Customer Information';
              document.getElementById('submitBtnText').textContent = 'Update Customer Information';
              document.getElementById('customerForm').reset(); // Reset form before populating
              document.getElementById('cusID').value = customer.cusID;
              
              document.getElementById('customer_type').value = customer.customer_type || '';
              // Call toggleCustomerType first to set up sections based on the customer's actual type
              toggleCustomerType(); 
              // Disable customer_type select in edit mode
              document.getElementById('customer_type').disabled = true;
              
              if (customer.customer_type === 'Handled') {
                console.log('[editCustomer] Handled client branch for cusID:', cusID);
                // toggleCustomerType would have made search input elements visible.
                // Hide them specifically for edit mode of a Handled client.
                const searchLabel = document.querySelector('label[for="account_search"]');
                if (searchLabel) searchLabel.style.display = 'none';
                const searchWrapper = document.getElementById('search-account-wrapper');
                if (searchWrapper) searchWrapper.style.display = 'none';
                document.getElementById('account_search').required = false; // No longer required as it's hidden

                document.getElementById('account_id').value = customer.account_id || '';
                // account_search input value is not needed as it's hidden
                
                document.getElementById('display_username').textContent = customer.Username || 'N/A';
                document.getElementById('display_email').textContent = customer.Email || 'N/A';
                document.getElementById('display_status').textContent = customer.AccountStatus || 'N/A'; 
                document.getElementById('accountInfoDisplay').style.display = 'block';

                // Populate handledClientPersonalInfoDisplay
                const acctFirst = customer.AccountFirstName || '';
                const acctLast = customer.AccountLastName || '';
                const acctDob = customer.AccountDateOfBirth || '';

                document.getElementById('display_handled_firstname').textContent = (customer.firstname || acctFirst || 'N/A');
                document.getElementById('display_handled_middlename').textContent = (customer.middlename || 'N/A');
                document.getElementById('display_handled_lastname').textContent = (customer.lastname || acctLast || 'N/A');
                document.getElementById('display_handled_suffix').textContent = (customer.suffix || 'N/A');
                
                let ageDisplay = 'N/A';
                const dob = customer.birthday || acctDob;
                if (dob) {
                    document.getElementById('display_handled_birthday').textContent = new Date(dob).toLocaleDateString('en-CA');
                    const birthdayDate = new Date(dob);
                    const today = new Date();
                    let age = today.getFullYear() - birthdayDate.getFullYear();
                    const monthDiff = today.getMonth() - birthdayDate.getMonth();
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdayDate.getDate())) {
                        age--;
                    }
                    ageDisplay = age;
                } else {
                    document.getElementById('display_handled_birthday').textContent = 'N/A';
                }
                document.getElementById('display_handled_age').textContent = customer.age || ageDisplay; // Use stored age if available, else calculate

                document.getElementById('display_handled_gender').textContent = customer.gender || 'N/A';
                document.getElementById('display_handled_nationality').textContent = customer.nationality || 'N/A';
                document.getElementById('display_handled_civil_status').textContent = customer.civil_status || 'N/A';
                document.getElementById('display_handled_mobile_number').textContent = customer.mobile_number || 'N/A';
                document.getElementById('display_handled_complete_address').textContent = customer.complete_address || 'N/A';
                
                document.getElementById('handledClientPersonalInfoDisplay').style.display = 'block';

                // Also show editable personal info inputs so missing fields can be updated
                const personalInfoSection = document.getElementById('personalInfoSection');
                if (personalInfoSection) {
                  personalInfoSection.style.display = 'block';
                  console.log('[editCustomer] Showing personalInfoSection for handled edit');
                  // Scroll into view to make it obvious
                  try {
                    document.querySelector('#customerForm .modal-body').scrollTo({ top: personalInfoSection.offsetTop - 20, behavior: 'smooth' });
                  } catch (e) {
                    // no-op if scrollTo not supported
                  }
                }

                // Prefill inputs with data (fallbacks to account fields where appropriate)
                const firstNameInput = document.getElementById('firstname');
                const lastNameInput = document.getElementById('lastname');
                const middleNameInput = document.getElementById('middlename');
                const suffixInput = document.getElementById('suffix');
                const nationalityInput = document.getElementById('nationality');
                const birthdayInput = document.getElementById('birthday');
                const ageInput = document.getElementById('age');
                const genderInput = document.getElementById('gender');
                const civilStatusInput = document.getElementById('civil_status');
                const mobileInput = document.getElementById('mobile_number');
                const addressInput = document.getElementById('complete_address');

                if (firstNameInput) firstNameInput.value = (customer.firstname || acctFirst || '');
                if (lastNameInput) lastNameInput.value = (customer.lastname || acctLast || '');
                if (middleNameInput) middleNameInput.value = (customer.middlename || '');
                if (suffixInput) suffixInput.value = (customer.suffix || '');
                if (nationalityInput) nationalityInput.value = (customer.nationality || '');

                // Set birthday (YYYY-MM-DD) using dob fallback
                if (birthdayInput) {
                  if (dob) {
                    const dt = new Date(dob);
                    const y = dt.getFullYear();
                    const m = String(dt.getMonth() + 1).padStart(2, '0');
                    const d = String(dt.getDate()).padStart(2, '0');
                    birthdayInput.value = `${y}-${m}-${d}`;
                  } else {
                    birthdayInput.value = '';
                  }
                }
                if (ageInput) ageInput.value = customer.age || ageDisplay || '';

                if (genderInput) genderInput.value = (customer.gender || '');
                if (civilStatusInput) civilStatusInput.value = (customer.civil_status || '');
                if (mobileInput) mobileInput.value = (customer.mobile_number || '');
                if (addressInput) addressInput.value = (customer.complete_address || '');

                // Ensure these are required for update
                if (firstNameInput) firstNameInput.required = true;
                if (lastNameInput) lastNameInput.required = true;
                if (birthdayInput) birthdayInput.required = true;

              } else { // Walk In
                // personalInfoSection is correctly shown by toggleCustomerType for 'Walk In'
                // Fill input form fields in personalInfoSection
                document.getElementById('firstname').value = customer.firstname || '';
                document.getElementById('lastname').value = customer.lastname || '';
                document.getElementById('middlename').value = customer.middlename || '';
                document.getElementById('suffix').value = customer.suffix || '';
                document.getElementById('nationality').value = customer.nationality || 'Filipino';
                document.getElementById('birthday').value = customer.birthday || '';
                if(customer.birthday) { // Recalculate age for display in input
                    const birthdayDate = new Date(customer.birthday);
                    const today = new Date();
                    let age = today.getFullYear() - birthdayDate.getFullYear();
                    const monthDiff = today.getMonth() - birthdayDate.getMonth();
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdayDate.getDate())) {
                        age--;
                    }
                    document.getElementById('age').value = age;
                } else {
                    document.getElementById('age').value = '';
                }
                document.getElementById('gender').value = customer.gender || '';
                document.getElementById('civil_status').value = customer.civil_status || '';
                document.getElementById('mobile_number').value = customer.mobile_number || '';

                // Display existing profile image for Walk-in if available
                // Assumes backend sends 'profile_image_url' (e.g., base64 data URI)
                if (customer.profile_image_url) {
                    document.getElementById('profilePreviewImg').src = customer.profile_image_url;
                    document.getElementById('profileImagePreview').style.display = 'block';
                } else {
                    document.getElementById('profileImagePreview').style.display = 'none';
                    document.getElementById('profilePreviewImg').src = '';
                }
              }
              
              // Common fields for both types (Employment & Financial, Valid ID)
              document.getElementById('employment_status').value = customer.employment_status || '';
              document.getElementById('company_name').value = customer.company_name || '';
              document.getElementById('position').value = customer.position || '';
              document.getElementById('monthly_income').value = customer.monthly_income || '';
              document.getElementById('valid_id_type').value = customer.valid_id_type || '';
              document.getElementById('valid_id_number').value = customer.valid_id_number || '';
              
              // Display existing valid ID image if available
              // Assumes backend sends 'valid_id_image_url' (e.g., base64 data URI)
              if (customer.valid_id_image_url) {
                  document.getElementById('validIdPreviewImg').src = customer.valid_id_image_url;
                  document.getElementById('validIdImagePreview').style.display = 'block';
              } else {
                  document.getElementById('validIdImagePreview').style.display = 'none';
                  document.getElementById('validIdPreviewImg').src = '';
              }
              
              document.getElementById('valid_id_image').required = false; // Not required when editing, user can choose to update
              document.getElementById('profile_image').required = false; // Not required when editing for walk-in
              
              openCustomerModal();
            } else {
              Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to load customer data',
                icon: 'error',
                confirmButtonColor: '#d60000',
                confirmButtonText: 'OK'
              });
            }
          } catch (error) {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error',
              text: 'Failed to load customer data',
              icon: 'error',
              confirmButtonColor: '#d60000',
              confirmButtonText: 'OK'
            });
          }
        }

        // Delete customer function
        function deleteCustomer(cusID) {
          Swal.fire({
            title: 'Delete Customer?',
            text: 'Are you sure you want to delete this customer? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d60000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            allowOutsideClick: true,
            allowEscapeKey: true,
            backdrop: true,
            heightAuto: false,
            width: '400px'
          }).then((result) => {
            if (result.isConfirmed) {
              fetch('customer-accounts-ajax.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_customer&cusID=${cusID}`
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  Swal.fire({
                    title: 'Deleted!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#d60000',
                    confirmButtonText: 'OK',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    backdrop: true,
                    heightAuto: false,
                    width: '400px'
                  }).then(() => {
                    location.reload();
                  });
                } else {
                  Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to delete customer',
                    icon: 'error',
                    confirmButtonColor: '#d60000',
                    confirmButtonText: 'OK',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    backdrop: true,
                    heightAuto: false,
                    width: '400px'
                  });
                }
              });
            }
          });
        }

        // Apply filters function
        function applyFilters() {
          const search = document.getElementById('customer-search').value;
          const status = document.getElementById('customer-status').value;
          
          fetch(`customer-accounts-ajax.php?action=get_customers&search=${encodeURIComponent(search)}&status=${status}`)
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                updateCustomersTable(data.customers);
              }
            })
            .catch(error => {
              console.error('Error applying filters:', error);
            });
        }

        // Update customers table
        function updateCustomersTable(customers) {
          const tbody = document.getElementById('customersTableBody');
          tbody.innerHTML = '';
          
          if (customers.length === 0) {
            tbody.innerHTML = `
              <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                  No customers found matching your criteria.
                </td>
              </tr>
            `;
            return;
          }
          
          customers.forEach(customer => {
            const row = `
              <tr>
                <td>
                  <div class="customer-info">
                    <div class="customer-avatar">
                      ${customer.firstname.charAt(0).toUpperCase()}${customer.lastname.charAt(0).toUpperCase()
                      }
                    </div>
                    <div class="customer-details">
                      <h4>${customer.full_name}</h4>
                      <p>${customer.Username}</p>
                    </div>
                  </div>
                </td>
                <td>
                  <p>${customer.Email}</p>
                  <p>${customer.mobile_number || 'N/A'}</p>
                  <p>${customer.complete_address || 'N/A'}</p>
                </td>
                <td>
                  <p>${customer.company_name || 'N/A'}</p>
                  <p>${customer.position || 'N/A'}</p>
                </td>
                <td>
                  <span class="status-badge ${customer.AccountStatus.toLowerCase()}">
                    ${customer.AccountStatus}
                  </span>
                </td>
                <td>
                  <span class="status-badge ${customer.Status.toLowerCase()}">
                    ${customer.Status}
                  </span>
                </td>
                <td>${new Date(customer.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-small btn-edit" onclick="editCustomer(${customer.cusID})">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-small btn-delete" onclick="deleteCustomer(${customer.cusID})">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            `;
            tbody.innerHTML += row;
          });
        }
      </script>
    </div>
  </div>
</body>
</html>
