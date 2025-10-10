<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

header('Content-Type: application/json');

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(dirname(dirname(__DIR__)) . '/includes/init.php');
require_once(dirname(dirname(__DIR__)) . '/includes/api/notification_api.php');

// Check if user is Admin or Sales Agent for inquiry responses
if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'No user role found in session. Please log in again.',
        'error_code' => 'NO_ROLE'
    ]);
    exit();
}

if (!in_array($_SESSION['user_role'], ['Admin', 'SalesAgent'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Access denied for role: ' . $_SESSION['user_role'],
        'error_code' => 'INVALID_ROLE'
    ]);
    exit();
}

// Use the database connection from init.php
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

// Determine the action
$action = '';
$input = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    } else {
        // Check if it's JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['action'])) {
            $action = $input['action'];
        }
    }
}

try {
    switch ($action) {
        case 'delete':
            handleDeleteInquiry($pdo, $input);
            break;
        
        case 'respond':
            handleRespondToInquiry($pdo, $_POST);
            break;
            
        case 'get_inquiry':
            handleGetInquiry($pdo, $_GET);
            break;
            
        case 'create':
            handleCreateInquiry($pdo, $_POST);
            break;
            
        default:
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Default to respond action for form submissions
                handleRespondToInquiry($pdo, $_POST);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
    }
} catch (Exception $e) {
    error_log("Inquiry action error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleDeleteInquiry($pdo, $input) {
    if (!isset($input['inquiry_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inquiry ID is required']);
        return;
    }

    $inquiryId = (int)$input['inquiry_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE Id = ?");
        $result = $stmt->execute([$inquiryId]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Inquiry deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found or could not be deleted']);
        }
    } catch (PDOException $e) {
        error_log("Delete inquiry error: " . $e->getMessage());
        throw new Exception('Failed to delete inquiry');
    }
}

function handleRespondToInquiry($pdo, $data) {
    // Validate required fields
    if (!isset($data['inquiry_id'], $data['response_type'], $data['response_message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }

    $inquiryId = (int)$data['inquiry_id'];
    $responseType = trim($data['response_type']);
    $responseMessage = trim($data['response_message']);
    $followUpDate = !empty($data['follow_up_date']) ? $data['follow_up_date'] : null;

    // Validate message length
    if (strlen($responseMessage) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Response message too short (minimum 10 characters)']);
        return;
    }

    try {
        // Get the inquiry details
        $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE Id = ?");
        $stmt->execute([$inquiryId]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inquiry) {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
            return;
        }

        // Create inquiry_responses table if it doesn't exist (simplified version without foreign keys)
        $createResponseTable = "CREATE TABLE IF NOT EXISTS inquiry_responses (
            Id INT PRIMARY KEY AUTO_INCREMENT,
            InquiryId INT NOT NULL,
            ResponseType VARCHAR(100) NOT NULL,
            ResponseMessage TEXT NOT NULL,
            FollowUpDate DATE NULL,
            RespondedBy INT NOT NULL,
            ResponseDate DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createResponseTable);

        // Insert the response
        $stmt = $pdo->prepare("
            INSERT INTO inquiry_responses (InquiryId, ResponseType, ResponseMessage, FollowUpDate, RespondedBy) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $inquiryId,
            $responseType,
            $responseMessage,
            $followUpDate,
            $_SESSION['user_id']
        ]);

        if ($result) {
            // Try to log the action, but don't fail if it doesn't work
            try {
                logAdminAction($pdo, $_SESSION['user_id'], 'RESPOND_INQUIRY', $inquiryId, 
                    "Responded to inquiry from {$inquiry['FullName']} via {$responseType}");
            } catch (Exception $logError) {
                error_log("Failed to log admin action: " . $logError->getMessage());
            }
            // Enhanced notification logic: Notify customer and optionally admins/sales agents
            if (!empty($inquiry['AccountId'])) {
                createNotification($inquiry['AccountId'], null, 'Inquiry Response', "Your inquiry (ID: $inquiryId) has been responded to. Please check your account for details.", 'inquiry', $inquiryId);
            }
            // Optionally notify Admins and SalesAgents for tracking (extensible for multi-channel)
            createNotification(null, 'Admin', 'Inquiry Responded', "Inquiry (ID: $inquiryId) has been responded to by {$_SESSION['user_id']}.", 'inquiry', $inquiryId);
            createNotification(null, 'SalesAgent', 'Inquiry Responded', "Inquiry (ID: $inquiryId) has been responded to by {$_SESSION['user_id']}.", 'inquiry', $inquiryId);
            // Placeholder for future: queueNotificationForChannels(...)
            echo json_encode([
                'success' => true, 
                'message' => 'Response sent successfully',
                'response_id' => $pdo->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to save response');
        }
    } catch (PDOException $e) {
        error_log("Respond to inquiry error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General error in handleRespondToInquiry: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}

function handleGetInquiry($pdo, $data) {
    if (!isset($data['inquiry_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inquiry ID is required']);
        return;
    }

    $inquiryId = (int)$data['inquiry_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                a.Username,
                a.FirstName,
                a.LastName,
                a.Status as AccountStatus
            FROM inquiries i
            LEFT JOIN accounts a ON i.AccountId = a.Id
            WHERE i.Id = ?
        ");
        $stmt->execute([$inquiryId]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inquiry) {
            // Get responses for this inquiry
            $stmt = $pdo->prepare("
                SELECT 
                    ir.*,
                    a.Username as ResponderUsername,
                    a.FirstName as ResponderFirstName,
                    a.LastName as ResponderLastName
                FROM inquiry_responses ir
                LEFT JOIN accounts a ON ir.RespondedBy = a.Id
                WHERE ir.InquiryId = ?
                ORDER BY ir.ResponseDate DESC
            ");
            $stmt->execute([$inquiryId]);
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $inquiry['responses'] = $responses;

            echo json_encode(['success' => true, 'inquiry' => $inquiry]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
        }
    } catch (PDOException $e) {
        error_log("Get inquiry error: " . $e->getMessage());
        throw new Exception('Failed to get inquiry details');
    }
}

function handleCreateInquiry($pdo, $data) {
    $requiredFields = ['full_name', 'email', 'vehicle_model', 'vehicle_year', 'vehicle_color'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO inquiries 
            (AccountId, FullName, Email, PhoneNumber, VehicleModel, VehicleVariant, 
             VehicleYear, VehicleColor, TradeInVehicleDetails, FinancingRequired, Comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['account_id'] ?? null,
            trim($data['full_name']),
            trim($data['email']),
            trim($data['phone_number'] ?? ''),
            trim($data['vehicle_model']),
            trim($data['vehicle_variant'] ?? ''),
            (int)$data['vehicle_year'],
            trim($data['vehicle_color']),
            trim($data['trade_in_details'] ?? ''),
            trim($data['financing_required'] ?? ''),
            trim($data['comments'] ?? '')
        ]);

        if ($result) {
            $inquiryId = $pdo->lastInsertId();
            logAdminAction($pdo, $_SESSION['user_id'], 'CREATE_INQUIRY', $inquiryId, 
                "Created inquiry for {$data['full_name']} - {$data['vehicle_model']}");
            // Enhanced notification logic: multi-channel ready, extensible
            createNotification(null, 'Admin', 'New Inquiry Submitted', "A new inquiry (ID: $inquiryId) was submitted by {$data['full_name']}.", 'inquiry', $inquiryId);
            createNotification(null, 'SalesAgent', 'New Inquiry Submitted', "A new inquiry (ID: $inquiryId) was submitted by {$data['full_name']}.", 'inquiry', $inquiryId);
            if (!empty($data['account_id'])) {
                createNotification($data['account_id'], null, 'Inquiry Submitted', "Your inquiry (ID: $inquiryId) has been received. We will contact you soon.", 'inquiry', $inquiryId);
            }
            // Placeholder for future: queueNotificationForChannels(...)
            echo json_encode([
                'success' => true, 
                'message' => 'Inquiry created successfully',
                'inquiry_id' => $inquiryId
            ]);
        } else {
            throw new Exception('Failed to create inquiry');
        }
    } catch (PDOException $e) {
        error_log("Create inquiry error: " . $e->getMessage());
        throw new Exception('Failed to create inquiry');
    }
}

function logAdminAction($pdo, $adminId, $actionType, $targetId, $description) {
    try {
        // Create admin_actions table if it doesn't exist (simplified version without foreign keys)
        $createTable = "CREATE TABLE IF NOT EXISTS admin_actions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action_type VARCHAR(100) NOT NULL,
            target_id INT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);

        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_id, description) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $actionType, $targetId, $description]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Log admin action error: " . $e->getMessage());
        // Don't throw exception here as it's just logging
        return false;
    } catch (Exception $e) {
        error_log("General error in logAdminAction: " . $e->getMessage());
        return false;
    }
}
?>
