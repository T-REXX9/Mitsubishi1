<?php
// Include database connection
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Set response header to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['delivery_date', 'supplier_dealer', 'model_name', 'variant', 'color', 'units_delivered', 'unit_price'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => 'Validation error', 'errors' => $errors]);
        exit;
    }
    
    // Prepare data
    $delivery_date = trim($_POST['delivery_date']);
    $supplier_dealer = trim($_POST['supplier_dealer']);
    $model_name = trim($_POST['model_name']);
    $variant = trim($_POST['variant']);
    $color = trim($_POST['color']);
    $units_delivered = intval($_POST['units_delivered']);
    $unit_price = floatval($_POST['unit_price']);
    $total_value = $units_delivered * $unit_price;
    $delivery_notes = isset($_POST['delivery_notes']) ? trim($_POST['delivery_notes']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'completed';
    $received_by = isset($_POST['received_by']) ? trim($_POST['received_by']) : '';
    $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    
    // Generate a reference number if not provided
    $delivery_reference = isset($_POST['delivery_reference']) && !empty($_POST['delivery_reference']) 
        ? trim($_POST['delivery_reference']) 
        : 'DEL-' . date('Ymd') . '-' . substr(uniqid(), -5);
    
    // Begin transaction
    $connect->beginTransaction();
    
    // Insert into deliveries table
    $sql = "INSERT INTO deliveries (
                delivery_date, delivery_reference, supplier_dealer, vehicle_id, 
                model_name, variant, color, units_delivered, unit_price, 
                total_value, delivery_notes, received_by, status
            ) VALUES (
                :delivery_date, :delivery_reference, :supplier_dealer, :vehicle_id,
                :model_name, :variant, :color, :units_delivered, :unit_price,
                :total_value, :delivery_notes, :received_by, :status
            )";
    
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':delivery_date', $delivery_date);
    $stmt->bindParam(':delivery_reference', $delivery_reference);
    $stmt->bindParam(':supplier_dealer', $supplier_dealer);
    $stmt->bindParam(':vehicle_id', $vehicle_id, $vehicle_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':model_name', $model_name);
    $stmt->bindParam(':variant', $variant);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':units_delivered', $units_delivered, PDO::PARAM_INT);
    $stmt->bindParam(':unit_price', $unit_price);
    $stmt->bindParam(':total_value', $total_value);
    $stmt->bindParam(':delivery_notes', $delivery_notes);
    $stmt->bindParam(':received_by', $received_by);
    $stmt->bindParam(':status', $status);
    
    $stmt->execute();
    $delivery_id = $connect->lastInsertId();
    
    // Update vehicle stock if status is completed
    if ($status === 'completed' && $vehicle_id) {
        $updateStockSql = "UPDATE vehicles SET 
                              stock_quantity = stock_quantity + :units,
                              updated_at = CURRENT_TIMESTAMP
                           WHERE id = :vehicle_id";
        $updateStockStmt = $connect->prepare($updateStockSql);
        $updateStockStmt->bindParam(':units', $units_delivered, PDO::PARAM_INT);
        $updateStockStmt->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
        $updateStockStmt->execute();
    }
    
    // Commit transaction
    $connect->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Delivery recorded successfully',
        'delivery_id' => $delivery_id,
        'delivery_reference' => $delivery_reference,
        'total_value' => $total_value
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
