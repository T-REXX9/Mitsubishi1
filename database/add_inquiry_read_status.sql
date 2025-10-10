-- Add is_read column to inquiries table
ALTER TABLE inquiries 
ADD COLUMN is_read_by_customer BOOLEAN DEFAULT 0,
ADD COLUMN is_read_by_admin BOOLEAN DEFAULT 1,
ADD COLUMN last_read_by_customer TIMESTAMP NULL,
ADD COLUMN last_read_by_admin TIMESTAMP NULL;

-- Update existing inquiries to mark them as read by admin (since they've been viewed in admin panel)
UPDATE inquiries SET is_read_by_admin = 1;

-- Create a new table to track inquiry views if it doesn't exist
CREATE TABLE IF NOT EXISTS inquiry_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    user_id INT NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(Id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES accounts(Id) ON DELETE CASCADE,
    INDEX idx_inquiry_views_inquiry_id (inquiry_id),
    INDEX idx_inquiry_views_user (user_id, user_role)
);
