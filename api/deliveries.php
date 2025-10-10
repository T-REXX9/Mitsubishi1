<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once(dirname(__DIR__) . '/includes/init.php');

// Check if user is logged in and is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
                getDeliveryStats($pdo);
            } else {
                getDeliveries($pdo);
            }
            break;
        case 'POST':
            createDelivery($pdo);
            break;
        case 'PUT':
            updateDelivery($pdo);
            break;
        case 'DELETE':
            deleteDelivery($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function getDeliveries($pdo) {
    $search = $_GET['search'] ?? '';
    $model = $_GET['model'] ?? '';
    $dateRange = $_GET['date_range'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;

    $whereConditions = [];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(d.model_name LIKE ? OR d.variant LIKE ? OR CONCAT('DEL-', LPAD(d.id, 5, '0')) LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($model) && $model !== 'all') {
        $whereConditions[] = "d.model_name LIKE ?";
        $params[] = "%$model%";
    }

    if (!empty($dateRange) && $dateRange !== 'all') {
        switch ($dateRange) {
            case 'today':
                $whereConditions[] = "DATE(d.delivery_date) = CURDATE()";
                break;
            case 'week':
                $whereConditions[] = "d.delivery_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $whereConditions[] = "d.delivery_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
        }
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Ensure limit and offset are integers
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $sql = "SELECT d.*, v.year_model, v.color_options, v.popular_color, v.base_price, v.promotional_price, v.stock_quantity
            FROM deliveries d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            $whereClause
            ORDER BY d.delivery_date DESC, d.created_at DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the deliveries data
    $formattedDeliveries = array_map(function($delivery) {
        // Safe date formatting
        $deliveryDate = $delivery['delivery_date'];
        $formattedDate = 'Invalid Date';
        if ($deliveryDate) {
            try {
                $formattedDate = date('F j, Y', strtotime($deliveryDate));
            } catch (Exception $e) {
                $formattedDate = $deliveryDate; // Fallback to original
            }
        }
        
        return [
            'id' => $delivery['id'],
            'delivery_id' => 'DEL-' . str_pad($delivery['id'], 5, '0', STR_PAD_LEFT),
            'delivery_reference' => 'DEL-' . str_pad($delivery['id'], 5, '0', STR_PAD_LEFT), // Generate from ID since field doesn't exist
            'delivery_date' => $delivery['delivery_date'],
            'delivery_date_formatted' => $formattedDate,
            'vehicle_id' => $delivery['vehicle_id'],
            'model_name' => $delivery['model_name'],
            'variant' => $delivery['variant'],
            'year_model' => $delivery['year_model'] ?? null,
            'color' => $delivery['popular_color'] ?? 'Not specified', // Use popular_color from vehicles table
            'vehicle_details' => trim(($delivery['popular_color'] ?? 'Not specified') . ', ' . ($delivery['year_model'] ?? 'N/A') . ' ' . ($delivery['variant'] ?? ''), ', '),
            'units_delivered' => (int)$delivery['units_delivered'],
            'unit_price' => (float)$delivery['unit_price'],
            'total_value' => (float)$delivery['total_value'],
            'supplier_dealer' => 'Main Supplier', // Default value since field doesn't exist
            'received_by' => 'Admin', // Default value since field doesn't exist
            'delivery_notes' => '', // Default empty since field doesn't exist
            'status' => 'completed', // Default value since field doesn't exist
            'current_stock' => (int)($delivery['stock_quantity'] ?? 0),
            'created_at' => $delivery['created_at'],
            'updated_at' => $delivery['updated_at']
        ];
    }, $deliveries);

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM deliveries d LEFT JOIN vehicles v ON d.vehicle_id = v.id $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'data' => $formattedDeliveries,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => (int)$total,
            'per_page' => $limit
        ]
    ]);
}

function getDeliveryStats($pdo) {
    $stats = [];

    // Total deliveries
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM deliveries");
    $stats['total_deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Today's deliveries
    $stmt = $pdo->query("SELECT COUNT(*) as today FROM deliveries WHERE DATE(delivery_date) = CURDATE()");
    $stats['today_deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC)['today'];

    // Pending deliveries (deliveries for future dates)
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM deliveries WHERE delivery_date > CURDATE()");
    $stats['pending_deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

    // Monthly value (current month)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_value), 0) as monthly_value FROM deliveries WHERE MONTH(delivery_date) = MONTH(CURDATE()) AND YEAR(delivery_date) = YEAR(CURDATE())");
    $stats['monthly_value'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['monthly_value'];

    echo json_encode(['success' => true, 'data' => $stats]);
}

function createDelivery($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['delivery_date', 'vehicle_id', 'model_name', 'variant', 'units_delivered', 'unit_price'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    // Validate units delivered
    if ((int)$input['units_delivered'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Units delivered must be greater than 0']);
        return;
    }

    // Inbound deliveries: just verify vehicle exists (no stock sufficiency check)
    $vehicleStmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ?");
    $vehicleStmt->execute([$input['vehicle_id']]);
    $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Calculate total value
        $totalValue = (float)$input['units_delivered'] * (float)$input['unit_price'];
        
        // Only insert fields that exist in the actual table schema
        $sql = "INSERT INTO deliveries (delivery_date, vehicle_id, model_name, variant, units_delivered, unit_price, total_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $input['delivery_date'],
            $input['vehicle_id'],
            $input['model_name'],
            $input['variant'],
            $input['units_delivered'],
            $input['unit_price'],
            $totalValue
        ]);

        if (!$success) {
            throw new Exception('Failed to create delivery record');
        }

        $deliveryId = $pdo->lastInsertId();

        // Inbound: increase vehicle stock by delivered units
        $updateStockSql = "UPDATE vehicles SET stock_quantity = stock_quantity + ? WHERE id = ?";
        $updateStockStmt = $pdo->prepare($updateStockSql);
        $stockUpdateSuccess = $updateStockStmt->execute([(int)$input['units_delivered'], $input['vehicle_id']]);

        if (!$stockUpdateSuccess) {
            throw new Exception('Failed to update vehicle stock');
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Delivery created successfully',
            'data' => [
                'id' => $deliveryId, 
                'delivery_id' => 'DEL-' . str_pad($deliveryId, 5, '0', STR_PAD_LEFT)
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create delivery']);
    }
}

function updateDelivery($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Delivery ID is required']);
        return;
    }

    // Validate units delivered
    if ((int)$input['units_delivered'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Units delivered must be greater than 0']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get original delivery data for stock adjustment
        $originalStmt = $pdo->prepare("SELECT vehicle_id, units_delivered FROM deliveries WHERE id = ?");
        $originalStmt->execute([$input['id']]);
        $originalDelivery = $originalStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$originalDelivery) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Delivery not found']);
            return;
        }

        $oldVehicleId = (int)$originalDelivery['vehicle_id'];
        $oldUnits = (int)$originalDelivery['units_delivered'];
        $newVehicleId = (int)$input['vehicle_id'];
        $newUnits = (int)$input['units_delivered'];

        // Inbound adjustment: previous record increased stock. Updating should reverse old effect then apply new effect.
        if ($newVehicleId !== $oldVehicleId) {
            // Revert from old vehicle
            $revertOldSql = "UPDATE vehicles SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $revertOldStmt = $pdo->prepare($revertOldSql);
            $revertOldStmt->execute([$oldUnits, $oldVehicleId]);

            // Apply to new vehicle
            $applyNewSql = "UPDATE vehicles SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $applyNewStmt = $pdo->prepare($applyNewSql);
            $applyNewStmt->execute([$newUnits, $newVehicleId]);
        } else {
            // Same vehicle: adjust by delta
            $delta = $newUnits - $oldUnits;
            if ($delta !== 0) {
                $adjustSql = "UPDATE vehicles SET stock_quantity = stock_quantity + ? WHERE id = ?";
                $adjustStmt = $pdo->prepare($adjustSql);
                $adjustStmt->execute([$delta, $newVehicleId]);
            }
        }

        // Calculate total value
        $totalValue = (float)$newUnits * (float)$input['unit_price'];

        // Only update fields that exist in the actual table schema
        $sql = "UPDATE deliveries SET delivery_date = ?, vehicle_id = ?, model_name = ?, variant = ?, 
                units_delivered = ?, unit_price = ?, total_value = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $input['delivery_date'],
            $newVehicleId,
            $input['model_name'],
            $input['variant'],
            $newUnits,
            $input['unit_price'],
            $totalValue,
            $input['id']
        ]);

        if (!$success) {
            throw new Exception('Failed to update delivery record');
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Delivery updated successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update delivery']);
    }
}

function deleteDelivery($pdo) {
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Delivery ID is required']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get delivery data before deletion for stock restoration
        $deliveryStmt = $pdo->prepare("SELECT vehicle_id, units_delivered FROM deliveries WHERE id = ?");
        $deliveryStmt->execute([$id]);
        $delivery = $deliveryStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$delivery) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Delivery not found']);
            return;
        }

        // Delete the delivery record
        $sql = "DELETE FROM deliveries WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id]);

        if (!$success) {
            throw new Exception('Failed to delete delivery record');
        }

        // Inbound: reverse the previous stock increase by subtracting the units
        if ($delivery['vehicle_id']) {
            $reverseStockSql = "UPDATE vehicles SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $reverseStockStmt = $pdo->prepare($reverseStockSql);
            $stockReverseSuccess = $reverseStockStmt->execute([(int)$delivery['units_delivered'], (int)$delivery['vehicle_id']]);
            
            if (!$stockReverseSuccess) {
                throw new Exception('Failed to update vehicle stock');
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Delivery deleted successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete delivery']);
    }
}
?>
