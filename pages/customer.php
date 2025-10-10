<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');
// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

// Check if customer information is filled out, if not, redirect to verification
$stmt_check_info = $connect->prepare("SELECT cusID, Status FROM customer_information WHERE account_id = ?");
$stmt_check_info->execute([$_SESSION['user_id']]);
$customer_info = $stmt_check_info->fetch(PDO::FETCH_ASSOC);

if (!$customer_info) {
    header("Location: verification.php");
    exit;
}

// Fetch user details
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];

// Get unread inquiries count (for customer badge)
$unread_count = 0;
try {
    $stmt = $connect->prepare("SELECT COUNT(*) FROM inquiries WHERE AccountId = ? AND (is_read = 0 OR is_read IS NULL)");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error (unread inquiries): " . $e->getMessage());
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
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
            overflow-x: hidden;
        }

        /* Animated background particles */
        .bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { width: 20px; height: 20px; top: 20%; left: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 15px; height: 15px; top: 60%; left: 80%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 25px; height: 25px; top: 80%; left: 30%; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.1; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 0.3; }
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
            height: auto;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3));
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
            font-weight: 500;
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
            box-shadow: 0 4px 15px rgba(214, 0, 0, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(214, 0, 0, 0.5);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 30px;
            position: relative;
            z-index: 5;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }

        .hero-section p {
            font-size: 1.3rem;
            opacity: 0.9;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 35px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.1), transparent);
            transition: all 0.6s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .card-icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .card h3 {
            color: #ffd700;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .card p {
            line-height: 1.8;
            margin-bottom: 25px;
            opacity: 0.9;
            font-weight: 300;
        }

        .card-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .card-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5);
            background: linear-gradient(45deg, #ffed4e, #fff);
        }

        /* Animation on page load */
        .card {
            opacity: 0;
            transform: translateY(50px);
            animation: slideUp 0.6s ease forwards;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        .card:nth-child(5) { animation-delay: 0.5s; }
        .card:nth-child(6) { animation-delay: 0.6s; }
        .card:nth-child(7) { animation-delay: 0.7s; }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Extra Small Devices (max-width: 575px) */
        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 12px;
            }

            .logo {
                width: 45px;
            }

            .brand-text {
                font-size: 1.1rem;
            }

            .user-section {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .welcome-text {
                font-size: 0.85rem;
            }

            .logout-btn {
                padding: 8px 16px;
                font-size: 0.75rem;
            }

            .container {
                padding: 25px 15px;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 0.9rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .card {
                padding: 20px;
            }

            .card-icon {
                font-size: 2.2rem;
            }

            .card h3 {
                font-size: 1.1rem;
            }

            .card p {
                font-size: 0.85rem;
            }

            .card-btn {
                padding: 12px 20px;
                font-size: 0.85rem;
            }
        }

        /* Small Devices (min-width: 576px) and (max-width: 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .header {
                padding: 18px 25px;
            }

            .logo {
                width: 50px;
            }

            .brand-text {
                font-size: 1.2rem;
            }

            .container {
                padding: 35px 20px;
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1.1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card {
                padding: 25px;
            }

            .card-icon {
                font-size: 2.5rem;
            }

            .card h3 {
                font-size: 1.3rem;
            }
        }

        /* Medium Devices (min-width: 768px) and (max-width: 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .container {
                padding: 40px 25px;
            }

            .hero-section h1 {
                font-size: 3rem;
            }

            .hero-section p {
                font-size: 1.2rem;
            }

            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 25px;
            }

            .card {
                padding: 30px;
            }

            .card-icon {
                font-size: 2.8rem;
            }

            .card h3 {
                font-size: 1.4rem;
            }
        }

        /* Large Devices (min-width: 992px) and (max-width: 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 25px;
            }
        }

        /* Extra Large Devices (min-width: 1200px) */
        @media (min-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <header class="header">
        <div class="logo-section">
            <img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="user-section">
            <div class="user-avatar">
                <?php echo strtoupper(substr($displayName, 0, 1)); ?>
            </div>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </header>

    <div class="container">
        <div class="hero-section">
            <h1>Customer Dashboard</h1>
            <p>Explore our vehicles, services, and manage your account with excellence</p>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-car"></i>
                </div>
                <h3>Car Menu</h3>
                <p>Browse car categories and view available models like Xpander, Mirage, and Triton. Select your preferred vehicle for a quote, test drive, or inquiry.</p>
                <button class="card-btn" onclick="window.location.href='car_menu.php'">
                    <i class="fas fa-search"></i> Explore Cars
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h3>Submit Inquiry</h3>
                <p>Have questions about a specific vehicle? Submit an inquiry and our sales team will get back to you with detailed information.</p>
                <button class="card-btn" onclick="window.location.href='inquiry.php'">
                    <i class="fas fa-paper-plane"></i> Submit Inquiry
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Chat Support</h3>
                <p>Ask questions about cars and talk directly with agents. If an agent is not available, a chatbot is there to assist you.</p>
                <button class="card-btn" onclick="window.location.href='chat_support.php'">
                    <i class="fas fa-comments"></i> Open Chat
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>My Inquiries
                    <?php if (!empty($unread_count) && (int)$unread_count > 0): ?>
                        <span style="background: #ffd700; color: #1a1a1a; font-size: 0.8rem; padding: 2px 8px; border-radius: 12px; margin-left: 8px;">
                            <?php echo (int)$unread_count; ?>
                        </span>
                    <?php endif; ?>
                </h3>
                <p>Track your vehicle inquiries and view responses from our sales team.
                    <?php if (!empty($unread_count) && (int)$unread_count > 0): ?>
                        <strong style="color: #ffc107;"><?php echo (int)$unread_count; ?> unread inquiry(ies).</strong>
                    <?php else: ?>
                        <strong style="color: #28a745;">You're all caught up. No unread inquiries.</strong>
                    <?php endif; ?>
                </p>
                <button class="card-btn" onclick="window.location.href='my_inquiries.php'">
                    <i class="fas fa-search"></i> View My Inquiries
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-life-ring"></i>
                </div>
                <h3>Help Center</h3>
                <p>Find answers to common questions like "How do I use the web system?", "Where can I see my reservation?", and more.</p>
                <button class="card-btn" onclick="window.location.href='help_center.php'">
                    <i class="fas fa-info-circle"></i> Find Answers
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Notifications</h3>
                <p>See all system updates such as account verification, application approval, upcoming payment reminders, and other important alerts.</p>
                <button class="card-btn" onclick="window.location.href='notifications.php'">
                    <i class="fas fa-eye"></i> View Notifications
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3>Order Details</h3>
                <p>View your balance, see your payment history, check the dates of your payments, and how much you have paid so far.</p>
                <button class="card-btn" onclick="window.location.href='order_details.php'">
                    <i class="fas fa-receipt"></i> View My Orders
                </button>
            </div>

            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Requirements Guide</h3>
                <p>See the needed documents if you want to apply through walk-in. Be prepared with all necessary paperwork.</p>
                <button class="card-btn" onclick="window.location.href='requirements_guide.php'">
                    <i class="fas fa-book-open"></i> View Guide
                </button>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3>Settings</h3>
                <p>Update your personal information, preferences, and account settings to keep your profile current.</p>
                <button class="card-btn" onclick="window.location.href='my_profile.php'">
                    <i class="fas fa-edit"></i> Manage Settings
                </button>
            </div>
        </div>
    </div>
</body>
</html>
