<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepSeek AI Chatbot Test - Mitsubishi Motors</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .role-selector {
            margin: 20px 0;
            text-align: center;
        }
        
        .role-btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .role-btn.customer { background: #28a745; color: white; }
        .role-btn.agent { background: #007bff; color: white; }
        .role-btn.admin { background: #dc3545; color: white; }
        .role-btn.active { transform: scale(1.1); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        
        .chat-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .chat-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 10px;
            max-width: 80%;
            word-wrap: break-word;
        }
        
        .message.user {
            background: #007bff;
            color: white;
            margin-left: auto;
            text-align: right;
        }
        
        .message.bot {
            background: #e9ecef;
            color: #333;
            margin-right: auto;
        }
        
        .input-section {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .send-btn {
            padding: 12px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .send-btn:hover { background: #0056b3; }
        
        .examples {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .example-btn {
            display: block;
            width: 100%;
            margin: 5px 0;
            padding: 8px 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-align: left;
            font-size: 12px;
        }
        
        .example-btn:hover { background: #5a6268; }
        
        .current-role {
            text-align: center;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .role-customer { background: #d4edda; color: #155724; }
        .role-agent { background: #cce7ff; color: #004085; }
        .role-admin { background: #f8d7da; color: #721c24; }
        
        .loading {
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🤖 DeepSeek AI Chatbot Test</h1>
        <h2>Mitsubishi Motors Role-Aware Assistant</h2>
        <p>Test the intelligent chatbot across different user roles with real database integration</p>
    </div>

    <div class="role-selector">
        <h3>Select User Role to Test:</h3>
        <button class="role-btn customer active" onclick="switchRole('Customer')">👤 Customer</button>
        <button class="role-btn agent" onclick="switchRole('SalesAgent')">🧑‍💼 Sales Agent</button>
        <button class="role-btn admin" onclick="switchRole('Admin')">👨‍💻 Admin</button>
    </div>

    <div class="current-role role-customer" id="currentRole">
        Current Role: Customer - Ask about vehicles, pricing, and your orders
    </div>

    <div class="chat-container">
        <div class="chat-section">
            <h3>💬 Chat Interface</h3>
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    👋 Hello! I'm your DeepSeek AI assistant. How can I help you today?
                </div>
            </div>
            
            <div class="input-section">
                <input type="text" class="chat-input" id="messageInput" placeholder="Type your message..." />
                <button class="send-btn" onclick="sendMessage()">Send</button>
            </div>
        </div>

        <div class="chat-section">
            <h3>💡 Example Queries by Role</h3>
            <div class="examples" id="exampleQueries">
                <!-- Examples will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        let currentRole = 'Customer';
        
        const roleExamples = {
            'Customer': [
                "Do you have a Montero Sport available?",
                "What's the price of the Xpander GLS?",
                "What financing options do you offer?",
                "Can I schedule a test drive?",
                "What's the status of my order?",
                "Tell me about your latest promotions"
            ],
            'SalesAgent': [
                "How many vehicles did I sell this month?",
                "Show me my assigned customers",
                "What's my sales performance?",
                "Which models are most popular?",
                "Help me with financing options for a customer",
                "What inventory do we have available?"
            ],
            'Admin': [
                "What are our top-selling models this quarter?",
                "Show me sales performance by agent",
                "What's our current inventory status?",
                "Generate a revenue report for this month",
                "Which agents are performing best?",
                "What are our profit margins by model?"
            ]
        };
        
        const roleDescriptions = {
            'Customer': 'Customer - Ask about vehicles, pricing, and your orders',
            'SalesAgent': 'Sales Agent - Access sales data, customer info, and performance metrics', 
            'Admin': 'Administrator - Full access to all analytics and system data'
        };
        
        function switchRole(role) {
            currentRole = role;
            
            // Update session for testing
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=switch_role&role=' + encodeURIComponent(role)
            });
            
            // Update UI
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.role-btn.${role.toLowerCase()}`).classList.add('active');
            
            const roleDiv = document.getElementById('currentRole');
            roleDiv.className = 'current-role role-' + role.toLowerCase();
            roleDiv.textContent = 'Current Role: ' + roleDescriptions[role];
            
            // Clear chat and update examples
            document.getElementById('chatMessages').innerHTML = 
                '<div class="message bot">👋 Hello! I\'m your DeepSeek AI assistant for ' + role + ' users. How can I help you today?</div>';
            
            updateExamples();
        }
        
        function updateExamples() {
            const container = document.getElementById('exampleQueries');
            const examples = roleExamples[currentRole];
            
            container.innerHTML = examples.map(example => 
                `<button class="example-btn" onclick="useExample('${example.replace(/'/g, "\\'")}')">${example}</button>`
            ).join('');
        }
        
        function useExample(text) {
            document.getElementById('messageInput').value = text;
            sendMessage();
        }
        
        function addMessage(content, isUser = false) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + (isUser ? 'user' : 'bot');
            messageDiv.innerHTML = content;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addMessage(message, true);
            input.value = '';
            
            // Add loading indicator
            addMessage('<div class="loading">🤖 Thinking...</div>');
            
            // Send to DeepSeek API
            fetch('includes/api/deepseek_chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                const messages = document.getElementById('chatMessages');
                messages.removeChild(messages.lastChild);
                
                if (data.success) {
                    let response = data.response;
                    if (data.escalation_triggered) {
                        response += '<br><br>🔄 <em>Your request has been escalated for specialized assistance.</em>';
                    }
                    addMessage(response);
                } else {
                    addMessage('<div class="error">❌ Error: ' + (data.error || 'Unknown error occurred') + '</div>');
                }
            })
            .catch(error => {
                // Remove loading indicator
                const messages = document.getElementById('chatMessages');
                messages.removeChild(messages.lastChild);
                addMessage('<div class="error">❌ Network error: ' + error.message + '</div>');
            });
        }
        
        // Allow Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Initialize
        updateExamples();
    </script>
    
    <?php
    // Handle role switching for testing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_role') {
        session_start();
        $role = $_POST['role'];
        
        // Simulate different user sessions for testing
        switch ($role) {
            case 'Customer':
                $_SESSION['user_role'] = 'Customer';
                $_SESSION['user_id'] = 1;
                break;
            case 'SalesAgent':
                $_SESSION['user_role'] = 'SalesAgent';
                $_SESSION['user_id'] = 2;
                break;
            case 'Admin':
                $_SESSION['user_role'] = 'Admin';
                $_SESSION['user_id'] = 1;
                break;
        }
        
        exit('OK');
    }
    
    // Initialize session with Customer role by default
    if (!isset($_SESSION)) {
        session_start();
        if (!isset($_SESSION['user_role'])) {
            $_SESSION['user_role'] = 'Customer';
            $_SESSION['user_id'] = 1;
        }
    }
    ?>
</body>
</html>