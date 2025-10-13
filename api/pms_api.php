<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/includes/handlers/pms_handler.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Admin', 'SalesAgent'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$pmsHandler = new PMSHandler($pdo);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_statistics':
            $stats = $pmsHandler->getPMSStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'get_pms_records':
            $filters = [
                'customer_search' => $_GET['customer_search'] ?? '',
                'pms_type' => $_GET['pms_type'] ?? '',
                'status' => $_GET['status'] ?? '',
                'completion_period' => $_GET['completion_period'] ?? ''
            ];
            $records = $pmsHandler->getAllPMSRecords($filters);
            
            // Ensure all records have required fields with default values
            foreach ($records as &$record) {
                $record['approved_at'] = $record['approved_at'] ?? null;
                $record['approved_by_name'] = $record['approved_by_name'] ?? 'N/A';
                $record['full_name'] = $record['full_name'] ?? 'N/A';
                $record['mobile_number'] = $record['mobile_number'] ?? 'N/A';
                $record['model'] = $record['model'] ?? 'N/A';
                $record['plate_number'] = $record['plate_number'] ?? 'N/A';
                $record['color'] = $record['color'] ?? 'N/A';
                $record['transmission'] = $record['transmission'] ?? 'N/A';
                $record['pms_info'] = $record['pms_info'] ?? 'N/A';
                $record['next_pms_due'] = $record['next_pms_due'] ?? 'N/A';
                $record['request_status'] = $record['request_status'] ?? 'Pending';
                $record['current_odometer'] = $record['current_odometer'] ?? 0;
                $record['customer_id'] = $record['customer_id'] ?? 0;
                $record['pms_id'] = $record['pms_id'] ?? 0;
            }
            
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        case 'get_customer_history':
            $customerId = $_GET['customer_id'] ?? 0;
            if (!$customerId) {
                throw new Exception('Customer ID is required');
            }
            
            $history = $pmsHandler->getCustomerPMSHistory($customerId);
            $stats = $pmsHandler->getCustomerSessionStats($customerId);
            
            // Ensure all history records have required fields
            foreach ($history as &$record) {
                $record['approved_at'] = $record['approved_at'] ?? null;
                $record['approved_by_name'] = $record['approved_by_name'] ?? 'N/A';
                $record['service_notes_findings'] = $record['service_notes_findings'] ?? '';
                $record['pms_info'] = $record['pms_info'] ?? 'N/A';
                $record['request_status'] = $record['request_status'] ?? 'Pending';
            }
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'history' => $history,
                    'stats' => $stats
                ]
            ]);
            break;
            
        case 'track_session':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = [
                'customer_id' => $_POST['customer_id'] ?? 0,
                'plate_number' => $_POST['plate_number'] ?? '',
                'model' => $_POST['model'] ?? '',
                'current_odometer' => $_POST['current_odometer'] ?? 0,
                'service_type' => $_POST['service_type'] ?? '',
                'service_date' => $_POST['service_date'] ?? '',
                'service_notes' => $_POST['service_notes'] ?? '',
                'approved_by' => $_SESSION['user_role'] === 'Admin' ? 1 : ($_SESSION['agent_profile_id'] ?? 1)
            ];
            
            // Validate required fields
            if (!$data['customer_id'] || !$data['service_type'] || !$data['service_date']) {
                throw new Exception('Customer ID, service type, and service date are required');
            }
            
            $result = $pmsHandler->trackNewSession($data);
            echo json_encode($result);
            break;
            
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $pmsId = $_POST['pms_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $data = [];
            
            if (!$pmsId || !$status) {
                throw new Exception('PMS ID and status are required');
            }
            
            if ($status === 'Approved') {
                $data['approved_by'] = $_SESSION['user_role'] === 'Admin' ? 1 : ($_SESSION['agent_profile_id'] ?? 1);
            } elseif ($status === 'Scheduled') {
                $data['scheduled_date'] = $_POST['scheduled_date'] ?? '';
            } elseif ($status === 'Rejected') {
                $data['rejection_reason'] = $_POST['rejection_reason'] ?? '';
            }
            
            $result = $pmsHandler->updatePMSStatus($pmsId, $status, $data);
            echo json_encode($result);
            break;
            
        case 'get_customers':
            $customers = $pmsHandler->getCustomers();
            echo json_encode(['success' => true, 'data' => $customers]);
            break;
            
        case 'get_agents':
            $agents = $pmsHandler->getSalesAgents();
            echo json_encode(['success' => true, 'data' => $agents]);
            break;
            
        case 'get_record':
            $pmsId = $_GET['pms_id'] ?? 0;
            if (!$pmsId) {
                throw new Exception('PMS ID is required');
            }
            
            $result = $pmsHandler->getPMSRecordById($pmsId);
            echo json_encode($result);
            break;
            
        case 'update_record':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $pmsId = $_POST['pms_id'] ?? 0;
            if (!$pmsId) {
                throw new Exception('PMS ID is required');
            }
            
            $data = [
                'pms_info' => $_POST['pms_info'] ?? null,
                'pms_date' => $_POST['pms_date'] ?? null,
                'current_odometer' => $_POST['current_odometer'] ?? null,
                'service_notes_findings' => $_POST['service_notes_findings'] ?? null,
                'next_pms_due' => $_POST['next_pms_due'] ?? null,
                'model' => $_POST['model'] ?? null,
                'plate_number' => $_POST['plate_number'] ?? null,
                'color' => $_POST['color'] ?? null,
                'transmission' => $_POST['transmission'] ?? null
            ];
            
            // Remove null values
            $data = array_filter($data, function($value) {
                return $value !== null;
            });
            
            $result = $pmsHandler->updatePMSRecord($pmsId, $data);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log("PMS API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

