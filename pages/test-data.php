<?php
include_once(dirname(__DIR__) . '/includes/init.php');

// Simple authentication check - you can modify this as needed
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'SalesAgent' && $_SESSION['user_role'] !== 'Admin')) {
	header("Location: login.php");
	exit();
}

// Use the database connection from init.php
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
	die("Database connection not available.");
}

// Get table data
$accounts = [];
$customers = [];
$vehicles = [];
$loanApplications = [];

try {
	// Get accounts data
	$stmt = $pdo->prepare("SELECT * FROM accounts ORDER BY Id");
	$stmt->execute();
	$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Get customer information data
	$stmt = $pdo->prepare("SELECT * FROM customer_information ORDER BY cusID");
	$stmt->execute();
	$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Get vehicles data
	$stmt = $pdo->prepare("SELECT * FROM vehicles ORDER BY id LIMIT 10");
	$stmt->execute();
	$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Get loan applications data
	$stmt = $pdo->prepare("SELECT * FROM loan_applications ORDER BY id");
	$stmt->execute();
	$loanApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Database Test Page</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: #f5f5f5;
			padding: 20px;
			line-height: 1.4;
		}

		.container {
			max-width: 1400px;
			margin: 0 auto;
		}

		.header {
			background: white;
			padding: 20px;
			border-radius: 8px;
			margin-bottom: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.section {
			background: white;
			margin-bottom: 30px;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.section-header {
			background: #b80000;
			color: white;
			padding: 15px 20px;
			font-weight: bold;
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.section-content {
			padding: 15px;
			max-height: 600px;
			overflow: auto;
		}

		.table-responsive {
			overflow-x: auto;
			margin-bottom: 20px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			font-size: 11px;
			min-width: 800px;
		}

		th,
		td {
			border: 1px solid #ddd;
			padding: 6px 8px;
			text-align: left;
			vertical-align: top;
			word-wrap: break-word;
			max-width: 150px;
		}

		th {
			background: #f8f9fa;
			font-weight: bold;
			position: sticky;
			top: 0;
			font-size: 10px;
			text-transform: uppercase;
		}

		td {
			font-size: 11px;
		}

		tr:nth-child(even) {
			background: #f9f9f9;
		}

		.json-data {
			background: #f8f9fa;
			padding: 15px;
			border-radius: 4px;
			font-family: 'Courier New', monospace;
			font-size: 10px;
			white-space: pre-wrap;
			max-height: 300px;
			overflow: auto;
			border: 1px solid #dee2e6;
			margin-top: 15px;
		}

		.stats {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
			margin-bottom: 20px;
		}

		.stat-card {
			background: white;
			padding: 20px;
			border-radius: 8px;
			text-align: center;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.stat-number {
			font-size: 2em;
			font-weight: bold;
			color: #b80000;
		}

		.collapse {
			max-height: 0;
			overflow: hidden;
			transition: max-height 0.3s ease;
		}

		.collapse.show {
			max-height: 700px;
		}

		.btn {
			background: #b80000;
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 4px;
			cursor: pointer;
			margin: 5px;
		}

		.btn:hover {
			background: #990000;
		}

		.error {
			background: #f8d7da;
			color: #721c24;
			padding: 15px;
			border-radius: 4px;
			margin-bottom: 20px;
		}

		.data-value {
			max-width: 120px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.data-value.expanded {
			white-space: normal;
			max-width: none;
		}

		.data-value:hover {
			background: #e9ecef;
			cursor: pointer;
		}

		.null-value {
			color: #6c757d;
			font-style: italic;
		}

		.empty-value {
			color: #dc3545;
			font-style: italic;
		}

		.id-column {
			background: #e3f2fd !important;
			font-weight: bold;
		}

		.table-info {
			background: #d1ecf1;
			border: 1px solid #bee5eb;
			padding: 10px;
			border-radius: 4px;
			margin-bottom: 15px;
			font-size: 12px;
		}

		.toggle-json {
			background: #17a2b8;
			color: white;
			border: none;
			padding: 5px 10px;
			border-radius: 3px;
			cursor: pointer;
			font-size: 10px;
			margin-bottom: 10px;
		}

		.toggle-json:hover {
			background: #138496;
		}
	</style>
</head>

<body>
	<div class="container">
		<div class="header">
			<h1><i class="fas fa-database"></i> Database Testing Page</h1>
			<p>Current User: <?php echo $_SESSION['user_role']; ?> | Session ID: <?php echo $_SESSION['user_id']; ?></p>
			<button class="btn" onclick="location.reload()">
				<i class="fas fa-refresh"></i> Refresh Data
			</button>
		</div>

		<?php if (isset($error)): ?>
			<div class="error">
				<i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
			</div>
		<?php endif; ?>

		<div class="stats">
			<div class="stat-card">
				<div class="stat-number"><?php echo count($accounts); ?></div>
				<div>Total Accounts</div>
			</div>
			<div class="stat-card">
				<div class="stat-number"><?php echo count($customers); ?></div>
				<div>Customer Records</div>
			</div>
			<div class="stat-card">
				<div class="stat-number"><?php echo count($vehicles); ?></div>
				<div>Vehicle Records</div>
			</div>
			<div class="stat-card">
				<div class="stat-number"><?php echo count($loanApplications); ?></div>
				<div>Loan Applications</div>
			</div>
		</div>

		<!-- Accounts Table -->
		<div class="section">
			<div class="section-header" onclick="toggleSection('accounts')">
				<span><i class="fas fa-users"></i> Accounts Table</span>
				<i class="fas fa-chevron-down" id="accounts-icon"></i>
			</div>
			<div class="section-content collapse" id="accounts-content">
				<div class="table-info">
					<strong>Accounts Table:</strong> Contains user account information with roles (Admin, SalesAgent, Customer)
				</div>
				<button class="toggle-json" onclick="toggleJson('accounts')">Toggle JSON View</button>
				<div class="table-responsive">
					<table>
						<thead>
							<tr>
								<?php if (!empty($accounts)): ?>
									<?php foreach (array_keys($accounts[0]) as $column): ?>
										<th><?php echo htmlspecialchars($column); ?></th>
									<?php endforeach; ?>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($accounts as $account): ?>
								<tr>
									<?php foreach ($account as $key => $value): ?>
										<td class="<?php echo $key === 'Id' ? 'id-column' : ''; ?>">
											<div class="data-value" onclick="toggleExpand(this)">
												<?php
												if (is_null($value)) {
													echo '<span class="null-value">NULL</span>';
												} elseif ($value === '') {
													echo '<span class="empty-value">EMPTY</span>';
												} elseif ($key === 'PasswordHash') {
													echo '<em>***HIDDEN***</em>';
												} elseif ($key === 'ProfileImage' && !empty($value)) {
													echo '<em>BLOB DATA (' . strlen($value) . ' bytes)</em>';
												} else {
													echo htmlspecialchars(substr($value, 0, 50));
													if (strlen($value) > 50) echo '<span style="color: #007bff;">...</span>';
												}
												?>
											</div>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="json-data" id="accounts-json" style="display: none;">
					<?php echo json_encode($accounts, JSON_PRETTY_PRINT); ?>
				</div>
			</div>
		</div>

		<!-- Customer Information Table -->
		<div class="section">
			<div class="section-header" onclick="toggleSection('customers')">
				<span><i class="fas fa-address-card"></i> Customer Information Table</span>
				<i class="fas fa-chevron-down" id="customers-icon"></i>
			</div>
			<div class="section-content collapse" id="customers-content">
				<div class="table-info">
					<strong>Customer Information Table:</strong> Contains detailed customer profile information linked to accounts
				</div>
				<button class="toggle-json" onclick="toggleJson('customers')">Toggle JSON View</button>
				<div class="table-responsive">
					<table>
						<thead>
							<tr>
								<?php if (!empty($customers)): ?>
									<?php foreach (array_keys($customers[0]) as $column): ?>
										<th><?php echo htmlspecialchars($column); ?></th>
									<?php endforeach; ?>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($customers as $customer): ?>
								<tr>
									<?php foreach ($customer as $key => $value): ?>
										<td class="<?php echo $key === 'cusID' ? 'id-column' : ''; ?>">
											<div class="data-value" onclick="toggleExpand(this)">
												<?php
												if (is_null($value)) {
													echo '<span class="null-value">NULL</span>';
												} elseif ($value === '') {
													echo '<span class="empty-value">EMPTY</span>';
												} elseif ($key === 'valid_id_image' && !empty($value)) {
													echo '<em>BLOB DATA (' . strlen($value) . ' bytes)</em>';
												} else {
													echo htmlspecialchars(substr($value, 0, 50));
													if (strlen($value) > 50) echo '<span style="color: #007bff;">...</span>';
												}
												?>
											</div>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="json-data" id="customers-json" style="display: none;">
					<?php echo json_encode($customers, JSON_PRETTY_PRINT); ?>
				</div>
			</div>
		</div>

		<!-- Vehicles Table -->
		<div class="section">
			<div class="section-header" onclick="toggleSection('vehicles')">
				<span><i class="fas fa-car"></i> Vehicles Table (First 10 records)</span>
				<i class="fas fa-chevron-down" id="vehicles-icon"></i>
			</div>
			<div class="section-content collapse" id="vehicles-content">
				<div class="table-info">
					<strong>Vehicles Table:</strong> Contains vehicle inventory with pricing and specifications
				</div>
				<button class="toggle-json" onclick="toggleJson('vehicles')">Toggle JSON View</button>
				<div class="table-responsive">
					<table>
						<thead>
							<tr>
								<?php if (!empty($vehicles)): ?>
									<?php foreach (array_keys($vehicles[0]) as $column): ?>
										<th><?php echo htmlspecialchars($column); ?></th>
									<?php endforeach; ?>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($vehicles as $vehicle): ?>
								<tr>
									<?php foreach ($vehicle as $key => $value): ?>
										<td class="<?php echo $key === 'id' ? 'id-column' : ''; ?>">
											<div class="data-value" onclick="toggleExpand(this)">
												<?php
												if (is_null($value)) {
													echo '<span class="null-value">NULL</span>';
												} elseif ($value === '') {
													echo '<span class="empty-value">EMPTY</span>';
												} elseif (in_array($key, ['main_image', 'additional_images', 'view_360_images']) && !empty($value)) {
													echo '<em>BLOB DATA (' . strlen($value) . ' bytes)</em>';
												} else {
													echo htmlspecialchars(substr($value, 0, 40));
													if (strlen($value) > 40) echo '<span style="color: #007bff;">...</span>';
												}
												?>
											</div>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="json-data" id="vehicles-json" style="display: none;">
					<?php echo json_encode($vehicles, JSON_PRETTY_PRINT); ?>
				</div>
			</div>
		</div>

		<!-- Loan Applications Table -->
		<div class="section">
			<div class="section-header" onclick="toggleSection('loans')">
				<span><i class="fas fa-file-contract"></i> Loan Applications Table</span>
				<i class="fas fa-chevron-down" id="loans-icon"></i>
			</div>
			<div class="section-content collapse" id="loans-content">
				<div class="table-info">
					<strong>Loan Applications Table:</strong> Contains loan application data with document attachments
				</div>
				<button class="toggle-json" onclick="toggleJson('loans')">Toggle JSON View</button>
				<div class="table-responsive">
					<table>
						<thead>
							<tr>
								<?php if (!empty($loanApplications)): ?>
									<?php foreach (array_keys($loanApplications[0]) as $column): ?>
										<th><?php echo htmlspecialchars($column); ?></th>
									<?php endforeach; ?>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($loanApplications as $loan): ?>
								<tr>
									<?php foreach ($loan as $key => $value): ?>
										<td class="<?php echo $key === 'id' ? 'id-column' : ''; ?>">
											<div class="data-value" onclick="toggleExpand(this)">
												<?php
												if (is_null($value)) {
													echo '<span class="null-value">NULL</span>';
												} elseif ($value === '') {
													echo '<span class="empty-value">EMPTY</span>';
												} elseif (strpos($key, '_file') !== false && !empty($value)) {
													echo '<em>BLOB DATA (' . strlen($value) . ' bytes)</em>';
												} else {
													echo htmlspecialchars(substr($value, 0, 50));
													if (strlen($value) > 50) echo '<span style="color: #007bff;">...</span>';
												}
												?>
											</div>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="json-data" id="loans-json" style="display: none;">
					<?php echo json_encode($loanApplications, JSON_PRETTY_PRINT); ?>
				</div>
			</div>
		</div>

		<!-- Test API Calls -->
		<div class="section">
			<div class="section-header" onclick="toggleSection('api')">
				<span><i class="fas fa-code"></i> API Testing</span>
				<i class="fas fa-chevron-down" id="api-icon"></i>
			</div>
			<div class="section-content collapse" id="api-content">
				<div class="table-info">
					<strong>API Testing:</strong> Test the loan applications API endpoints
				</div>
				<button class="btn" onclick="testAPI('statistics')">Test Statistics API</button>
				<button class="btn" onclick="testAPI('applications')">Test Applications API</button>
				<button class="btn" onclick="testAPI('application&id=1')">Test Application Details API</button>

				<div id="api-results" class="json-data" style="margin-top: 20px; min-height: 100px;">
					API results will appear here...
				</div>
			</div>
		</div>
	</div>

	<script>
		function toggleSection(sectionName) {
			const content = document.getElementById(sectionName + '-content');
			const icon = document.getElementById(sectionName + '-icon');

			content.classList.toggle('show');

			if (content.classList.contains('show')) {
				icon.className = 'fas fa-chevron-up';
			} else {
				icon.className = 'fas fa-chevron-down';
			}
		}

		function toggleJson(sectionName) {
			const jsonDiv = document.getElementById(sectionName + '-json');
			const tableDiv = jsonDiv.previousElementSibling;

			if (jsonDiv.style.display === 'none') {
				jsonDiv.style.display = 'block';
				tableDiv.style.display = 'none';
			} else {
				jsonDiv.style.display = 'none';
				tableDiv.style.display = 'block';
			}
		}

		function toggleExpand(element) {
			element.classList.toggle('expanded');
		}

		function testAPI(action) {
			const resultsDiv = document.getElementById('api-results');
			resultsDiv.innerHTML = 'Loading...';

			fetch(`../api/loan-applications.php?action=${action}`)
				.then(response => response.json())
				.then(data => {
					resultsDiv.innerHTML = JSON.stringify(data, null, 2);
				})
				.catch(error => {
					resultsDiv.innerHTML = 'Error: ' + error.message;
				});
		}

		// Auto-expand first section
		document.addEventListener('DOMContentLoaded', function() {
			toggleSection('accounts');
		});
	</script>
</body>

</html>