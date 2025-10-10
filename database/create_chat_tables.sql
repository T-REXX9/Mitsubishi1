CREATE TABLE
	IF NOT EXISTS `conversations` (
		`conversation_id` int NOT NULL AUTO_INCREMENT,
		`customer_id` int NOT NULL,
		`agent_id` int NULL,
		`status` enum ('Active', 'Closed', 'Pending') DEFAULT 'Active',
		`last_message_at` timestamp NULL DEFAULT NULL,
		`created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
		`updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`conversation_id`),
		UNIQUE KEY `unique_customer_agent` (`customer_id`, `agent_id`),
		KEY `idx_customer` (`customer_id`),
		KEY `idx_agent` (`agent_id`),
		KEY `idx_status` (`status`)
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE
	IF NOT EXISTS `messages` (
		`message_id` int NOT NULL AUTO_INCREMENT,
		`conversation_id` int NOT NULL,
		`sender_id` int NOT NULL,
		`sender_type` enum ('Customer', 'SalesAgent') NOT NULL,
		`message_text` text NOT NULL,
		`is_read` tinyint (1) DEFAULT 0,
		`created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`message_id`),
		KEY `idx_conversation` (`conversation_id`),
		KEY `idx_sender` (`sender_id`),
		KEY `idx_created_at` (`created_at`)
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `conversations` MODIFY `agent_id` int NULL;

DROP INDEX `unique_customer_agent` ON `conversations`;

CREATE UNIQUE INDEX `unique_customer_agent` ON `conversations` (`customer_id`, `agent_id`);