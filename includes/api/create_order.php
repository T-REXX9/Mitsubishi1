<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(dirname(__DIR__)) . '/includes/database/db_conn.php';
require_once dirname(__DIR__) . '/api/notification_api.php';
require_once dirname(__DIR__) . '/backend/order_backend.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $requiredFields = [
        'client_type', 'vehicle_model', 'vehicle_variant', 'vehicle_color',
        'model_year', 'base_price', 'total_price', 'payment_method', 'order_status'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Get sales agent ID from session
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'SalesAgent') {
        throw new Exception('Unauthorized: Invalid sales agent session');
    }
    
    $sales_agent_id = $_SESSION['user_id'];
    $customer_id = null;
    $account_id = null;
    $order_id = null;
    $order_number = null;
    
    // Handle different client types
    if ($input['client_type'] === 'handled') {
        // For handled clients, no transaction needed - just verify customer exists
        if (!isset($input['customer_id']) || empty($input['customer_id'])) {
            throw new Exception('Customer ID is required for handled clients');
        }
        
        $customer_id = $input['customer_id'];
        
        // Verify customer exists
        $stmt = $connect->prepare("SELECT cusID, account_id FROM customer_information WHERE cusID = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception('Customer not found');
        }
        
        $account_id = $customer['account_id'];
        
        // Generate order number if not provided
        $order_number = $input['order_number'] ?? generateOrderNumber();
        
        // Create the order directly (no transaction needed for handled clients)
        $stmt = $connect->prepare("
            INSERT INTO orders (
                order_number, customer_id, sales_agent_id, vehicle_id, client_type,
                vehicle_model, vehicle_variant, vehicle_color, model_year,
                base_price, discount_amount, total_price, payment_method,
                down_payment, financing_term, monthly_payment, order_status,
                delivery_date, actual_delivery_date, delivery_address,
                order_notes, special_instructions, warranty_package, insurance_details,
                created_at, order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $order_number,
            $customer_id,
            $sales_agent_id,
            $input['selected_vehicle_id'] ?? 0,
            $input['client_type'],
            $input['vehicle_model'],
            $input['vehicle_variant'],
            $input['vehicle_color'],
            $input['model_year'],
            $input['base_price'],
            $input['discount_amount'] ?? 0,
            $input['total_price'],
            $input['payment_method'],
            $input['down_payment'] ?? null,
            $input['financing_term'] ?? null,
            $input['monthly_payment'] ?? null,
            $input['order_status'],
            !empty($input['delivery_date']) ? $input['delivery_date'] : null,
            !empty($input['actual_delivery_date']) ? $input['actual_delivery_date'] : null,
            $input['delivery_address'] ?? null,
            $input['order_notes'] ?? null,
            $input['special_instructions'] ?? null,
            $input['warranty_package'] ?? null,
            $input['insurance_details'] ?? null
        ]);
        
        $order_id = $connect->lastInsertId();
        
        // Generate payment schedule for financing orders
        if ($input['payment_method'] === 'financing' && !empty($input['financing_term']) && !empty($input['monthly_payment'])) {
            generatePaymentSchedule(
                $order_id,
                $customer_id,
                $input['total_price'],
                $input['down_payment'] ?? 0,
                $input['financing_term'],
                $input['monthly_payment']
            );
        }
        
    } else if ($input['client_type'] === 'walkin') {
        // For walk-in clients, use transaction for multiple table inserts
        
        // Validate walk-in required fields
        $walkinRequired = ['manual_firstname', 'manual_lastname', 'manual_mobile', 'manual_birthday'];
        foreach ($walkinRequired as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field for walk-in: $field");
            }
        }
        
        // Start transaction for walk-in clients only
        $connect->beginTransaction();
        
        // Generate unique username and email for walk-in customer
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $username = 'walkin_' . $timestamp . '_' . $random;
        $email = $input['manual_email'] ?: ('walkin_' . $timestamp . '@temp.mitsubishi.com');
        
        // Create account first
        $stmt = $connect->prepare("
            INSERT INTO accounts (Username, Email, Role, FirstName, LastName, DateOfBirth, Status, CreatedAt) 
            VALUES (?, ?, 'Customer', ?, ?, ?, 'Approved', NOW())
        ");
        
        $stmt->execute([
            $username,
            $email,
            $input['manual_firstname'],
            $input['manual_lastname'],
            $input['manual_birthday']
        ]);
        
        $account_id = $connect->lastInsertId();
        
        // Calculate age from birthday
        $birthday = new DateTime($input['manual_birthday']);
        $today = new DateTime();
        $age = $today->diff($birthday)->y;
        
        // Create customer information
        $stmt = $connect->prepare("
            INSERT INTO customer_information (
                account_id, lastname, firstname, middlename, suffix, nationality,
                birthday, age, gender, civil_status, mobile_number, employment_status,
                company_name, position, monthly_income, valid_id_type, valid_id_number,
                Status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', NOW())
        ");
        
        $stmt->execute([
            $account_id,
            $input['manual_lastname'],
            $input['manual_firstname'],
            $input['manual_middlename'] ?? null,
            $input['manual_suffix'] ?? null,
            $input['manual_nationality'] ?? 'Filipino',
            $input['manual_birthday'],
            $age,
            $input['manual_gender'] ?? null,
            $input['manual_civil_status'] ?? null,
            $input['manual_mobile'],
            $input['manual_employment'] ?? null,
            $input['manual_company'] ?? null,
            $input['manual_position'] ?? null,
            $input['manual_income'] ?? null,
            $input['manual_valid_id'] ?? null,
            $input['manual_valid_id_number'] ?? null
        ]);
        
        $customer_id = $connect->lastInsertId();
        
        // Generate order number if not provided
        $order_number = $input['order_number'] ?? generateOrderNumber();
        
        // Create the order
        $stmt = $connect->prepare("
            INSERT INTO orders (
                order_number, customer_id, sales_agent_id, vehicle_id, client_type,
                vehicle_model, vehicle_variant, vehicle_color, model_year,
                base_price, discount_amount, total_price, payment_method,
                down_payment, financing_term, monthly_payment, order_status,
                delivery_date, actual_delivery_date, delivery_address,
                order_notes, special_instructions, warranty_package, insurance_details,
                created_at, order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $order_number,
            $customer_id,
            $sales_agent_id,
            $input['selected_vehicle_id'] ?? 0,
            $input['client_type'],
            $input['vehicle_model'],
            $input['vehicle_variant'],
            $input['vehicle_color'],
            $input['model_year'],
            $input['base_price'],
            $input['discount_amount'] ?? 0,
            $input['total_price'],
            $input['payment_method'],
            $input['down_payment'] ?? null,
            $input['financing_term'] ?? null,
            $input['monthly_payment'] ?? null,
            $input['order_status'],
            !empty($input['delivery_date']) ? $input['delivery_date'] : null,
            !empty($input['actual_delivery_date']) ? $input['actual_delivery_date'] : null,
            $input['delivery_address'] ?? null,
            $input['order_notes'] ?? null,
            $input['special_instructions'] ?? null,
            $input['warranty_package'] ?? null,
            $input['insurance_details'] ?? null
        ]);
        
        $order_id = $connect->lastInsertId();
        
        // Generate payment schedule for financing orders
        if ($input['payment_method'] === 'financing' && !empty($input['financing_term']) && !empty($input['monthly_payment'])) {
            generatePaymentSchedule(
                $order_id,
                $customer_id,
                $input['total_price'],
                $input['down_payment'] ?? 0,
                $input['financing_term'],
                $input['monthly_payment']
            );
        }
        
        // Commit transaction for walk-in clients
        $connect->commit();
        
    } else {
        throw new Exception('Invalid client type');
    }

    // --- Notification Logic ---
    // Notify the customer (if account_id is available)
    if ($account_id) {
        createNotification($account_id, null, 'Order Placed', 'Your order #' . $order_number . ' has been placed successfully.', 'order', $order_id);
    }
    // Notify all admins (role-based notification)
    createNotification(null, 'Admin', 'New Order Placed', 'A new order #' . $order_number . ' has been placed by a customer.', 'order', $order_id);
    // Notify the sales agent (self notification)
    createNotification($sales_agent_id, null, 'Order Created', 'You have created order #' . $order_number . ' for a customer.', 'order', $order_id);
    // --- End Notification Logic ---

    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'data' => [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'customer_id' => $customer_id,
            'account_id' => $account_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error (only for walk-in clients)
    if ($connect->inTransaction()) {
        $connect->rollback();
    }
    
    error_log("Order creation error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generateOrderNumber() {
    $now = new DateTime();
    $year = $now->format('y');
    $month = $now->format('m');
    $day = $now->format('d');
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return "ORD-{$year}{$month}{$day}{$random}";
}
?>
