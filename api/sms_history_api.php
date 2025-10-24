<?php
/**
 * SMS History API
 * Handles retrieval, filtering, and management of SMS logs
 */

header('Content-Type: application/json');

// Initialize session and database
require_once __DIR__ . '/../includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_history';
    
    switch ($action) {
        case 'get_history':
            getSmsHistory($pdo, $user_id, $user_role);
            break;
            
        case 'get_sms_details':
            $sms_id = intval($_GET['sms_id'] ?? $_POST['sms_id'] ?? 0);
            getSmsDetails($pdo, $sms_id, $user_id, $user_role);
            break;
            
        case 'delete_sms':
            $sms_id = intval($_POST['sms_id'] ?? 0);
            deleteSms($pdo, $sms_id, $user_id, $user_role);
            break;
            
        case 'get_stats':
            getSmsStats($pdo, $user_id, $user_role);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

exit;

/**
 * Get SMS history with pagination and filtering
 */
function getSmsHistory($pdo, $user_id, $user_role) {
    try {
        // Get filter parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20))); // Ensure limit is between 1 and 100
        $search = $_GET['search'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        $offset = max(0, ($page - 1) * $limit); // Ensure offset is not negative
        
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
            $where_conditions[] = "(recipient LIKE ? OR message LIKE ?)";
            $search_param = '%' . $search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "sent_at >= ?";
            $params[] = $date_from . ' 00:00:00';
        }
        if (!empty($date_to)) {
            $where_conditions[] = "sent_at <= ?";
            $params[] = $date_to . ' 23:59:59';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM sms_logs $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        // Note: LIMIT and OFFSET are directly interpolated as integers (already validated with intval())
        // to avoid PDO quoting them as strings which causes SQL syntax errors
        // Explicitly cast to int to ensure they're not treated as strings
        $limit_int = (int)$limit;
        $offset_int = (int)$offset;

        $sql = "SELECT id, sender_id, sender_name, recipient,
                       LEFT(message, 100) as message_preview,
                       message_length, segment_count, is_unicode,
                       sender_id_name, provider, status, delivery_status,
                       sent_at, delivered_at, error_message
                FROM sms_logs
                $where_clause
                ORDER BY sent_at DESC
                LIMIT {$limit_int} OFFSET {$offset_int}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sms_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the results
        $formatted_sms = array_map(function($sms) {
            return [
                'id' => $sms['id'],
                'sender_id' => $sms['sender_id'],
                'sender_name' => $sms['sender_name'],
                'recipient' => $sms['recipient'],
                'message_preview' => $sms['message_preview'] . (strlen($sms['message_preview']) >= 100 ? '...' : ''),
                'message_length' => $sms['message_length'],
                'segment_count' => $sms['segment_count'],
                'is_unicode' => $sms['is_unicode'],
                'sender_id_name' => $sms['sender_id_name'],
                'provider' => $sms['provider'],
                'status' => $sms['status'],
                'delivery_status' => $sms['delivery_status'],
                'sent_at' => $sms['sent_at'],
                'delivered_at' => $sms['delivered_at'],
                'error_message' => $sms['error_message'],
                'formatted_date' => date('M j, Y g:i A', strtotime($sms['sent_at']))
            ];
        }, $sms_list);
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_sms,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total_records / $limit),
                'total_records' => $total_records,
                'per_page' => $limit
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching SMS history: ' . $e->getMessage()]);
    }
}

/**
 * Get detailed SMS information
 */
function getSmsDetails($pdo, $sms_id, $user_id, $user_role) {
    try {
        if (!$sms_id) {
            echo json_encode(['success' => false, 'message' => 'SMS ID is required']);
            return;
        }
        
        // Build query with role-based access control
        $where_clause = "id = ?";
        $params = [$sms_id];
        
        if ($user_role !== 'Admin') {
            $where_clause .= " AND sender_id = ?";
            $params[] = $user_id;
        }
        
        $sql = "SELECT * FROM sms_logs WHERE $where_clause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sms = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sms) {
            echo json_encode(['success' => false, 'message' => 'SMS not found or access denied']);
            return;
        }
        
        // Format the response
        $sms['formatted_date'] = date('M j, Y g:i A', strtotime($sms['sent_at']));
        $sms['formatted_delivered_at'] = $sms['delivered_at'] ? date('M j, Y g:i A', strtotime($sms['delivered_at'])) : null;
        
        echo json_encode([
            'success' => true,
            'data' => $sms
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching SMS details: ' . $e->getMessage()]);
    }
}

/**
 * Delete SMS log (Admin only)
 */
function deleteSms($pdo, $sms_id, $user_id, $user_role) {
    try {
        if (!$sms_id) {
            echo json_encode(['success' => false, 'message' => 'SMS ID is required']);
            return;
        }
        
        // Build query with role-based access control
        $where_clause = "id = ?";
        $params = [$sms_id];
        
        // Only allow deletion of own SMS or Admin can delete any
        if ($user_role !== 'Admin') {
            $where_clause .= " AND sender_id = ?";
            $params[] = $user_id;
        }
        
        $sql = "DELETE FROM sms_logs WHERE $where_clause";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'SMS deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SMS not found or access denied']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting SMS: ' . $e->getMessage()]);
    }
}

/**
 * Get SMS statistics
 */
function getSmsStats($pdo, $user_id, $user_role) {
    try {
        // Build WHERE clause for role-based filtering
        $where_conditions = [];
        $params = [];
        
        if ($user_role !== 'Admin') {
            $where_conditions[] = "sender_id = ?";
            $params[] = $user_id;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get overall stats
        $stats_sql = "SELECT 
                        COUNT(*) as total_sent,
                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                        SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                        SUM(segment_count) as total_segments
                      FROM sms_logs $where_clause";
        
        $stats_stmt = $pdo->prepare($stats_sql);
        $stats_stmt->execute($params);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent activity (last 30 days)
        $activity_where = $where_clause ? $where_clause . " AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" : "WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $activity_sql = "SELECT DATE(sent_at) as date, COUNT(*) as count 
                         FROM sms_logs $activity_where 
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
                'recent_activity' => $activity_stats
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching SMS stats: ' . $e->getMessage()]);
    }
}

