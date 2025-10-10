-- Create deliveries table for Mitsubishi Dealership System
-- This table stores all delivery records and integrates with the vehicles inventory

CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_date` date NOT NULL,
  `delivery_reference` varchar(100) NOT NULL,
  `supplier_dealer` varchar(255) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `model_name` varchar(100) NOT NULL,
  `variant` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `units_delivered` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delivery_notes` text DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_reference` (`delivery_reference`),
  KEY `idx_delivery_date` (`delivery_date`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_model_name` (`model_name`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint if vehicles table exists and has proper structure
-- This is commented out by default to avoid conflicts, uncomment if needed
-- ALTER TABLE `deliveries` 
--   ADD CONSTRAINT `fk_deliveries_vehicle_id` 
--   FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) 
--   ON DELETE SET NULL ON UPDATE CASCADE;

-- Insert some sample data (optional, comment out if not needed)
INSERT IGNORE INTO `deliveries` (`delivery_date`, `delivery_reference`, `supplier_dealer`, `vehicle_id`, `model_name`, `variant`, `color`, `units_delivered`, `unit_price`, `total_value`, `delivery_notes`, `received_by`, `status`) VALUES
('2024-01-15', 'DEL-20240115-001', 'Main Supplier', 1, 'Montero Sport', 'GLS Premium', 'Pearl White', 2, 1850000.00, 3700000.00, 'First delivery batch', 'Admin', 'completed'),
('2024-01-20', 'DEL-20240120-002', 'Regional Dealer', 2, 'Strada', 'GLX 4x4', 'Red Diamond', 3, 1200000.00, 3600000.00, 'Regular stock replenishment', 'Admin', 'completed'),
('2024-02-01', 'DEL-20240201-003', 'Main Supplier', 3, 'Xpander', 'GLS Premium', 'Sterling Silver', 5, 1150000.00, 5750000.00, 'High demand model', 'Admin', 'completed');