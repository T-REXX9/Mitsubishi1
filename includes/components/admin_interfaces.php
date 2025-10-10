<!-- Admin Account Review Interface -->
<div class="interface-container" id="accountReviewInterface">
  <div class="interface-header">
    <h2 class="interface-title">
      <i class="fas fa-user-check"></i>
      Admin Account Review
    </h2>
    <button class="interface-close" id="closeAccountReview">&times;</button>
  </div>
  
  <div class="tab-navigation">
    <button class="tab-button active" data-tab="account-pending">Pending Approvals</button>
    <button class="tab-button" data-tab="account-approved">Approved Accounts</button>
    <button class="tab-button" data-tab="account-rejected">Rejected Accounts</button>
    <button class="tab-button" data-tab="account-reports">Account Reports</button>
  </div>
  
  <!-- Pending Approvals Tab -->
  <div class="tab-content active" id="account-pending">
    <div class="info-cards">
      <div class="info-card">
        <div class="info-card-title">Pending Reviews</div>
        <div class="info-card-value">18</div>
      </div>
      <div class="info-card">
        <div class="info-card-title">Today's Registrations</div>
        <div class="info-card-value">7</div>
      </div>
      <div class="info-card">
        <div class="info-card-title">Approval Rate</div>
        <div class="info-card-value">92%</div>
      </div>
      <div class="info-card">
        <div class="info-card-title">Average Review Time</div>
        <div class="info-card-value">2.4 hours</div>
      </div>
    </div>
    
    <div class="filter-bar">
      <div class="search-input">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search customer name or email...">
      </div>
      <select class="filter-select">
        <option value="">All Registration Types</option>
        <option value="individual">Individual Customer</option>
        <option value="corporate">Corporate Account</option>
        <option value="government">Government Entity</option>
      </select>
      <select class="filter-select">
        <option value="">Registration Date</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="week">This Week</option>
      </select>
    </div>
    
    <table class="data-table">
      <thead>
        <tr>
          <th>Registration ID</th>
          <th>Customer Information</th>
          <th>Account Type</th>
          <th>Registration Date</th>
          <th>Documents Status</th>
          <th>Risk Assessment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>REG-2024-089</td>
          <td>John Doe<br><small>john.doe@example.com<br>+63 917 123 4567<br>Makati City, Metro Manila</small></td>
          <td>Individual Customer</td>
          <td>Mar 24, 2024<br>10:30 AM</td>
          <td><span class="status approved">Complete</span></td>
          <td><span class="status approved">Low Risk</span></td>
          <td class="table-actions">
            <button class="btn btn-small btn-primary" onclick="reviewAccount('REG-2024-089')">Review</button>
            <button class="btn btn-small btn-outline" onclick="quickApproveAccount('REG-2024-089')">Quick Approve</button>
            <button class="btn btn-small btn-secondary" onclick="rejectAccount('REG-2024-089')">Reject</button>
          </td>
        </tr>
        <!-- ...existing table rows... -->
      </tbody>
    </table>
    
    <div class="action-area">
      <button class="btn btn-primary">Approve Selected</button>
      <button class="btn btn-secondary">Export Pending List</button>
      <button class="btn btn-outline">Bulk Actions</button>
    </div>
  </div>
  
  <!-- ...existing tabs content... -->
</div>

<!-- Admin Transaction Update Interface -->
<div class="interface-container" id="transactionUpdateInterface">
  <!-- ...existing transaction interface content... -->
</div>

<!-- Admin Car Listing Interface -->
<div class="interface-container" id="carListingInterface">
  <!-- ...existing car listing interface content... -->
</div>
