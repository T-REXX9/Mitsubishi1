<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
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
    <title>Help Center - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Common styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%); min-height: 100vh; color: white; }
        .header { background: rgba(0, 0, 0, 0.4); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 215, 0, 0.2); }
        .logo-section { display: flex; align-items: center; gap: 20px; }
        .logo { width: 60px; }
        .brand-text { font-size: 1.4rem; font-weight: 700; background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .user-section { display: flex; align-items: center; gap: 20px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(45deg, #ffd700, #ffed4e); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #b80000; font-size: 1.2rem; }
        .welcome-text { font-size: 1rem; }
        .logout-btn { background: linear-gradient(45deg, #d60000, #b30000); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; }
        .container { max-width: 1000px; margin: 0 auto; padding: 50px 30px; }
        .back-btn { display: inline-block; margin-bottom: 30px; background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .back-btn:hover { background: #ffd700; color: #1a1a1a; }
        .page-title { text-align: center; font-size: 2.8rem; color: #ffd700; margin-bottom: 15px; }
        .page-subtitle { text-align: center; font-size: 1.2rem; opacity: 0.8; margin-bottom: 40px; }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .category-tab {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .category-tab.active,
        .category-tab:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        /* Accordion Styles */
        .accordion { display: flex; flex-direction: column; gap: 15px; }
        .accordion-item { 
            background: rgba(0, 0, 0, 0.3); 
            border-radius: 15px; 
            border: 1px solid rgba(255, 215, 0, 0.1); 
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .accordion-item:hover {
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .accordion-header { 
            padding: 25px; 
            cursor: pointer; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: rgba(0, 0, 0, 0.2);
        }
        .accordion-header h3 { 
            margin: 0; 
            font-size: 1.3rem; 
            color: #ffd700;
            font-weight: 600;
        }
        .accordion-icon { 
            font-size: 1.2rem; 
            transition: transform 0.3s ease; 
            color: #ffd700;
        }
        .accordion-content { 
            padding: 0 25px; 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.4s ease, padding 0.4s ease; 
        }
        .accordion-content .content-inner { 
            padding: 25px 0; 
            border-top: 1px solid rgba(255, 215, 0, 0.1); 
        }
        .accordion-content p { 
            line-height: 1.8; 
            margin-bottom: 15px;
            font-size: 1.05rem;
        }
        .accordion-content ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        .accordion-content li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .accordion-content .highlight {
            background: rgba(255, 215, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ffd700;
        }
        .accordion-content .steps {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .accordion-content .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .accordion-content .step-number {
            background: #ffd700;
            color: #1a1a1a;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .accordion-item.active .accordion-icon { transform: rotate(180deg); }

        /* Contact info styling */
        .contact-info {
            background: rgba(255, 215, 0, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        .contact-info .phone {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffd700;
        }

        /* Custom Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 215, 0, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 215, 0, 0.5);
        }
        ::-webkit-scrollbar-corner {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Firefox Scrollbar */
        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 215, 0, 0.3) rgba(255, 255, 255, 0.05);
        }

        @media (max-width: 768px) {
            .container { padding: 30px 20px; }
            .page-title { font-size: 2.2rem; }
            .category-tabs { gap: 8px; }
            .category-tab { padding: 8px 16px; font-size: 0.9rem; }
            .accordion-header { padding: 20px; }
            .accordion-header h3 { font-size: 1.1rem; }
            .accordion-content { padding: 0 20px; }
            .accordion-content .content-inner { padding: 20px 0; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
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
        <h1 class="page-title">Help Center</h1>
        <p class="page-subtitle">Find answers to common questions and get help with our services</p>

        <div class="category-tabs">
            <button class="category-tab active" data-category="general">General</button>
            <button class="category-tab" data-category="account">Account</button>
            <button class="category-tab" data-category="payment">Payment</button>
            <button class="category-tab" data-category="reservation">Reservation</button>
            <button class="category-tab" data-category="support">Support</button>
        </div>

        <div class="accordion" id="faq-accordion">
            <!-- General Category -->
            <div class="accordion-item" data-category="general">
                <div class="accordion-header">
                    <h3>How do I manage my notifications?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>To manage your notifications effectively, follow these steps:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div>Go to your Dashboard and click on "Notifications"</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>Review all your current notifications including account verification, payment reminders, and system updates</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div>You can mark notifications as read or delete them as needed</div>
                            </div>
                        </div>
                        <div class="highlight">
                            <strong>Tip:</strong> Important notifications like payment due dates and appointment reminders cannot be deleted to ensure you don't miss critical information.
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="general">
                <div class="accordion-header">
                    <h3>How do I use this web system?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>Our web system is designed to be user-friendly and intuitive. Here's a comprehensive guide:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div><strong>Dashboard Navigation:</strong> From your main dashboard, you'll see cards for different services</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div><strong>Car Menu:</strong> Browse vehicles by category, view detailed specifications, and submit inquiries</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div><strong>Chat Support:</strong> Get real-time help from our agents or chatbot</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div><strong>Order Management:</strong> Track your payments, view balances, and manage reservations</div>
                            </div>
                        </div>
                        <p>Each section has clear navigation buttons and helpful tooltips to guide you through the process.</p>
                    </div>
                </div>
            </div>

            <!-- Account Category -->
            <div class="accordion-item" data-category="account">
                <div class="accordion-header">
                    <h3>Where can I see my balance?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>Your account balance and financial information can be found in the Order Details section:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div>Click on "Order Details" from your dashboard</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>View your current balance, payment history, and due dates</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div>Check how much you've paid so far and remaining balance</div>
                            </div>
                        </div>
                        <div class="highlight">
                            <strong>Note:</strong> Your balance is updated in real-time after each payment is processed.
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="account">
                <div class="accordion-header">
                    <h3>Is my data safe and private?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>Yes, your data security and privacy are our top priorities. We implement multiple layers of protection:</p>
                        <ul>
                            <li><strong>Encryption:</strong> All data is encrypted both in transit and at rest</li>
                            <li><strong>Secure Authentication:</strong> Password protection and session management</li>
                            <li><strong>Privacy Policy:</strong> We never share your personal information with third parties without consent</li>
                            <li><strong>Regular Security Updates:</strong> Our systems are continuously monitored and updated</li>
                            <li><strong>Access Control:</strong> Only authorized personnel can access customer data</li>
                        </ul>
                        <div class="highlight">
                            <strong>Your Rights:</strong> You can request to view, update, or delete your personal data at any time by contacting our support team.
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="account">
                <div class="accordion-header">
                    <h3>How can I update my credentials?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>You can update your personal information and account settings easily:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div>Navigate to "Settings" from your dashboard</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>Click on "Manage Settings" or go to "My Profile"</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div>Update your personal details, contact information, or password</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div>Save your changes and verify the update via email if required</div>
                            </div>
                        </div>
                        <p><strong>What you can update:</strong></p>
                        <ul>
                            <li>Name and contact information</li>
                            <li>Email address and phone number</li>
                            <li>Password and security settings</li>
                            <li>Notification preferences</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Reservation Category -->
            <div class="accordion-item" data-category="reservation">
                <div class="accordion-header">
                    <h3>Where can I see my reservations?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>All your reservations and bookings are consolidated in one convenient location:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div>Go to "Order Details" from your dashboard</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>View all your reservations including:</div>
                            </div>
                        </div>
                        <ul>
                            <li>Vehicle purchase applications</li>
                            <li>Test drive appointments</li>
                            <li>Service center bookings</li>
                            <li>Financing applications</li>
                        </ul>
                        <div class="highlight">
                            <strong>Status Tracking:</strong> Each reservation shows its current status - pending, approved, completed, or cancelled.
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="reservation">
                <div class="accordion-header">
                    <h3>How would I know if my request has been approved?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>You'll be notified about your request status through multiple channels:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div><strong>Notifications:</strong> Check your dashboard notifications for real-time updates</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div><strong>Email Updates:</strong> You'll receive email confirmations for status changes</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div><strong>Order Details:</strong> View detailed status in your Order Details section</div>
                            </div>
                        </div>
                        <p><strong>Status Types:</strong></p>
                        <ul>
                            <li><strong>Pending:</strong> Under review by our team</li>
                            <li><strong>Approved:</strong> Request accepted, next steps will be provided</li>
                            <li><strong>Requires Action:</strong> Additional information needed from you</li>
                            <li><strong>Completed:</strong> Process finished successfully</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="reservation">
                <div class="accordion-header">
                    <h3>After reserving, what should I do next?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>After making a reservation, here are the next steps to ensure a smooth process:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div><strong>Check Your Notifications:</strong> Look for confirmation and next steps</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div><strong>Prepare Documents:</strong> Gather required documents (see Requirements Guide)</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div><strong>Wait for Contact:</strong> Our team will reach out within 24-48 hours</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div><strong>Follow Instructions:</strong> Complete any additional steps as guided</div>
                            </div>
                        </div>
                        <div class="highlight">
                            <strong>Pro Tip:</strong> Keep your phone accessible as our team may call to schedule appointments or clarify details.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Category -->
            <div class="accordion-item" data-category="payment">
                <div class="accordion-header">
                    <h3>How do I make payments?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>We offer multiple convenient payment options:</p>
                        <ul>
                            <li><strong>Online Banking:</strong> Direct bank transfers</li>
                            <li><strong>Credit/Debit Cards:</strong> Visa, Mastercard accepted</li>
                            <li><strong>Dealership Payments:</strong> Pay directly at our service centers</li>
                            <li><strong>Financing Options:</strong> Installment plans available</li>
                        </ul>
                        <div class="highlight">
                            <strong>Payment Schedule:</strong> Your payment due dates and amounts are clearly shown in your Order Details section.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Category -->
            <div class="accordion-item" data-category="support">
                <div class="accordion-header">
                    <h3>How can I contact customer support?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>We offer multiple ways to get help when you need it:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div><strong>Live Chat:</strong> Use our Chat Support for immediate assistance</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div><strong>Phone Support:</strong> Call our hotline for direct conversation</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div><strong>Email:</strong> Send detailed inquiries for complex issues</div>
                            </div>
                        </div>
                        <div class="contact-info">
                            <div class="phone">📞 1-800-MITSUBISHI (1-800-648-7824)</div>
                            <p>Available 9 AM - 6 PM EST, Monday to Friday</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" data-category="support">
                <div class="accordion-header">
                    <h3>What if I encounter technical issues?</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="content-inner">
                        <p>If you experience technical problems with the website:</p>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div>Try refreshing your browser or clearing your cache</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>Check your internet connection</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div>Try using a different browser or device</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div>Contact our technical support team if issues persist</div>
                            </div>
                        </div>
                        <p><strong>When contacting support, please provide:</strong></p>
                        <ul>
                            <li>Your account email</li>
                            <li>Description of the problem</li>
                            <li>Browser and device information</li>
                            <li>Screenshot if applicable</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category filtering
        const categoryTabs = document.querySelectorAll('.category-tab');
        const accordionItems = document.querySelectorAll('.accordion-item');

        categoryTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const category = tab.getAttribute('data-category');
                
                // Update active tab
                categoryTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Filter accordion items
                accordionItems.forEach(item => {
                    if (category === 'general' || item.getAttribute('data-category') === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                        item.classList.remove('active');
                        const content = item.querySelector('.accordion-content');
                        content.style.maxHeight = '0px';
                    }
                });
            });
        });

        // Accordion functionality
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        accordionHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                const content = item.querySelector('.accordion-content');
                const isActive = item.classList.contains('active');
                
                // Close all other items
                accordionItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                        const otherContent = otherItem.querySelector('.accordion-content');
                        otherContent.style.maxHeight = '0px';
                    }
                });
                
                // Toggle current item
                if (isActive) {
                    item.classList.remove('active');
                    content.style.maxHeight = '0px';
                } else {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });

        // Auto-adjust content height on window resize
        window.addEventListener('resize', () => {
            accordionItems.forEach(item => {
                if (item.classList.contains('active')) {
                    const content = item.querySelector('.accordion-content');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
    </script>
</body>
</html>
