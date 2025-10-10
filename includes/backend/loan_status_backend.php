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
	case 'get_loan_statuses':
		getLoanStatuses();
		break;
	case 'get_status_statistics':
		getStatusStatistics();
		break;
	case 'update_loan_status':
		updateLoanStatus();
		break;
	case 'mark_approved':
		markApproved();
		break;
	case 'mark_rejected':
		markRejected();
		break;
	case 'mark_disbursed':
		markDisbursed();
		break;
	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'error' => 'Invalid action']);
		exit;
}

function getLoanStatuses()
{
	global $connect;

	try {
		$sql = "SELECT 
                    la.id,
                    la.application_date,
                    la.status,
                    la.notes,
                    la.approval_notes,
                    la.reviewed_at,
                    la.created_at,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    ci.mobile_number,
                    v.model_name,
                    v.variant,
                    v.base_price,
                    CONCAT(reviewer.FirstName, ' ', reviewer.LastName) as reviewed_by_name
                FROM loan_applications la
                JOIN accounts a ON la.customer_id = a.Id
                LEFT JOIN customer_information ci ON la.customer_id = ci.account_id
                JOIN vehicles v ON la.vehicle_id = v.id
                LEFT JOIN accounts reviewer ON la.reviewed_by = reviewer.Id
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
			'error' => 'Failed to fetch loan statuses: ' . $e->getMessage()
		]);
	}
}

function getStatusStatistics()
{
	global $connect;

	try {
		$stats = [];

		// Get in progress count (Pending + Under Review)
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status IN ('Pending', 'Under Review')");
		$stmt->execute();
		$stats['in_progress'] = $stmt->fetchColumn();

		// Get approved count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Approved'");
		$stmt->execute();
		$stats['approved'] = $stmt->fetchColumn();

		// Get rejected count
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Rejected'");
		$stmt->execute();
		$stats['rejected'] = $stmt->fetchColumn();

		// Get completed count (simulating disbursed)
		$stmt = $connect->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'Completed'");
		$stmt->execute();
		$stats['disbursed'] = $stmt->fetchColumn();

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

function updateLoanStatus()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$newStatus = $_POST['status'] ?? null;
	$notes = $_POST['notes'] ?? '';

	if (!$id || !$newStatus) {
		echo json_encode(['success' => false, 'error' => 'Application ID and status required']);
		return;
	}

	// Map frontend status to database status
	$statusMap = [
		'in-progress' => 'Under Review',
		'approved' => 'Approved',
		'rejected' => 'Rejected',
		'disbursed' => 'Completed'
	];

	$dbStatus = $statusMap[$newStatus] ?? $newStatus;

	try {
		$connect->beginTransaction();

		$sql = "UPDATE loan_applications 
                SET status = ?, 
                    reviewed_by = ?, 
                    reviewed_at = NOW(), 
                    approval_notes = ?, 
                    updated_at = NOW() 
                WHERE id = ?";

		$stmt = $connect->prepare($sql);
		$stmt->execute([$dbStatus, $_SESSION['user_id'], $notes, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found');
		}

		$connect->commit();

		echo json_encode([
			'success' => true,
			'message' => 'Loan status updated successfully'
		]);
	} catch (Exception $e) {
		$connect->rollBack();
		echo json_encode([
			'success' => false,
			'error' => 'Failed to update status: ' . $e->getMessage()
		]);
	}
}

function markApproved()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$notes = $_POST['notes'] ?? 'Loan approved by sales agent';

	if (!$id) {
		echo json_encode(['success' => false, 'error' => 'Application ID required']);
		return;
	}

	try {
		$connect->beginTransaction();

		// Get loan application details
		        $loanStmt = $connect->prepare("SELECT la.*, ci.cusID, ci.account_id, v.model_name, v.variant, v.base_price, v.promotional_price, v.stock_quantity, v.popular_color, a.FirstName, a.LastName, a.Email 
                                     FROM loan_applications la 
                                     JOIN customer_information ci ON la.customer_id = ci.account_id 
                                     JOIN vehicles v ON la.vehicle_id = v.id 
                                     JOIN accounts a ON la.customer_id = a.Id
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
		$stmt->execute([$_SESSION['user_id'], $notes, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found');
		}

		// Create order from approved loan application
		$orderNumber = generateLoanOrderNumber();
		$effectivePrice = ($loanData['promotional_price'] && $loanData['promotional_price'] < $loanData['base_price']) 
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
			'Order created from approved loan application #' . $id . '. ' . $notes
		]);

		if (!$orderSuccess) {
			throw new Exception('Failed to create order');
		}

		$orderId = $connect->lastInsertId();

        // Create notifications for customer and admin
        try {
            // Notification for customer
            $customerMessage = "Your loan application #{$id} has been approved. Order #{$orderNumber} has been created. Delivery details will be provided soon.";
            $customerTitle = "Loan Application Approved";
            
            // Notification for admin
            $adminMessage = "Loan application #{$id} has been approved by {$_SESSION['firstname']} {$_SESSION['lastname']} for customer {$loanData['FirstName']} {$loanData['LastName']} (Order #{$orderNumber}).";
            $adminTitle = "Loan Application Approved";
            
            // Include notification API
            include_once(dirname(__DIR__) . '/api/notification_api.php');
            
            // Create notification for customer
            createNotification(
                $loanData['account_id'], // Customer's account ID
                null,                    // No specific role
                $customerTitle,
                $customerMessage,
                'loan_approval',         // Notification type
                $orderId                 // Related order ID
            );
            
            // Create notification for admin
            createNotification(
                null,                    // No specific user
                'Admin',                 // Target admin role
                $adminTitle,
                $adminMessage,
                'loan_approval',         // Notification type
                $orderId                 // Related order ID
            );
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log("Failed to create notification: " . $e->getMessage());
        }

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

function markRejected()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$reason = $_POST['reason'] ?? '';

	if (!$id || !$reason) {
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
		$stmt->execute([$_SESSION['user_id'], 'Rejected: ' . $reason, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found');
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

function markDisbursed()
{
	global $connect;

	$id = $_POST['id'] ?? null;
	$notes = $_POST['notes'] ?? 'Loan disbursed successfully';

	if (!$id) {
		echo json_encode(['success' => false, 'error' => 'Application ID required']);
		return;
	}

	try {
		$connect->beginTransaction();

		// Check if loan is approved first
		$checkStmt = $connect->prepare("SELECT status FROM loan_applications WHERE id = ?");
		$checkStmt->execute([$id]);
		$currentStatus = $checkStmt->fetchColumn();

		if ($currentStatus !== 'Approved') {
			throw new Exception('Only approved loans can be marked as disbursed');
		}

		$sql = "UPDATE loan_applications 
                SET status = 'Completed', 
                    reviewed_by = ?, 
                    reviewed_at = NOW(), 
                    approval_notes = ?, 
                    updated_at = NOW() 
                WHERE id = ?";

		$stmt = $connect->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $notes, $id]);

		if ($stmt->rowCount() === 0) {
			throw new Exception('Application not found');
		}

		$connect->commit();

		echo json_encode([
			'success' => true,
			'message' => 'Loan marked as disbursed successfully'
		]);
	} catch (Exception $e) {
		$connect->rollBack();
		echo json_encode([
			'success' => false,
			'error' => 'Failed to mark as disbursed: ' . $e->getMessage()
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
