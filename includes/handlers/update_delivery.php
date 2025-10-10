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

// Check if required parameters are provided
if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing delivery ID']);
    exit;
}

$deliveryId = (int)$_POST['id'];

// Validate input
if ($deliveryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid delivery ID']);
    exit;
}

// Validation array for errors
$errors = [];
$requiredFields = [
    'delivery_date' => 'Delivery date',
    'supplier_dealer' => 'Supplier/Dealer',
    'model_name' => 'Vehicle model',
    'variant' => 'Variant',
    'color' => 'Color',
    'units_delivered' => 'Units delivered',
    'unit_price' => 'Unit price'
];

// Check required fields
foreach ($requiredFields as $field => $label) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = "$label is required";
    }
}

// Check if there are validation errors
if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Validation errors occurred',
        'errors' => $errors
    ]);
    exit;
}

try {
    // First check if delivery exists and get old units for stock adjustment
    $checkSql = "SELECT id, units_delivered, vehicle_id FROM deliveries WHERE id = :id";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':id', $deliveryId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Delivery not found']);
        exit;
    }
    
    $oldDelivery = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $oldUnits = $oldDelivery['units_delivered'];
    $oldVehicleId = $oldDelivery['vehicle_id'];
    
    // Prepare data for update
    $deliveryDate = $_POST['delivery_date'];
    $supplierDealer = trim($_POST['supplier_dealer']);
    $vehicleId = isset($_POST['vehicle_id']) && !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
    $modelName = trim($_POST['model_name']);
    $variant = trim($_POST['variant']);
    $color = trim($_POST['color']);
    $unitsDelivered = (int)$_POST['units_delivered'];
    $unitPrice = (float)$_POST['unit_price'];
    $totalValue = $unitsDelivered * $unitPrice;
    $deliveryNotes = isset($_POST['delivery_notes']) ? trim($_POST['delivery_notes']) : null;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'completed';
    
    // Build SQL to update delivery
    $sql = "UPDATE deliveries SET 
            delivery_date = :delivery_date,
            supplier_dealer = :supplier_dealer,
            vehicle_id = :vehicle_id,
            model_name = :model_name,
            variant = :variant,
            color = :color,
            units_delivered = :units_delivered,
            unit_price = :unit_price,
            total_value = :total_value,
            delivery_notes = :delivery_notes,
            status = :status
            WHERE id = :id";
    
    $stmt = $connect->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':delivery_date', $deliveryDate);
    $stmt->bindParam(':supplier_dealer', $supplierDealer);
    $stmt->bindParam(':vehicle_id', $vehicleId);
    $stmt->bindParam(':model_name', $modelName);
    $stmt->bindParam(':variant', $variant);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':units_delivered', $unitsDelivered);
    $stmt->bindParam(':unit_price', $unitPrice);
    $stmt->bindParam(':total_value', $totalValue);
    $stmt->bindParam(':delivery_notes', $deliveryNotes);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $deliveryId);
    
    // Execute the update
    $stmt->execute();
    
    // Update vehicle stock if needed
    if ($oldVehicleId || $vehicleId) {
        // If vehicle changed or units changed, adjust stock
        if ($oldVehicleId && ($oldVehicleId != $vehicleId || $oldUnits != $unitsDelivered)) {
            // Subtract old units from old vehicle
            $updateOldStockQuery = "UPDATE vehicles SET stock_quantity = stock_quantity - :units WHERE id = :vehicle_id";
            $updateOldStmt = $connect->prepare($updateOldStockQuery);
            $updateOldStmt->bindParam(':units', $oldUnits);
            $updateOldStmt->bindParam(':vehicle_id', $oldVehicleId);
            $updateOldStmt->execute();
        }
        
        if ($vehicleId && ($oldVehicleId != $vehicleId || $oldUnits != $unitsDelivered)) {
            // Add new units to new vehicle
            $updateNewStockQuery = "UPDATE vehicles SET stock_quantity = stock_quantity + :units WHERE id = :vehicle_id";
            $updateNewStmt = $connect->prepare($updateNewStockQuery);
            $updateNewStmt->bindParam(':units', $unitsDelivered);
            $updateNewStmt->bindParam(':vehicle_id', $vehicleId);
            $updateNewStmt->execute();
        }
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Delivery updated successfully',
        'delivery_id' => $deliveryId,
        'total_value' => $totalValue
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
