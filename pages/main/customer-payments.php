<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php 
  // Mobile Responsive Include - Optimized 2025 Standards
  $css_path = '../../css/';
  $js_path = '../../js/';
  include '../../includes/components/mobile-responsive-include.php'; 
  ?>
  <title>Customer Payments - Mitsubishi</title>
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
    }
    
    body {
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

    .payment-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .overview-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .overview-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: white;
    }

    .overview-icon.green { background: var(--success-green); }
    .overview-icon.orange { background: var(--warning-orange); }
    .overview-icon.red { background: var(--primary-red); }
    .overview-icon.blue { background: var(--accent-blue); }

    .overview-info h3 {
      font-size: 1.6rem;
      color: var(--text-dark);
      margin-bottom: 5px;
    }

    .overview-info p {
      color: var(--text-light);
      font-size: 14px;
    }

    .amount {
      font-weight: 700;
      color: var(--primary-red);
    }

    .quick-actions {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      margin-bottom: 25px;
    }

    .quick-actions h3 {
      font-size: 1.2rem;
      color: var(--text-dark);
      margin-bottom: 20px;
    }

    .action-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .action-card {
      padding: 20px;
      border: 2px solid var(--border-light);
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
    }

    .action-card:hover {
      border-color: var(--primary-red);
      transform: translateY(-2px);
    }

    .action-card i {
      font-size: 24px;
      color: var(--primary-red);
      margin-bottom: 10px;
    }

    .action-card h4 {
      font-size: 14px;
      color: var(--text-dark);
      font-weight: 600;
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
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

    .payments-table {
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      overflow: hidden;
    }

    .table-header {
      padding: 20px 25px;
      background: var(--primary-light);
      border-bottom: 1px solid var(--border-light);
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

    .payment-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .payment-id {
      font-weight: 600;
      color: var(--text-dark);
    }

    .payment-date {
      font-size: 12px;
      color: var(--text-light);
    }

    .customer-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .customer-name {
      font-weight: 500;
      color: var(--text-dark);
    }

    .customer-contact {
      font-size: 12px;
      color: var(--text-light);
    }

    .amount-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .amount-paid {
      font-weight: 600;
      color: var(--success-green);
      font-size: 16px;
    }

    .amount-due {
      font-size: 12px;
      color: var(--text-light);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-badge.paid {
      background: #d4edda;
      color: #155724;
    }

    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge.overdue {
      background: #f8d7da;
      color: #721c24;
    }

    .status-badge.partial {
      background: #d1ecf1;
      color: #0c5460;
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

    .btn-send {
      background: var(--warning-orange);
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

      .payment-overview {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .action-grid {
        grid-template-columns: repeat(2, 1fr);
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
      .payment-overview {
        grid-template-columns: repeat(2, 1fr);
      }

      .action-grid {
        grid-template-columns: repeat(3, 1fr);
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
      .payment-overview {
        grid-template-columns: repeat(2, 1fr);
      }

      .filter-row {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (min-width: 992px) and (max-width: 1199px) {
      .payment-overview {
        grid-template-columns: repeat(4, 1fr);
      }

      .filter-row {
        grid-template-columns: repeat(4, 1fr);
      }
    }
  </style>
</head>
<body>
  <?php include '../../includes/components/sidebar.php'; ?>

  <div class="main">
    <?php include '../../includes/components/topbar.php'; ?>

    <div class="main-content">
      <div class="page-header">
        <h1>Customer Payments</h1>
        <button class="add-btn">
          <i class="fas fa-plus"></i>
          Record Payment
        </button>
      </div>

      <div class="payment-overview">
        <div class="overview-card">
          <div class="overview-icon green">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="overview-info">
            <h3 class="amount">₱8,450,000</h3>
            <p>Total Collected</p>
          </div>
        </div>
        <div class="overview-card">
          <div class="overview-icon orange">
            <i class="fas fa-clock"></i>
          </div>
          <div class="overview-info">
            <h3 class="amount">₱2,150,000</h3>
            <p>Pending Payments</p>
          </div>
        </div>
        <div class="overview-card">
          <div class="overview-icon red">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="overview-info">
            <h3 class="amount">₱680,000</h3>
            <p>Overdue Payments</p>
          </div>
        </div>
        <div class="overview-card">
          <div class="overview-icon blue">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="overview-info">
            <h3>156</h3>
            <p>Active Accounts</p>
          </div>
        </div>
      </div>

      <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-grid">
          <div class="action-card">
            <i class="fas fa-receipt"></i>
            <h4>Generate Invoice</h4>
          </div>
          <div class="action-card">
            <i class="fas fa-envelope"></i>
            <h4>Send Reminder</h4>
          </div>
          <div class="action-card">
            <i class="fas fa-file-excel"></i>
            <h4>Export Report</h4>
          </div>
          <div class="action-card">
            <i class="fas fa-calculator"></i>
            <h4>Payment Calculator</h4>
          </div>
        </div>
      </div>

      <div class="filters-section">
        <div class="filter-row">
          <div class="filter-group">
            <label>Search Payment</label>
            <input type="text" class="filter-input" placeholder="Payment ID, customer name...">
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select class="filter-select">
              <option>All Status</option>
              <option>Paid</option>
              <option>Pending</option>
              <option>Overdue</option>
              <option>Partial</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Payment Method</label>
            <select class="filter-select">
              <option>All Methods</option>
              <option>Cash</option>
              <option>Bank Transfer</option>
              <option>Credit Card</option>
              <option>Check</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Date Range</label>
            <input type="date" class="filter-input">
          </div>
          <div class="filter-group">
            <label>&nbsp;</label>
            <button class="filter-btn">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
        </div>
      </div>

      <div class="payments-table">
        <div class="table-header">
          <h2>Payment Records</h2>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Payment Info</th>
                <th>Customer</th>
                <th>Vehicle/Order</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="payment-info">
                    <div class="payment-id">#PAY-2024-001</div>
                    <div class="payment-date">March 15, 2024</div>
                  </div>
                </td>
                <td>
                  <div class="customer-info">
                    <div class="customer-name">John Doe</div>
                    <div class="customer-contact">john.doe@email.com</div>
                  </div>
                </td>
                <td>
                  <div class="vehicle-info">
                    <div class="vehicle-model">Montero Sport</div>
                    <div class="vehicle-details">#ORD-2024-001</div>
                  </div>
                </td>
                <td>
                  <div class="amount-info">
                    <div class="amount-paid">₱150,000</div>
                    <div class="amount-due">Due: ₱1,500,000</div>
                  </div>
                </td>
                <td>Bank Transfer</td>
                <td><span class="status-badge paid">Paid</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-small btn-view">View</button>
                    <button class="btn-small btn-edit">Edit</button>
                    <button class="btn-small btn-send">Receipt</button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="payment-info">
                    <div class="payment-id">#PAY-2024-002</div>
                    <div class="payment-date">March 10, 2024</div>
                  </div>
                </td>
                <td>
                  <div class="customer-info">
                    <div class="customer-name">Maria Santos</div>
                    <div class="customer-contact">maria.santos@email.com</div>
                  </div>
                </td>
                <td>
                  <div class="vehicle-info">
                    <div class="vehicle-model">Mirage</div>
                    <div class="vehicle-details">#ORD-2024-002</div>
                  </div>
                </td>
                <td>
                  <div class="amount-info">
                    <div class="amount-paid">₱72,000</div>
                    <div class="amount-due">Due: ₱648,000</div>
                  </div>
                </td>
                <td>Credit Card</td>
                <td><span class="status-badge partial">Partial</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-small btn-view">View</button>
                    <button class="btn-small btn-edit">Edit</button>
                    <button class="btn-small btn-send">Receipt</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../includes/js/common-scripts.js"></script>
</body>
</html>
