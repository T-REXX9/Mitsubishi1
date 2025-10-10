<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

// Get vehicle ID from URL
$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
$vehicle = null;

if ($vehicle_id) {
    try {
        $stmt_vehicle = $connect->prepare("SELECT * FROM vehicles WHERE id = ? AND availability_status = 'available'");
        $stmt_vehicle->execute([$vehicle_id]);
        $vehicle = $stmt_vehicle->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Fetch user details for pre-filling
$stmt = $connect->prepare("SELECT a.*, ci.firstname, ci.lastname, ci.mobile_number 
                          FROM accounts a 
                          LEFT JOIN customer_information ci ON a.Id = ci.account_id 
                          WHERE a.Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle file upload
        if (isset($_FILES['drivers_license']) && $_FILES['drivers_license']['error'] === UPLOAD_ERR_OK) {
            $license_data = file_get_contents($_FILES['drivers_license']['tmp_name']);
            
            // Generate gate pass number
            $gate_pass_number = 'MAG-' . strtoupper(substr(md5(time() . $_SESSION['user_id']), 0, 8));
            
            $stmt_insert = $connect->prepare("INSERT INTO test_drive_requests 
                (account_id, vehicle_id, gate_pass_number, customer_name, mobile_number, 
                 selected_date, selected_time_slot, test_drive_location, instructor_agent, drivers_license, terms_accepted) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt_insert->execute([
                $_SESSION['user_id'],
                $vehicle_id,
                $gate_pass_number,
                $_POST['customer_name'],
                $_POST['mobile_number'],
                $_POST['selected_date'],
                $_POST['selected_time'],
                'Showroom',
                $_POST['instructor'],
                $license_data,
                isset($_POST['terms_accepted']) ? 1 : 0
            ]);
            
            header("Location: test_drive_success.php?request_id=" . $connect->lastInsertId());
            exit;
        } else {
            $error_message = "Please upload your driver's license.";
        }
    } catch (PDOException $e) {
        error_log("Test drive insertion error: " . $e->getMessage());
        $error_message = "Failed to submit test drive request. Please try again.";
    }
}

// Get available dates (next 30 days, excluding weekends)
$available_dates = [];
$current_date = new DateTime();
for ($i = 1; $i <= 30; $i++) {
    $check_date = clone $current_date;
    $check_date->add(new DateInterval('P' . $i . 'D'));
    
    // Skip weekends
    if ($check_date->format('N') < 6) { // 1-5 are Monday to Friday
        $available_dates[] = $check_date;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Drive Request - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%); min-height: 100vh; color: white; }
        .header { background: rgba(0, 0, 0, 0.4); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 215, 0, 0.2); position: relative; z-index: 10; }
        .logo-section { display: flex; align-items: center; gap: 20px; }
        .logo { width: 60px; height: auto; filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3)); }
        .brand-text { font-size: 1.4rem; font-weight: 700; background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .user-section { display: flex; align-items: center; gap: 20px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(45deg, #ffd700, #ffed4e); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #b80000; font-size: 1.2rem; }
        .welcome-text { font-size: 1rem; font-weight: 500; }
        .logout-btn { background: linear-gradient(45deg, #d60000, #b30000); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(214, 0, 0, 0.3); }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(214, 0, 0, 0.5); }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px 20px; position: relative; z-index: 5; }
        .back-btn { display: inline-block; margin-bottom: 20px; background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; font-size: 0.9rem; }
        .back-btn:hover { background: #ffd700; color: #1a1a1a; }

        .form-container {
            background: white;
            color: #333;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .form-header {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .form-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .form-body {
            padding: 30px;
            background: #f5f5f5;
        }

        .vehicle-info {
            background: rgba(211, 47, 47, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #d32f2f;
        }

        .vehicle-name {
            color: #d32f2f;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        input, select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .date-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .date-option {
            background: white;
            border: 2px solid #ddd;
            padding: 10px 5px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .date-option:hover, .date-option.selected {
            background: #d32f2f;
            color: white;
            border-color: #d32f2f;
        }

        .date-number {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }

        .date-day {
            font-size: 0.8rem;
            display: block;
        }

        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .time-option {
            background: white;
            border: 2px solid #ddd;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .time-option:hover, .time-option.selected {
            background: #d32f2f;
            color: white;
            border-color: #d32f2f;
        }

        .upload-area {
            border: 2px dashed #d32f2f;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            background: rgba(211, 47, 47, 0.05);
            margin-bottom: 20px;
        }

        .upload-area.dragover {
            background: rgba(211, 47, 47, 0.1);
        }

        .upload-icon {
            font-size: 2rem;
            color: #d32f2f;
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: inline-block;
            background: #d32f2f;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .file-label:hover {
            background: #b71c1c;
        }

        .terms-section {
            background: rgba(255, 215, 0, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .submit-actions {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .btn-primary {
            background: linear-gradient(45deg, #d32f2f, #b71c1c);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .date-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .time-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .submit-actions {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .form-body {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .date-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .time-grid {
                grid-template-columns: 1fr;
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
        <a href="<?php echo $vehicle ? 'car_details.php?id=' . $vehicle['id'] : 'car_menu.php'; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="form-container">
            <div class="form-header">
                <h1 class="form-title">APPLICATION FOR TEST DRIVE</h1>
                <p class="form-subtitle">KM 85.5 MAHARLIKA HIGHWAY, BRGY.SAN IGNACIO, SAN PABLO CITY LAGUNA</p>
            </div>

            <div class="form-body">
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($vehicle): ?>
                    <div class="vehicle-info">
                        <div class="vehicle-name"><?php echo htmlspecialchars($vehicle['model_name']); ?></div>
                        <?php if ($vehicle['variant']): ?>
                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($vehicle['variant']); ?></div>
                        <?php endif; ?>
                        <div style="color: #d32f2f; font-weight: bold; margin-top: 5px;">
                            SRP: ₱<?php echo number_format($vehicle['base_price'], 2); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="customer_name">NAME*</label>
                            <input type="text" id="customer_name" name="customer_name" 
                                   value="<?php echo htmlspecialchars(($user['firstname'] ?? $user['FirstName'] ?? '') . ' ' . ($user['lastname'] ?? $user['LastName'] ?? '')); ?>" 
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="mobile_number">MOBILE*</label>
                            <input type="tel" id="mobile_number" name="mobile_number" 
                                   value="<?php echo htmlspecialchars($user['mobile_number'] ?? ''); ?>" 
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="instructor">INSTRUCTOR*</label>
                            <select id="instructor" name="instructor" required>
                                <option value="">Select Instructor</option>
                                <option value="Reo Remos">Reo Remos</option>
                                <option value="Maria Santos">Maria Santos</option>
                                <option value="John Cruz">John Cruz</option>
                                <option value="Ana Garcia">Ana Garcia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Test Drive AT</label>
                            <div style="background: rgba(68, 138, 255, 0.1); padding: 10px; border-radius: 6px; text-align: center; font-weight: bold; color: #448AFF;">
                                <i class="fas fa-building" style="margin-right: 8px;"></i>
                                Showroom
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Pick the Date (April 2027)*</label>
                        <div class="date-grid">
                            <?php foreach (array_slice($available_dates, 0, 6) as $index => $date): ?>
                                <div class="date-option" data-date="<?php echo $date->format('Y-m-d'); ?>">
                                    <span class="date-number"><?php echo $date->format('j'); ?></span>
                                    <span class="date-day"><?php echo $date->format('D'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selected_date" name="selected_date" required>
                    </div>

                    <div class="form-group">
                        <label>Select Time Slot*</label>
                        <div class="time-grid">
                            <div class="time-option" data-time="8:00 - 9:00">8:00 - 9:00</div>
                            <div class="time-option" data-time="10:00 - 11:00">10:00 - 11:00</div>
                            <div class="time-option" data-time="1:00 - 2:00">1:00 - 2:00</div>
                            <div class="time-option" data-time="2:00 - 3:00">2:00 - 3:00</div>
                        </div>
                        <input type="hidden" id="selected_time" name="selected_time" required>
                    </div>

                    <div class="form-group">
                        <label>Upload Driver's License*</label>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p>Drag and drop your driver's license or</p>
                            <label for="drivers_license" class="file-label">
                                <i class="fas fa-folder-open"></i> UPLOAD
                            </label>
                            <input type="file" id="drivers_license" name="drivers_license" class="file-input" 
                                   accept="image/*,.pdf" required>
                        </div>
                        <div id="fileInfo" style="display: none; color: #d32f2f; font-weight: bold;"></div>
                    </div>

                    <div class="terms-section">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms_accepted" name="terms_accepted" required>
                            <label for="terms_accepted">
                                I agree with Mitsubishi Motors <a href="#" style="color: #d32f2f;">Terms of Service</a>
                            </label>
                        </div>
                    </div>

                    <div class="submit-actions">
                        <a href="<?php echo $vehicle ? 'car_details.php?id=' . $vehicle['id'] : 'car_menu.php'; ?>" class="btn btn-secondary">
                            SKIP
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> SUBMIT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Date selection
        document.querySelectorAll('.date-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.date-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selected_date').value = this.dataset.date;
            });
        });

        // Time selection
        document.querySelectorAll('.time-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.time-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selected_time').value = this.dataset.time;
            });
        });

        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('drivers_license');
        const fileInfo = document.getElementById('fileInfo');

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileInfo(files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                showFileInfo(this.files[0]);
            }
        });

        function showFileInfo(file) {
            fileInfo.style.display = 'block';
            fileInfo.innerHTML = `<i class="fas fa-file"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        }
    </script>
</body>
</html>