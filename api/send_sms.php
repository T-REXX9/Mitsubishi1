<?php
// Minimal, production-ready API endpoint for sending SMS via PhilSMS

header('Content-Type: application/json; charset=utf-8');

// Initialize session and database connection
require_once __DIR__ . '/../includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'response' => null,
        'error' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'User';

// Get user name from database
$user_name = 'User';
try {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT FirstName, LastName, Username FROM accounts WHERE Id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            if (!empty($user_data['FirstName']) && !empty($user_data['LastName'])) {
                $user_name = $user_data['FirstName'] . ' ' . $user_data['LastName'];
            } elseif (!empty($user_data['FirstName'])) {
                $user_name = $user_data['FirstName'];
            } else {
                $user_name = $user_data['Username'];
            }
        }
    }
} catch (Exception $e) {
    error_log('[SMS DEBUG] Error fetching user name: ' . $e->getMessage());
}

// [SMS DEBUG] Log request method and input
error_log('[SMS DEBUG] Incoming request: method=' . $_SERVER['REQUEST_METHOD'] . ' | POST=' . json_encode($_POST)); // DEBUG LOG

// [SMS DEBUG] Log raw POST data (for JSON requests)
$raw_post_data = file_get_contents('php://input');
error_log('[SMS DEBUG] Raw POST body: ' . $raw_post_data); // DEBUG LOG

// Parse JSON if Content-Type is application/json
$input_data = null;
if (
    isset($_SERVER['CONTENT_TYPE']) &&
    stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) {
    $input_data = json_decode($raw_post_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($input_data)) {
        $_POST = $input_data;
    } else {
        error_log('[SMS DEBUG] Failed to decode JSON body: ' . json_last_error_msg()); // DEBUG LOG
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('[SMS DEBUG] Invalid request method: ' . $_SERVER['REQUEST_METHOD']); // DEBUG LOG
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'response' => null,
        'error' => 'Method Not Allowed'
    ]);
    exit;
}

require_once __DIR__ . '/../includes/backend/PhilSmsSender.php';

/**
 * Normalize a phone number for PH SMS sending.
 * - Trims, removes all spaces, allows only leading '+', strips other non-digits.
 * - Returns normalized string.
 */
function normalize_ph_number($number) {
    $number = trim($number);
    // Remove all spaces and non-digit chars except leading +
    $number = preg_replace('/[^\d+]/', '', $number);
    // Only allow + at the start, remove any other +
    if (strpos($number, '+') > 0) {
        $number = str_replace('+', '', $number);
    }
    // If multiple +, keep only the first if at start
    if (substr($number, 0, 1) !== '+') {
        $number = ltrim($number, '+');
    }
    return $number;
}

/**
 * Validate PH mobile number in E.164, international, or local format.
 * Accepts:
 *   - +639XXXXXXXXX (E.164)
 *   - 639XXXXXXXXX  (International)
 *   - 09XXXXXXXXX   (Local)
 */
function is_valid_ph_number($number) {
    // Already normalized, so just match
    return preg_match('/^(\+639\d{9}|639\d{9}|09\d{9})$/', $number);
}

// Sanitize and validate input
$numbers = $_POST['numbers'] ?? null;
$message = $_POST['message'] ?? null;
$senderName = isset($_POST['senderName']) ? trim($_POST['senderName']) : null;

// [SMS DEBUG] Parsed/decoded input types and values
error_log('[SMS DEBUG] Parsed input: numbers type=' . (is_array($numbers) ? 'array' : gettype($numbers)) . ' | value=' . json_encode($numbers) . ' | message type=' . gettype($message) . ' | value=' . json_encode($message)); // DEBUG LOG

// [SMS DEBUG] Raw input values
error_log('[SMS DEBUG] Raw input: numbers=' . json_encode($numbers) . ' | message=' . json_encode($message) . ' | senderName=' . json_encode($senderName)); // DEBUG LOG

$error = null;
$cleanNumbers = [];

// Normalize and validate numbers
$rawNumbers = $numbers;
$normalizedNumbers = [];
$validNumbers = [];

if (is_array($numbers)) {
    error_log('[SMS DEBUG] numbers is array, proceeding as array'); // DEBUG LOG
    $inputNumbers = $numbers;
} elseif (is_string($numbers)) {
    error_log('[SMS DEBUG] numbers is string, will split'); // DEBUG LOG
    // Accept comma/space separated or single number
    $inputNumbers = preg_split('/[\s,]+/', $numbers, -1, PREG_SPLIT_NO_EMPTY);
} else {
    error_log('[SMS DEBUG] numbers is neither array nor string, treating as empty'); // DEBUG LOG
    $inputNumbers = [];
}

// Normalize all numbers
foreach ($inputNumbers as $num) {
    $norm = normalize_ph_number($num);
    if ($norm !== '') {
        // Convert to E.164 +63XXXXXXXXXX (PH) for PhilSMS
        if (preg_match('/^\+639\d{9}$/', $norm)) {
            // already in E.164 format
        } elseif (preg_match('/^639\d{9}$/', $norm)) {
            $norm = '+' . $norm;
        } elseif (preg_match('/^09\d{9}$/', $norm)) {
            $norm = '+63' . substr($norm, 1);
        }
        $normalizedNumbers[] = $norm;
        if (is_valid_ph_number($norm)) {
            $validNumbers[] = $norm;
        }
    }
}

// [SMS DEBUG] Log normalized and valid numbers
error_log('[SMS DEBUG] Normalized numbers: ' . json_encode($normalizedNumbers)); // DEBUG LOG
error_log('[SMS DEBUG] Valid numbers: ' . json_encode($validNumbers)); // DEBUG LOG

$cleanNumbers = $validNumbers;

// [SMS DEBUG] Cleaned numbers:
error_log('[SMS DEBUG] Cleaned numbers: ' . json_encode($cleanNumbers)); // DEBUG LOG

if (empty($cleanNumbers)) {
    $error = 'At least one valid recipient number is required.';
    error_log('[SMS DEBUG] Validation failed: No valid recipient number'); // DEBUG LOG
}

// Validate message
if ($error === null) {
    $message = is_string($message) ? trim($message) : '';
    if ($message === '') {
        $error = 'Message is required.';
        error_log('[SMS DEBUG] Validation failed: Message is required'); // DEBUG LOG
    } elseif (mb_strlen($message) > 1000) {
        // PhilSMS supports long/segmented messages; set a safe upper bound
        $error = 'Message exceeds maximum allowed length (1000 characters).';
        error_log('[SMS DEBUG] Validation failed: Message too long'); // DEBUG LOG
    }
}

// Validate senderName (optional, 3-11 chars, alphanumeric)
if ($error === null && $senderName !== null && $senderName !== '') {
    if (!preg_match('/^[a-zA-Z0-9]{3,11}$/', $senderName)) {
        $error = 'Sender name must be 3-11 alphanumeric characters.';
        error_log('[SMS DEBUG] Validation failed: Invalid senderName'); // DEBUG LOG
    }
    // If empty after trim, treat as null
    if ($senderName === '') {
        $senderName = null;
    }
}

if ($error !== null) {
    error_log('[SMS DEBUG] Input validation error: ' . $error); // DEBUG LOG
    echo json_encode([
        'success' => false,
        'response' => null,
        'error' => $error
    ]);
    exit;
}

// Call backend
error_log('[SMS DEBUG] Calling PhilSmsSender::sendSms'); // DEBUG LOG
$result = PhilSmsSender::sendSms($cleanNumbers, $message, $senderName);

// Output only JSON
error_log('[SMS DEBUG] SMS send result: ' . json_encode($result)); // DEBUG LOG

// Log SMS to database
try {
    if ($pdo) {
        // Calculate message metadata
        $message_length = mb_strlen($message);
        $is_unicode = !preg_match('/^[\x00-\x7F]*$/', $message); // Check if contains non-ASCII

        // Calculate segments (GSM-7: 160 chars single, 153 concat; Unicode: 70 single, 67 concat)
        if ($is_unicode) {
            $segment_count = $message_length <= 70 ? 1 : ceil($message_length / 67);
        } else {
            $segment_count = $message_length <= 160 ? 1 : ceil($message_length / 153);
        }

        // Determine status and delivery status
        $status = $result['success'] ? 'sent' : 'failed';
        $delivery_status = $result['success'] ? 'pending' : 'failed';

        // Extract API response data
        $api_response = isset($result['response']) ? json_encode($result['response']) : null;
        $api_message_id = null;
        if ($result['success'] && isset($result['response']['data']['id'])) {
            $api_message_id = $result['response']['data']['id'];
        }

        $error_message = $result['error'] ?? null;

        // Resolve sender ID name
        $sender_id_name = $senderName ?? 'PhilSMS';

        // Log each recipient separately
        foreach ($cleanNumbers as $recipient) {
            $log_sql = "INSERT INTO sms_logs (
                sender_id, sender_name, recipient, message,
                message_length, segment_count, is_unicode,
                sender_id_name, provider, status, delivery_status,
                api_response, api_message_id, error_message, sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $pdo->prepare($log_sql);
            $stmt->execute([
                $user_id,
                $user_name,
                $recipient,
                $message,
                $message_length,
                $segment_count,
                $is_unicode ? 1 : 0,
                $sender_id_name,
                'PhilSMS',
                $status,
                $delivery_status,
                $api_response,
                $api_message_id,
                $error_message
            ]);

            error_log('[SMS DEBUG] SMS logged to database for recipient: ' . $recipient);
        }
    }
} catch (Exception $e) {
    error_log('[SMS DEBUG] Error logging SMS to database: ' . $e->getMessage());
    // Don't fail the request if logging fails
}

echo json_encode([
    'success' => $result['success'],
    'response' => $result['response'],
    'error' => $result['error']
]);
exit;