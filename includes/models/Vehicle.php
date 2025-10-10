<?php
class Vehicle {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all vehicles with optional filters
    public function getAll($filters = []) {
        $sql = "SELECT * FROM vehicles WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (model_name LIKE :search OR variant LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] == 'out-of-stock') {
                $sql .= " AND stock_quantity = 0";
            } elseif ($filters['status'] == 'low-stock') {
                $sql .= " AND stock_quantity > 0 AND stock_quantity <= min_stock_alert";
            } elseif ($filters['status'] == 'available') {
                $sql .= " AND stock_quantity > min_stock_alert";
            }
        }
        
        $sql .= " ORDER BY model_name ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::getAll(): " . $e->getMessage());
            return [];
        }
    }
    
    // Get single vehicle by ID
    public static function getById($pdo, $id) {
        $sql = "SELECT * FROM vehicles WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($vehicle) {
            // Handle main_image - check if it's a file path or binary data
            if (!empty($vehicle['main_image'])) {
                // If it looks like a file path, keep it as is
                if (strpos($vehicle['main_image'], 'uploads') !== false || strpos($vehicle['main_image'], '.png') !== false || strpos($vehicle['main_image'], '.jpg') !== false || strpos($vehicle['main_image'], '.jpeg') !== false) {
                    // It's a file path, keep as is
                    $vehicle['main_image'] = $vehicle['main_image'];
                } else {
                    // It's binary data, convert to base64
                    $vehicle['main_image'] = base64_encode($vehicle['main_image']);
                }
            }
            // Handle additional_images - check if it's JSON paths or binary data
            if (!empty($vehicle['additional_images'])) {
                // Try to decode as JSON first (file paths)
                $decoded = json_decode($vehicle['additional_images'], true);
                if (is_array($decoded)) {
                    $vehicle['additional_images'] = $decoded;
                } else {
                    // If not JSON, assume it's serialized binary data
                    $unserialized = @unserialize($vehicle['additional_images']);
                    if (is_array($unserialized)) {
                        $vehicle['additional_images'] = array_map('base64_encode', $unserialized);
                    } else {
                        $vehicle['additional_images'] = [];
                    }
                }
            } else {
                $vehicle['additional_images'] = [];
            }
        }
        return $vehicle;
    }
      // Create new vehicle
    public function create($data) {
        $sql = "INSERT INTO vehicles (
            model_name, variant, year_model, category, engine_type,
            transmission, fuel_type, seating_capacity, key_features,
            base_price, promotional_price, min_downpayment_percentage,
            financing_terms, color_options, popular_color,
            main_image, additional_images, view_360_images,
            stock_quantity, min_stock_alert, availability_status,
            expected_delivery_time
        ) VALUES (
            :model_name, :variant, :year_model, :category, :engine_type,
            :transmission, :fuel_type, :seating_capacity, :key_features,
            :base_price, :promotional_price, :min_downpayment_percentage,
            :financing_terms, :color_options, :popular_color,
            :main_image, :additional_images, :view_360_images,
            :stock_quantity, :min_stock_alert, :availability_status,
            :expected_delivery_time
        )";
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::create(): " . $e->getMessage());
            return false;
        }
    }
    
    // Update vehicle
    public function update($id, $data) {
        try {
            $sql = "UPDATE vehicles SET
                model_name = :model_name,
                variant = :variant,
                year_model = :year_model,
                category = :category,
                engine_type = :engine_type,
                transmission = :transmission,
                fuel_type = :fuel_type,
                seating_capacity = :seating_capacity,
                key_features = :key_features,
                base_price = :base_price,
                promotional_price = :promotional_price,
                min_downpayment_percentage = :min_downpayment_percentage,
                financing_terms = :financing_terms,
                color_options = :color_options,
                popular_color = :popular_color,
                stock_quantity = :stock_quantity,
                min_stock_alert = :min_stock_alert,
                availability_status = :availability_status,
                expected_delivery_time = :expected_delivery_time
                WHERE id = :id";
                
            $stmt = $this->db->prepare($sql);
            $data[':id'] = $id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::update(): " . $e->getMessage());
            return false;
        }
    }
    
    // Delete vehicle
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM vehicles WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::delete(): " . $e->getMessage());
            return false;
        }
    }
    
    // Update stock quantity
    public function updateStock($id, $quantity) {
        try {
            $stmt = $this->db->prepare("UPDATE vehicles SET stock_quantity = :quantity WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::updateStock(): " . $e->getMessage());
            return false;
        }
    }
    
    // Get vehicle statistics
    public function getStats() {
        try {
            // Total units available
            $stmt = $this->db->query("SELECT SUM(stock_quantity) as total_units FROM vehicles");
            $totalUnits = $stmt->fetch(PDO::FETCH_ASSOC)['total_units'] ?? 0;
            
            // Models in stock (distinct models with stock > 0)
            $stmt = $this->db->query("SELECT COUNT(DISTINCT model_name) as count FROM vehicles WHERE stock_quantity > 0");
            $modelsInStock = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Low stock alerts (count of vehicles with stock <= min_stock_alert but > 0)
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM vehicles WHERE stock_quantity <= min_stock_alert AND stock_quantity > 0");
            $lowStockAlerts = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Total inventory value
            $stmt = $this->db->query("SELECT SUM(stock_quantity * base_price) as total_value FROM vehicles");
            $totalValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
            
            return [
                'total_units' => $totalUnits,
                'models_in_stock' => $modelsInStock,
                'low_stock_alerts' => $lowStockAlerts,
                'total_value' => $totalValue
            ];
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::getStats(): " . $e->getMessage());
            return [
                'total_units' => 0,
                'models_in_stock' => 0,
                'low_stock_alerts' => 0,
                'total_value' => 0
            ];
        }
    }
    
    // Get distinct categories
    public function getCategories() {
        try {
            $stmt = $this->db->query("SELECT DISTINCT category FROM vehicles ORDER BY category");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Database error in Vehicle::getCategories(): " . $e->getMessage());
            return [];
        }
    }
    
    // Check if stock is low
    public function checkStockStatus($vehicle) {
        if ($vehicle['stock_quantity'] == 0) {
            return 'out-of-stock';
        } elseif ($vehicle['stock_quantity'] <= $vehicle['min_stock_alert']) {
            return 'low-stock';
        }
        return 'available';
    }
    
    // Process image upload
    public function processImageUpload($file) {
        if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        try {
            $content = file_get_contents($_FILES[$file]['tmp_name']);
            return $content;
        } catch (Exception $e) {
            error_log("Image processing error: " . $e->getMessage());
            return null;
        }
    }
    
    // Process multiple image uploads
    public function processMultipleImages($fieldName) {
        $images = [];
        if (!empty($_FILES[$fieldName]['name'][0])) {
            foreach ($_FILES[$fieldName]['tmp_name'] as $key => $tmp_name) {
                if ($_FILES[$fieldName]['error'][$key] === UPLOAD_ERR_OK) {
                    try {
                        $content = file_get_contents($tmp_name);
                        $images[] = base64_encode($content);
                    } catch (Exception $e) {
                        error_log("Multiple image processing error: " . $e->getMessage());
                    }
                }
            }
        }
        
        return !empty($images) ? json_encode($images) : null;
    }
}
?>
