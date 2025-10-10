<?php
require_once __DIR__ . '/../database/db_conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_account_details':
        getAccountDetails($connect);
        break;
    case 'get_customer_details':
        getCustomerDetails($connect);
        break;
    case 'approve_account':
        approveAccount($connect);
        break;
    case 'reject_account':
        rejectAccount($connect);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getAccountDetails($connect) {
    $accountId = $_GET['account_id'] ?? 0;
    
    try {
        $query = "SELECT 
            a.Id as account_id,
            a.Username,
            a.Email,
            a.CreatedAt,
            ci.cusID,
            ci.firstname,
            ci.lastname,
            ci.mobile_number,
            ci.employment_status,
            ci.company_name
        FROM `accounts` a
        LEFT JOIN customer_information ci ON a.Id = ci.account_id
        WHERE a.Id = ?";
        
        $stmt = $connect->prepare($query);
        $stmt->execute([$accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            echo json_encode(['success' => true, 'account' => $account]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Account not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCustomerDetails($connect) {
    $accountId = $_GET['account_id'] ?? 0;
    
    try {
        $query = "SELECT 
            a.Id as account_id,
            a.Username,
            a.Email,
            a.CreatedAt,
            a.LastLoginAt,
            ci.*
        FROM `accounts` a
        INNER JOIN customer_information ci ON a.Id = ci.account_id
        WHERE a.Id = ?";
        
        $stmt = $connect->prepare($query);
        $stmt->execute([$accountId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo json_encode(['success' => true, 'customer' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function approveAccount($connect) {
    $accountId = $_POST['account_id'] ?? 0;
    
    try {
        // Check if account exists and is a customer
        $query = "SELECT Id, Email FROM `accounts` WHERE Id = ? AND Role = 'Customer'";
        $stmt = $connect->prepare($query);
        $stmt->execute([$accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Account not found or not a customer account']);
            return;
        }
        
        // Update account status or perform approval logic here
        // For now, we'll just log the approval
        $logQuery = "INSERT INTO admin_logs (admin_id, action, target_account_id, details, created_at) 
                     VALUES (?, 'account_approved', ?, 'Account approved by admin', NOW())";
        
        // Create logs table if it doesn't exist
        $createLogTable = "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_account_id INT,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $connect->exec($createLogTable);
        
        $logStmt = $connect->prepare($logQuery);
        $logStmt->execute([$_SESSION['user_id'], $accountId]);
        
        echo json_encode(['success' => true, 'message' => 'Account approved successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function rejectAccount($connect) {
    $accountId = $_POST['account_id'] ?? 0;
    $reason = $_POST['reason'] ?? 'No reason provided';
    
    try {
        // Check if account exists
        $query = "SELECT Id FROM `accounts` WHERE Id = ? AND Role = 'Customer'";
        $stmt = $connect->prepare($query);
        $stmt->execute([$accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Account not found']);
            return;
        }
        
        // Log the rejection
        $logQuery = "INSERT INTO admin_logs (admin_id, action, target_account_id, details, created_at) 
                     VALUES (?, 'account_rejected', ?, ?, NOW())";
        $logStmt = $connect->prepare($logQuery);
        $logStmt->execute([$_SESSION['user_id'], $accountId, 'Account rejected. Reason: ' . $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Account rejected successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
