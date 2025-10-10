-- Create payment-related tables for order management

-- Payment History Table - stores all payment transactions
CREATE TABLE IF NOT EXISTS `payment_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) NOT NULL,
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','online') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `payment_receipt` longblob DEFAULT NULL,
  `receipt_filename` varchar(255) DEFAULT NULL,
  `payment_type` enum('Full','Monthly','Partial','Down Payment') NOT NULL,
  `status` enum('Pending','Confirmed','Rejected') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `processed_by` int DEFAULT NULL COMMENT 'Admin/Agent who processed the payment',
  `processed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_type` (`payment_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Payment Schedule Table - stores expected payment schedule for financing orders
CREATE TABLE IF NOT EXISTS `payment_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `payment_number` int NOT NULL COMMENT 'Payment sequence (1, 2, 3, etc.)',
  `due_date` date NOT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL,
  `status` enum('Pending','Paid','Overdue','Partial') NOT NULL DEFAULT 'Pending',
  `payment_type` enum('Down Payment','Monthly','Final') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_payment` (`order_id`, `payment_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_type` (`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: Foreign key constraints are commented out because the referenced tables use MyISAM engine
-- which doesn't support foreign key constraints. The application will handle referential integrity.

-- Uncomment these constraints if you convert the referenced tables to InnoDB:
-- ALTER TABLE `payment_history`
--   ADD CONSTRAINT `fk_payment_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_payment_history_customer` FOREIGN KEY (`customer_id`) REFERENCES `accounts` (`Id`) ON DELETE CASCADE;

-- ALTER TABLE `payment_schedule`
--   ADD CONSTRAINT `fk_payment_schedule_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_payment_schedule_customer` FOREIGN KEY (`customer_id`) REFERENCES `accounts` (`Id`) ON DELETE CASCADE;

COMMIT;