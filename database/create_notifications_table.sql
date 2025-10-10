-- Notifications table for Mitsubishi system
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL, -- Target user (nullable for role/global notifications)
  target_role VARCHAR(32) DEFAULT NULL, -- Target role (e.g., 'Admin', 'Customer', 'SalesAgent')
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(32) DEFAULT NULL, -- e.g., 'order', 'system', 'payment'
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  related_id INT DEFAULT NULL, -- Optional: link to order, payment, etc.
  INDEX idx_user_id (user_id),
  INDEX idx_target_role (target_role),
  INDEX idx_type (type),
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;