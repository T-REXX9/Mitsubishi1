<?php
// Include the session initialization file
include_once(dirname(__DIR__) . '/includes/init.php');

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_history';
    
    switch ($action) {
        case 'get_history':
            getEmailHistory($pdo, $user_id, $user_role);
            break;
            
        case 'get_email_details':
            $email_id = intval($_GET['email_id'] ?? $_POST['email_id'] ?? 0);
            getEmailDetails($pdo, $email_id, $user_id, $user_role);
            break;
            
        case 'get_templates':
            getEmailTemplates($pdo, $user_id);
            break;
            
        case 'delete_email':
            $email_id = intval($_POST['email_id'] ?? 0);
            deleteEmail($pdo, $email_id, $user_id, $user_role);
            break;
            
        case 'get_stats':
            getEmailStats($pdo, $user_id, $user_role);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Email History API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}

/**
 * Get email history with pagination and filtering
 */
function getEmailHistory($pdo, $user_id, $user_role) {
    try {
        // Get filter parameters
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $type_filter = $_GET['type'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $where_conditions = [];
        $params = [];
        
        // Role-based filtering
        if ($user_role !== 'Admin') {
            $where_conditions[] = "sender_id = ?";
            $params[] = $user_id;
        }
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(recipient LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $search_param = '%' . $search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        // Type filter
        if (!empty($type_filter)) {
            $where_conditions[] = "email_type = ?";
            $params[] = $type_filter;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(sent_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(sent_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM email_logs $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $sql = "SELECT id, sender_id, sender_name, recipient, subject, 
                       LEFT(message, 100) as message_preview, 
                       email_type, priority, sent_at, status, delivery_status, 
                       error_message, opened_at, clicked_at
                FROM email_logs 
                $where_clause 
                ORDER BY sent_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the results
        $formatted_emails = array_map(function($email) {
            return [
                'id' => $email['id'],
                'sender_id' => $email['sender_id'],
                'sender_name' => $email['sender_name'],
                'recipient' => $email['recipient'],
                'subject' => $email['subject'],
                'message_preview' => $email['message_preview'] . (strlen($email['message_preview']) >= 100 ? '...' : ''),
                'email_type' => $email['email_type'],
                'priority' => $email['priority'],
                'sent_at' => $email['sent_at'],
                'status' => $email['status'],
                'delivery_status' => $email['delivery_status'],
                'error_message' => $email['error_message'],
                'opened_at' => $email['opened_at'],
                'clicked_at' => $email['clicked_at'],
                'formatted_date' => date('M j, Y g:i A', strtotime($email['sent_at']))
            ];
        }, $emails);
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_emails,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total_records / $limit),
                'total_records' => $total_records,
                'per_page' => $limit
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching email history: ' . $e->getMessage()]);
    }
}

/**
 * Get detailed email information
 */
function getEmailDetails($pdo, $email_id, $user_id, $user_role) {
    try {
        if (!$email_id) {
            echo json_encode(['success' => false, 'message' => 'Email ID is required']);
            return;
        }
        
        // Build query with role-based access control
        $where_clause = "id = ?";
        $params = [$email_id];
        
        if ($user_role !== 'Admin') {
            $where_clause .= " AND sender_id = ?";
            $params[] = $user_id;
        }
        
        $sql = "SELECT * FROM email_logs WHERE $where_clause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $email = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Email not found or access denied']);
            return;
        }
        
        // Format the email data
        $formatted_email = [
            'id' => $email['id'],
            'sender_id' => $email['sender_id'],
            'sender_name' => $email['sender_name'],
            'recipient' => $email['recipient'],
            'subject' => $email['subject'],
            'message' => $email['message'],
            'email_type' => $email['email_type'],
            'priority' => $email['priority'],
            'sent_at' => $email['sent_at'],
            'status' => $email['status'],
            'delivery_status' => $email['delivery_status'],
            'error_message' => $email['error_message'],
            'opened_at' => $email['opened_at'],
            'clicked_at' => $email['clicked_at'],
            'formatted_date' => date('M j, Y g:i A', strtotime($email['sent_at']))
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_email
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching email details: ' . $e->getMessage()]);
    }
}

/**
 * Get email templates for the user
 */
function getEmailTemplates($pdo, $user_id) {
    try {
        // Check if templates table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'email_templates'");
        if ($table_check->rowCount() == 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => 'No templates found'
            ]);
            return;
        }
        
        $sql = "SELECT id, template_name, subject, message, email_type, created_at 
                FROM email_templates 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $templates
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching templates: ' . $e->getMessage()]);
    }
}

/**
 * Delete an email (soft delete)
 */
function deleteEmail($pdo, $email_id, $user_id, $user_role) {
    try {
        if (!$email_id) {
            echo json_encode(['success' => false, 'message' => 'Email ID is required']);
            return;
        }
        
        // Build query with role-based access control
        $where_clause = "id = ?";
        $params = [$email_id];
        
        if ($user_role !== 'Admin') {
            $where_clause .= " AND sender_id = ?";
            $params[] = $user_id;
        }
        
        // Check if email exists and user has permission
        $check_sql = "SELECT id FROM email_logs WHERE $where_clause";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute($params);
        
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email not found or access denied']);
            return;
        }
        
        // Add deleted flag to the update
        $update_sql = "UPDATE email_logs SET status = 'deleted', updated_at = NOW() WHERE $where_clause";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting email: ' . $e->getMessage()]);
    }
}

/**
 * Get email statistics
 */
function getEmailStats($pdo, $user_id, $user_role) {
    try {
        // Build WHERE clause based on role
        $where_clause = "";
        $params = [];
        
        if ($user_role !== 'Admin') {
            $where_clause = "WHERE sender_id = ?";
            $params[] = $user_id;
        }
        
        // Get overall stats
        $stats_sql = "SELECT 
                        COUNT(*) as total_emails,
                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_emails,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_emails,
                        SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_emails,
                        SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_emails,
                        SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked_emails
                      FROM email_logs $where_clause";
        
        $stats_stmt = $pdo->prepare($stats_sql);
        $stats_stmt->execute($params);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get stats by email type
        $type_stats_sql = "SELECT email_type, COUNT(*) as count 
                           FROM email_logs $where_clause 
                           GROUP BY email_type 
                           ORDER BY count DESC";
        
        $type_stmt = $pdo->prepare($type_stats_sql);
        $type_stmt->execute($params);
        $type_stats = $type_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent activity (last 30 days)
        $activity_where = $where_clause ? $where_clause . " AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" : "WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $activity_sql = "SELECT DATE(sent_at) as date, COUNT(*) as count 
                         FROM email_logs $activity_where 
                         GROUP BY DATE(sent_at) 
                         ORDER BY date DESC 
                         LIMIT 30";
        
        $activity_stmt = $pdo->prepare($activity_sql);
        $activity_stmt->execute($params);
        $activity_stats = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'overall' => $stats,
                'by_type' => $type_stats,
                'recent_activity' => $activity_stats
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching email stats: ' . $e->getMessage()]);
    }
}
?>