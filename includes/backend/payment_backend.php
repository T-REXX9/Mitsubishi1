<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

try {
    switch ($action) {
        case 'get_payment_stats':
            getPaymentStats();
            break;
        case 'get_agent_payments':
            getAgentPayments();
            break;
        case 'get_payment_details':
            getPaymentDetails();
            break;
        case 'process_payment':
            processPayment();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get payment statistics for the current agent
 */
function getPaymentStats()
{
    global $pdo, $user_id, $user_role;
    
    try {
        // Base query - different for different roles
        if ($user_role === 'Admin') {
            // Admin can see all payments
            $sql = "SELECT 
                        COUNT(CASE WHEN ph.status = 'Pending' THEN 1 END) as pending,
                        COUNT(CASE WHEN ph.status = 'Confirmed' THEN 1 END) as confirmed,
                        COUNT(CASE WHEN ph.status = 'Rejected' THEN 1 END) as rejected,
                        COALESCE(SUM(CASE WHEN ph.status = 'Confirmed' THEN ph.amount END), 0) as total_amount
                    FROM payment_history ph
                    INNER JOIN orders o ON ph.order_id = o.order_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } else {
            // Sales agents can only see payments for their orders
            $sql = "SELECT 
                        COUNT(CASE WHEN ph.status = 'Pending' THEN 1 END) as pending,
                        COUNT(CASE WHEN ph.status = 'Confirmed' THEN 1 END) as confirmed,
                        COUNT(CASE WHEN ph.status = 'Rejected' THEN 1 END) as rejected,
                        COALESCE(SUM(CASE WHEN ph.status = 'Confirmed' THEN ph.amount END), 0) as total_amount
                    FROM payment_history ph
                    INNER JOIN orders o ON ph.order_id = o.order_id
                    WHERE o.sales_agent_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
        }
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('Failed to fetch payment statistics: ' . $e->getMessage());
    }
}

/**
 * Get payments for the current agent with filtering and pagination
 */
function getAgentPayments()
{
    global $pdo, $user_id, $user_role;
    
    try {
        // Get filter parameters
        $status = $_POST['status'] ?? '';
        $payment_type = $_POST['payment_type'] ?? '';
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = max(1, min(50, intval($_POST['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        // Build WHERE conditions
        $whereConditions = [];
        $params = [];
        
        // Role-based filtering
        if ($user_role !== 'Admin') {
            $whereConditions[] = "o.sales_agent_id = ?";
            $params[] = $user_id;
        }
        
        if (!empty($status)) {
            $whereConditions[] = "ph.status = ?";
            $params[] = $status;
        }
        
        if (!empty($payment_type)) {
            $whereConditions[] = "ph.payment_type = ?";
            $params[] = $payment_type;
        }
        
        if (!empty($date_from)) {
            $whereConditions[] = "DATE(ph.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $whereConditions[] = "DATE(ph.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total
                     FROM payment_history ph
                     INNER JOIN orders o ON ph.order_id = o.order_id
                     INNER JOIN accounts a ON ph.customer_id = a.Id
                     INNER JOIN vehicles v ON o.vehicle_id = v.id
                     $whereClause";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $sql = "SELECT 
                    ph.id,
                    ph.payment_number,
                    ph.amount,
                    ph.payment_method,
                    ph.payment_type,
                    ph.status,
                    ph.created_at,
                    o.order_number,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    v.model_name as vehicle_model,
                    v.variant as vehicle_variant
                FROM payment_history ph
                INNER JOIN orders o ON ph.order_id = o.order_id
                INNER JOIN accounts a ON ph.customer_id = a.Id
                INNER JOIN vehicles v ON o.vehicle_id = v.id
                $whereClause
                ORDER BY ph.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('Failed to fetch payments: ' . $e->getMessage());
    }
}

/**
 * Get detailed information for a specific payment
 */
function getPaymentDetails()
{
    global $pdo, $user_id, $user_role;
    
    $payment_id = $_POST['payment_id'] ?? '';
    
    if (empty($payment_id)) {
        throw new Exception('Payment ID is required');
    }
    
    try {
        // Build query with role-based access control
        $sql = "SELECT 
                    ph.*,
                    o.order_number,
                    CONCAT(a.FirstName, ' ', a.LastName) as customer_name,
                    v.model_name as vehicle_model,
                    v.variant as vehicle_variant,
                    CONCAT(proc.FirstName, ' ', proc.LastName) as processed_by_name
                FROM payment_history ph
                INNER JOIN orders o ON ph.order_id = o.order_id
                INNER JOIN accounts a ON ph.customer_id = a.Id
                INNER JOIN vehicles v ON o.vehicle_id = v.id
                LEFT JOIN accounts proc ON ph.processed_by = proc.Id
                WHERE ph.id = ?";
        
        $params = [$payment_id];
        
        // Add role-based restriction
        if ($user_role !== 'Admin') {
            $sql .= " AND o.sales_agent_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Payment not found or access denied');
        }
        
        // Convert BLOB to base64 for receipt image
        if ($payment['payment_receipt']) {
            $payment['payment_receipt'] = base64_encode($payment['payment_receipt']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $payment
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('Failed to fetch payment details: ' . $e->getMessage());
    }
}

/**
 * Process payment (approve or reject)
 */
function processPayment()
{
    global $pdo, $user_id, $user_role;
    
    $payment_id = $_POST['payment_id'] ?? '';
    $process_action = $_POST['process_action'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if (empty($payment_id) || empty($process_action)) {
        throw new Exception('Payment ID and action are required');
    }
    
    if (!in_array($process_action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }
    
    if ($process_action === 'reject' && empty($rejection_reason)) {
        throw new Exception('Rejection reason is required');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify payment exists and user has access
        $verifySql = "SELECT ph.id, ph.status, ph.order_id, ph.amount, ph.payment_type
                      FROM payment_history ph
                      INNER JOIN orders o ON ph.order_id = o.order_id
                      WHERE ph.id = ? AND ph.status = 'Pending'";
        
        $verifyParams = [$payment_id];
        
        // Add role-based restriction
        if ($user_role !== 'Admin') {
            $verifySql .= " AND o.sales_agent_id = ?";
            $verifyParams[] = $user_id;
        }
        
        $verifyStmt = $pdo->prepare($verifySql);
        $verifyStmt->execute($verifyParams);
        $payment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Payment not found, already processed, or access denied');
        }
        
        // Update payment status
        $newStatus = $process_action === 'approve' ? 'Confirmed' : 'Rejected';
        $updateSql = "UPDATE payment_history 
                      SET status = ?, 
                          processed_by = ?, 
                          processed_at = NOW(),
                          rejection_reason = ?
                      WHERE id = ?";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $newStatus,
            $user_id,
            $process_action === 'reject' ? $rejection_reason : null,
            $payment_id
        ]);
        
        // If approved, update payment schedule
        if ($process_action === 'approve') {
            updatePaymentSchedule($payment['order_id'], $payment['amount'], $payment['payment_type']);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment ' . $process_action . 'd successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to process payment: ' . $e->getMessage());
    }
}

/**
 * Update payment schedule after payment confirmation
 */
function updatePaymentSchedule($order_id, $amount, $payment_type)
{
    global $pdo;
    
    try {
        // Get the next pending payment in the schedule
        $scheduleSql = "SELECT * FROM payment_schedule 
                        WHERE order_id = ? AND status = 'Pending' 
                        ORDER BY due_date ASC 
                        LIMIT 1";
        
        $scheduleStmt = $pdo->prepare($scheduleSql);
        $scheduleStmt->execute([$order_id]);
        $nextPayment = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nextPayment) {
            $remaining_amount = $amount;
            $payment_id = $nextPayment['id'];
            
            // Update the payment schedule entry
            if ($remaining_amount >= $nextPayment['balance']) {
                // Full payment of this installment
                $updateSql = "UPDATE payment_schedule 
                              SET amount_paid = amount_due, 
                                  balance = 0, 
                                  status = 'Paid',
                                  updated_at = NOW()
                              WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$payment_id]);
                
                $remaining_amount -= $nextPayment['balance'];
                
                // If there's remaining amount, apply to next payments
                if ($remaining_amount > 0) {
                    applyRemainingAmount($order_id, $remaining_amount);
                }
            } else {
                // Partial payment
                $new_balance = $nextPayment['balance'] - $remaining_amount;
                $new_amount_paid = $nextPayment['amount_paid'] + $remaining_amount;
                
                $updateSql = "UPDATE payment_schedule 
                              SET amount_paid = ?, 
                                  balance = ?, 
                                  status = 'Partial',
                                  updated_at = NOW()
                              WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$new_amount_paid, $new_balance, $payment_id]);
            }
        }
        
    } catch (PDOException $e) {
        throw new Exception('Failed to update payment schedule: ' . $e->getMessage());
    }
}

/**
 * Apply remaining payment amount to subsequent payments
 */
function applyRemainingAmount($order_id, $remaining_amount)
{
    global $pdo;
    
    try {
        // Get next pending payments
        $sql = "SELECT * FROM payment_schedule 
                WHERE order_id = ? AND status = 'Pending' 
                ORDER BY due_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($payments as $payment) {
            if ($remaining_amount <= 0) break;
            
            if ($remaining_amount >= $payment['balance']) {
                // Full payment
                $updateSql = "UPDATE payment_schedule 
                              SET amount_paid = amount_due, 
                                  balance = 0, 
                                  status = 'Paid',
                                  updated_at = NOW()
                              WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$payment['id']]);
                
                $remaining_amount -= $payment['balance'];
            } else {
                // Partial payment
                $new_balance = $payment['balance'] - $remaining_amount;
                $new_amount_paid = $payment['amount_paid'] + $remaining_amount;
                
                $updateSql = "UPDATE payment_schedule 
                              SET amount_paid = ?, 
                                  balance = ?, 
                                  status = 'Partial',
                                  updated_at = NOW()
                              WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$new_amount_paid, $new_balance, $payment['id']]);
                
                $remaining_amount = 0;
            }
        }
        
    } catch (PDOException $e) {
        throw new Exception('Failed to apply remaining amount: ' . $e->getMessage());
    }
}

/**
 * Create notification for payment status change
 */
function createPaymentNotification($customer_id, $payment_number, $status, $amount)
{
    global $pdo;
    
    try {
        $title = "Payment " . ucfirst(strtolower($status));
        $message = "Your payment #{$payment_number} of ₱" . number_format($amount, 2) . " has been {$status}.";
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, related_id) 
                VALUES (?, ?, ?, 'payment', ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $title, $message, $payment_number]);
        
    } catch (PDOException $e) {
        // Log error but don't throw - notification failure shouldn't break payment processing
        error_log('Failed to create payment notification: ' . $e->getMessage());
    }
}
?>