<?php
session_start();
require_once '../../includes/database/db_conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Set JSON header
header('Content-Type: application/json');

// Get action from request
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'add_vehicle':
        addVehicle($connect);
        break;
    case 'save_draft':
        saveVehicleDraft($connect);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addVehicle($connect) {
    try {
        // Validate required fields
        $required_fields = ['model_name'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => "Model name is required"]);
                return;
            }
        }
        
        // Handle file uploads - save to file system and store relative paths
        $main_image_path = null;
        $additional_images_path = null;
        $view_360_images_path = null;
        
        // Process main image
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
            $uploadDir = dirname(dirname(__DIR__)) . '/uploads/vehicle_images/main/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            $fileName = 'vehicle_' . time() . '_main.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $uploadPath)) {
                // Store relative path (no leading slash)
                $main_image_path = 'uploads/vehicle_images/main/' . $fileName;
            }
        }
        
        // Process additional images
        if (isset($_FILES['additional_images'])) {
            $uploadDir = dirname(dirname(__DIR__)) . '/uploads/vehicle_images/additional/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $additional_paths = [];
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] == 0) {
                    $fileExtension = strtolower(pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION));
                    $fileName = 'vehicle_' . time() . '_additional_' . $key . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $additional_paths[] = 'uploads/vehicle_images/additional/' . $fileName;
                    }
                }
            }
            // If we have images, store as JSON
            if (!empty($additional_paths)) {
                $additional_images_path = json_encode($additional_paths);
            }
        }
        
        // Process 360 view images
        if (isset($_FILES['view_360_images'])) {
            $uploadDir = dirname(dirname(__DIR__)) . '/uploads/vehicle_images/360/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $view_360_paths = [];
            foreach ($_FILES['view_360_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['view_360_images']['error'][$key] == 0) {
                    $fileExtension = strtolower(pathinfo($_FILES['view_360_images']['name'][$key], PATHINFO_EXTENSION));
                    $fileName = 'vehicle_' . time() . '_360_' . $key . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $view_360_paths[] = 'uploads/vehicle_images/360/' . $fileName;
                    }
                }
            }
            // If we have images, store as JSON
            if (!empty($view_360_paths)) {
                $view_360_images_path = json_encode($view_360_paths);
            }
        }
        
        // Prepare insert query - matching the existing columns in your vehicles table
        $query = "INSERT INTO vehicles (
            model_name, variant, year_model, category, engine_type, transmission,
            fuel_type, seating_capacity, key_features, base_price, promotional_price,
            min_downpayment_percentage, financing_terms, color_options, popular_color,
            main_image, additional_images, view_360_images, stock_quantity,
            min_stock_alert, availability_status, expected_delivery_time
        ) VALUES (
            :model_name, :variant, :year_model, :category, :engine_type, :transmission,
            :fuel_type, :seating_capacity, :key_features, :base_price, :promotional_price,
            :min_downpayment_percentage, :financing_terms, :color_options, :popular_color,
            :main_image, :additional_images, :view_360_images, :stock_quantity,
            :min_stock_alert, :availability_status, :expected_delivery_time
        )";
        
        $stmt = $connect->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':model_name', $_POST['model_name']);
        $stmt->bindParam(':variant', $_POST['variant']);
        $stmt->bindParam(':year_model', $_POST['year_model']);
        $stmt->bindParam(':category', $_POST['category']);
        $stmt->bindParam(':engine_type', $_POST['engine_type']);
        $stmt->bindParam(':transmission', $_POST['transmission']);
        $stmt->bindParam(':fuel_type', $_POST['fuel_type']);
        $stmt->bindParam(':seating_capacity', $_POST['seating_capacity']);
        $stmt->bindParam(':key_features', $_POST['key_features']);
        $stmt->bindParam(':base_price', $_POST['base_price']);
        $stmt->bindParam(':promotional_price', $_POST['promotional_price']);
        $stmt->bindParam(':min_downpayment_percentage', $_POST['min_downpayment_percentage']);
        $stmt->bindParam(':financing_terms', $_POST['financing_terms']);
        $stmt->bindParam(':color_options', $_POST['color_options']);
        $stmt->bindParam(':popular_color', $_POST['popular_color']);
        $stmt->bindParam(':main_image', $main_image_path);
        $stmt->bindParam(':additional_images', $additional_images_path);
        $stmt->bindParam(':view_360_images', $view_360_images_path);
        $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
        $stmt->bindParam(':min_stock_alert', $_POST['min_stock_alert']);
        $stmt->bindParam(':availability_status', $_POST['availability_status']);
        $stmt->bindParam(':expected_delivery_time', $_POST['expected_delivery_time']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Vehicle added successfully to inventory!',
                'vehicle_id' => $connect->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add vehicle to database']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function saveVehicleDraft($connect) {
    try {
        // Create a basic implementation - you can enhance this later
        // Here we'll just add the vehicle with a draft availability status
        
        // Set availability status to 'draft' regardless of what was selected
        $_POST['availability_status'] = 'draft';
        
        // Use the same function as regular add
        addVehicle($connect);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
