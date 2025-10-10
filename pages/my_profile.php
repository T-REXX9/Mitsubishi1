<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Check for redirect messages
if (isset($_SESSION['profile_success'])) {
    $success_message = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

if (isset($_SESSION['profile_error'])) {
    $error_message = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}

// Fetch user data first
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch customer information
$stmt_customer = $connect->prepare("SELECT * FROM customer_information WHERE account_id = ?");
$stmt_customer->execute([$_SESSION['user_id']]);
$customer_info = $stmt_customer->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $connect->beginTransaction();

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profile_image']['type'];
            $fileSize = $_FILES['profile_image']['size'];

            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type. Please upload JPEG, PNG, or GIF images only.");
            }

            if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("File size too large. Please upload images smaller than 5MB.");
            }

            $profileImage = file_get_contents($_FILES['profile_image']['tmp_name']);

            // Update profile image in accounts table
            $stmt_image = $connect->prepare("UPDATE accounts SET ProfileImage = ?, UpdatedAt = NOW() WHERE Id = ?");
            $stmt_image->execute([$profileImage, $_SESSION['user_id']]);
        }

        // Handle other profile updates
        if (isset($_POST['saveChanges'])) {
            $updates = [];
            $params = [];

            // Update accounts table - map form fields to correct database columns
            if (!empty($_POST['firstname'])) {
                $updates[] = "FirstName = ?";
                $params[] = $_POST['firstname'];
            }

            if (!empty($_POST['lastname'])) {
                $updates[] = "LastName = ?";
                $params[] = $_POST['lastname'];
            }

            if (!empty($_POST['password'])) {
                $updates[] = "PasswordHash = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $updates[] = "UpdatedAt = NOW()";
                $params[] = $_SESSION['user_id'];

                $sql = "UPDATE accounts SET " . implode(", ", $updates) . " WHERE Id = ?";
                $stmt_acc = $connect->prepare($sql);
                $stmt_acc->execute($params);
            }

            // Handle customer information updates - fix field mapping
            if ($customer_info) {
                $customer_updates = [];
                $customer_params = [];

                if (!empty($_POST['mobile_number'])) {
                    $customer_updates[] = "mobile_number = ?";
                    $customer_params[] = $_POST['mobile_number'];
                }

                if (!empty($_POST['complete_address'])) {
                    $customer_updates[] = "complete_address = ?";
                    $customer_params[] = $_POST['complete_address'];
                }

                if (!empty($_POST['middlename'])) {
                    $customer_updates[] = "middlename = ?";
                    $customer_params[] = $_POST['middlename'];
                }

                // Also update firstname and lastname in customer_information table
                if (!empty($_POST['firstname'])) {
                    $customer_updates[] = "firstname = ?";
                    $customer_params[] = $_POST['firstname'];
                }

                if (!empty($_POST['lastname'])) {
                    $customer_updates[] = "lastname = ?";
                    $customer_params[] = $_POST['lastname'];
                }

                if (!empty($customer_updates)) {
                    $customer_updates[] = "updated_at = NOW()";
                    $customer_params[] = $_SESSION['user_id'];

                    $sql = "UPDATE customer_information SET " . implode(", ", $customer_updates) . " WHERE account_id = ?";
                    $stmt_cust = $connect->prepare($sql);
                    $stmt_cust->execute($customer_params);
                }
            }
        }

        $connect->commit();

        // Use Post-Redirect-Get pattern to prevent form resubmission
        $_SESSION['profile_success'] = "Profile updated successfully!";
        header("Location: my_profile.php");
        exit;
    } catch (Exception $e) {
        $connect->rollBack();
        $_SESSION['profile_error'] = "Update failed: " . $e->getMessage();
        header("Location: my_profile.php");
        exit;
    }
}

$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Common styles */
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
            max-width: 1000px;
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

        .page-title {
            text-align: center;
            font-size: 2.8rem;
            color: #ffd700;
            margin-bottom: 40px;
        }

        /* Profile Form Styles */
        .profile-form {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 1.5rem;
            color: #ffd700;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ffd700;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
        }

        .form-group input:disabled,
        .form-group select:disabled {
            background: rgba(255, 255, 255, 0.05);
            color: #aaa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .form-actions {
            text-align: right;
            margin-top: 30px;
        }

        .form-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .id-image-container {
            margin-top: 10px;
        }

        .id-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        /* Profile Image Styles */
        .profile-image-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .profile-image-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: #b80000;
            border: 3px solid #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .image-upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #ffd700;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-overlay:hover {
            background: #ffed4e;
            transform: scale(1.1);
        }

        .image-upload-overlay i {
            color: #1a1a1a;
            font-size: 1rem;
        }

        .image-upload-input {
            display: none;
        }

        .upload-buttons {
            margin-top: 15px;
            display: none;
            gap: 10px;
            justify-content: center;
        }

        .upload-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a1a, #2d1b1b);
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 95vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 215, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h2 {
            color: #ffd700;
            margin: 0;
            font-size: 1.8rem;
        }

        .close {
            color: #ffd700;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .close:hover,
        .close:focus {
            background: rgba(255, 215, 0, 0.2);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
            line-height: 1.8;
            flex: 1;
            min-height: 0;
        }

        .modal-body h4 {
            color: #ffd700;
            margin: 25px 0 15px 0;
            font-size: 1.2rem;
        }

        .modal-body h4:first-child {
            margin-top: 0;
        }

        .modal-body p {
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .modal-body ul {
            margin: 15px 0 20px 20px;
        }

        .modal-body li {
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .modal-footer {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            min-height: 80px;
        }

        .terms-acceptance-modal {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .terms-acceptance-modal input[type="checkbox"] {
            width: auto;
            margin: 0;
            flex-shrink: 0;
        }

        .terms-acceptance-modal label {
            margin: 0;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            flex-shrink: 0;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-accept {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-close {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .last-updated-modal {
            font-size: 0.9rem;
            color: #aaa;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 215, 0, 0.1);
        }

        /* Terms Link Styles */
        .terms-link {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            text-align: center;
            margin-top: 30px;
        }

        .terms-link h3 {
            color: #ffd700;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .terms-link p {
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .view-terms-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .view-terms-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
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

        /* Responsive Modal */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 2% auto;
                max-height: 98vh;
            }

            .modal-header {
                padding: 15px 20px;
            }

            .modal-header h2 {
                font-size: 1.4rem;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-footer {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
                min-height: auto;
            }

            .terms-acceptance-modal {
                justify-content: center;
                text-align: center;
            }

            .terms-acceptance-modal label {
                font-size: 0.9rem;
            }

            .modal-buttons {
                width: 100%;
                justify-content: center;
            }

            .modal-btn {
                flex: 1;
                max-width: 120px;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 98%;
                margin: 1% auto;
                max-height: 99vh;
            }

            .modal-header {
                padding: 12px 15px;
            }

            .modal-header h2 {
                font-size: 1.2rem;
            }

            .modal-body {
                padding: 15px;
            }

            .modal-footer {
                padding: 12px 15px;
            }

            .terms-acceptance-modal label {
                font-size: 0.85rem;
            }

            .modal-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
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
        <h1 class="page-title">My Profile & Settings</h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form class="profile-form" method="POST" enctype="multipart/form-data">
            <!-- Profile Image Section -->
            <div class="profile-image-section">
                <h2>Profile Picture</h2>
                <div class="profile-image-container">
                    <?php if (!empty($user['ProfileImage'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($user['ProfileImage']); ?>"
                            alt="Profile Picture" class="profile-image" id="mainProfileImage">
                    <?php else: ?>
                        <div class="profile-image-placeholder" id="mainProfilePlaceholder">
                            <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="image-upload-overlay" onclick="document.getElementById('profileImageInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <input type="file" id="profileImageInput" name="profile_image" class="image-upload-input"
                    accept="image/*" onchange="previewImageInMain(this)">

                <div class="upload-buttons" id="uploadButtons">
                    <button type="submit" class="upload-btn" name="upload_image">
                        <i class="fas fa-upload"></i> Update Profile
                    </button>
                    <button type="button" class="cancel-btn" onclick="cancelImageUpload()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>

            <div class="form-section">
                <h2>Account Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['Username'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" disabled>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_status">Account Status
                            <span class="status-badge status-<?php echo strtolower($user['Status'] ?? 'pending'); ?>">
                                <?php echo ucfirst($user['Status'] ?? 'Pending'); ?>
                            </span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" autocomplete="new-password" disabled>
                    </div>
                </div>
            </div>

            <?php if ($customer_info): ?>
                <div class="form-section">
                    <h2>Personal Information
                        <span class="status-badge status-<?php echo strtolower($customer_info['Status'] ?? 'pending'); ?>">
                            Verification: <?php echo ucfirst($customer_info['Status'] ?? 'Pending'); ?>
                        </span>
                    </h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname">First Name</label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($customer_info['firstname'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="middlename">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($customer_info['middlename'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($customer_info['lastname'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" value="<?php echo htmlspecialchars($customer_info['suffix'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="birthday">Date of Birth</label>
                            <input type="date" id="birthday" value="<?php echo $customer_info['birthday'] ?? ''; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" value="<?php echo $customer_info['age'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" disabled>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($customer_info['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($customer_info['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="civil_status">Civil Status</label>
                            <select id="civil_status" disabled>
                                <option value="">Select Status</option>
                                <option value="Single" <?php echo ($customer_info['civil_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo ($customer_info['civil_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                                <option value="Widowed" <?php echo ($customer_info['civil_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                <option value="Separated" <?php echo ($customer_info['civil_status'] ?? '') == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nationality">Nationality</label>
                            <input type="text" id="nationality" value="<?php echo htmlspecialchars($customer_info['nationality'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="mobile_number">Mobile Number</label>
                            <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($customer_info['mobile_number'] ?? ''); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="complete_address">Complete Address</label>
                            <input type="text" id="complete_address" name="complete_address" value="<?php echo htmlspecialchars($customer_info['complete_address'] ?? ''); ?>" disabled placeholder="House/Unit, Street, Barangay, City/Municipality, Province, ZIP">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Employment Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employment_status">Employment Status</label>
                            <select id="employment_status" disabled>
                                <option value="">Select Status</option>
                                <option value="Employed" <?php echo ($customer_info['employment_status'] ?? '') == 'Employed' ? 'selected' : ''; ?>>Employed</option>
                                <option value="Self-Employed" <?php echo ($customer_info['employment_status'] ?? '') == 'Self-Employed' ? 'selected' : ''; ?>>Self-Employed</option>
                                <option value="Unemployed" <?php echo ($customer_info['employment_status'] ?? '') == 'Unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                <option value="Student" <?php echo ($customer_info['employment_status'] ?? '') == 'Student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" value="<?php echo htmlspecialchars($customer_info['company_name'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" id="position" value="<?php echo htmlspecialchars($customer_info['position'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="monthly_income">Monthly Income (PHP)</label>
                            <input type="number" step="0.01" id="monthly_income" value="<?php echo $customer_info['monthly_income'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Valid ID Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="valid_id_type">Valid ID Type</label>
                            <input type="text" id="valid_id_type" value="<?php echo htmlspecialchars($customer_info['valid_id_type'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="valid_id_number">Valid ID Number</label>
                            <input type="text" id="valid_id_number" value="<?php echo htmlspecialchars($customer_info['valid_id_number'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <?php if (!empty($customer_info['valid_id_image'])): ?>
                        <div class="form-group">
                            <label>Valid ID Image</label>
                            <div class="id-image-container">
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($customer_info['valid_id_image']); ?>" alt="Valid ID" class="id-image">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="form-section">
                    <h2>Personal Information</h2>
                    <div style="text-align: center; padding: 40px; color: #ffd700;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <h3>Verification Required</h3>
                        <p style="margin: 20px 0;">You need to complete your verification to view your full profile.</p>
                        <button type="button" class="form-btn" onclick="window.location.href='verification.php'">
                            <i class="fas fa-user-check"></i> Complete Verification
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Terms and Conditions Link -->
            <div class="terms-link">
                <h3>Terms and Conditions</h3>
                <p>Review our platform policies and user agreement</p>
                <button type="button" class="view-terms-btn" onclick="openTermsModal()">
                    <i class="fas fa-file-contract"></i> View Terms & Conditions
                </button>
                <div style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.95rem;">
                        <input type="checkbox" id="termsAgreed" name="terms_agreed" <?php echo ($customer_info && $customer_info['Status'] == 'Approved') ? 'checked' : ''; ?>>
                        I have read and agree to the Terms and Conditions
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="form-btn" id="editBtn">Edit Profile</button>
                <button type="submit" class="form-btn" id="saveBtn" name="saveChanges" style="display:none;">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Terms and Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Terms and Conditions</h2>
                <span class="close" onclick="closeTermsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <h4>1. Account Usage and Responsibilities</h4>
                <p>By using your Mitsubishi Motors account, you agree to:</p>
                <ul>
                    <li>Provide accurate and complete information during registration and verification</li>
                    <li>Keep your login credentials secure and confidential</li>
                    <li>Notify us immediately of any unauthorized use of your account</li>
                    <li>Use the platform only for legitimate automotive inquiries and transactions</li>
                </ul>

                <h4>2. Privacy and Data Protection</h4>
                <p>We are committed to protecting your personal information:</p>
                <ul>
                    <li>Your personal data is encrypted and stored securely</li>
                    <li>We will never share your information with third parties without consent</li>
                    <li>You have the right to access, update, or delete your personal data</li>
                    <li>All communication through our platform is monitored for quality assurance</li>
                </ul>

                <h4>3. Vehicle Reservations and Purchases</h4>
                <p>When making reservations or purchases:</p>
                <ul>
                    <li>All pricing and availability are subject to change without notice</li>
                    <li>Reservations require approval and may be subject to credit checks</li>
                    <li>Final purchase terms will be confirmed at the dealership</li>
                    <li>Down payments and deposits are non-refundable unless otherwise specified</li>
                </ul>

                <h4>4. Payment Terms</h4>
                <p>Payment obligations include:</p>
                <ul>
                    <li>All payments must be made on time according to agreed schedules</li>
                    <li>Late payments may incur additional fees and penalties</li>
                    <li>We accept various payment methods as specified in your agreement</li>
                    <li>Payment confirmations will be provided via email and notifications</li>
                </ul>

                <h4>5. Platform Usage Guidelines</h4>
                <p>To maintain a positive experience for all users:</p>
                <ul>
                    <li>Use respectful language in all communications</li>
                    <li>Do not attempt to circumvent security measures</li>
                    <li>Report any technical issues or suspicious activity</li>
                    <li>Follow all instructions provided by our support team</li>
                </ul>

                <h4>6. Limitation of Liability</h4>
                <p>Mitsubishi Motors Philippines reserves the right to:</p>
                <ul>
                    <li>Modify these terms and conditions with proper notice</li>
                    <li>Suspend or terminate accounts that violate these terms</li>
                    <li>Limit liability for technical issues beyond our control</li>
                    <li>Update system features and functionality as needed</li>
                </ul>

                <h4>7. Customer Support and Dispute Resolution</h4>
                <p>For any issues or concerns:</p>
                <ul>
                    <li>Contact our customer support team first for resolution</li>
                    <li>Disputes will be handled according to Philippine law</li>
                    <li>Mediation services are available for complex issues</li>
                    <li>All communications regarding disputes must be in writing</li>
                </ul>

                <div class="last-updated-modal">
                    Last updated: January 2025
                </div>
            </div>
            <div class="modal-footer">
                <div class="terms-acceptance-modal">
                    <input type="checkbox" id="modalTermsAgreed">
                    <label for="modalTermsAgreed">I have read and agree to these Terms and Conditions</label>
                </div>
                <div class="modal-buttons">
                    <button class="modal-btn btn-accept" onclick="acceptTerms()">
                        <i class="fas fa-check"></i> Accept
                    </button>
                    <button class="modal-btn btn-close" onclick="closeTermsModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const inputs = document.querySelectorAll('.profile-form input, .profile-form select');

        editBtn.addEventListener('click', () => {
            inputs.forEach(input => {
                // Allow editing of specific fields - use name attribute for better identification
                const fieldName = input.getAttribute('name') || input.id;
                const editableFields = ['password', 'firstname', 'lastname', 'middlename', 'mobile_number', 'complete_address'];

                if (editableFields.includes(fieldName)) {
                    input.disabled = false;
                    // Add visual indication that field is editable
                    input.style.background = 'rgba(255, 255, 255, 0.15)';
                    input.style.borderColor = 'rgba(255, 215, 0, 0.4)';
                }
            });
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
        });

        // Function to reset edit mode
        function resetEditMode() {
            inputs.forEach(input => {
                const fieldName = input.getAttribute('name') || input.id;
                const editableFields = ['password', 'firstname', 'lastname', 'middlename', 'mobile_number', 'complete_address'];

                if (editableFields.includes(fieldName)) {
                    input.disabled = true;
                    input.style.background = 'rgba(255, 255, 255, 0.05)';
                    input.style.borderColor = 'rgba(255, 215, 0, 0.2)';
                }
            });
            editBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
        }

        // Add cancel functionality
        saveBtn.addEventListener('click', (e) => {
            // Form will submit naturally and be handled by PHP
            return true;
        });

        // Handle profile image upload separately from edit mode
        function handleImageUpload() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            form.style.display = 'none';

            const fileInput = document.getElementById('profileImageInput').cloneNode(true);
            form.appendChild(fileInput);

            const submitBtn = document.createElement('input');
            submitBtn.type = 'submit';
            submitBtn.name = 'upload_image';
            form.appendChild(submitBtn);

            document.body.appendChild(form);
            form.submit();
        }

        let originalImageSrc = null;
        let hasPlaceholder = <?php echo empty($user['ProfileImage']) ? 'true' : 'false'; ?>;

        function previewImageInMain(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only JPEG, PNG, or GIF images.');
                    input.value = '';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size too large. Please upload images smaller than 5MB.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Store original state
                    if (!originalImageSrc) {
                        const mainImage = document.getElementById('mainProfileImage');
                        const placeholder = document.getElementById('mainProfilePlaceholder');

                        if (mainImage) {
                            originalImageSrc = mainImage.src;
                        } else if (placeholder) {
                            originalImageSrc = 'placeholder';
                        }
                    }

                    // Replace placeholder or image with preview
                    const container = document.querySelector('.profile-image-container');
                    const existingImage = document.getElementById('mainProfileImage');
                    const existingPlaceholder = document.getElementById('mainProfilePlaceholder');

                    if (existingImage) {
                        existingImage.src = e.target.result;
                    } else if (existingPlaceholder) {
                        // Replace placeholder with image
                        existingPlaceholder.style.display = 'none';
                        const newImage = document.createElement('img');
                        newImage.src = e.target.result;
                        newImage.alt = 'Profile Picture';
                        newImage.className = 'profile-image';
                        newImage.id = 'mainProfileImage';
                        container.insertBefore(newImage, container.querySelector('.image-upload-overlay'));
                    }

                    document.getElementById('uploadButtons').style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        }

        function cancelImageUpload() {
            document.getElementById('profileImageInput').value = '';
            document.getElementById('uploadButtons').style.display = 'none';

            // Restore original state
            if (originalImageSrc) {
                const mainImage = document.getElementById('mainProfileImage');
                const placeholder = document.getElementById('mainProfilePlaceholder');

                if (originalImageSrc === 'placeholder') {
                    if (mainImage) {
                        mainImage.remove();
                    }
                    if (placeholder) {
                        placeholder.style.display = 'flex';
                    }
                } else if (mainImage) {
                    mainImage.src = originalImageSrc;
                } else if (placeholder) {
                    // Restore image if it was replaced
                    placeholder.style.display = 'none';
                    const container = document.querySelector('.profile-image-container');
                    const newImage = document.createElement('img');
                    newImage.src = originalImageSrc;
                    newImage.alt = 'Profile Picture';
                    newImage.className = 'profile-image';
                    newImage.id = 'mainProfileImage';
                    container.insertBefore(newImage, container.querySelector('.image-upload-overlay'));
                }

                originalImageSrc = null;
            }
        }

        function openTermsModal() {
            const modal = document.getElementById('termsModal');
            const modalCheckbox = document.getElementById('modalTermsAgreed');
            const formCheckbox = document.getElementById('termsAgreed');

            // Sync modal checkbox with form checkbox
            modalCheckbox.checked = formCheckbox.checked;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeTermsModal() {
            const modal = document.getElementById('termsModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore background scrolling
        }

        function acceptTerms() {
            const modalCheckbox = document.getElementById('modalTermsAgreed');
            const formCheckbox = document.getElementById('termsAgreed');

            if (modalCheckbox.checked) {
                formCheckbox.checked = true;
                closeTermsModal();
            } else {
                alert('Please check the agreement checkbox to accept the terms.');
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('termsModal');
            if (event.target == modal) {
                closeTermsModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTermsModal();
            }
        });
    </script>
</body>

</html>