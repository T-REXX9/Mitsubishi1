-- Note: The orders table already exists with the structure from orders (2).sql
-- This file only creates the supporting tables for payment tracking
-- Payment history table
CREATE TABLE
	IF NOT EXISTS `payment_history` (
		`id` INT AUTO_INCREMENT PRIMARY KEY,
		`order_id` INT NOT NULL,
		`customer_id` INT NOT NULL,
		`payment_number` VARCHAR(50) UNIQUE NOT NULL,
		`payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`amount_paid` DECIMAL(12, 2) NOT NULL,
		`payment_type` ENUM (
			'Down Payment',
			'Monthly Payment',
			'Full Payment',
			'Partial Payment'
		) NOT NULL,
		`payment_method` ENUM (
			'Cash',
			'Bank Transfer',
			'Check',
			'Credit Card',
			'Online Payment'
		) NOT NULL,
		`reference_number` VARCHAR(100) DEFAULT NULL,
		`bank_name` VARCHAR(100) DEFAULT NULL,
		`transaction_id` VARCHAR(100) DEFAULT NULL,
		`status` ENUM ('Pending', 'Confirmed', 'Failed', 'Cancelled') DEFAULT 'Pending',
		`processed_by` INT DEFAULT NULL,
		`receipt_image` LONGBLOB DEFAULT NULL,
		`receipt_filename` VARCHAR(255) DEFAULT NULL,
		`notes` TEXT,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
		INDEX `idx_order_id` (`order_id`),
		INDEX `idx_customer_id` (`customer_id`),
		INDEX `idx_payment_date` (`payment_date`),
		INDEX `idx_status` (`status`)
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Payment schedule table for tracking expected payments
CREATE TABLE
	IF NOT EXISTS `payment_schedule` (
		`id` INT AUTO_INCREMENT PRIMARY KEY,
		`order_id` INT NOT NULL,
		`customer_id` INT NOT NULL,
		`payment_number` INT NOT NULL,
		`due_date` DATE NOT NULL,
		`amount_due` DECIMAL(12, 2) NOT NULL,
		`amount_paid` DECIMAL(12, 2) DEFAULT 0.00,
		`status` ENUM ('Pending', 'Paid', 'Overdue', 'Partial') DEFAULT 'Pending',
		`paid_date` DATE DEFAULT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
		INDEX `idx_order_id` (`order_id`),
		INDEX `idx_customer_id` (`customer_id`),
		INDEX `idx_due_date` (`due_date`),
		INDEX `idx_status` (`status`)
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;