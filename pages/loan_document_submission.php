<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
	header("Location: login.php");
	exit;
}

// Get vehicle ID and applicant type from URL
$vehicle_id = $_GET['vehicle_id'] ?? null;
$applicant_type = $_GET['applicant_type'] ?? 'EMPLOYED'; // Default to EMPLOYED

if (!$vehicle_id) {
	header("Location: car_menu.php");
	exit;
}

// Validate applicant type
if (!in_array($applicant_type, ['EMPLOYED', 'BUSINESS', 'OFW'])) {
	$applicant_type = 'EMPLOYED';
}

// Fetch vehicle details
$stmt = $connect->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
	header("Location: car_menu.php");
	exit;
}

// Fetch user details
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch customer information
$stmt_cust = $connect->prepare("SELECT * FROM customer_information WHERE account_id = ?");
$stmt_cust->execute([$_SESSION['user_id']]);
$customer_info = $stmt_cust->fetch(PDO::FETCH_ASSOC);

$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$connect->beginTransaction();

		// Define required and optional files based on applicant type
		$required_files = ['valid_id']; // Always required
		$optional_files = [];

		switch ($applicant_type) {
			case 'EMPLOYED':
				$required_files = array_merge($required_files, ['coec_payslip', 'itr_2316', 'proof_billing']);
				$optional_files = ['ada_pdc', 'employment_certificate', 'payslip', 'company_id'];
				break;
			case 'BUSINESS':
				$required_files = array_merge($required_files, ['bank_statement', 'itr_1701', 'dti_permit', 'proof_billing']);
				$optional_files = ['ada_pdc'];
				break;
			case 'OFW':
				$required_files = array_merge($required_files, ['proof_remittance', 'latest_contract', 'spa', 'proof_billing']);
				$optional_files = ['ada_pdc'];
				break;
		}

		foreach ($required_files as $file_key) {
			if (empty($_FILES[$file_key]['tmp_name'])) {
				throw new Exception("Please upload " . str_replace('_', ' ', $file_key) . " document.");
			}
		}

		// Prepare file data for all possible document types
		$files_data = [];
		$all_possible_files = [
			'valid_id', 'coec_payslip', 'itr_2316', 'itr_1701', 'employment_certificate', 
			'payslip', 'company_id', 'bank_statement', 'dti_permit', 'proof_billing',
			'proof_remittance', 'latest_contract', 'spa', 'ada_pdc'
		];

		foreach ($all_possible_files as $file_key) {
			if (!empty($_FILES[$file_key]['tmp_name'])) {
				$files_data[$file_key] = [
					'data' => file_get_contents($_FILES[$file_key]['tmp_name']),
					'name' => $_FILES[$file_key]['name'],
					'type' => $_FILES[$file_key]['type']
				];
			}
		}

		// Map form field names to database field names
		$field_mapping = [
			'coec_payslip' => 'income_source',
			'itr_2316' => 'itr',
			'itr_1701' => 'itr',
			'proof_remittance' => 'remittance_proof',
			'latest_contract' => 'contract'
		];

		// Remap files_data keys to match database fields
		$mapped_files = [];
		foreach ($files_data as $form_key => $file_data) {
			$db_key = $field_mapping[$form_key] ?? $form_key;
			$mapped_files[$db_key] = $file_data;
		}

		// Get payment plan and vehicle price data from temporary table
		$tempDataSql = "SELECT down_payment, financing_term, monthly_payment, total_amount, interest_rate,
		                       vehicle_base_price, vehicle_promotional_price, vehicle_effective_price
		                FROM loan_applications_temp 
		                WHERE customer_id = ? AND vehicle_id = ?";
		$tempStmt = $connect->prepare($tempDataSql);
		$tempStmt->execute([$_SESSION['user_id'], $vehicle_id]);
		$paymentPlanData = $tempStmt->fetch(PDO::FETCH_ASSOC);

		// Insert loan application with all document types, payment plan data, and vehicle prices
		$sql = "INSERT INTO loan_applications (
            customer_id, vehicle_id, vehicle_base_price, vehicle_promotional_price, vehicle_effective_price,
            applicant_type, application_date, status,
            down_payment, financing_term, monthly_payment, total_amount, interest_rate,
            valid_id_file, valid_id_filename, valid_id_type,
            income_source_file, income_source_filename, income_source_type,
            employment_certificate_file, employment_certificate_filename, employment_certificate_type,
            payslip_file, payslip_filename, payslip_type,
            company_id_file, company_id_filename, company_id_type,
            itr_file, itr_filename, itr_type,
            bank_statement_file, bank_statement_filename, bank_statement_type,
            dti_permit_file, dti_permit_filename, dti_permit_type,
            proof_billing_file, proof_billing_filename, proof_billing_type,
            remittance_proof_file, remittance_proof_filename, remittance_proof_type,
            contract_file, contract_filename, contract_type,
            spa_file, spa_filename, spa_type,
            ada_pdc_file, ada_pdc_filename, ada_pdc_type,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

		$stmt = $connect->prepare($sql);
		$stmt->execute([
			$_SESSION['user_id'],
			$vehicle_id,
			$paymentPlanData['vehicle_base_price'] ?? 0,
			$paymentPlanData['vehicle_promotional_price'] ?? null,
			$paymentPlanData['vehicle_effective_price'] ?? 0,
			$applicant_type,
			$paymentPlanData['down_payment'] ?? 0,
			$paymentPlanData['financing_term'] ?? 12,
			$paymentPlanData['monthly_payment'] ?? 0,
			$paymentPlanData['total_amount'] ?? 0,
			$paymentPlanData['interest_rate'] ?? 0,
			$mapped_files['valid_id']['data'] ?? null,
			$mapped_files['valid_id']['name'] ?? null,
			$mapped_files['valid_id']['type'] ?? null,
			$mapped_files['income_source']['data'] ?? null,
			$mapped_files['income_source']['name'] ?? null,
			$mapped_files['income_source']['type'] ?? null,
			$mapped_files['employment_certificate']['data'] ?? null,
			$mapped_files['employment_certificate']['name'] ?? null,
			$mapped_files['employment_certificate']['type'] ?? null,
			$mapped_files['payslip']['data'] ?? null,
			$mapped_files['payslip']['name'] ?? null,
			$mapped_files['payslip']['type'] ?? null,
			$mapped_files['company_id']['data'] ?? null,
			$mapped_files['company_id']['name'] ?? null,
			$mapped_files['company_id']['type'] ?? null,
			$mapped_files['itr']['data'] ?? null,
			$mapped_files['itr']['name'] ?? null,
			$mapped_files['itr']['type'] ?? null,
			$mapped_files['bank_statement']['data'] ?? null,
			$mapped_files['bank_statement']['name'] ?? null,
			$mapped_files['bank_statement']['type'] ?? null,
			$mapped_files['dti_permit']['data'] ?? null,
			$mapped_files['dti_permit']['name'] ?? null,
			$mapped_files['dti_permit']['type'] ?? null,
			$mapped_files['proof_billing']['data'] ?? null,
			$mapped_files['proof_billing']['name'] ?? null,
			$mapped_files['proof_billing']['type'] ?? null,
			$mapped_files['remittance_proof']['data'] ?? null,
			$mapped_files['remittance_proof']['name'] ?? null,
			$mapped_files['remittance_proof']['type'] ?? null,
			$mapped_files['contract']['data'] ?? null,
			$mapped_files['contract']['name'] ?? null,
			$mapped_files['contract']['type'] ?? null,
			$mapped_files['spa']['data'] ?? null,
			$mapped_files['spa']['name'] ?? null,
			$mapped_files['spa']['type'] ?? null,
			$mapped_files['ada_pdc']['data'] ?? null,
			$mapped_files['ada_pdc']['name'] ?? null,
			$mapped_files['ada_pdc']['type'] ?? null
		]);

		$connect->commit();
		
		// Clean up temporary table entry after successful insertion
		try {
			$cleanupSql = "DELETE FROM loan_applications_temp WHERE customer_id = ? AND vehicle_id = ?";
			$cleanupStmt = $connect->prepare($cleanupSql);
			$cleanupStmt->execute([$_SESSION['user_id'], $vehicle_id]);
		} catch (Exception $cleanupError) {
			// Log cleanup error but don't fail the main operation
			error_log("Failed to cleanup temp table: " . $cleanupError->getMessage());
		}
		
		$success_message = "Your loan application has been submitted successfully! Our sales team will review your application and contact you soon.";
	} catch (Exception $e) {
		


		$error_message = "Submission failed: " . $e->getMessage();
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document Submission - Mitsubishi Motors</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
			font-family: 'Inter', 'Segoe UI', sans-serif;
		}

		body {
			background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%);
			min-height: 100vh;
			color: white;
		}

		.header {
			background: rgba(0, 0, 0, 0.4);
			padding: 20px 30px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			backdrop-filter: blur(20px);
			border-bottom: 1px solid rgba(255, 215, 0, 0.2);
		}

		.logo-section {
			display: flex;
			align-items: center;
			gap: 20px;
		}

		.logo {
			width: 60px;
			height: auto;
			filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3));
		}

		.brand-text {
			font-size: 1.4rem;
			font-weight: 700;
			background: linear-gradient(45deg, #ffd700, #ffed4e);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.container {
			max-width: 900px;
			margin: 0 auto;
			padding: 40px 30px;
		}

		.page-header {
			text-align: center;
			margin-bottom: 40px;
		}

		.page-title {
			font-size: 2.5rem;
			margin-bottom: 20px;
			background: linear-gradient(45deg, #ffd700, #ffed4e, #fff);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			font-weight: 800;
		}

		.vehicle-info {
			background: rgba(255, 255, 255, 0.1);
			padding: 15px;
			border-radius: 10px;
			text-align: center;
			margin-bottom: 30px;
			backdrop-filter: blur(20px);
		}

		.form-container {
			background: rgba(255, 255, 255, 0.1);
			border-radius: 20px;
			padding: 40px;
			backdrop-filter: blur(20px);
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
			border: 1px solid rgba(255, 215, 0, 0.1);
		}

		.message {
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 10px;
			text-align: center;
		}

		.error {
			background: rgba(220, 53, 69, 0.2);
			border: 1px solid #dc3545;
			color: #ff6b6b;
		}

		.success {
			background: rgba(40, 167, 69, 0.2);
			border: 1px solid #28a745;
			color: #4caf50;
		}

		.form-section {
			margin-bottom: 30px;
		}

		.form-section h3 {
			color: #ffd700;
			margin-bottom: 20px;
			font-size: 1.3rem;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.file-upload-group {
			margin-bottom: 25px;
			padding: 20px;
			background: rgba(255, 255, 255, 0.05);
			border-radius: 10px;
			border: 1px solid rgba(255, 215, 0, 0.1);
		}

		.file-upload-label {
			display: block;
			margin-bottom: 10px;
			color: #ffd700;
			font-weight: 600;
			font-size: 1rem;
		}

		.file-upload-label .required {
			color: #ff6b6b;
		}

		.file-upload-description {
			color: #ccc;
			font-size: 0.9rem;
			margin-bottom: 15px;
			line-height: 1.5;
		}

		.file-input {
			width: 100%;
			padding: 12px;
			border: 2px dashed rgba(255, 215, 0, 0.3);
			border-radius: 8px;
			background: rgba(255, 255, 255, 0.05);
			color: white;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.file-input:hover {
			border-color: rgba(255, 215, 0, 0.6);
			background: rgba(255, 255, 255, 0.1);
		}

		.file-input:focus {
			outline: none;
			border-color: #ffd700;
			box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
		}

		.textarea-field {
			width: 100%;
			padding: 15px;
			border: 1px solid rgba(255, 215, 0, 0.3);
			border-radius: 8px;
			background: rgba(255, 255, 255, 0.05);
			color: white;
			resize: vertical;
			min-height: 100px;
			font-family: inherit;
		}

		.textarea-field:focus {
			outline: none;
			border-color: #ffd700;
			box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
		}

		.action-buttons {
			display: flex;
			gap: 20px;
			justify-content: center;
			margin-top: 40px;
		}

		.btn {
			padding: 15px 30px;
			border: none;
			border-radius: 15px;
			cursor: pointer;
			font-weight: 700;
			font-size: 1rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			transition: all 0.3s ease;
			text-decoration: none;
			display: inline-block;
		}

		.btn-submit {
			background: linear-gradient(45deg, #27ae60, #2ecc71);
			color: white;
			box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
		}

		.btn-submit:hover {
			transform: translateY(-3px);
			box-shadow: 0 8px 25px rgba(39, 174, 96, 0.5);
		}

		.btn-back {
			background: rgba(255, 255, 255, 0.1);
			color: #ffd700;
			border: 2px solid #ffd700;
		}

		.btn-back:hover {
			background: #ffd700;
			color: #1a1a1a;
		}

		@media (max-width: 768px) {
			.container {
				padding: 20px 15px;
			}

			.page-title {
				font-size: 2rem;
			}

			.form-container {
				padding: 20px;
			}

			.action-buttons {
				flex-direction: column;
				align-items: center;
			}

			.btn {
				width: 100%;
				max-width: 300px;
			}
		}
	</style>
</head>

<body>
	<header class="header">
		<div class="logo-section">
			<img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
			<div class="brand-text">MITSUBISHI MOTORS</div>
		</div>
		<div class="user-section">
			<span style="color: #ffd700;">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
		</div>
	</header>

	<div class="container">
		<div class="page-header">
			<h1 class="page-title">Document Submission</h1>
		</div>

		<div class="vehicle-info">
			<h3 style="color: #ffd700; margin-bottom: 10px;">Loan Application for: <?php echo htmlspecialchars($vehicle['model_name']); ?></h3>
			<?php if (!empty($vehicle['variant'])): ?>
				<p style="color: #ccc;"><?php echo htmlspecialchars($vehicle['variant']); ?></p>
			<?php endif; ?>
			<p style="color: #ffd700; margin-top: 10px;"><i class="fas fa-user-tag"></i> Applicant Type: <strong><?php echo htmlspecialchars($applicant_type); ?></strong></p>
		</div>

		<div class="form-container">
			<?php if (!empty($error_message)): ?>
				<div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
			<?php endif; ?>

			<?php if (!empty($success_message)): ?>
				<div class="message success">
					<?php echo htmlspecialchars($success_message); ?>
					<div style="margin-top: 20px;">
						<a href="customer.php" class="btn btn-submit">Return to Dashboard</a>
					</div>
				</div>
			<?php else: ?>
				<form method="POST" enctype="multipart/form-data">
					<input type="hidden" name="applicant_type" value="<?php echo htmlspecialchars($applicant_type); ?>">
					
					<div class="form-section">
						<h3><i class="fas fa-upload"></i> Required Documents for <?php echo htmlspecialchars($applicant_type); ?> Applicants</h3>

						<!-- Always Required: Valid ID -->
						<div class="file-upload-group">
							<label class="file-upload-label">
								2 Valid IDs (Gov't Issued) <span class="required">*</span>
							</label>
							<p class="file-upload-description">
								Upload two (2) government-issued IDs with photos and signatures
							</p>
							<input type="file" name="valid_id" class="file-input" accept="image/*,.pdf" required>
						</div>

						<?php if ($applicant_type === 'EMPLOYED'): ?>
							<!-- EMPLOYED Requirements -->
							<div class="file-upload-group">
								<label class="file-upload-label">
									COEC or 3 Months Latest Payslip <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Certificate of Employment and Compensation OR Latest 3 months payslips
								</p>
								<input type="file" name="coec_payslip" class="file-input" accept="image/*,.pdf" required>
							</div>

							<div class="file-upload-group">
								<label class="file-upload-label">
									ITR (2316) <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Income Tax Return form 2316 for employed individuals
								</p>
								<input type="file" name="itr_2316" class="file-input" accept="image/*,.pdf" required>
							</div>

						<?php elseif ($applicant_type === 'BUSINESS'): ?>
							<!-- BUSINESS Requirements -->
							<div class="file-upload-group">
								<label class="file-upload-label">
									Bank Statement (Latest 3 Months) <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Latest 3 months bank statements for business account
								</p>
								<input type="file" name="bank_statement" class="file-input" accept="image/*,.pdf" required>
							</div>

							<div class="file-upload-group">
								<label class="file-upload-label">
									ITR (1701) <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Income Tax Return form 1701 for business owners
								</p>
								<input type="file" name="itr_1701" class="file-input" accept="image/*,.pdf" required>
							</div>

							<div class="file-upload-group">
								<label class="file-upload-label">
									DTI Permit <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Department of Trade and Industry business permit
								</p>
								<input type="file" name="dti_permit" class="file-input" accept="image/*,.pdf" required>
							</div>

						<?php elseif ($applicant_type === 'OFW'): ?>
							<!-- OFW Requirements -->
							<div class="file-upload-group">
								<label class="file-upload-label">
									Proof of Remittance (Latest 3 Months) <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Latest 3 months proof of money remittance to Philippines
								</p>
								<input type="file" name="proof_remittance" class="file-input" accept="image/*,.pdf" required>
							</div>

							<div class="file-upload-group">
								<label class="file-upload-label">
									Latest Contract <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Current overseas employment contract
								</p>
								<input type="file" name="latest_contract" class="file-input" accept="image/*,.pdf" required>
							</div>

							<div class="file-upload-group">
								<label class="file-upload-label">
									SPA <span class="required">*</span>
								</label>
								<p class="file-upload-description">
									Special Power of Attorney for authorized representative
								</p>
								<input type="file" name="spa" class="file-input" accept="image/*,.pdf" required>
							</div>
						<?php endif; ?>

						<!-- Common Required: Proof of Billing -->
						<div class="file-upload-group">
							<label class="file-upload-label">
								Proof of Billing (Original) <span class="required">*</span>
							</label>
							<p class="file-upload-description">
								Original utility bills or other proof of billing documents
							</p>
							<input type="file" name="proof_billing" class="file-input" accept="image/*,.pdf" required>
						</div>
					</div>

					<div class="form-section">
						<h3><i class="fas fa-file-alt"></i> Optional Documents</h3>

						<div class="file-upload-group">
							<label class="file-upload-label">
								ADA/PDC
							</label>
							<p class="file-upload-description">
								Authorized Dealer Agreement / Post Dated Checks
							</p>
							<input type="file" name="ada_pdc" class="file-input" accept="image/*,.pdf">
						</div>
					</div>



					<div class="action-buttons">
						<a href="loan_excel_form.php?vehicle_id=<?php echo $vehicle_id; ?>&applicant_type=<?php echo $applicant_type; ?>" class="btn btn-back">
							<i class="fas fa-arrow-left"></i> Back to Form
						</a>
						<button type="submit" class="btn btn-submit">
							<i class="fas fa-paper-plane"></i> Submit Application
						</button>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</body>

</html>