<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

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
    <title>Requirements Guide - Mitsubishi Motors</title>
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
        .page-title { text-align: center; font-size: 2.8rem; color: #ffd700; margin-bottom: 40px; }

        /* Requirements Guide Styles */
        .requirements-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .intro-text {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }
        .requirements-card {
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            border: 1px solid rgba(255,215,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        .requirements-card:hover {
            border-color: rgba(255,215,0,0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .card-header {
            background: rgba(0,0,0,0.4);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255,215,0,0.1);
        }
        .card-header h2 {
            font-size: 1.8rem;
            color: #ffd700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-header i {
            font-size: 2rem;
            color: #ffd700;
        }
        .card-content {
            padding: 30px;
        }
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
        }
        .requirements-table th {
            background: rgba(255,215,0,0.1);
            color: #ffd700;
            padding: 15px;
            text-align: left;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 2px solid rgba(255,215,0,0.2);
        }
        .requirements-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 1rem;
            line-height: 1.6;
        }
        .requirements-table tr:hover {
            background: rgba(255,215,0,0.05);
        }
        .req-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .req-item i {
            color: #ffd700;
            margin-top: 3px;
            font-size: 1.1rem;
        }
        .req-item-content {
            flex: 1;
        }
        .req-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .req-item-desc {
            font-size: 0.9rem;
            opacity: 0.8;
            line-height: 1.4;
        }
        .highlight-note {
            background: rgba(255,215,0,0.1);
            border-left: 4px solid #ffd700;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 10px 10px 0;
        }
        .highlight-note h4 {
            color: #ffd700;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .highlight-note p {
            margin: 0;
            line-height: 1.6;
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
            .card-header { padding: 15px 20px; }
            .card-header h2 { font-size: 1.4rem; }
            .card-content { padding: 20px; }
            .requirements-table th,
            .requirements-table td { padding: 12px; }
            .intro-text { font-size: 1rem; }
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
        <h1 class="page-title">Requirements Guide</h1>
        
        <div class="requirements-container">
            <p class="intro-text">The following requirements are also necessary if you want to visit the branch directly to inquire about cars.</p>
            
            <div class="requirements-card">
                <div class="card-header">
                    <h2><i class="fas fa-money-check-alt"></i> Loan Requirements</h2>
                </div>
                <div class="card-content">
                    <table class="requirements-table">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="req-item">
                                        <i class="fas fa-id-card"></i>
                                        <div class="req-item-content">
                                            <div class="req-item-title">Valid ID</div>
                                            <div class="req-item-desc">Two (2) government-issued IDs with photos and signatures</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="req-item">
                                        <i class="fas fa-file-invoice"></i>
                                        <div class="req-item-content">
                                            <div class="req-item-title">Copy Of Source of Income</div>
                                            <div class="req-item-desc">Latest payslips, bank statements, or business income documents</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="req-item">
                                        <i class="fas fa-briefcase"></i>
                                        <div class="req-item-content">
                                            <div class="req-item-title"><span style="color: #dc3545;">*If employed</span><br>Certificate of Employment</div>
                                            <div class="req-item-desc">With salary indication and length of service</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="req-item">
                                        <i class="fas fa-receipt"></i>
                                        <div class="req-item-content">
                                            <div class="req-item-title">Payslip</div>
                                            <div class="req-item-desc">Latest 3 months payslips or salary certificate</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="req-item">
                                        <i class="fas fa-building"></i>
                                        <div class="req-item-content">
                                            <div class="req-item-title">Company ID</div>
                                            <div class="req-item-desc">Valid company identification card or employment badge</div>
                                        </div>
                                    </div>
                                </td>
                            </tr> 
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="highlight-note">
                <h4><i class="fas fa-info-circle"></i> Important Note</h4>
                <p>Please ensure all documents are valid and up-to-date. Photocopies should be clear and readable. For employed individuals, additional employment verification documents may be required depending on your chosen financing option.</p>
            </div>

            <div class="highlight-note">
                <h4><i class="fas fa-phone"></i> Need Help?</h4>
                <p>If you have questions about specific requirements or need clarification, please contact our customer service team or visit our Help Center for more detailed information.</p>
            </div>
        </div>
    </div>
</body>
</html>
                                      