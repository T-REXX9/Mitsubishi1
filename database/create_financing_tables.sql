-- Create financing configuration tables

-- Table for financing rates by term
CREATE TABLE IF NOT EXISTS `financing_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `term_months` int NOT NULL,
  `annual_rate` decimal(6,4) NOT NULL COMMENT 'Annual interest rate as decimal (e.g., 0.1050 for 10.5%)',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_term` (`term_months`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default financing rates
INSERT INTO `financing_rates` (`term_months`, `annual_rate`) VALUES
(3, 0.0850),
(6, 0.0900),
(12, 0.1050),
(24, 0.1200),
(36, 0.1350),
(48, 0.1500),
(60, 0.1650)
ON DUPLICATE KEY UPDATE
  `annual_rate` = VALUES(`annual_rate`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Table for financing rules and policies
CREATE TABLE IF NOT EXISTS `financing_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `min_down_payment_percent` decimal(5,4) NOT NULL COMMENT 'Minimum down payment as decimal (e.g., 0.2000 for 20%)',
  `max_financing_amount` decimal(12,2) DEFAULT NULL COMMENT 'Maximum loan amount allowed',
  `min_credit_score` int DEFAULT NULL COMMENT 'Minimum credit score required',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default financing rule
INSERT INTO `financing_rules` (`rule_name`, `min_down_payment_percent`, `max_financing_amount`, `is_active`) VALUES
('Default Financing Policy', 0.2000, 5000000.00, 1)
ON DUPLICATE KEY UPDATE
  `min_down_payment_percent` = VALUES(`min_down_payment_percent`),
  `max_financing_amount` = VALUES(`max_financing_amount`),
  `updated_at` = CURRENT_TIMESTAMP;