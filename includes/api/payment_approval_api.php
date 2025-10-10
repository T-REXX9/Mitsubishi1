<?php
session_start();
require_once '../database/db_conn.php';
require_once '../backend/order_backend.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'getPendingPayments':
        handleGetPendingPayments();
        break;
        
    case 'approvePayment':
        handleApprovePayment();
        break;
        
    case 'rejectPayment':
        handleRejectPayment();
        break;
        
    case 'getPaymentDetails':
        handleGetPaymentDetails();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

/**
 * Get all pending payments for admin approval
 */
function handleGetPendingPayments()
{
    try {
        $payments = getPendingPayments();
        echo json_encode([
            'success' => true,
            'data' => $payments
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch pending payments: ' . $e->getMessage()
        ]);
    }
}

/**
 * Approve a payment
 */
function handleApprovePayment()
{
    $payment_id = $_POST['payment_id'] ?? null;
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'error' => 'Payment ID required']);
        return;
    }
    
    try {
        $result = approvePayment($payment_id, $_SESSION['user_id']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment approved successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to approve payment'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to approve payment: ' . $e->getMessage()
        ]);
    }
}

/**
 * Reject a payment
 */
function handleRejectPayment()
{
    $payment_id = $_POST['payment_id'] ?? null;
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    if (!$payment_id || !$rejection_reason) {
        echo json_encode(['success' => false, 'error' => 'Payment ID and rejection reason required']);
        return;
    }
    
    try {
        $result = rejectPayment($payment_id, $_SESSION['user_id'], $rejection_reason);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment rejected successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to reject payment'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to reject payment: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get detailed payment information for review
 */
function handleGetPaymentDetails()
{
    global $connect;
    
    $payment_id = $_GET['payment_id'] ?? null;
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'error' => 'Payment ID required']);
        return;
    }
    
    try {
        $sql = "SELECT 
                    ph.*,
                    CONCAT(ci.firstname, ' ', ci.lastname) as customer_name,
                    ci.mobile_number,
                    ci.email,
                    o.vehicle_model,
                    o.vehicle_variant,
                    o.vehicle_color,
                    o.total_price,
                    o.down_payment,
                    o.financing_term,
                    o.monthly_payment,
                    o.payment_method as order_payment_method,
                    CONCAT(processor.FirstName, ' ', processor.LastName) as processed_by_name
                FROM payment_history ph
                INNER JOIN customer_information ci ON ph.customer_id = ci.cusID
                INNER JOIN orders o ON ph.order_id = o.order_id
                LEFT JOIN accounts processor ON ph.processed_by = processor.Id
                WHERE ph.id = ?";
                
        $stmt = $connect->prepare($sql);
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['success' => false, 'error' => 'Payment not found']);
            return;
        }
        
        // Add receipt file URL if receipt exists
        if (!empty($payment['receipt_filename'])) {
            $payment['receipt_url'] = '../uploads/receipts/' . $payment['receipt_filename'];
            $payment['has_receipt'] = true;
        } else {
            $payment['has_receipt'] = false;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $payment
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch payment details: ' . $e->getMessage()
        ]);
    }
}
?>