<?php
session_start();
include_once(dirname(__DIR__) . '/database/db_conn.php');

// Check if user is a sales agent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'SalesAgent') {
	http_response_code(403);
	echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
	exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
	case 'get_loan_applications':
		getLoanApplications();
		break;
	case 'get_application_details':
		getApplicationDetails();
		break;
	case 'download_document':
		downloadDocument();
		break;
	case 'approve_application':
		approveApplication();
		break;
	case 'reject_application':
		rejectApplication();
		break;
	case 'get_statistics':
		getStatistics();
		break;
	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'error' => 'Invalid action']);
		exit;
}

function getLoanApplications()
{
	global $connect;

	try {
		$sql = "SELECT 
                    la.id,
                    la.application_date,
                    la.status,
                    la.notes,
                    la.created_at,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    ci.mobile_number,
                    v.model_name,
                    v.variant,
                    v.base_price
                FROM loan_applications la
                JOIN accounts a ON la.customer_id = a.Id
                LEFT JOIN customer_information ci ON la.customer_id = ci.account_id
                JOIN vehicles v ON la.vehicle_id = v.id
                ORDER BY la.created_at DESC";

		$stmt = $connect->prepare($sql);
		$stmt->execute();
		$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo json_encode([
			'success' => true,
			'data' => $applications
		]);
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => 'Failed to fetch loan applications: ' . $e->getMessage()
		]);
	}
}

function getApplicationDetails()
{
	global $connect;

	$id = $_GET['id'] ?? null;
	if (!$id) {
		echo json_encode(['success' => false, 'error' => 'Application ID required']);
		return;
	}

	try {
		$sql = "SELECT 
                    la.*,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    a.Username,
                    ci.*,
                    v.model_name,
                    v.variant,
                    v.base_price,
                    v.key_features
                FROM loan_applications la
                JOIN accounts a ON la.customer_id = a.Id
                LEFT JOIN customer_information ci ON la.customer_id = ci.account_id
                JOIN vehicles v ON la.vehicle_id = v.id
                WHERE la.id = ?";

		$stmt = $connect->prepare($sql);
		$stmt->execute([$id]);
		$application = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$application) {
			echo json_encode(['success' => false, 'error' => 'Application not found']);
			return;
		}

		echo json_encode([
			'success' => true,
			'data' => $application
		]);
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => 'Failed to fetch application details: ' . $e->getMessage()
		]);
	}
}

function downloadDocument()
{
	global $connect;

	$id = $_GET['id'] ?? null;
	$document_type = $_GET['document_type'] ?? null;

	if (!$id || !$document_type) {
		http_response_code(400);
		echo "Invalid parameters";
		return;
	}

	try {
		$valid_documents = [
			'valid_id' => ['file' => 'valid_id_file', 'name' => 'valid_id_filename', 'type' => 'valid_id_type'],
			'income_source' => ['file' => 'income_source_file', 'name' => 'income_source_filename', 'type' => 'income_source_type'],
			'employment_certificate' => ['file' => 'employment_certificate_file', 'name' => 'employment_certificate_filename', 'type' => 'employment_certificate_type'],
			'payslip' => ['file' => 'payslip_file', 'name' => 'payslip_filename', 'type' => 'payslip_type'],
			'company_id' => ['file' => 'company_id_file', 'name' => 'company_id_filename', 'type' => 'company_id_type']
		];

		if (!isset($valid_documents[$document_type])) {
			http_response_code(400);
			echo "Invalid document type";
			return;
		}

		$doc_config = $valid_documents[$document_type];

		$sql = "SELECT {$doc_config['file']}, {$doc_config['name']}, {$doc_config['type']} 
                FROM loan_applications WHERE id = ?";
		$stmt = $connect->prepare($sql);
		$stmt->execute([$id]);
		$document = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$document || !$document[$doc_config['file']]) {
			http_response_code(404);
			echo "Document not found";
			return;
		}

		$file_data = $document[$doc_config['file']];
		$file_name = $document[$doc_config['name']] ?: "document_LA{$id}_{$document_type}";
		$file_type = $document[$doc_config['type']] ?: 'application/octet-stream';

		header('Content-Type: ' . $file_type);
		header('Content-Disposition: attachment; filename="' . $file_name . '"');
		header('Content-Length: ' . strlen($file_data));

		echo $file_data;
	} catch (Exception $e) {
		http_response_code(500);
		echo "Error downloading document: " . $e->getMessage();
	}
}

function approveApplication()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$approval_notes = $_POST['approval_notes'] ?? '';

	if (!$id) {
		echo json_encode(['success' => false, 'error' => 'Application ID required']);
		return;
	}

	try {
		$connect->beginTransaction();

		// Get loan application details
		$loanStmt = $connect->prepare("SELECT la.*, ci.cusID, ci.account_id, v.model_name, v.variant, v.base_price, v.promotional_price, v.stock_quantity, v.popular_color 
									 FROM loan_applications la 
									 JOIN customer_information ci ON la.customer_id = ci.account_id 
									 JOIN vehicles v ON la.vehicle_id = v.id 
									 WHERE la.id = ?");
		$loanStmt->execute([$id]);
		$loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

		if (!$loanData) {
			throw new Exception('Loan application not found');
		}

		// Check if vehicle has sufficient stock
		if ($loanData['stock_quantity'] <= 0) {
			throw new Exception('Vehicle is out of stock');
		}

		// Update loan application status
		$sql = "UPDATE loan_applications 
                SET status = 'Approved', 
                    reviewed_by = ?, 
                    reviewed_at = NOW(), 
                    approval_notes = ?, 
                    updated_at = NOW() 
                WHERE id = ?";

		$stmt = $connect->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $approval_notes, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found or already processed');
		}

		// Create order from approved loan application
		$orderNumber = generateLoanOrderNumber();
		$effectivePrice = ($loanData['promotional_price'] && $loanData['promotional_price'] > 0 && $loanData['promotional_price'] < $loanData['base_price']) 
							? $loanData['promotional_price'] 
							: $loanData['base_price'];

		$orderSql = "INSERT INTO orders (
						order_number, customer_id, sales_agent_id, vehicle_id, client_type,
						vehicle_model, vehicle_variant, vehicle_color, model_year,
						base_price, discount_amount, total_price, payment_method,
						down_payment, financing_term, monthly_payment, order_status,
						order_notes, created_at, order_date
					) VALUES (?, ?, ?, ?, 'handled', ?, ?, ?, YEAR(NOW()), ?, 0, ?, 'financing', ?, ?, ?, 'confirmed', ?, NOW(), NOW())";

		$orderStmt = $connect->prepare($orderSql);
		$orderSuccess = $orderStmt->execute([
			$orderNumber,
			$loanData['cusID'],
			$_SESSION['user_id'],
			$loanData['vehicle_id'],
			$loanData['model_name'],
			$loanData['variant'],
			$loanData['popular_color'] ?? 'Standard',
			$effectivePrice,
			$effectivePrice,
			$loanData['down_payment'] ?? 0,
			$loanData['financing_term'] ?? 12,
			$loanData['monthly_payment'] ?? 0,
			'Order created from approved loan application #' . $id . '. ' . $approval_notes
		]);

		if (!$orderSuccess) {
			throw new Exception('Failed to create order');
		}

		$orderId = $connect->lastInsertId();

		// Generate payment schedule if financing terms are available
		if (($loanData['down_payment'] ?? 0) > 0 && ($loanData['financing_term'] ?? 0) > 0 && ($loanData['monthly_payment'] ?? 0) > 0) {
			$paymentScheduleSql = "INSERT INTO payment_schedule (order_id, payment_number, due_date, amount_due, status) VALUES (?, ?, ?, ?, 'pending')";
			$paymentScheduleStmt = $connect->prepare($paymentScheduleSql);
			
			for ($i = 1; $i <= ($loanData['financing_term'] ?? 12); $i++) {
				$dueDate = date('Y-m-d', strtotime("+$i month"));
				$paymentScheduleStmt->execute([$orderId, $i, $dueDate, $loanData['monthly_payment'] ?? 0]);
			}
		}

		// Decrease vehicle inventory
		$inventoryStmt = $connect->prepare("UPDATE vehicles SET stock_quantity = stock_quantity - 1 WHERE id = ?");
		$inventorySuccess = $inventoryStmt->execute([$loanData['vehicle_id']]);

		if (!$inventorySuccess) {
			throw new Exception('Failed to update inventory');
		}

		$connect->commit();

		echo json_encode([
			'success' => true,
			'message' => 'Application approved successfully and order created',
			'data' => [
				'order_id' => $orderId,
				'order_number' => $orderNumber,
				'remaining_stock' => $loanData['stock_quantity'] - 1
			]
		]);
	} catch (Exception $e) {
		$connect->rollBack();
		echo json_encode([
			'success' => false,
			'error' => 'Failed to approve application: ' . $e->getMessage()
		]);
	}
}

function rejectApplication()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$rejection_reason = $_POST['rejection_reason'] ?? '';

	if (!$id || !$rejection_reason) {
		echo json_encode(['success' => false, 'error' => 'Application ID and rejection reason required']);
		return;
	}

	try {
		$connect->beginTransaction();

		$sql = "UPDATE loan_applications 
                SET status = 'Rejected', 
                    reviewed_by = ?, 
                    reviewed_at = NOW(), 
                    approval_notes = ?, 
                    updated_at = NOW() 
                WHERE id = ?";

		$stmt = $connect->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $rejection_reason, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found or already processed');
		}

		$connect->commit();

		echo json_encode([
			'success' => true,
			'message' => 'Loan application rejected'
		]);
	} catch (Exception $e) {
		$connect->rollBack();
		echo json_encode([
			'success' => false,
			'error' => 'Failed to reject application: ' . $e->getMessage()
		]);
	}
}

function getStatistics()
{
	global $connect;

	try {
		$stats = [];

		// Get pending count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Pending'");
		$stmt->execute();
		$stats['pending'] = $stmt->fetchColumn();

		// Get under review count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Under Review'");
		$stmt->execute();
		$stats['under_review'] = $stmt->fetchColumn();

		// Get approved count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Approved'");
		$stmt->execute();
		$stats['approved'] = $stmt->fetchColumn();

		// Get rejected count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Rejected'");
		$stmt->execute();
		$stats['rejected'] = $stmt->fetchColumn();

		echo json_encode([
			'success' => true,
			'data' => $stats
		]);
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => 'Failed to fetch statistics: ' . $e->getMessage()
		]);
	}
}

/**
 * Generate order number for loan-based orders
 */
function generateLoanOrderNumber() {
    $now = new DateTime();
    $year = $now->format('y');
    $month = $now->format('m');
    $day = $now->format('d');
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return "LOAN-{$year}{$month}{$day}{$random}";
}
