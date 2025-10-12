<?php
// Amortization Payment Management API
// Start session and include necessary files
session_start();
require_once dirname(dirname(__DIR__)) . '/includes/database/db_conn.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$agent_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'Customer';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Use the database connection from db_conn.php
$pdo = $connect;

try {
    switch ($action) {
        case 'getUpcomingPayments':
            echo json_encode(getUpcomingPayments($pdo, $agent_id));
            break;
            
        case 'getOverduePayments':
            echo json_encode(getOverduePayments($pdo, $agent_id));
            break;
            
        case 'getRecentConfirmations':
            echo json_encode(getRecentConfirmations($pdo, $agent_id));
            break;
            
        case 'confirmPayment':
            echo json_encode(confirmPayment($pdo, $agent_id));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Amortization API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}

/**
 * Get upcoming payments for agent's customers (due within next 30 days)
 */
function getUpcomingPayments($pdo, $agent_id) {
    try {
        $sql = "SELECT 
                    ps.id as schedule_id,
                    ps.order_id,
                    ps.customer_id,
                    ps.payment_number,
                    ps.due_date,
                    ps.amount_due,
                    ps.amount_paid,
                    ps.status,
                    o.order_number,
                    o.financing_term,
                    o.vehicle_model,
                    o.vehicle_variant,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    ci.mobile_number as customer_mobile,
                    DATEDIFF(ps.due_date, CURDATE()) as days_until_due,
                    (SELECT COUNT(*) FROM payment_schedule ps2 
                     WHERE ps2.order_id = ps.order_id) as total_payments,
                    (SELECT COUNT(*) FROM payment_schedule ps3 
                     WHERE ps3.order_id = ps.order_id AND ps3.status = 'Paid') as payments_made
                FROM payment_schedule ps
                INNER JOIN orders o ON ps.order_id = o.order_id
                INNER JOIN accounts a ON ps.customer_id = a.Id
                INNER JOIN customer_information ci ON a.Id = ci.account_id
                WHERE ci.agent_id = ?
                AND ps.status IN ('Pending', 'Partial')
                AND ps.due_date >= CURDATE()
                AND ps.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY ps.due_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$agent_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $total_upcoming = count($payments);
        $total_amount = array_sum(array_column($payments, 'amount_due'));
        $due_this_week = count(array_filter($payments, function($p) {
            return $p['days_until_due'] <= 7;
        }));
        
        return [
            'success' => true,
            'data' => $payments,
            'stats' => [
                'total_upcoming' => $total_upcoming,
                'total_amount' => $total_amount,
                'due_this_week' => $due_this_week
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting upcoming payments: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}

/**
 * Get overdue payments for agent's customers
 */
function getOverduePayments($pdo, $agent_id) {
    try {
        $sql = "SELECT 
                    ps.id as schedule_id,
                    ps.order_id,
                    ps.customer_id,
                    ps.payment_number,
                    ps.due_date,
                    ps.amount_due,
                    ps.amount_paid,
                    ps.status,
                    o.order_number,
                    o.financing_term,
                    o.vehicle_model,
                    o.vehicle_variant,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    ci.mobile_number as customer_mobile,
                    DATEDIFF(CURDATE(), ps.due_date) as days_overdue,
                    (SELECT COUNT(*) FROM payment_schedule ps2 
                     WHERE ps2.order_id = ps.order_id) as total_payments,
                    (SELECT COUNT(*) FROM payment_schedule ps3 
                     WHERE ps3.order_id = ps.order_id AND ps3.status = 'Paid') as payments_made
                FROM payment_schedule ps
                INNER JOIN orders o ON ps.order_id = o.order_id
                INNER JOIN accounts a ON ps.customer_id = a.Id
                INNER JOIN customer_information ci ON a.Id = ci.account_id
                WHERE ci.agent_id = ?
                AND ps.status IN ('Pending', 'Partial', 'Overdue')
                AND ps.due_date < CURDATE()
                ORDER BY ps.due_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$agent_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $total_overdue = count($payments);
        $total_amount = array_sum(array_column($payments, 'amount_due'));
        $critical = count(array_filter($payments, function($p) {
            return $p['days_overdue'] > 30;
        }));
        
        return [
            'success' => true,
            'data' => $payments,
            'stats' => [
                'total_overdue' => $total_overdue,
                'total_amount' => $total_amount,
                'critical' => $critical
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting overdue payments: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}

/**
 * Get recent payment confirmations (last 30 days)
 */
function getRecentConfirmations($pdo, $agent_id) {
    try {
        $sql = "SELECT 
                    ph.id as payment_id,
                    ph.order_id,
                    ph.customer_id,
                    ph.payment_number as payment_ref,
                    ph.payment_date,
                    ph.amount_paid,
                    ph.payment_type,
                    ph.payment_method,
                    ph.status,
                    o.order_number,
                    o.vehicle_model,
                    o.vehicle_variant,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    a.Email as customer_email,
                    CONCAT(processor.FirstName, ' ', processor.LastName) as processed_by_name
                FROM payment_history ph
                INNER JOIN orders o ON ph.order_id = o.order_id
                INNER JOIN accounts a ON ph.customer_id = a.Id
                INNER JOIN customer_information ci ON a.Id = ci.account_id
                LEFT JOIN accounts processor ON ph.processed_by = processor.Id
                WHERE ci.agent_id = ?
                AND ph.status = 'Confirmed'
                AND ph.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY ph.payment_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$agent_id]);
        $confirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $today_confirmations = count(array_filter($confirmations, function($c) {
            return date('Y-m-d', strtotime($c['payment_date'])) === date('Y-m-d');
        }));
        
        $week_confirmations = count(array_filter($confirmations, function($c) {
            $payment_date = strtotime($c['payment_date']);
            $week_ago = strtotime('-7 days');
            return $payment_date >= $week_ago;
        }));
        
        $total_amount = array_sum(array_column($confirmations, 'amount_paid'));
        
        return [
            'success' => true,
            'data' => $confirmations,
            'stats' => [
                'today_confirmations' => $today_confirmations,
                'week_confirmations' => $week_confirmations,
                'total_amount' => $total_amount
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting recent confirmations: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}

/**
 * Confirm a payment
 */
function confirmPayment($pdo, $agent_id) {
    try {
        $schedule_id = $_POST['schedule_id'] ?? null;
        
        if (!$schedule_id) {
            return ['success' => false, 'error' => 'Missing schedule ID'];
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get payment schedule details and verify agent owns this customer
        $sql = "SELECT 
                    ps.*,
                    o.order_number,
                    o.vehicle_model,
                    ci.agent_id,
                    (SELECT COUNT(*) FROM payment_schedule ps2 
                     WHERE ps2.order_id = ps.order_id) as total_payments
                FROM payment_schedule ps
                INNER JOIN orders o ON ps.order_id = o.order_id
                INNER JOIN customer_information ci ON ps.customer_id = ci.account_id
                WHERE ps.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Payment schedule not found'];
        }
        
        // Verify agent owns this customer
        if ($schedule['agent_id'] != $agent_id) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Unauthorized: This customer is not assigned to you'];
        }
        
        // Verify payment is in valid status
        if ($schedule['status'] === 'Paid') {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Payment already confirmed'];
        }
        
        // Generate unique payment number
        $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($schedule['order_id'], 6, '0', STR_PAD_LEFT) . '-' . str_pad($schedule['payment_number'], 3, '0', STR_PAD_LEFT);
        
        // Create payment history record
        $insertSql = "INSERT INTO payment_history (
                        order_id, customer_id, payment_number, payment_date, 
                        amount_paid, payment_type, payment_method, 
                        reference_number, status, processed_by, notes, created_at
                      ) VALUES (?, ?, ?, NOW(), ?, 'Monthly Payment', 'Cash', ?, 'Confirmed', ?, ?, NOW())";
        
        $reference = 'AMORT-' . $schedule['order_number'] . '-PMT' . $schedule['payment_number'];
        $notes = 'Payment ' . $schedule['payment_number'] . ' of ' . $schedule['total_payments'] . ' confirmed by agent';
        
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            $schedule['order_id'],
            $schedule['customer_id'],
            $payment_number,
            $schedule['amount_due'],
            $reference,
            $agent_id,
            $notes
        ]);
        
        // Update payment schedule status
        $updateSql = "UPDATE payment_schedule 
                      SET status = 'Paid', 
                          amount_paid = amount_due, 
                          paid_date = CURDATE(),
                          updated_at = NOW()
                      WHERE id = ?";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$schedule_id]);
        
        // Create notification for customer
        require_once dirname(__DIR__) . '/api/notification_api.php';
        $notification_message = "Payment confirmed for Order {$schedule['order_number']} - Payment {$schedule['payment_number']} of {$schedule['total_payments']} (₱" . number_format($schedule['amount_due'], 2) . ") received on " . date('M d, Y');
        createNotification($schedule['customer_id'], null, 'Payment Confirmed', $notification_message, 'payment', $schedule['order_id']);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Payment confirmed successfully',
            'payment_number' => $payment_number
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error confirming payment: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}
?>

