<?php
/**
 * DeepSeek AI-Powered Chatbot with Role-Aware Logic
 * Mitsubishi Motors Dealership System
 * 
 * This service provides intelligent responses based on user roles:
 * - Customer: Vehicle info, pricing, order status
 * - Sales Agent: Customer data, sales performance, inventory
 * - Admin: Full access to all data and analytics
 */

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once(dirname(__DIR__) . '/database/db_conn.php');

// Get PDO connection
$pdo = $connect ?? null;
if (!$pdo) {
    throw new Exception('Database connection not available');
}

class DeepSeekChatbot {
    private $pdo;
    private $apiKey;
    private $apiEndpoint = 'https://api.deepseek.com/chat/completions';
    private $userRole;
    private $userId;

    public function __construct($pdo = null) {
        global $connect;
        $this->pdo = $pdo ?: $connect;
        if (!$this->pdo) {
            throw new Exception('Database connection not available');
        }
        $this->loadApiKey();
        $this->initializeSession();
    }

    private function loadApiKey() {
        // Hardcoded API key
        $this->apiKey = 'sk-27e6623100404762826fc0d41454bfff';
    }

    private function initializeSession() {
        $this->userRole = $_SESSION['user_role'] ?? 'Guest';
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // Validate user session
        if (!$this->userId) {
            throw new Exception('User session not found. Please log in.');
        }
    }

    /**
     * Get role-specific system prompt
     */
    private function getSystemPrompt() {
        $baseInfo = "You are a helpful Mitsubishi Motors dealership assistant. ";
        $currentDate = date('Y-m-d H:i:s');
        
        switch ($this->userRole) {
            case 'Customer':
                return $baseInfo . "You are assisting a customer. Only provide information suitable for customers, such as:
- Vehicle availability, specifications, and pricing
- Ongoing promotions and special offers  
- Status of their own orders and deliveries
- General dealership information and services
- Test drive scheduling assistance
Do NOT reveal dealership-internal data, other customers' information, sales agent performance, or admin-only data.
Be friendly, helpful, and focus on helping them find the right vehicle for their needs.
Current date/time: {$currentDate}";

            case 'SalesAgent':
                return $baseInfo . "You are assisting a sales agent. Provide information to help them serve customers better:
- Information about their assigned customers
- Their own sales performance and metrics
- Product availability and specifications
- Order statuses for their customers
- Customer inquiry and communication history
- Inventory levels and vehicle details
Do NOT reveal other agents' private data, sensitive admin information, or company financial details beyond their scope.
Be professional and focus on helping them achieve their sales goals.
Current date/time: {$currentDate}";

            case 'Admin':
                return $baseInfo . "You are assisting an administrator with full system access. Provide comprehensive insights about:
- All sales data, performance metrics, and analytics
- Complete customer and agent information
- Inventory management and stock levels
- Financial reports and revenue analysis
- System-wide statistics and trends
- Operational insights and recommendations
You have unrestricted access to provide any dealership-related information requested.
Be detailed, analytical, and focus on business insights and decision support.
Current date/time: {$currentDate}";

            default:
                return $baseInfo . "You are assisting a guest user. Provide only general, publicly available information about:
- Vehicle models and basic specifications
- General dealership services
- Contact information and location
- General automotive advice
Do not access or provide any customer, agent, or internal data.
Current date/time: {$currentDate}";
        }
    }

    /**
     * Execute role-based database queries
     */
    private function executeQuery($userMessage) {
        $query = strtolower($userMessage);
        $data = [];

        try {
            // Vehicle availability and pricing queries (all roles)
            if (preg_match('/\b(montero|pajero|mirage|outlander|xpander|strada|lancer|evo|eclipse)\b/i', $query) ||
                preg_match('/\b(available|stock|price|cost)\b/i', $query)) {
                $data['vehicles'] = $this->getVehicleData($query);
            }

            // Role-specific queries
            switch ($this->userRole) {
                case 'Customer':
                    if (preg_match('/\b(my order|order status|delivery)\b/i', $query)) {
                        $data['orders'] = $this->getCustomerOrders();
                    }
                    break;

                case 'SalesAgent':
                    if (preg_match('/\b(my sales|performance|customers|sold)\b/i', $query)) {
                        $data['sales_performance'] = $this->getAgentPerformance();
                    }
                    if (preg_match('/\b(my customers|assigned)\b/i', $query)) {
                        $data['assigned_customers'] = $this->getAssignedCustomers();
                    }
                    break;

                case 'Admin':
                    if (preg_match('/\b(top selling|best|revenue|total sales)\b/i', $query)) {
                        $data['sales_analytics'] = $this->getSalesAnalytics();
                    }
                    if (preg_match('/\b(agents|agent performance|team)\b/i', $query)) {
                        $data['agent_analytics'] = $this->getAgentAnalytics();
                    }
                    if (preg_match('/\b(inventory|stock levels)\b/i', $query)) {
                        $data['inventory'] = $this->getInventoryData();
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Get vehicle data based on query
     */
    private function getVehicleData($query) {
        $sql = "SELECT model_name, variant, year_model, category, base_price, promotional_price, 
                       stock_quantity, availability_status, popular_color 
                FROM vehicles 
                WHERE availability_status = 'available'";
        
        // Add specific model filter if mentioned
        $models = ['montero', 'pajero', 'mirage', 'outlander', 'xpander', 'strada', 'lancer', 'eclipse'];
        foreach ($models as $model) {
            if (strpos($query, $model) !== false) {
                $sql .= " AND LOWER(model_name) LIKE '%" . $model . "%'";
                break;
            }
        }
        
        $sql .= " ORDER BY model_name, variant LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get customer's own orders (Customer role only)
     */
    private function getCustomerOrders() {
        $sql = "SELECT order_number, vehicle_model, vehicle_variant, vehicle_color, 
                       order_status, total_price, order_date, delivery_date 
                FROM orders 
                WHERE customer_id = ? 
                ORDER BY order_date DESC LIMIT 5";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get sales agent performance (SalesAgent role only)
     */
    private function getAgentPerformance() {
        $sql = "SELECT COUNT(o.id) as total_units, 
                       SUM(o.total_price) as total_sales,
                       AVG(o.total_price) as avg_order_value,
                       COUNT(CASE WHEN o.order_status = 'Completed' THEN 1 END) as completed_orders
                FROM orders o 
                WHERE o.sales_agent_id = ? 
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get assigned customers (SalesAgent role only)
     */
    private function getAssignedCustomers() {
        $sql = "SELECT DISTINCT c.FirstName, c.LastName, c.Email,
                       COUNT(o.id) as total_orders,
                       MAX(o.order_date) as last_order_date
                FROM accounts c
                LEFT JOIN orders o ON c.Id = o.customer_id AND o.sales_agent_id = ?
                WHERE c.Role = 'Customer'
                GROUP BY c.Id, c.FirstName, c.LastName, c.Email
                HAVING total_orders > 0
                ORDER BY last_order_date DESC LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get sales analytics (Admin role only)
     */
    private function getSalesAnalytics() {
        $sql = "SELECT vehicle_model, 
                       COUNT(*) as units_sold, 
                       SUM(total_price) as revenue,
                       AVG(total_price) as avg_price
                FROM orders 
                WHERE order_status IN ('Completed', 'Delivered')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                GROUP BY vehicle_model 
                ORDER BY units_sold DESC LIMIT 5";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get agent analytics (Admin role only)
     */
    private function getAgentAnalytics() {
        $sql = "SELECT a.FirstName, a.LastName,
                       COUNT(o.id) as total_sales,
                       SUM(o.total_price) as total_revenue,
                       AVG(o.total_price) as avg_order_value
                FROM accounts a
                LEFT JOIN orders o ON a.Id = o.sales_agent_id
                WHERE a.Role = 'SalesAgent'
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                GROUP BY a.Id, a.FirstName, a.LastName
                ORDER BY total_revenue DESC LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get inventory data (Admin role only)
     */
    private function getInventoryData() {
        $sql = "SELECT model_name, variant, stock_quantity, availability_status,
                       base_price, promotional_price
                FROM vehicles 
                ORDER BY stock_quantity ASC LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Call DeepSeek API
     */
    private function callDeepSeekAPI($userMessage, $contextData = []) {
        $systemPrompt = $this->getSystemPrompt();
        
        // Prepare context information
        $contextText = "";
        if (!empty($contextData)) {
            $contextText = "\n\nRelevant Data Context:\n" . json_encode($contextData, JSON_PRETTY_PRINT);
        }

        $messages = [
            [
                "role" => "system",
                "content" => $systemPrompt
            ],
            [
                "role" => "user", 
                "content" => $userMessage . $contextText
            ]
        ];

        $postData = [
            "model" => "deepseek-chat",
            "messages" => $messages,
            "temperature" => 0.7,
            "max_tokens" => 1500
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('DeepSeek API Error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('DeepSeek API returned status: ' . $httpCode);
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from DeepSeek API');
        }

        return $result['choices'][0]['message']['content'] ?? 'I apologize, but I couldn\'t process your request at this time.';
    }

    /**
     * Generate escalation message
     */
    private function getEscalationMessage() {
        switch ($this->userRole) {
            case 'Customer':
                return "I've forwarded your inquiry to one of our sales agents who will provide you with more detailed assistance shortly. They will have access to more specific information about your account and can help with specialized requests.";
            
            case 'SalesAgent':
                return "Your inquiry requires administrative review. I've escalated this to management who will provide you with the detailed information or assistance you need. An administrator will follow up with you soon.";
            
            default:
                return "For more detailed assistance, please contact our customer service team who can provide specialized support for your needs.";
        }
    }

    /**
     * Process chat message
     */
    public function processMessage($userMessage) {
        try {
            // Get relevant database context
            $contextData = $this->executeQuery($userMessage);
            
            // Call DeepSeek API
            $aiResponse = $this->callDeepSeekAPI($userMessage, $contextData);
            
            return [
                'success' => true,
                'response' => $aiResponse,
                'role' => $this->userRole,
                'escalation_available' => in_array($this->userRole, ['Customer', 'SalesAgent'])
            ];
            
        } catch (Exception $e) {
            error_log("DeepSeek Chatbot Error: " . $e->getMessage());
            
            // Fallback to escalation
            return [
                'success' => true,
                'response' => $this->getEscalationMessage(),
                'role' => $this->userRole,
                'escalation_triggered' => true,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';
        
        if (empty($userMessage)) {
            throw new Exception('Message cannot be empty');
        }

        $chatbot = new DeepSeekChatbot();
        $result = $chatbot->processMessage($userMessage);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
?>