<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    include_once(dirname(__DIR__) . '/includes/init.php');
} catch (Exception $e) {
    error_log("Init include error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System initialization failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in', 'debug' => 'No user_role in session']);
    exit();
}

// Check permissions based on request method
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied - Admin role required for modifications', 'debug' => 'User role: ' . $_SESSION['user_role'] . ', Method: ' . $method]);
    exit();
}

$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    error_log("Vehicles API: No PDO connection available");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available', 'debug' => 'PDO not found in globals']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'stock') {
                getVehicleStock($pdo);
            } elseif ($action === 'low-stock') {
                getLowStockVehicles($pdo);
            } elseif (isset($_GET['categories'])) {
                getVehicleCategories($pdo);
            } elseif (isset($_GET['stats'])) {
                getVehicleStats($pdo);
            } elseif (isset($_GET['id'])) {
                getVehicleById($pdo, $_GET['id']);
            } else {
                getVehicles($pdo);
            }
            break;
        case 'POST':
            createVehicle($pdo);
            break;
        case 'PUT':
            if ($action === 'stock') {
                updateVehicleStock($pdo);
            } elseif ($action === 'price') {
                updateVehiclePrice($pdo);
            } else {
                updateVehicle($pdo);
            }
            break;
        case 'DELETE':
            deleteVehicle($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Vehicles API Error - Method: {$method}, Action: {$action}, Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error',
        'debug' => $_SERVER['SERVER_NAME'] === 'localhost' ? $e->getMessage() : 'Check server logs'
    ]);
}

function getVehicles($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $availability = $_GET['availability'] ?? '';
        
        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "(model_name LIKE ? OR variant LIKE ? OR category LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if (!empty($category) && $category !== 'all') {
            $whereConditions[] = "category = ?";
            $params[] = $category;
        }

        if (!empty($availability) && $availability !== 'all') {
            $whereConditions[] = "availability_status = ?";
            $params[] = $availability;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT * FROM vehicles $whereClause ORDER BY model_name, variant";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $vehicles]);
    } catch (Exception $e) {
        error_log("getVehicles error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load vehicles', 'debug' => $e->getMessage()]);
    }
}

function getVehicleStock($pdo) {
    $sql = "SELECT id, model_name, variant, stock_quantity, min_stock_alert, 
                   (stock_quantity - min_stock_alert) as stock_difference,
                   CASE 
                       WHEN stock_quantity <= 0 THEN 'out_of_stock'
                       WHEN stock_quantity <= min_stock_alert THEN 'low_stock'
                       ELSE 'in_stock'
                   END as stock_status
            FROM vehicles 
            ORDER BY stock_quantity ASC, model_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stockData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stockData]);
}

function updateVehicleStock($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['vehicle_id']) || !isset($input['quantity'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vehicle ID and quantity are required']);
        return;
    }

    $vehicleId = (int)$input['vehicle_id'];
    $quantity = (int)$input['quantity'];
    $operation = $input['operation'] ?? 'set'; // set, add, subtract

    try {
        $pdo->beginTransaction();

        if ($operation === 'set') {
            $sql = "UPDATE vehicles SET stock_quantity = ? WHERE id = ?";
            $params = [$quantity, $vehicleId];
        } elseif ($operation === 'add') {
            $sql = "UPDATE vehicles SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $params = [$quantity, $vehicleId];
        } elseif ($operation === 'subtract') {
            $sql = "UPDATE vehicles SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?";
            $params = [$quantity, $vehicleId];
        } else {
            throw new Exception('Invalid operation');
        }

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            throw new Exception('Failed to update vehicle stock');
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateVehiclePrice($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['vehicle_id', 'base_price'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    try {
        $sql = "UPDATE vehicles SET base_price = ?, promotional_price = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $input['base_price'],
            $input['promotional_price'] ?? null,
            $input['vehicle_id']
        ]);

        if (!$success) {
            throw new Exception('Failed to update vehicle prices');
        }

        echo json_encode(['success' => true, 'message' => 'Prices updated successfully']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getLowStockVehicles($pdo) {
    $sql = "SELECT id, model_name, variant, stock_quantity, min_stock_alert, base_price
            FROM vehicles 
            WHERE stock_quantity <= min_stock_alert 
            AND availability_status != 'discontinued'
            ORDER BY (stock_quantity / NULLIF(min_stock_alert, 0)) ASC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lowStockVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $lowStockVehicles]);
}

function getVehicleCategories($pdo) {
    try {
        $sql = "SELECT DISTINCT category FROM vehicles WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'data' => $categories]);
    } catch (Exception $e) {
        error_log("getVehicleCategories error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load categories', 'debug' => $e->getMessage()]);
    }
}

function getVehicleStats($pdo) {
    try {
        $sql = "SELECT 
                    COUNT(*) as total_units,
                    COUNT(DISTINCT CONCAT(model_name, variant)) as models_in_stock,
                    COUNT(CASE WHEN stock_quantity <= min_stock_alert THEN 1 END) as low_stock_alerts,
                    COALESCE(SUM(base_price * stock_quantity), 0) as total_value
                FROM vehicles
                WHERE availability_status != 'discontinued'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure all values are properly formatted
        $stats['total_units'] = (int)$stats['total_units'];
        $stats['models_in_stock'] = (int)$stats['models_in_stock'];
        $stats['low_stock_alerts'] = (int)$stats['low_stock_alerts'];
        $stats['total_value'] = (float)$stats['total_value'];
        
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        error_log("getVehicleStats error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load stats', 'debug' => $e->getMessage()]);
    }
}

function getVehicleById($pdo, $id) {
    $sql = "SELECT * FROM vehicles WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle) {
        echo json_encode(['success' => true, 'data' => $vehicle]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
    }
}

function createVehicle($pdo) {
    $requiredFields = ['model_name', 'variant', 'year_model', 'category', 'base_price', 'stock_quantity'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $sql = "INSERT INTO vehicles (
                    model_name, variant, year_model, category, engine_type, transmission, 
                    fuel_type, seating_capacity, key_features, base_price, promotional_price,
                    min_downpayment_percentage, financing_terms, color_options, popular_color,
                    stock_quantity, min_stock_alert, availability_status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available'
                )";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $_POST['model_name'],
            $_POST['variant'],
            $_POST['year_model'],
            $_POST['category'],
            $_POST['engine_type'] ?? null,
            $_POST['transmission'] ?? null,
            $_POST['fuel_type'] ?? null,
            $_POST['seating_capacity'] ?? null,
            $_POST['key_features'] ?? null,
            $_POST['base_price'],
            $_POST['promotional_price'] ?? null,
            $_POST['min_downpayment'] ?? null,
            $_POST['financing_terms'] ?? null,
            $_POST['color_options'] ?? null,
            $_POST['popular_color'] ?? null,
            $_POST['stock_quantity'],
            $_POST['min_stock_alert'] ?? 5
        ]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Vehicle created successfully']);
        } else {
            throw new Exception('Failed to create vehicle');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateVehicle($pdo) {
    parse_str(file_get_contents('php://input'), $input);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vehicle ID is required']);
        return;
    }
    
    try {
        // Check if this is just a stock update
        if (isset($input['stock_quantity']) && count($input) == 2) {
            $sql = "UPDATE vehicles SET stock_quantity = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$input['stock_quantity'], $input['id']]);
        } else {
            // Full vehicle update
            $sql = "UPDATE vehicles SET 
                        model_name = ?, variant = ?, year_model = ?, category = ?,
                        engine_type = ?, transmission = ?, fuel_type = ?, seating_capacity = ?,
                        key_features = ?, base_price = ?, promotional_price = ?,
                        min_downpayment_percentage = ?, financing_terms = ?, color_options = ?,
                        popular_color = ?, stock_quantity = ?, min_stock_alert = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $input['modelName'] ?? $input['model_name'],
                $input['variant'],
                $input['yearModel'] ?? $input['year_model'],
                $input['category'],
                $input['engineType'] ?? $input['engine_type'],
                $input['transmission'],
                $input['fuelType'] ?? $input['fuel_type'],
                $input['seatingCapacity'] ?? $input['seating_capacity'],
                $input['keyFeatures'] ?? $input['key_features'],
                $input['basePrice'] ?? $input['base_price'],
                $input['promotionalPrice'] ?? $input['promotional_price'],
                $input['minDownpayment'] ?? $input['min_downpayment_percentage'],
                $input['financingTerms'] ?? $input['financing_terms'],
                $input['colorOptions'] ?? $input['color_options'],
                $input['popularColor'] ?? $input['popular_color'],
                $input['stockQuantity'] ?? $input['stock_quantity'],
                $input['minStockAlert'] ?? $input['min_stock_alert'],
                $input['id']
            ]);
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully']);
        } else {
            throw new Exception('Failed to update vehicle');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteVehicle($pdo) {
    parse_str(file_get_contents('php://input'), $input);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vehicle ID is required']);
        return;
    }
    
    try {
        $sql = "DELETE FROM vehicles WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$input['id']]);
        
        if ($success) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
            }
        } else {
            throw new Exception('Failed to delete vehicle');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>