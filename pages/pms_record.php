<?php
session_start();
include_once(dirname(__DIR__) . '/includes/init.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

// Use the database connection from init.php
$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    die("Database connection not available. Please check your database configuration.");
}

// For backward compatibility, create $connect variable
$connect = $pdo;

// Fetch user details
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];

// Prepare profile image HTML
$profile_image_html = '';
if (!empty($user['ProfileImage'])) {
    $imageData = base64_encode($user['ProfileImage']);
    $imageMimeType = 'image/jpeg';
    $profile_image_html = '<img src="data:' . $imageMimeType . ';base64,' . $imageData . '" alt="User Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
} else {
    // Show initial if no profile image
    $profile_image_html = strtoupper(substr($displayName, 0, 1));
}

// Get vehicle ID if passed from car_details page
$selected_vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
$selected_vehicle = null;

if ($selected_vehicle_id) {
    try {
        $stmt_vehicle = $connect->prepare("SELECT * FROM vehicles WHERE id = ? AND availability_status = 'available'");
        $stmt_vehicle->execute([$selected_vehicle_id]);
        $selected_vehicle = $stmt_vehicle->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = [
            'customer_name', 'customer_address', 'mobile_number',
            'vehicle_no', 'chassis_no', 'year_make_model', 'reg_no', 'engine_no', 'model_no', 'car_line', 'current_odometer',
            'pms_date', 'pms_time'
        ];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = ucwords(str_replace('_', ' ', $field));
            }
        }

        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        // Check time slot availability (max 3 bookings per slot)
        $pms_date = $_POST['pms_date'];
        $pms_time = $_POST['pms_time'];

        $stmt_check = $connect->prepare("
            SELECT COUNT(*) FROM car_pms_records
            WHERE pms_date = ? AND pms_time = ?
            AND request_status IN ('Pending', 'Approved', 'Scheduled')
        ");
        $stmt_check->execute([$pms_date, $pms_time]);
        $booking_count = $stmt_check->fetchColumn();

        if ($booking_count >= 3) {
            throw new Exception("Time is already booked, choose another time");
        }

        // Sanitize odometer value - remove any non-numeric characters
        $current_odometer = preg_replace('/\D/', '', $_POST['current_odometer']);
        $current_odometer = $current_odometer === '' ? 0 : intval($current_odometer);

        // Get service checkboxes
        $service_eco_oil = isset($_POST['service_eco_oil']) ? 1 : 0;
        $service_oil_filter = isset($_POST['service_oil_filter']) ? 1 : 0;
        $service_gasket_drain = isset($_POST['service_gasket_drain']) ? 1 : 0;
        $service_windshield_washer = isset($_POST['service_windshield_washer']) ? 1 : 0;
        $service_engine_treatment = isset($_POST['service_engine_treatment']) ? 1 : 0;
        $service_ethanol_drier = isset($_POST['service_ethanol_drier']) ? 1 : 0;
        $service_radiator_cap = isset($_POST['service_radiator_cap']) ? 1 : 0;
        $service_parts_misc = isset($_POST['service_parts_misc']) ? 1 : 0;
        $service_parts_lubricants = isset($_POST['service_parts_lubricants']) ? 1 : 0;
        $service_bactaleen = isset($_POST['service_bactaleen']) ? 1 : 0;
        $service_engine_flush = isset($_POST['service_engine_flush']) ? 1 : 0;
        $service_petrol_decarb = isset($_POST['service_petrol_decarb']) ? 1 : 0;
        $service_brake_lube = isset($_POST['service_brake_lube']) ? 1 : 0;
        $service_brake_cleaner = isset($_POST['service_brake_cleaner']) ? 1 : 0;
        $service_klima_fresh = isset($_POST['service_klima_fresh']) ? 1 : 0;

        // Handle file upload
        $uploaded_receipt = null;
        if (isset($_FILES['uploaded_receipt']) && $_FILES['uploaded_receipt']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $file_type = $_FILES['uploaded_receipt']['type'];

            if (in_array($file_type, $allowed_types)) {
                $uploaded_receipt = file_get_contents($_FILES['uploaded_receipt']['tmp_name']);
            } else {
                throw new Exception('Invalid file type. Only JPG, PNG, PDF, and DOCX files are allowed.');
            }
        }

        // Insert PMS record into database with customer_id and default status
        $stmt_insert = $connect->prepare("
            INSERT INTO car_pms_records (
                customer_id, customer_name, customer_address, driver_name, driver_contact, mobile_number,
                selling_dealer, delivery_kms, delivery_date,
                vehicle_no, chassis_no, year_make_model, reg_no, engine_no, color, stock_no, model_no, car_line,
                prod_date, tag_no, eng_trans, current_odometer,
                pms_date, pms_time, service_adviser_id,
                service_eco_oil, service_oil_filter, service_gasket_drain, service_windshield_washer,
                service_engine_treatment, service_ethanol_drier, service_radiator_cap, service_parts_misc,
                service_parts_lubricants, service_bactaleen, service_engine_flush, service_petrol_decarb,
                service_brake_lube, service_brake_cleaner, service_klima_fresh,
                other_concerns, uploaded_receipt,
                request_status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
        ");

        $stmt_insert->execute([
            $_SESSION['user_id'], // customer_id
            $_POST['customer_name'],
            $_POST['customer_address'],
            $_POST['driver_name'] ?? null,
            $_POST['driver_contact'] ?? null,
            $_POST['mobile_number'],
            $_POST['selling_dealer'] ?? null,
            $_POST['delivery_kms'] ?? null,
            !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
            $_POST['vehicle_no'],
            $_POST['chassis_no'],
            $_POST['year_make_model'],
            $_POST['reg_no'],
            $_POST['engine_no'],
            $_POST['color'] ?? null,
            $_POST['stock_no'] ?? null,
            $_POST['model_no'],
            $_POST['car_line'],
            !empty($_POST['prod_date']) ? $_POST['prod_date'] : null,
            $_POST['tag_no'] ?? null,
            $_POST['eng_trans'] ?? null,
            $current_odometer,
            $pms_date,
            $pms_time,
            !empty($_POST['service_adviser_id']) ? $_POST['service_adviser_id'] : null,
            $service_eco_oil, $service_oil_filter, $service_gasket_drain, $service_windshield_washer,
            $service_engine_treatment, $service_ethanol_drier, $service_radiator_cap, $service_parts_misc,
            $service_parts_lubricants, $service_bactaleen, $service_engine_flush, $service_petrol_decarb,
            $service_brake_lube, $service_brake_cleaner, $service_klima_fresh,
            $_POST['other_concerns'] ?? null,
            $uploaded_receipt
        ]);

        // Get the inserted PMS record ID
        $pms_id = $connect->lastInsertId();

        // Create a PMS inquiry record (if table exists)
        try {
            // Get the assigned agent for this customer
            $stmt_agent = $connect->prepare("
                SELECT agent_id FROM customer_information WHERE account_id = ?
            ");
            $stmt_agent->execute([$_SESSION['user_id']]);
            $customer_info = $stmt_agent->fetch(PDO::FETCH_ASSOC);
            $assigned_agent_id = $customer_info['agent_id'] ?? null;

            $stmt_inquiry = $connect->prepare("
                INSERT INTO pms_inquiries (pms_id, customer_id, inquiry_type, status, assigned_agent_id, created_at)
                VALUES (?, ?, 'PMS', 'Open', ?, NOW())
            ");
            $stmt_inquiry->execute([$pms_id, $_SESSION['user_id'], $assigned_agent_id]);

            // Redirect to My PMS Inquiries page
            header("Location: my_pms_inquiries.php?success=1");
            exit;
        } catch (PDOException $e) {
            // If pms_inquiries table doesn't exist, show success message instead
            error_log("PMS Inquiry table error: " . $e->getMessage());
            $success_message = "PMS request has been submitted successfully! Please execute the database SQL queries to enable the inquiry tracking system.";
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS Record - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body { background: #ffffff; min-height: 100vh; color: white; }
        .header { background: #000000; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 215, 0, 0.2); position: relative; z-index: 10; }
        .logo-section { display: flex; align-items: center; gap: 20px; }
        .logo { width: 60px; height: auto; filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3)); }
        .brand-text { font-size: 1.4rem; font-weight: 700; background: #ffffff; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .user-section { display: flex; align-items: center; gap: 5px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(45deg, #ffd700, #ffed4e); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #b80000; font-size: 1.2rem; }
        .welcome-text { font-size: 1rem; font-weight: 500; }
        .logout-btn { background: linear-gradient(45deg, #d60000, #b30000); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(214, 0, 0, 0.3); }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(214, 0, 0, 0.5); }
        .container { max-width: 800px; margin: 0 auto; padding: 30px 20px; position: relative; z-index: 5; }
        .back-btn { display: inline-block; margin-bottom: 20px; background: #808080; color: #ffffff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; font-size: 0.9rem; }
        .back-btn:hover { background: #E60012; color: #ffffff; }

        .pms-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .card-header {
            background: #808080;
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #000000;
            font-size: 0.9rem;
        }

        .form-container {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            color: #E60012;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            color: rgba(0, 0, 0, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-label.required::after {
            content: ' *';
            color: #ff6b6b;
        }

        .form-input, .form-select, .form-textarea {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(2, 2, 2, 0.2);
            border-radius: 8px;
            padding: 12px;
            color: #000000;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #808080;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-select option {
            background: #2a2a2a;
            color: white;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-top: 5px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            color: rgba(0, 0, 0, 0.9);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .radio-group input[type="radio"] {
            accent-color: #E60012;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #E60012;
        }

        .checkbox-group label {
            color: rgba(0, 0, 0, 0.9);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .file-name {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }

        .submit-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .current-date {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 10px;
            border-radius: 6px;
            color: #ffd700;
            font-weight: 500;
            text-align: center;
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; padding: 15px 20px; }
            .user-section { flex-direction: column; gap: 12px; text-align: center; width: 100%; }
            .container { padding: 20px 15px; }
            .form-container { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .radio-group { flex-direction: column; align-items: flex-start; gap: 8px; }
            .checkbox-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?php echo $profile_image_html; ?></div>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <a href="<?php echo $selected_vehicle_id ? 'car_details.php?id=' . $selected_vehicle_id : 'car_menu.php'; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="pms-card">
            <div class="card-header">
                <h1 class="page-title"><i class="fas fa-car"></i> CAR PMS RECORD</h1>
                <p class="page-subtitle">Enter your vehicle's preventive maintenance service details</p>
            </div>

            <div class="form-container">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- CUSTOMER INFORMATION Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            CUSTOMER INFORMATION
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">Customer Name</label>
                                <input type="text" name="customer_name" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label required">Customer Address</label>
                                <input type="text" name="customer_address" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['customer_address'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Driver</label>
                                <input type="text" name="driver_name" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['driver_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Driver Contact No.</label>
                                <input type="text" name="driver_contact" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['driver_contact'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Mobile Number</label>
                                <input type="text" name="mobile_number" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['mobile_number'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Selling Dealer</label>
                                <input type="text" name="selling_dealer" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['selling_dealer'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Delivery KMS</label>
                                <input type="text" name="delivery_kms" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['delivery_kms'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['delivery_date'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER VEHICLE INFORMATION Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-car-side"></i>
                            CUSTOMER VEHICLE INFORMATION
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">Vehicle No</label>
                                <input type="text" name="vehicle_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['vehicle_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Chassis No</label>
                                <input type="text" name="chassis_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['chassis_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Year/Make/Model</label>
                                <input type="text" name="year_make_model" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['year_make_model'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Reg No</label>
                                <input type="text" name="reg_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['reg_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Engine No</label>
                                <input type="text" name="engine_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['engine_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <input type="text" name="color" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Stock No</label>
                                <input type="text" name="stock_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['stock_no'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Model No</label>
                                <input type="text" name="model_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['model_no'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Car Line</label>
                                <input type="text" name="car_line" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['car_line'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prod. Date</label>
                                <input type="date" name="prod_date" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['prod_date'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tag No</label>
                                <input type="text" name="tag_no" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['tag_no'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Eng/Trans</label>
                                <input type="text" name="eng_trans" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['eng_trans'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Current Odometer (km)</label>
                                <input type="text" name="current_odometer" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['current_odometer'] ?? ''); ?>"
                                       pattern="[0-9]*" inputmode="numeric"
                                       placeholder="e.g., 20000" required>
                                <small style="color: #666; font-size: 0.85em;">Enter numbers only (e.g., 20000)</small>
                            </div>
                        </div>
                    </div>

                    <!-- PMS INFORMATION Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            PMS INFORMATION
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">PMS Date</label>
                                <input type="date" name="pms_date" id="pms_date" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['pms_date'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">PMS Time</label>
                                <select name="pms_time" id="pms_time" class="form-select" required>
                                    <option value="">Select Time</option>
                                    <option value="07:45" <?php echo (($_POST['pms_time'] ?? '') === '07:45') ? 'selected' : ''; ?>>7:45 AM</option>
                                    <option value="08:45" <?php echo (($_POST['pms_time'] ?? '') === '08:45') ? 'selected' : ''; ?>>8:45 AM</option>
                                    <option value="09:45" <?php echo (($_POST['pms_time'] ?? '') === '09:45') ? 'selected' : ''; ?>>9:45 AM</option>
                                    <option value="10:45" <?php echo (($_POST['pms_time'] ?? '') === '10:45') ? 'selected' : ''; ?>>10:45 AM</option>
                                    <option value="11:45" <?php echo (($_POST['pms_time'] ?? '') === '11:45') ? 'selected' : ''; ?>>11:45 AM</option>
                                    <option value="13:00" <?php echo (($_POST['pms_time'] ?? '') === '13:00') ? 'selected' : ''; ?>>1:00 PM</option>
                                    <option value="14:00" <?php echo (($_POST['pms_time'] ?? '') === '14:00') ? 'selected' : ''; ?>>2:00 PM</option>
                                    <option value="15:00" <?php echo (($_POST['pms_time'] ?? '') === '15:00') ? 'selected' : ''; ?>>3:00 PM</option>
                                </select>
                                <div id="time-slot-warning" style="display: none; color: #ff6b6b; margin-top: 5px; font-size: 0.85em;">
                                    <i class="fas fa-exclamation-triangle"></i> <span id="warning-text"></span>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); padding: 10px; border-radius: 6px; color: #000; font-size: 0.9em;">
                                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> Please arrive earlier than your scheduled time. If you are late by 15 minutes, you will be considered a walk-in and will need to line up until it is your turn.
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Advisor (prefer service adviser)</label>
                                <input type="text" name="service_adviser_id" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['service_adviser_id'] ?? ''); ?>"
                                       placeholder="Optional - Service adviser name or ID">
                            </div>
                        </div>
                    </div>

                    <!-- SERVICES Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-tools"></i>
                            SERVICES
                        </h3>
                        <div class="checkbox-grid">
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_eco_oil" name="service_eco_oil" value="1"
                                       <?php echo isset($_POST['service_eco_oil']) ? 'checked' : ''; ?>>
                                <label for="service_eco_oil">ECO OIL</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_oil_filter" name="service_oil_filter" value="1"
                                       <?php echo isset($_POST['service_oil_filter']) ? 'checked' : ''; ?>>
                                <label for="service_oil_filter">OIL FILTER</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_gasket_drain" name="service_gasket_drain" value="1"
                                       <?php echo isset($_POST['service_gasket_drain']) ? 'checked' : ''; ?>>
                                <label for="service_gasket_drain">GASKET, DRAIN PLUG</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_windshield_washer" name="service_windshield_washer" value="1"
                                       <?php echo isset($_POST['service_windshield_washer']) ? 'checked' : ''; ?>>
                                <label for="service_windshield_washer">WINDSHIELD WASHER FLUID</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_engine_treatment" name="service_engine_treatment" value="1"
                                       <?php echo isset($_POST['service_engine_treatment']) ? 'checked' : ''; ?>>
                                <label for="service_engine_treatment">ENGINE TREATMENT</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_ethanol_drier" name="service_ethanol_drier" value="1"
                                       <?php echo isset($_POST['service_ethanol_drier']) ? 'checked' : ''; ?>>
                                <label for="service_ethanol_drier">ETHANOL DRIER</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_radiator_cap" name="service_radiator_cap" value="1"
                                       <?php echo isset($_POST['service_radiator_cap']) ? 'checked' : ''; ?>>
                                <label for="service_radiator_cap">RADIATOR CAP STICKER</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_parts_misc" name="service_parts_misc" value="1"
                                       <?php echo isset($_POST['service_parts_misc']) ? 'checked' : ''; ?>>
                                <label for="service_parts_misc">PARTS MATERIAL/MISC</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_parts_lubricants" name="service_parts_lubricants" value="1"
                                       <?php echo isset($_POST['service_parts_lubricants']) ? 'checked' : ''; ?>>
                                <label for="service_parts_lubricants">PARTS MATERIAL/LUBRICANTS</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_bactaleen" name="service_bactaleen" value="1"
                                       <?php echo isset($_POST['service_bactaleen']) ? 'checked' : ''; ?>>
                                <label for="service_bactaleen">BACTALEEN ULTRAMIST</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_engine_flush" name="service_engine_flush" value="1"
                                       <?php echo isset($_POST['service_engine_flush']) ? 'checked' : ''; ?>>
                                <label for="service_engine_flush">ENGINE FLUSH</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_petrol_decarb" name="service_petrol_decarb" value="1"
                                       <?php echo isset($_POST['service_petrol_decarb']) ? 'checked' : ''; ?>>
                                <label for="service_petrol_decarb">PETROL DECARB</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_brake_lube" name="service_brake_lube" value="1"
                                       <?php echo isset($_POST['service_brake_lube']) ? 'checked' : ''; ?>>
                                <label for="service_brake_lube">BRAKE LUBE</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_brake_cleaner" name="service_brake_cleaner" value="1"
                                       <?php echo isset($_POST['service_brake_cleaner']) ? 'checked' : ''; ?>>
                                <label for="service_brake_cleaner">BRAKE CLEANER</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="service_klima_fresh" name="service_klima_fresh" value="1"
                                       <?php echo isset($_POST['service_klima_fresh']) ? 'checked' : ''; ?>>
                                <label for="service_klima_fresh">KLIMA FRESH</label>
                            </div>
                        </div>
                    </div>

                    <!-- OTHER CONCERNS Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-comment-dots"></i>
                            OTHER CONCERNS
                        </h3>
                        <div class="form-group full-width">
                            <textarea name="other_concerns" class="form-textarea" rows="5"
                                      placeholder="Please describe any other concerns, issues, or special requests you may have..."><?php echo htmlspecialchars($_POST['other_concerns'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-upload"></i>
                            UPLOADED RECEIPT
                        </h3>
                        <div class="form-group">
                            <div class="file-upload">
                                <input type="file" id="uploaded_receipt" name="uploaded_receipt" accept=".jpg,.jpeg,.png,.pdf,.docx">
                                <label for="uploaded_receipt" class="file-upload-label">
                                    <i class="fas fa-upload"></i> Upload File
                                </label>
                                <span class="file-name"></span>
                            </div>
                            <small style="color: #000000; margin-top: 5px; display: block;">
                                Accepted formats: JPG, PNG, PDF, DOCX (Max 10MB)
                            </small>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Submit PMS Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // File upload handling
        const uploadInput = document.getElementById('uploaded_receipt');
        if (uploadInput) {
            uploadInput.addEventListener('change', function() {
                const fileName = this.files[0]?.name || '';
                const fileNameSpan = document.querySelector('.file-name');
                if (fileNameSpan) {
                    fileNameSpan.textContent = fileName;
                }
            });
        }

        // Odometer input validation - only allow numbers
        const odometerInput = document.querySelector('input[name="current_odometer"]');
        if (odometerInput) {
            odometerInput.addEventListener('input', function(e) {
                // Remove all non-numeric characters
                this.value = this.value.replace(/\D/g, '');
            });

            odometerInput.addEventListener('paste', function(e) {
                setTimeout(() => {
                    this.value = this.value.replace(/\D/g, '');
                }, 0);
            });
        }

        // Time slot availability check
        const pmsDateInput = document.getElementById('pms_date');
        const pmsTimeInput = document.getElementById('pms_time');
        const warningDiv = document.getElementById('time-slot-warning');
        const warningText = document.getElementById('warning-text');

        function checkTimeSlotAvailability() {
            const date = pmsDateInput?.value;
            const time = pmsTimeInput?.value;

            if (!date || !time || !warningDiv || !warningText) return;

            // Make AJAX request to check availability
            fetch('check_pms_time_slot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pms_date=${encodeURIComponent(date)}&pms_time=${encodeURIComponent(time)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.available === false) {
                    warningDiv.style.display = 'block';
                    warningText.textContent = 'Time is already booked, choose another time';
                    pmsTimeInput.setCustomValidity('This time slot is full');
                } else if (data.count >= 2) {
                    warningDiv.style.display = 'block';
                    warningDiv.style.color = '#ffa500';
                    warningText.textContent = `Only ${3 - data.count} slot(s) remaining for this time`;
                    pmsTimeInput.setCustomValidity('');
                } else {
                    warningDiv.style.display = 'none';
                    pmsTimeInput.setCustomValidity('');
                }
            })
            .catch(error => {
                console.error('Error checking time slot:', error);
            });
        }

        if (pmsDateInput && pmsTimeInput) {
            pmsDateInput.addEventListener('change', checkTimeSlotAvailability);
            pmsTimeInput.addEventListener('change', checkTimeSlotAvailability);
        }
    </script>
</body>
</html>
