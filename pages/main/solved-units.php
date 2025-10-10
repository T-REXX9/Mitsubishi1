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
  <title>Sold Units - Mitsubishi</title>
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
      zoom: 75%;
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

    /* Monthly calendar view */
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .calendar-title {
      font-size: 18px;
      font-weight: 600;
    }
    
    .calendar-nav {
      display: flex;
      gap: 10px;
    }
    
    .calendar-nav button {
      background: none;
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--text-dark);
    }
    
    .calendar-nav button:hover {
      background: var(--border-light);
    }
    
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
    }
    
    .calendar-weekday {
      text-align: center;
      font-weight: 600;
      padding: 10px;
      color: var(--text-light);
      font-size: 12px;
    }
    
    .calendar-day {
      aspect-ratio: 1;
      border-radius: 5px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      padding: 5px;
      position: relative;
    }
    
    .calendar-day:hover {
      background: var(--border-light);
    }
    
    .calendar-date {
      font-weight: 500;
      margin-bottom: 5px;
    }
    
    .calendar-value {
      font-size: 10px;
      font-weight: 700;
      color: var(--primary-red);
    }
    
    .calendar-day.has-sales::after {
      content: '';
      position: absolute;
      bottom: 8px;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--primary-red);
    }
    
    .calendar-day.today {
      background: #f3f4f6;
      font-weight: 700;
    }
    
    .calendar-day.inactive {
      opacity: 0.3;
    }

    /* Responsive Design */
    @media (max-width: 575px) {
      .info-cards, .stat-cards {
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
      .calendar-grid {
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
      }
      .calendar-day {
        padding: 2px;
      }
      .calendar-date {
        font-size: 10px;
      }
      .calendar-value {
        font-size: 8px;
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
          <i class="fas fa-check-square"></i>
          Sold Units
        </h1>
      </div>

      <div class="tab-navigation">
        <button class="tab-button active" data-tab="solved-monthly">Monthly Performance</button>
        <button class="tab-button" data-tab="solved-agents">Agent Rankings</button>
        <button class="tab-button" data-tab="solved-targets">Targets & Goals</button>
        <button class="tab-button" data-tab="solved-analytics">Performance Analytics</button>
      </div>

      <!-- Monthly Performance Tab -->
      <div class="tab-content active" id="solved-monthly">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-card-title">Total Units Sold (Month)</div>
            <div class="info-card-value">87</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Sales Target</div>
            <div class="info-card-value">100</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Achievement Rate</div>
            <div class="info-card-value">87%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Top Performer</div>
            <div class="info-card-value">Carlos M.</div>
          </div>
        </div>

        <!-- Sales Calendar View -->
        <div class="leaderboard">
          <div class="calendar-header">
            <div class="calendar-title">March 2024 Sales Distribution</div>
            <div class="calendar-nav">
              <button><i class="fas fa-chevron-left"></i></button>
              <button><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
          
          <div class="calendar-grid">
            <div class="calendar-weekday">Sun</div>
            <div class="calendar-weekday">Mon</div>
            <div class="calendar-weekday">Tue</div>
            <div class="calendar-weekday">Wed</div>
            <div class="calendar-weekday">Thu</div>
            <div class="calendar-weekday">Fri</div>
            <div class="calendar-weekday">Sat</div>
            
            <!-- Week 1 -->
            <div class="calendar-day inactive">
              <div class="calendar-date">25</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">26</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">27</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">28</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">29</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">1</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">2</div>
              <div class="calendar-value">2</div>
            </div>
            
            <!-- Week 2 -->
            <div class="calendar-day has-sales">
              <div class="calendar-date">3</div>
              <div class="calendar-value">1</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">4</div>
              <div class="calendar-value">4</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">5</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">6</div>
              <div class="calendar-value">2</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">7</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">8</div>
              <div class="calendar-value">5</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">9</div>
              <div class="calendar-value">4</div>
            </div>
            
            <!-- Week 3 -->
            <div class="calendar-day has-sales">
              <div class="calendar-date">10</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">11</div>
              <div class="calendar-value">2</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">12</div>
              <div class="calendar-value">4</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">13</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">14</div>
              <div class="calendar-value">2</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">15</div>
              <div class="calendar-value">4</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">16</div>
              <div class="calendar-value">5</div>
            </div>
            
            <!-- Week 4 -->
            <div class="calendar-day has-sales">
              <div class="calendar-date">17</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">18</div>
              <div class="calendar-value">4</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">19</div>
              <div class="calendar-value">2</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">20</div>
              <div class="calendar-value">3</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">21</div>
              <div class="calendar-value">1</div>
            </div>
            <div class="calendar-day has-sales">
              <div class="calendar-date">22</div>
              <div class="calendar-value">4</div>
            </div>
            <div class="calendar-day has-sales today">
              <div class="calendar-date">23</div>
              <div class="calendar-value">5</div>
            </div>
            
            <!-- Week 5 -->
            <div class="calendar-day">
              <div class="calendar-date">24</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">25</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">26</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">27</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">28</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">29</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day">
              <div class="calendar-date">30</div>
              <div class="calendar-value">0</div>
            </div>
            
            <!-- Week 6 (partial) -->
            <div class="calendar-day">
              <div class="calendar-date">31</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">1</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">2</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">3</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">4</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">5</div>
              <div class="calendar-value">0</div>
            </div>
            <div class="calendar-day inactive">
              <div class="calendar-date">6</div>
              <div class="calendar-value">0</div>
            </div>
          </div>
        </div>

        <div class="filter-bar">
          <select class="filter-select">
            <option value="">Select Month</option>
            <option value="march-2024" selected>March 2024</option>
            <option value="february-2024">February 2024</option>
            <option value="january-2024">January 2024</option>
          </select>
          <select class="filter-select">
            <option value="">All Agents</option>
            <option value="carlos">Carlos Mendoza</option>
            <option value="ana">Ana Santos</option>
            <option value="juan">Juan Reyes</option>
          </select>
          <button class="btn btn-primary">Apply Filters</button>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Sales Agent</th>
              <th>Units Sold</th>
              <th>Monthly Target</th>
              <th>Achievement %</th>
              <th>Total Sales Value</th>
              <th>Commission Earned</th>
              <th>Ranking</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Carlos Mendoza<br><small>Senior Sales Agent</small></td>
              <td>23</td>
              <td>20</td>
              <td><span class="status approved">115%</span></td>
              <td>₱34,270,000</td>
              <td>₱856,750</td>
              <td>1st</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-primary">Add Bonus</button>
              </td>
            </tr>
            <tr>
              <td>Ana Santos<br><small>Sales Agent</small></td>
              <td>19</td>
              <td>18</td>
              <td><span class="status approved">106%</span></td>
              <td>₱24,845,000</td>
              <td>₱621,125</td>
              <td>2nd</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-primary">Add Bonus</button>
              </td>
            </tr>
            <tr>
              <td>Juan Reyes<br><small>Junior Sales Agent</small></td>
              <td>12</td>
              <td>15</td>
              <td><span class="status pending">80%</span></td>
              <td>₱15,240,000</td>
              <td>₱381,000</td>
              <td>3rd</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-secondary">Set Training</button>
              </td>
            </tr>
            <tr>
              <td>Maria Gonzales<br><small>Sales Agent</small></td>
              <td>8</td>
              <td>15</td>
              <td><span class="status overdue">53%</span></td>
              <td>₱9,560,000</td>
              <td>₱239,000</td>
              <td>4th</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">View Details</button>
                <button class="btn btn-small btn-secondary">Performance Plan</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Export Performance Data</button>
          <button class="btn btn-secondary">Generate Monthly Report</button>
          <button class="btn btn-outline">Adjust Targets</button>
        </div>
      </div>

      <!-- Agent Rankings Tab -->
      <div class="tab-content" id="solved-agents">
        <div class="filter-bar">
          <select class="filter-select">
            <option value="month">Monthly Rankings</option>
            <option value="quarter">Quarterly Rankings</option>
            <option value="ytd">Year-to-Date Rankings</option>
            <option value="annual">Annual Rankings</option>
          </select>
          <select class="filter-select">
            <option value="2024" selected>2024</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
          </select>
          <button class="btn btn-primary">Apply</button>
        </div>
        
        <div class="stat-cards">
          <div class="stat-card rank-1">
            <div class="stat-value">1st</div>
            <div class="stat-label">Carlos Mendoza</div>
            <div class="stat-label">204 Units YTD</div>
          </div>
          <div class="stat-card rank-2">
            <div class="stat-value">2nd</div>
            <div class="stat-label">Ana Santos</div>
            <div class="stat-label">180 Units YTD</div>
          </div>
          <div class="stat-card rank-3">
            <div class="stat-value">3rd</div>
            <div class="stat-label">Juan Reyes</div>
            <div class="stat-label">135 Units YTD</div>
          </div>
          <div class="stat-card rank-other">
            <div class="stat-value">4th</div>
            <div class="stat-label">Maria Gonzales</div>
            <div class="stat-label">112 Units YTD</div>
          </div>
        </div>
        
        <!-- Top Performers Leaderboard -->
        <div class="leaderboard">
          <div class="leaderboard-title">
            <i class="fas fa-trophy"></i>
            Top Performers Leaderboard
          </div>
          
          <div class="leaderboard-item rank-1">
            <div class="leaderboard-rank">1</div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">Carlos Mendoza</div>
              <div class="leaderboard-stats">204 Units | ₱305.6M Value | 115% Target</div>
            </div>
            <div class="leaderboard-score">95.8 pts</div>
          </div>
          
          <div class="leaderboard-item rank-2">
            <div class="leaderboard-rank">2</div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">Ana Santos</div>
              <div class="leaderboard-stats">180 Units | ₱270.2M Value | 106% Target</div>
            </div>
            <div class="leaderboard-score">89.3 pts</div>
          </div>
          
          <div class="leaderboard-item rank-3">
            <div class="leaderboard-rank">3</div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">Juan Reyes</div>
              <div class="leaderboard-stats">135 Units | ₱218.4M Value | 80% Target</div>
            </div>
            <div class="leaderboard-score">78.6 pts</div>
          </div>
          
          <div class="leaderboard-item">
            <div class="leaderboard-rank">4</div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">Maria Gonzales</div>
              <div class="leaderboard-stats">112 Units | ₱179.5M Value | 53% Target</div>
            </div>
            <div class="leaderboard-score">65.2 pts</div>
          </div>
          
          <div class="leaderboard-item">
            <div class="leaderboard-rank">5</div>
            <div class="leaderboard-info">
              <div class="leaderboard-name">Roberto Cruz</div>
              <div class="leaderboard-stats">92 Units | ₱148.3M Value | 63% Target</div>
            </div>
            <div class="leaderboard-score">58.9 pts</div>
          </div>
        </div>
        
        <div class="chart-container">
          <h3>Agent Performance Comparison</h3>
          <div id="agentPerformanceChart" style="height: 350px; width: 100%;">
            <!-- Chart placeholder -->
            <div style="height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light);">
              Agent performance comparison chart would be displayed here
            </div>
          </div>
        </div>
        
        <h3>Detailed Agent Rankings</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Agent</th>
              <th>Q1 Units</th>
              <th>Q2 Units</th>
              <th>Q3 Units</th>
              <th>Q4 Units</th>
              <th>YTD Total</th>
              <th>Trend</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Carlos Mendoza</td>
              <td>65</td>
              <td>71</td>
              <td>68</td>
              <td>-</td>
              <td>204</td>
              <td><span class="status approved">↗ +8.5%</span></td>
            </tr>
            <tr>
              <td>2</td>
              <td>Ana Santos</td>
              <td>58</td>
              <td>62</td>
              <td>60</td>
              <td>-</td>
              <td>180</td>
              <td><span class="status approved">↗ +3.2%</span></td>
            </tr>
            <tr>
              <td>3</td>
              <td>Juan Reyes</td>
              <td>45</td>
              <td>48</td>
              <td>42</td>
              <td>-</td>
              <td>135</td>
              <td><span class="status overdue">↘ -2.1%</span></td>
            </tr>
            <tr>
              <td>4</td>
              <td>Maria Gonzales</td>
              <td>40</td>
              <td>38</td>
              <td>34</td>
              <td>-</td>
              <td>112</td>
              <td><span class="status overdue">↘ -5.0%</span></td>
            </tr>
            <tr>
              <td>5</td>
              <td>Roberto Cruz</td>
              <td>28</td>
              <td>30</td>
              <td>34</td>
              <td>-</td>
              <td>92</td>
              <td><span class="status approved">↗ +12.7%</span></td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Export Rankings</button>
          <button class="btn btn-secondary">Print Leaderboard</button>
        </div>
      </div>

      <!-- Targets & Goals Tab -->
      <div class="tab-content" id="solved-targets">
        <h3 class="section-heading">Set Monthly Targets</h3>
        <form id="targetsForm">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Target Month <span class="required">*</span></label>
              <input type="month" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Overall Target Units <span class="required">*</span></label>
              <input type="number" class="form-input" placeholder="Enter total target units" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Sales Agent <span class="required">*</span></label>
              <select class="form-select" required>
                <option value="">Select agent</option>
                <option value="carlos">Carlos Mendoza</option>
                <option value="ana">Ana Santos</option>
                <option value="juan">Juan Reyes</option>
                <option value="maria">Maria Gonzales</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Individual Target <span class="required">*</span></label>
              <input type="number" class="form-input" placeholder="Enter individual target" required>
            </div>
          </div>
          <div class="action-area">
            <button type="submit" class="btn btn-primary">Set Target</button>
            <button type="button" class="btn btn-secondary">Clear</button>
          </div>
        </form>

        <h3 class="section-heading">Current Targets & Progress</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Monthly Target</th>
              <th>Current Progress</th>
              <th>Achievement %</th>
              <th>Days Remaining</th>
              <th>Projected Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Carlos Mendoza</td>
              <td>20</td>
              <td>17</td>
              <td>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: 85%;"></div>
                </div>
                <span>85%</span>
              </td>
              <td>8</td>
              <td>22</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Adjust Target</button>
              </td>
            </tr>
            <tr>
              <td>Ana Santos</td>
              <td>18</td>
              <td>14</td>
              <td>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: 78%;"></div>
                </div>
                <span>78%</span>
              </td>
              <td>8</td>
              <td>18</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Adjust Target</button>
              </td>
            </tr>
            <tr>
              <td>Juan Reyes</td>
              <td>15</td>
              <td>9</td>
              <td>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: 60%;"></div>
                </div>
                <span>60%</span>
              </td>
              <td>8</td>
              <td>12</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Adjust Target</button>
              </td>
            </tr>
            <tr>
              <td>Maria Gonzales</td>
              <td>15</td>
              <td>6</td>
              <td>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: 40%;"></div>
                </div>
                <span>40%</span>
              </td>
              <td>8</td>
              <td>8</td>
              <td class="table-actions">
                <button class="btn btn-small btn-outline">Adjust Target</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div class="action-area">
          <button class="btn btn-primary">Save All Targets</button>
          <button class="btn btn-secondary">Export Target Sheet</button>
        </div>
      </div>

      <!-- Performance Analytics Tab -->
      <div class="tab-content" id="solved-analytics">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-card-title">Team Performance</div>
            <div class="info-card-value">89%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Best Quarter</div>
            <div class="info-card-value">Q2 2024</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Growth Rate</div>
            <div class="info-card-value">+15.2%</div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Avg. Sale Time</div>
            <div class="info-card-value">12 days</div>
          </div>
        </div>
        
        <div class="chart-container">
          <h3>Sales Performance Trend</h3>
          <div id="performanceChart" style="height: 350px; width: 100%;">
            <!-- Chart placeholder -->
            <div style="height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light);">
              Performance trend chart would be displayed here
            </div>
          </div>
        </div>
        
        <h3>Vehicle Model Performance</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Vehicle Model</th>
              <th>Units Sold</th>
              <th>Total Value</th>
              <th>Top Agent</th>
              <th>Average Sale Time</th>
              <th>Popular Colors</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Montero Sport</td>
              <td>34</td>
              <td>₱81.5M</td>
              <td>Carlos Mendoza</td>
              <td>12 days</td>
              <td>White Pearl, Black Mica</td>
            </tr>
            <tr>
              <td>Xpander</td>
              <td>48</td>
              <td>₱60.2M</td>
              <td>Ana Santos</td>
              <td>8 days</td>
              <td>Silver Metallic, White Pearl</td>
            </tr>
            <tr>
              <td>Mirage G4</td>
              <td>32</td>
              <td>₱30.2M</td>
              <td>Juan Reyes</td>
              <td>15 days</td>
              <td>Red Diamond, Silver Metallic</td>
            </tr>
            <tr>
              <td>Strada</td>
              <td>25</td>
              <td>₱38.5M</td>
              <td>Carlos Mendoza</td>
              <td>10 days</td>
              <td>Jet Black, White Diamond</td>
            </tr>
            <tr>
              <td>Outlander PHEV</td>
              <td>4</td>
              <td>₱11.6M</td>
              <td>Maria Gonzales</td>
              <td>20 days</td>
              <td>Ruby Black, White Diamond</td>
            </tr>
          </tbody>
        </table>
        
        <h3>Performance Analysis</h3>
        <div class="chart-container">
          <div id="analysisChart" style="height: 350px; width: 100%;">
            <!-- Chart placeholder -->
            <div style="height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light);">
              Performance analysis chart would be displayed here
            </div>
          </div>
        </div>
        
        <div class="action-area">
          <button class="btn btn-primary">Export Analysis</button>
          <button class="btn btn-secondary">Generate Performance Report</button>
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

      // Form submission for targets
      document.getElementById('targetsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Target set successfully!');
        // Refresh the current targets table
        location.reload();
      });
    });
  </script>
</body>
</html>
