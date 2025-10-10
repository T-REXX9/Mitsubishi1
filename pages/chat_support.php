<?php
// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Get existing conversation if any
$conversation = null;
$conversation_id = null;
$assignedAgent = null;

try {
    // Check for existing conversation - only get the most recent one
    $convQuery = $connect->prepare("
        SELECT c.*, a.FirstName, a.LastName, a.Username
        FROM conversations c
        LEFT JOIN accounts a ON c.agent_id = a.Id
        WHERE c.customer_id = ?
        ORDER BY c.created_at DESC
        LIMIT 1
    ");
    $convQuery->execute([$customer_id]);
    $conversation = $convQuery->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        $conversation_id = $conversation['conversation_id'];

        // Check if agent has actually sent any messages (meaning they've responded)
        if ($conversation['agent_id']) {
            $agentResponseQuery = $connect->prepare("
                SELECT COUNT(*) as agent_messages
                FROM messages 
                WHERE conversation_id = ? AND sender_type = 'SalesAgent' AND sender_id != 0
            ");
            $agentResponseQuery->execute([$conversation_id]);
            $agentResponse = $agentResponseQuery->fetch(PDO::FETCH_ASSOC);

            // Only show agent info if they have sent at least one message
            if ($agentResponse['agent_messages'] > 0) {
                $assignedAgent = $conversation;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error in chat_support.php: " . $e->getMessage());
}

// Database query functions for vehicle information
function getVehicleByModel($modelName) {
    global $connect;
    error_log("getVehicleByModel called with: " . $modelName);
    try {
        if (!$connect) {
            error_log("Database connection is null in getVehicleByModel");
            return [];
        }
        $stmt = $connect->prepare("SELECT * FROM vehicles WHERE model_name LIKE ? ORDER BY year_model DESC LIMIT 5");
        $searchTerm = '%' . $modelName . '%';
        $stmt->execute([$searchTerm]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("getVehicleByModel returned " . count($result) . " vehicles");
        return $result;
    } catch (Exception $e) {
        error_log("Database query error in getVehicleByModel: " . $e->getMessage());
        return [];
    }
}

function getAllVehicles() {
    global $connect;
    error_log("getAllVehicles called");
    try {
        if (!$connect) {
            error_log("Database connection is null in getAllVehicles");
            return [];
        }
        $stmt = $connect->prepare("SELECT model_name, variant, year_model, category, base_price, promotional_price, fuel_type, seating_capacity, availability_status FROM vehicles WHERE availability_status = 'available' ORDER BY model_name");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("getAllVehicles returned " . count($result) . " vehicles");
        return $result;
    } catch (Exception $e) {
        error_log("Database query error in getAllVehicles: " . $e->getMessage());
        return [];
    }
}

function getVehiclesByCategory($category) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM vehicles WHERE category LIKE ? AND availability_status = 'available' ORDER BY base_price");
        $searchTerm = '%' . $category . '%';
        $stmt->execute([$searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return [];
    }
}

function getVehiclesByPriceRange($minPrice, $maxPrice) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM vehicles WHERE (promotional_price BETWEEN ? AND ?) OR (promotional_price IS NULL AND base_price BETWEEN ? AND ?) AND availability_status = 'available' ORDER BY COALESCE(promotional_price, base_price)");
        $stmt->execute([$minPrice, $maxPrice, $minPrice, $maxPrice]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return [];
    }
}

function formatVehicleInfo($vehicles) {
    if (empty($vehicles)) {
        return "No vehicles found matching your criteria.";
    }
    
    $info = "Here are the vehicles currently in stock at Mitsubishi Motors: 🚗\n\n";
    foreach ($vehicles as $vehicle) {
        $price = $vehicle['promotional_price'] ? $vehicle['promotional_price'] : $vehicle['base_price'];
        
        // Format each vehicle in one clean line
        $info .= "**{$vehicle['model_name']} {$vehicle['variant']} ({$vehicle['year_model']})** ";
        $info .= "{$vehicle['category']} | ₱" . number_format($price, 0);
        
        // Add promo indicator if applicable
        if ($vehicle['promotional_price']) {
            $info .= " (Promo Price)";
        }
        
        $info .= " | {$vehicle['fuel_type']} | {$vehicle['seating_capacity']} Seats 🚗 ";
    }
    
    $info .= "We also have additional variants of the Mirage G4, Montero Sport, Strada, and Outlander PHEV available. Let me know if you'd like details on a specific model or want to schedule a test drive! 😊 📞";
    
    return $info;
}

// Enhanced DeepSeek API bot responses with database integration
function getBotResponse($message)
{
    $apiKey = 'sk-27e6623100404762826fc0d41454bfff';
    $apiUrl = 'https://api.deepseek.com/chat/completions';
    
    // Check if the message is asking for vehicle information
    $vehicleData = "";
    $message_lower = strtolower($message);
    
    // Enhanced vehicle-related keyword detection
    $vehicleKeywords = [
        'car', 'cars', 'vehicle', 'vehicles', 'auto', 'automobile', 'model', 'models',
        'stock', 'inventory', 'available', 'sell', 'selling', 'buy', 'buying',
        'price', 'prices', 'cost', 'costs', 'budget', 'affordable', 'cheap', 'expensive',
        'new', 'latest', 'current', 'offer', 'offers', 'deal', 'deals', 'promo', 'promotion',
        'mitsubishi', 'brand', 'lineup', 'range', 'selection', 'options', 'choices',
        // Add specific model names as keywords
        'montero', 'pajero', 'mirage', 'outlander', 'xpander', 'strada', 'lancer', 'eclipse', 'l300'
    ];
    
    $isVehicleQuery = false;
    foreach ($vehicleKeywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            $isVehicleQuery = true;
            error_log("Vehicle keyword matched: " . $keyword . " in query: " . $message);
            break;
        }
    }
    
    // Also check for question patterns that likely relate to vehicles
    $questionPatterns = [
        '/what.*(?:car|vehicle|model|auto|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/which.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/how much.*(?:car|vehicle|model|auto|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300|is|for|does)/',
        '/do you have.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/show me.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/tell me.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/i want.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/i need.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/looking for.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        '/interested in.*(?:car|vehicle|model|montero|pajero|mirage|outlander|xpander|strada|lancer|eclipse|l300)/',
        // Add more flexible price inquiry patterns
        '/how much.*(?:is|does|cost)/',
        '/what.*(?:price|cost)/',
        '/price.*(?:of|for)/'
    ];
    
    foreach ($questionPatterns as $pattern) {
        if (preg_match($pattern, $message_lower)) {
            $isVehicleQuery = true;
            error_log("Question pattern matched: " . $pattern . " in query: " . $message);
            break;
        }
    }
    
    // If it's likely a vehicle query, try to get relevant data
    if ($isVehicleQuery) {
        error_log("Processing vehicle query: " . $message);
        // Detect specific vehicle model queries (enhanced with more variations)
        $models = [
            'montero' => ['montero', 'montero sport'],
            'pajero' => ['pajero', 'pajero sport'],
            'mirage' => ['mirage', 'mirage g4'],
            'outlander' => ['outlander', 'outlander phev'],
            'xpander' => ['xpander', 'expander'],
            'strada' => ['strada', 'triton'],
            'lancer' => ['lancer', 'lancer ex'],
            'eclipse' => ['eclipse', 'eclipse cross'],
            'l300' => ['l300', 'delica']
        ];
        
        foreach ($models as $baseModel => $variations) {
            foreach ($variations as $variation) {
                if (strpos($message_lower, $variation) !== false) {
                    error_log("Model variation matched: " . $variation . " for base model: " . $baseModel);
                    $vehicles = getVehicleByModel($baseModel);
                    if (!empty($vehicles)) {
                        $vehicleData = formatVehicleInfo($vehicles);
                        error_log("Vehicle data retrieved for model: " . $baseModel);
                        break 2;
                    } else {
                        error_log("No vehicles found for model: " . $baseModel);
                    }
                }
            }
        }
        
        // Detect category queries (enhanced)
        if (empty($vehicleData)) {
            $categories = [
                'suv' => ['suv', 'sport utility', 'crossover', 'cross over'],
                'mpv' => ['mpv', 'multi purpose', 'family car', 'van', 'minivan'],
                'hatchback' => ['hatchback', 'hatch', 'compact car', 'small car'],
                'pickup' => ['pickup', 'pick up', 'truck', 'commercial'],
                'sedan' => ['sedan', 'saloon', '4 door', 'four door']
            ];
            
            foreach ($categories as $baseCategory => $variations) {
                foreach ($variations as $variation) {
                    if (strpos($message_lower, $variation) !== false) {
                        $vehicles = getVehiclesByCategory($baseCategory);
                        if (!empty($vehicles)) {
                            $vehicleData = formatVehicleInfo($vehicles);
                            break 2;
                        }
                    }
                }
            }
        }
        
        // Detect price range queries (enhanced)
        if (empty($vehicleData)) {
            $priceKeywords = ['price', 'budget', 'cost', 'afford', 'cheap', 'expensive', 'money', 'pesos', '₱'];
            $hasPriceKeyword = false;
            foreach ($priceKeywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    $hasPriceKeyword = true;
                    break;
                }
            }
            
            if ($hasPriceKeyword) {
                // Extract price ranges from various patterns
                if (preg_match('/(\d+)\s*(?:k|thousand)/i', $message, $matches)) {
                    $price = intval($matches[1]) * 1000;
                    $vehicles = getVehiclesByPriceRange($price * 0.8, $price * 1.2);
                    if (!empty($vehicles)) {
                        $vehicleData = formatVehicleInfo($vehicles);
                    }
                } elseif (preg_match('/(\d+)\s*(?:m|million)/i', $message, $matches)) {
                    $price = intval($matches[1]) * 1000000;
                    $vehicles = getVehiclesByPriceRange($price * 0.8, $price * 1.2);
                    if (!empty($vehicles)) {
                        $vehicleData = formatVehicleInfo($vehicles);
                    }
                } elseif (preg_match('/under\s*(\d+)/i', $message, $matches)) {
                    $maxPrice = intval($matches[1]) * (strpos($message_lower, 'million') ? 1000000 : (strpos($message_lower, 'k') ? 1000 : 1));
                    $vehicles = getVehiclesByPriceRange(0, $maxPrice);
                    if (!empty($vehicles)) {
                        $vehicleData = formatVehicleInfo($vehicles);
                    }
                }
            }
        }
        
        // If no specific match found but it's clearly a vehicle query, show all vehicles
        if (empty($vehicleData)) {
            $vehicles = getAllVehicles();
            if (!empty($vehicles)) {
                $vehicleData = formatVehicleInfo($vehicles);
            }
        }
    }
    
    // Debug logging to check if vehicle data is being retrieved
    if (!empty($vehicleData)) {
        error_log("Vehicle data found: " . substr($vehicleData, 0, 200) . "...");
    } else {
        error_log("No vehicle data found for query: " . $message);
    }
    
    $systemMessage = "You are a helpful customer support assistant for Mitsubishi Motors. You help customers with inquiries about Mitsubishi vehicles. Provide helpful, accurate, and friendly responses about vehicle features, pricing inquiries, test drives, and general automotive questions. Keep responses concise and professional.";
    
    // If we have vehicle data, include it in the system message
    if (!empty($vehicleData)) {
        $systemMessage .= "\n\nCurrent vehicle information from our database:\n" . $vehicleData;
        $systemMessage .= "\n\nUse this information to answer the customer's question accurately. Always mention specific prices, features, and availability from the data provided.";
    }
    
    $data = [
        'model' => 'deepseek-chat',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemMessage
            ],
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ]);
    
    try {
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            return "I apologize, but I'm having trouble connecting right now. A sales agent will assist you shortly.";
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['choices'][0]['message']['content'])) {
            return trim($responseData['choices'][0]['message']['content']);
        } else {
            return "Thank you for your message! I'm connecting you with one of our sales agents who will provide you with detailed assistance.";
        }
    } catch (Exception $e) {
        error_log("DeepSeek API Error: " . $e->getMessage());
        return "I apologize for the technical difficulty. A sales agent will be with you shortly to assist with your inquiry.";
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'send_message') {
            $message_text = trim($_POST['message_text']);

            if (!empty($message_text)) {
                // Create conversation if it doesn't exist
                if (!$conversation_id) {
                    // First check if a conversation already exists for this customer
                    $existingConv = $connect->prepare("
                        SELECT conversation_id FROM conversations WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1
                    ");
                    $existingConv->execute([$customer_id]);
                    $existing = $existingConv->fetch();

                    if ($existing) {
                        $conversation_id = $existing['conversation_id'];
                    } else {
                        $createConv = $connect->prepare("
                            INSERT INTO conversations (customer_id, agent_id, status, last_message_at) 
                            VALUES (?, NULL, 'Pending', NOW())
                        ");
                        $createConv->execute([$customer_id]);
                        $conversation_id = $connect->lastInsertId();
                    }
                }

                // Insert customer message
                $insertMessage = $connect->prepare("
                    INSERT INTO messages (conversation_id, sender_id, sender_type, message_text) 
                    VALUES (?, ?, 'Customer', ?)
                ");
                $insertMessage->execute([$conversation_id, $customer_id, $message_text]);

                // Generate bot response
                $botResponse = getBotResponse($message_text);

                // Insert bot response (using sender_id = 0 for bot)
                $insertBotMessage = $connect->prepare("
                    INSERT INTO messages (conversation_id, sender_id, sender_type, message_text) 
                    VALUES (?, 0, 'SalesAgent', ?)
                ");
                $insertBotMessage->execute([$conversation_id, $botResponse]);

                // Update conversation last message time
                $updateConv = $connect->prepare("UPDATE conversations SET last_message_at = NOW() WHERE conversation_id = ?");
                $updateConv->execute([$conversation_id]);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
            }
            exit;
        }

        if ($_POST['action'] === 'get_messages') {
            if ($conversation_id) {
                // Mark messages as read
                $markRead = $connect->prepare("
                    UPDATE messages SET is_read = 1 
                    WHERE conversation_id = ? AND sender_type = 'SalesAgent'
                ");
                $markRead->execute([$conversation_id]);

                // Get messages
                $messagesQuery = $connect->prepare("
                    SELECT m.*, 
                           CASE 
                               WHEN m.sender_type = 'SalesAgent' AND m.sender_id = 0 THEN 'Support Bot'
                               ELSE CONCAT(COALESCE(a.FirstName, ''), ' ', COALESCE(a.LastName, ''))
                           END as sender_name,
                           CASE 
                               WHEN m.sender_type = 'SalesAgent' AND m.sender_id = 0 THEN 'Bot'
                               ELSE COALESCE(a.Username, 'Unknown')
                           END as sender_username
                    FROM messages m
                    LEFT JOIN accounts a ON m.sender_id = a.Id AND m.sender_id != 0
                    WHERE m.conversation_id = ?
                    ORDER BY m.created_at ASC
                ");
                $messagesQuery->execute([$conversation_id]);
                $messages = $messagesQuery->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'messages' => $messages]);
            } else {
                echo json_encode(['success' => false, 'messages' => []]);
            }
            exit;
        }
    } catch (Exception $e) {
        error_log("Error in AJAX handler: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch user details for header
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reusing common styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%);
            min-height: 100vh;
            color: white;
        }

        .header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            position: relative;
            z-index: 10;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 60px;
        }

        .brand-text {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #b80000;
            font-size: 1.2rem;
        }

        .welcome-text {
            font-size: 1rem;
        }

        .logout-btn {
            background: linear-gradient(45deg, #d60000, #b30000);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 50px 30px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffd700;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        /* Chat Container Styles */
        .chat-container {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: 75vh;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .chat-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        .chat-header h2 {
            font-size: 1.5rem;
            color: #ffd700;
            margin: 0;
        }

        .chat-header p {
            margin: 5px 0 0;
            color: #9f9f9f;
            font-size: 0.9rem;
        }

        .online-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #28a745;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .chat-body {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Custom scrollbar */
        .chat-body::-webkit-scrollbar {
            width: 8px;
        }

        .chat-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .chat-body::-webkit-scrollbar-thumb {
            background-color: #ffd700;
            border-radius: 10px;
        }

        .chat-message {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.5;
            position: relative;
        }

        .chat-message .timestamp {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
            display: block;
        }

        .message-received {
            background: rgba(255, 255, 255, 0.1);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message-sent {
            background: #b80000;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .chat-footer {
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 215, 0, 0.1);
            display: flex;
            gap: 15px;
        }

        .chat-input {
            flex-grow: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 10px;
            padding: 15px;
            color: white;
            font-size: 1rem;
            resize: none;
        }

        .chat-input:focus {
            outline: none;
            border-color: #ffd700;
        }

        .send-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 0 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .send-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }
        
        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .chat-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .typing-indicator {
            opacity: 0.8;
        }
        
        .typing-dots {
            display: inline-flex;
            gap: 4px;
            margin-right: 8px;
        }
        
        .typing-dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #ffd700;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .typing-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="logo-section">
            <img src="../includes/images/Mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <div class="container">
        <a href="customer.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="chat-container">
            <div class="chat-header">
                <h2>Chat with Support</h2>
                <p><span class="online-indicator"></span>
                    <?php if ($assignedAgent): ?>
                        Your agent: <?php echo htmlspecialchars($assignedAgent['FirstName'] ?
                                        $assignedAgent['FirstName'] . ' ' . $assignedAgent['LastName'] :
                                        $assignedAgent['Username']); ?>
                    <?php else: ?>
                        Support Bot is online - An agent will assist you shortly
                    <?php endif; ?>
                </p>
            </div>
            <div class="chat-body" id="chatBody">
                <?php if (!$conversation_id): ?>
                    <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.7);">
                        <i class="fas fa-robot" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>Chat with our Support Bot</h3>
                        <p>Start by asking about our vehicles, pricing, or test drives</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="chat-footer">
                <textarea id="chatInput" class="chat-input" placeholder="Type your message..." rows="1"></textarea>
                <button id="sendBtn" class="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        let conversationId = <?php echo $conversation_id ?? 'null'; ?>;

        function loadMessages() {
            if (!conversationId) return;

            fetch('chat_support.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_messages`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                });
        }

        function displayMessages(messages) {
            const chatBody = document.getElementById('chatBody');
            chatBody.innerHTML = '';

            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `chat-message ${message.sender_type === 'Customer' ? 'message-sent' : 'message-received'}`;

                const time = new Date(message.created_at).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Add bot indicator for bot messages
                const senderIndicator = message.sender_id == 0 ? ' 🤖' : '';
                messageDiv.innerHTML = `${message.message_text}${senderIndicator}<span class="timestamp">${time}</span>`;
                chatBody.appendChild(messageDiv);
            });

            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function addMessageToUI(messageText, isUser = true, isBot = false) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${isUser ? 'message-sent' : 'message-received'}`;
            
            const time = new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const botIndicator = isBot ? ' 🤖' : '';
            messageDiv.innerHTML = `${messageText}${botIndicator}<span class="timestamp">${time}</span>`;
            chatBody.appendChild(messageDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
            
            return messageDiv;
        }
        
        function showTypingIndicator() {
            const chatBody = document.getElementById('chatBody');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat-message message-received typing-indicator';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
                🤖 <span class="timestamp">typing...</span>
            `;
            chatBody.appendChild(typingDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }
        
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function sendMessage() {
            const messageInput = document.getElementById('chatInput');
            const sendBtn = document.getElementById('sendBtn');
            const messageText = messageInput.value.trim();

            if (!messageText) return;
            
            // Immediately show user message
            addMessageToUI(messageText, true, false);
            
            // Clear input and disable controls
            messageInput.value = '';
            messageInput.style.height = 'auto';
            messageInput.disabled = true;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Show typing indicator
            showTypingIndicator();

            fetch('chat_support.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&message_text=${encodeURIComponent(messageText)}`
                })
                .then(response => response.json())
                .then(data => {
                    hideTypingIndicator();
                    
                    if (data.success) {
                        // Refresh the page if no conversation exists yet
                        if (!conversationId) {
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            // Load messages to get the bot response
                            setTimeout(() => {
                                loadMessages();
                            }, 500);
                        }
                    } else {
                        // Show error message
                        addMessageToUI('Sorry, there was an error sending your message. Please try again.', false, true);
                    }
                })
                .catch(error => {
                    hideTypingIndicator();
                    addMessageToUI('Sorry, there was a connection error. Please try again.', false, true);
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Re-enable controls
                    messageInput.disabled = false;
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                    messageInput.focus();
                });
        }

        // Event listeners
        document.getElementById('sendBtn').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        document.getElementById('chatInput').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Load messages on page load and auto-refresh only if conversation exists
        if (conversationId) {
            loadMessages();
            setInterval(loadMessages, 3000); // Refresh every 3 seconds
        }

        // Scroll to bottom on load
        window.onload = () => {
            const chatBody = document.getElementById('chatBody');
            chatBody.scrollTop = chatBody.scrollHeight;
        };
    </script>
</body>

</html>