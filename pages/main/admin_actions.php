<?php
session_start();
require_once '../../includes/database/db_conn.php';
require_once '../../includes/database/customer_operations.php';

// Set proper JSON headers
header('Content-Type: application/json');

// Disable output buffering to prevent extra content
if (ob_get_level()) {
    ob_clean();
}

// Check if user is logged in and is an admin
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'admin')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access',
        'debug' => [
            'user_id' => $user_id,
            'role' => $user_role,
            'session_keys' => array_keys($_SESSION)
        ]
    ]);
    exit;
}

$admin_id = $user_id;

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_customer_details':
            getCustomerDetails($connect);
            break;
            
        case 'approve_customer':
            approveCustomer($connect, $admin_id);
            break;
            
        case 'reject_customer':
            rejectCustomer($connect, $admin_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getCustomerDetails($connect) {
    $cusID = $_GET['cusID'] ?? null;
    $accountId = $_GET['accountId'] ?? null;
    
    if (!$cusID && !$accountId) {
        echo json_encode(['success' => false, 'message' => 'No customer ID or account ID provided']);
        return;
    }
    
    try {
        if ($cusID) {
            // Query by customer ID
            $query = "SELECT 
                ci.*,
                a.Username,
                a.Email,
                a.FirstName,
                a.LastName,
                a.Status as AccountStatus,
                a.CreatedAt,
                a.LastLoginAt
                FROM customer_information ci
                LEFT JOIN accounts a ON ci.account_id = a.Id
                WHERE ci.cusID = :cusID";
            $stmt = $connect->prepare($query);
            $stmt->bindParam(':cusID', $cusID, PDO::PARAM_INT);
        } else {
            // Query by account ID
            $query = "SELECT 
                ci.*,
                a.Username,
                a.Email,
                a.FirstName,
                a.LastName,
                a.Status as AccountStatus,
                a.CreatedAt,
                a.LastLoginAt
                FROM accounts a
                LEFT JOIN customer_information ci ON a.Id = ci.account_id
                WHERE a.Id = :accountId";
            $stmt = $connect->prepare($query);
            $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo json_encode(['success' => true, 'customer' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function approveCustomer($connect, $admin_id) {
    $customerId = $_POST['customer_id'] ?? null;
    $accountId = $_POST['account_id'] ?? null;
    $comments = $_POST['approval_comments'] ?? '';
    
    if (!$customerId && !$accountId) {
        echo json_encode(['success' => false, 'message' => 'No customer ID or account ID provided']);
        return;
    }
    
    try {
        $connect->beginTransaction();
        
        // If we have a customer ID, get the associated account ID
        if ($customerId && !$accountId) {
            $stmt = $connect->prepare("SELECT account_id FROM customer_information WHERE cusID = :cusID");
            $stmt->bindParam(':cusID', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $accountId = $result['account_id'];
            }
        }
        
        // If we have an account ID, find the associated customer ID
        if ($accountId && !$customerId) {
            $stmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = :accountId");
            $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $customerId = $result['cusID'];
            }
        }
        
        // Update account status to Approved
        if ($accountId) {
            $stmt = $connect->prepare("UPDATE accounts SET Status = 'Approved', UpdatedAt = CURRENT_TIMESTAMP WHERE Id = :accountId");
            $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Update customer information status to Approved
        if ($customerId) {
            $stmt = $connect->prepare("UPDATE customer_information SET Status = 'Approved', updated_at = CURRENT_TIMESTAMP WHERE cusID = :cusID");
            $stmt->bindParam(':cusID', $customerId, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Assign a random active sales agent if none assigned yet
        $assignedAgentId = null;
        $custOps = new CustomerOperations();
        if ($customerId) {
            $assignedAgentId = $custOps->assignRandomAgentToCustomerByCustomerId($customerId);
        } elseif ($accountId) {
            $assignedAgentId = $custOps->assignRandomAgentToCustomerByAccountId($accountId);
        }
        
        // Log the approval action
        $targetId = $customerId ?: $accountId;
        $targetType = $customerId ? 'customer' : 'account';
        $description = "Customer account approved" . ($comments ? ": " . $comments : "");
        
        $stmt = $connect->prepare("INSERT INTO admin_actions (admin_id, action_type, target_id, target_type, description) VALUES (:admin_id, 'APPROVE_CUSTOMER', :target_id, :target_type, :description)");
        $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(':target_id', $targetId, PDO::PARAM_INT);
        $stmt->bindParam(':target_type', $targetType, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->execute();
        
        $connect->commit();
require_once(dirname(dirname(__DIR__)) . '/includes/api/notification_api.php');
if ($accountId) {
    createNotification($accountId, null, 'Customer Approved', 'Your customer account has been approved.', 'customer');
}
if (!empty($assignedAgentId)) {
    createNotification($assignedAgentId, null, 'New Customer Assigned', 'A new customer has been assigned to you.', 'assignment');
}
createNotification(null, 'Admin', 'Customer Approved', 'Customer account approved: ID ' . ($accountId ?? $customerId), 'customer');
echo json_encode(['success' => true, 'message' => 'Customer approved successfully']);
        
    } catch (Exception $e) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to approve customer: ' . $e->getMessage()]);
    }
}

function rejectCustomer($connect, $admin_id) {
    $customerId = $_POST['customer_id'] ?? null;
    $accountId = $_POST['account_id'] ?? null;
    $reason = $_POST['rejection_reason'] ?? '';
    $comments = $_POST['rejection_comments'] ?? '';
    
    if (!$customerId && !$accountId) {
        echo json_encode(['success' => false, 'message' => 'No customer ID or account ID provided']);
        return;
    }
    
    if (!$reason) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        return;
    }
    
    try {
        $connect->beginTransaction();
        
        // If we have a customer ID, get the associated account ID
        if ($customerId && !$accountId) {
            $stmt = $connect->prepare("SELECT account_id FROM customer_information WHERE cusID = :cusID");
            $stmt->bindParam(':cusID', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $accountId = $result['account_id'];
            }
        }
        
        // If we have an account ID, find the associated customer ID
        if ($accountId && !$customerId) {
            $stmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = :accountId");
            $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $customerId = $result['cusID'];
            }
        }
        
        // Update account status to Rejected
        if ($accountId) {
            $stmt = $connect->prepare("UPDATE accounts SET Status = 'Rejected', UpdatedAt = CURRENT_TIMESTAMP WHERE Id = :accountId");
            $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Update customer information status to Rejected
        if ($customerId) {
            $stmt = $connect->prepare("UPDATE customer_information SET Status = 'Rejected', updated_at = CURRENT_TIMESTAMP WHERE cusID = :cusID");
            $stmt->bindParam(':cusID', $customerId, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Create rejection description
        $reasonText = ucfirst(str_replace('_', ' ', $reason));
        $description = "Customer account rejected - Reason: " . $reasonText;
        if ($comments) {
            $description .= ". Additional comments: " . $comments;
        }
        
        // Log the rejection action
        $targetId = $customerId ?: $accountId;
        $targetType = $customerId ? 'customer' : 'account';
        
        $stmt = $connect->prepare("INSERT INTO admin_actions (admin_id, action_type, target_id, target_type, description) VALUES (:admin_id, 'REJECT_CUSTOMER', :target_id, :target_type, :description)");
        $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(':target_id', $targetId, PDO::PARAM_INT);
        $stmt->bindParam(':target_type', $targetType, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->execute();
        
        $connect->commit();
require_once(dirname(dirname(__DIR__)) . '/includes/api/notification_api.php');
if ($accountId) {
    createNotification($accountId, null, 'Customer Rejected', 'Your customer account has been rejected. Reason: ' . ($reason ?? ''), 'customer');
}
createNotification(null, 'Admin', 'Customer Rejected', 'Customer account rejected: ID ' . ($accountId ?? $customerId) . '. Reason: ' . ($reason ?? ''), 'customer');
echo json_encode(['success' => true, 'message' => 'Customer rejected successfully']);
        
    } catch (Exception $e) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to reject customer: ' . $e->getMessage()]);
    }
}
?>