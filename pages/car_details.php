<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Allow public access to view car details - no login required

// Check if vehicle ID is set
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: car_menu.php");
    exit;
}

$vehicle_id = (int)$_GET['id'];

// Fetch vehicle data from database
try {
    $stmt_vehicle = $connect->prepare("SELECT * FROM vehicles WHERE id = ? AND availability_status = 'available'");
    $stmt_vehicle->execute([$vehicle_id]);
    $vehicle = $stmt_vehicle->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        header("Location: car_menu.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: car_menu.php");
    exit;
}

// Fetch user details for header (if logged in)
$user = null;
$displayName = 'Guest';
if (isset($_SESSION['user_id'])) {
    $stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];
}

// Process color options
$color_options = !empty($vehicle['color_options']) ? explode(',', $vehicle['color_options']) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vehicle['model_name']); ?> Details - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reusing styles from car_menu.php for consistency */
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
            z-index: 5;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffd700;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        /* Modern Card Design */
        .vehicle-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .vehicle-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffd700;
            margin-bottom: 5px;
        }

        .vehicle-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .image-section {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .vehicle-image {
            max-width: 100%;
            height: 200px;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }

        .info-section {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .price-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .description-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin: 10px 0;
        }

        .spec-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            border-left: 3px solid #ffd700;
        }

        .spec-label {
            color: #ffd700;
            font-weight: 600;
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            color: white;
            margin-top: 2px;
        }

        .features-section {
            grid-column: 1 / -1;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            color: #ffd700;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .color-tag {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .stock-available {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .stock-low {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
        }

        .stock-out {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .actions-section {
            grid-column: 1 / -1;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.2));
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 15px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 80px;
            cursor: pointer;
        }

        .action-btn i {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .action-btn:hover {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        /* Remove the primary class special styling - all buttons are now equal */
        .action-btn.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Remove the loan class special styling - make it match others */
        .action-btn.loan:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Responsive Grid Layouts */
        @media (max-width: 575px) {
            .action-grid {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(6, 1fr);
                gap: 12px;
            }

            .action-btn {
                padding: 12px 15px;
                font-size: 0.8rem;
                min-height: 65px;
            }

            .action-btn i {
                font-size: 1.2rem;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .action-grid {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(3, 1fr);
                gap: 12px;
            }

            .action-btn {
                padding: 13px 16px;
                font-size: 0.85rem;
                min-height: 70px;
            }

            .action-btn i {
                font-size: 1.3rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 13px;
            }

            .action-btn {
                padding: 14px 18px;
                font-size: 0.87rem;
                min-height: 75px;
            }
        }

        @media (min-width: 992px) {
            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 15px;
            }

            .action-btn {
                padding: 15px 20px;
                font-size: 0.9rem;
                min-height: 80px;
            }
        }

        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .user-section {
                flex-direction: column;
                gap: 10px;
            }

            .card-body {
                grid-template-columns: 1fr;
            }

            .vehicle-title {
                font-size: 1.3rem;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .container {
                padding: 15px 10px;
            }

            .card-header,
            .image-section,
            .info-section,
            .features-section,
            .actions-section {
                padding: 12px;
            }

            .action-btn {
                padding: 8px 12px;
                font-size: 0.75rem;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .card-body {
                grid-template-columns: 1fr;
            }

            .vehicle-title {
                font-size: 1.4rem;
            }

            .specs-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                padding: 20px 15px;
            }

            .card-header,
            .image-section,
            .info-section,
            .features-section,
            .actions-section {
                padding: 15px;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .vehicle-title {
                font-size: 1.6rem;
            }

            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 13px;
            }

            .action-btn {
                padding: 14px 18px;
                font-size: 0.87rem;
                min-height: 75px;
            }
        }

        @media (min-width: 992px) {
            .vehicle-title {
                font-size: 1.8rem;
            }

            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 15px;
            }

            .action-btn {
                padding: 15px 20px;
                font-size: 0.9rem;
                min-height: 80px;
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-avatar"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
                <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            <?php else: ?>
                <span class="welcome-text">Browse as Guest</span>
                <button class="logout-btn" onclick="window.location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Login</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <a href="car_menu.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Car Menu
        </a>

        <div class="vehicle-card">
            <!-- Card Header -->
            <div class="card-header">
                <h1 class="vehicle-title"><?php echo htmlspecialchars($vehicle['model_name']); ?></h1>
                <div class="vehicle-subtitle">
                    <?php if (!empty($vehicle['variant'])): ?>
                        <span><?php echo htmlspecialchars($vehicle['variant']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($vehicle['year_model'])): ?>
                        <span>•</span>
                        <span><?php echo htmlspecialchars($vehicle['year_model']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($vehicle['category'])): ?>
                        <span>•</span>
                        <span><?php echo htmlspecialchars(ucfirst($vehicle['category'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <!-- Image Section -->
                <div class="image-section">
                    <?php if (!empty($vehicle['main_image'])): ?>
                        <?php 
                        // Check if it's a file path or base64 data
                        if (strpos($vehicle['main_image'], 'uploads') !== false || strpos($vehicle['main_image'], '.png') !== false || strpos($vehicle['main_image'], '.jpg') !== false || strpos($vehicle['main_image'], '.jpeg') !== false) {
                            // It's a file path - convert to web path
                            $webPath = str_replace('\\', '/', $vehicle['main_image']);
                            $webPath = preg_replace('/^.*\/htdocs\//', '/', $webPath);
                            echo '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($vehicle['model_name']) . '" class="vehicle-image">';
                        } else if (preg_match('/^[A-Za-z0-9+\/=]+$/', $vehicle['main_image']) && strlen($vehicle['main_image']) > 100) {
                            // It's base64 data
                            echo '<img src="data:image/jpeg;base64,' . $vehicle['main_image'] . '" alt="' . htmlspecialchars($vehicle['model_name']) . '" class="vehicle-image">';
                        } else {
                            // Try base64_encode for backward compatibility
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($vehicle['main_image']) . '" alt="' . htmlspecialchars($vehicle['model_name']) . '" class="vehicle-image">';
                        }
                        ?>
                    <?php else: ?>
                        <img src="../includes/images/default-car.svg"
                            alt="<?php echo htmlspecialchars($vehicle['model_name']); ?>"
                            class="vehicle-image">
                    <?php endif; ?>
                </div>

                <!-- Info Section -->
                <div class="info-section">
                    <!-- Price -->
                    <?php if ($vehicle['base_price']): ?>
                        <div class="price-badge">
                            <?php
                                $base  = (float)$vehicle['base_price'];
                                $promo = isset($vehicle['promotional_price']) ? (float)$vehicle['promotional_price'] : 0;
                                $hasPromo = $promo > 0 && $promo < $base;
                            ?>
                            ₱<?php echo number_format($hasPromo ? $promo : $base, 2); ?>
                            <?php if ($hasPromo): ?>
                                <span style="text-decoration: line-through; opacity: 0.7; margin-left: 8px;">
                                    ₱<?php echo number_format($base, 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if (!empty($vehicle['key_features'])): ?>
                        <p class="description-text"><?php echo htmlspecialchars($vehicle['key_features']); ?></p>
                    <?php endif; ?>

                    <!-- Specs Grid -->
                    <div class="specs-grid">
                        <?php if ($vehicle['engine_type']): ?>
                            <div class="spec-item">
                                <span class="spec-label">Engine</span>
                                <span class="spec-value"><?php echo htmlspecialchars($vehicle['engine_type']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($vehicle['transmission']): ?>
                            <div class="spec-item">
                                <span class="spec-label">Transmission</span>
                                <span class="spec-value"><?php echo htmlspecialchars($vehicle['transmission']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($vehicle['fuel_type']): ?>
                            <div class="spec-item">
                                <span class="spec-label">Fuel Type</span>
                                <span class="spec-value"><?php echo htmlspecialchars($vehicle['fuel_type']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($vehicle['seating_capacity']): ?>
                            <div class="spec-item">
                                <span class="spec-label">Seating</span>
                                <span class="spec-value"><?php echo htmlspecialchars($vehicle['seating_capacity']); ?> passengers</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stock Status -->
                    <div>
                        <?php if ($vehicle['stock_quantity'] > 10): ?>
                            <span class="stock-badge stock-available">
                                <i class="fas fa-check-circle"></i>
                                In Stock (<?php echo $vehicle['stock_quantity']; ?>)
                            </span>
                        <?php elseif ($vehicle['stock_quantity'] > 0): ?>
                            <span class="stock-badge stock-low">
                                <i class="fas fa-exclamation-triangle"></i>
                                Limited Stock (<?php echo $vehicle['stock_quantity']; ?>)
                            </span>
                        <?php else: ?>
                            <span class="stock-badge stock-out">
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="features-section">
                    <!-- Colors -->
                    <?php if (!empty($color_options)): ?>
                        <div style="margin-bottom: 15px;">
                            <div class="section-title">
                                <i class="fas fa-palette"></i>
                                Available Colors
                            </div>
                            <div class="color-tags">
                                <?php foreach ($color_options as $color): ?>
                                    <span class="color-tag"><?php echo htmlspecialchars(trim($color)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Financing Info -->
                    <?php if ($vehicle['min_downpayment_percentage'] || $vehicle['financing_terms']): ?>
                        <div>
                            <div class="section-title">
                                <i class="fas fa-calculator"></i>
                                Financing Options
                            </div>
                            <p class="description-text">
                                <?php if ($vehicle['min_downpayment_percentage']): ?>
                                    Down payment from <?php echo $vehicle['min_downpayment_percentage']; ?>%
                                <?php endif; ?>
                                <?php if ($vehicle['financing_terms']): ?>
                                    <?php echo $vehicle['min_downpayment_percentage'] ? ' • ' : ''; ?>
                                    <?php echo htmlspecialchars($vehicle['financing_terms']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions Section -->
                <div class="actions-section">
                    <div class="action-grid">
                        <a href="#" class="action-btn" onclick="getQuote(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-calculator"></i>
                            <span>Get Quote</span>
                        </a>
                        <a href="#" class="action-btn" onclick="bookTestDrive(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-car"></i>
                            <span>Test Drive</span>
                        </a>
                        <a href="#" class="action-btn" onclick="inquireVehicle(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-question-circle"></i>
                            <span>Inquiry</span>
                        </a>
                        <a href="#" class="action-btn" onclick="view3D(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-cube"></i>
                            <span>3D View</span>
                        </a>
                        <a href="#" class="action-btn" onclick="viewPMSRecord(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-clipboard-list"></i>
                            <span>PMS Record</span>
                        </a>
                        <a href="#" class="action-btn" onclick="applyLoan(<?php echo $vehicle['id']; ?>)">
                            <i class="fas fa-file-contract"></i>
                            <span>Loan</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Remove all modal-related JavaScript since we're not using modal anymore
        function applyLoan(vehicleId) {
            window.location.href = `loan_requirements.php?vehicle_id=${vehicleId}`;
        }

        // Get Quote function
        function getQuote(vehicleId) {
            window.location.href = `quote_request.php?vehicle_id=${vehicleId}`;
        }

        // Book Test Drive function
        function bookTestDrive(vehicleId) {
            window.location.href = `test_drive.php?vehicle_id=${vehicleId}`;
        }

        // Inquire Vehicle function
        function inquireVehicle(vehicleId) {
            window.location.href = `inquiry.php?vehicle_id=${vehicleId}`;
        }

        // 3D View function
        function view3D(vehicleId) {
            window.location.href = `car_3d_view.php?vehicle_id=${vehicleId}`;
        }

        // View PMS Record function
        function viewPMSRecord(vehicleId) {
            window.location.href = `pms_record.php?vehicle_id=${vehicleId}`;
        }
    </script>

    <style>
        /* Remove all modal-related CSS since we're not using modal anymore */
        /* Keep only the existing styles for the page */
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
            z-index: 5;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffd700;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        /* Modern Card Design */
        .vehicle-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .vehicle-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffd700;
            margin-bottom: 5px;
        }

        .vehicle-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .image-section {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .vehicle-image {
            max-width: 100%;
            height: 200px;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }

        .info-section {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .price-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .description-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin: 10px 0;
        }

        .spec-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            border-left: 3px solid #ffd700;
        }

        .spec-label {
            color: #ffd700;
            font-weight: 600;
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            color: white;
            margin-top: 2px;
        }

        .features-section {
            grid-column: 1 / -1;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            color: #ffd700;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .color-tag {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .stock-available {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .stock-low {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
        }

        .stock-out {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .actions-section {
            grid-column: 1 / -1;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.2));
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 15px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 80px;
            cursor: pointer;
        }

        .action-btn i {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .action-btn:hover {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        /* Remove the primary class special styling - all buttons are now equal */
        .action-btn.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Remove the loan class special styling - make it match others */
        .action-btn.loan:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Responsive Grid Layouts */
        @media (max-width: 575px) {
            .action-grid {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(6, 1fr);
                gap: 12px;
            }

            .action-btn {
                padding: 12px 15px;
                font-size: 0.8rem;
                min-height: 65px;
            }

            .action-btn i {
                font-size: 1.2rem;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .action-grid {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(3, 1fr);
                gap: 12px;
            }

            .action-btn {
                padding: 13px 16px;
                font-size: 0.85rem;
                min-height: 70px;
            }

            .action-btn i {
                font-size: 1.3rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 13px;
            }

            .action-btn {
                padding: 14px 18px;
                font-size: 0.87rem;
                min-height: 75px;
            }
        }

        @media (min-width: 992px) {
            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 15px;
            }

            .action-btn {
                padding: 15px 20px;
                font-size: 0.9rem;
                min-height: 80px;
            }
        }

        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .user-section {
                flex-direction: column;
                gap: 10px;
            }

            .card-body {
                grid-template-columns: 1fr;
            }

            .vehicle-title {
                font-size: 1.3rem;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .container {
                padding: 15px 10px;
            }

            .card-header,
            .image-section,
            .info-section,
            .features-section,
            .actions-section {
                padding: 12px;
            }

            .action-btn {
                padding: 8px 12px;
                font-size: 0.75rem;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .card-body {
                grid-template-columns: 1fr;
            }

            .vehicle-title {
                font-size: 1.4rem;
            }

            .specs-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                padding: 20px 15px;
            }

            .card-header,
            .image-section,
            .info-section,
            .features-section,
            .actions-section {
                padding: 15px;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .vehicle-title {
                font-size: 1.6rem;
            }

            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 13px;
            }

            .action-btn {
                padding: 14px 18px;
                font-size: 0.87rem;
                min-height: 75px;
            }
        }

        @media (min-width: 992px) {
            .vehicle-title {
                font-size: 1.8rem;
            }

            .action-grid {
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 15px;
            }

            .action-btn {
                padding: 15px 20px;
                font-size: 0.9rem;
                min-height: 80px;
            }
        }
    </style>
</body>

</html>