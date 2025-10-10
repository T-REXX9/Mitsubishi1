<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(dirname(__DIR__)) . '/includes/init.php';
require_once dirname(__DIR__) . '/api/notification_api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get database connection
    $connect = $GLOBALS['pdo'] ?? null;
    
    if (!$connect) {
        throw new Exception('Database connection not available');
    }
    
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
            !empty($input['discount_amount']) ? $input['discount_amount'] : 0,
            $input['total_price'],
            $input['payment_method'],
            !empty($input['down_payment']) ? $input['down_payment'] : null,
            !empty($input['financing_term']) ? $input['financing_term'] : null,
            !empty($input['monthly_payment']) ? $input['monthly_payment'] : null,
            $input['order_status'],
            !empty($input['delivery_date']) ? $input['delivery_date'] : null,
            !empty($input['actual_delivery_date']) ? $input['actual_delivery_date'] : null,
            !empty($input['delivery_address']) ? $input['delivery_address'] : null,
            !empty($input['order_notes']) ? $input['order_notes'] : null,
            !empty($input['special_instructions']) ? $input['special_instructions'] : null,
            !empty($input['warranty_package']) ? $input['warranty_package'] : null,
            !empty($input['insurance_details']) ? $input['insurance_details'] : null
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
        
        // Create customer information - assign to current sales agent
        $stmt = $connect->prepare("
            INSERT INTO customer_information (
                account_id, agent_id, lastname, firstname, middlename, suffix, nationality,
                birthday, age, gender, civil_status, mobile_number, complete_address,
                employment_status, company_name, position, monthly_income,
                valid_id_type, valid_id_image, valid_id_number, Status, customer_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', 'Walk In')
        ");
        
        $stmt->execute([
            $account_id,
            $sales_agent_id, // Assign walk-in customer to current sales agent
            !empty($input['manual_lastname']) ? $input['manual_lastname'] : 'N/A',
            !empty($input['manual_firstname']) ? $input['manual_firstname'] : 'N/A',
            !empty($input['manual_middlename']) ? $input['manual_middlename'] : null,
            !empty($input['manual_suffix']) ? $input['manual_suffix'] : null,
            !empty($input['manual_nationality']) ? $input['manual_nationality'] : 'Filipino',
            $input['manual_birthday'],
            $age,
            !empty($input['manual_gender']) ? $input['manual_gender'] : 'N/A',
            !empty($input['manual_civil_status']) ? $input['manual_civil_status'] : 'N/A',
            !empty($input['manual_mobile']) ? $input['manual_mobile'] : 'N/A',
            !empty($input['manual_address']) ? $input['manual_address'] : 'N/A',
            !empty($input['manual_employment']) ? $input['manual_employment'] : 'N/A',
            !empty($input['manual_company']) ? $input['manual_company'] : 'N/A',
            !empty($input['manual_position']) ? $input['manual_position'] : 'N/A',
            !empty($input['manual_income']) ? $input['manual_income'] : null,
            !empty($input['manual_valid_id']) ? $input['manual_valid_id'] : 'N/A',
            'N/A', // valid_id_image - not collected in walk-in form
            !empty($input['manual_valid_id_number']) ? $input['manual_valid_id_number'] : 'N/A'
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
            !empty($input['discount_amount']) ? $input['discount_amount'] : 0,
            $input['total_price'],
            $input['payment_method'],
            !empty($input['down_payment']) ? $input['down_payment'] : null,
            !empty($input['financing_term']) ? $input['financing_term'] : null,
            !empty($input['monthly_payment']) ? $input['monthly_payment'] : null,
            $input['order_status'],
            !empty($input['delivery_date']) ? $input['delivery_date'] : null,
            !empty($input['actual_delivery_date']) ? $input['actual_delivery_date'] : null,
            !empty($input['delivery_address']) ? $input['delivery_address'] : null,
            !empty($input['order_notes']) ? $input['order_notes'] : null,
            !empty($input['special_instructions']) ? $input['special_instructions'] : null,
            !empty($input['warranty_package']) ? $input['warranty_package'] : null,
            !empty($input['insurance_details']) ? $input['insurance_details'] : null
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

/**
 * Generate payment schedule for financing orders
 */
function generatePaymentSchedule($order_id, $customer_id, $total_price, $down_payment, $financing_term, $monthly_payment, $order_date = null)
{
    global $connect;
    
    if (!$financing_term || !$monthly_payment) {
        return false;
    }
    
    try {
        // Extract number of months from financing term (e.g., "36 months" -> 36)
        $months = (int) filter_var($financing_term, FILTER_SANITIZE_NUMBER_INT);
        
        if ($months <= 0) {
            return false;
        }
        
        // Calculate loan amount (total price minus down payment)
        $loan_amount = $total_price - ($down_payment ?? 0);
        
        // Use order date or current date as starting point
        $start_date = $order_date ? new DateTime($order_date) : new DateTime();
        
        // Generate payment schedule
        for ($i = 1; $i <= $months; $i++) {
            // Calculate due date (first payment due 30 days after order)
            $due_date = clone $start_date;
            $due_date->add(new DateInterval('P' . ($i * 30) . 'D'));
            
            // Insert payment schedule record
            $sql = "INSERT INTO payment_schedule (
                        order_id, customer_id, payment_number, due_date, 
                        amount_due, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
                        
            $stmt = $connect->prepare($sql);
            $stmt->execute([
                $order_id,
                $customer_id,
                $i,
                $due_date->format('Y-m-d'),
                $monthly_payment
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Payment schedule generation failed: " . $e->getMessage());
        return false;
    }
}
?>
