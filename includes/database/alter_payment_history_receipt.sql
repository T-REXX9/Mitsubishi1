-- Add receipt_filename column to payment_history table
-- This script adds support for storing receipt file names

ALTER TABLE `payment_history` 
ADD COLUMN `receipt_filename` VARCHAR(255) DEFAULT NULL 
AFTER `receipt_image`;

-- Add comment to document the change
ALTER TABLE `payment_history` 
COMMENT = 'Payment history table with receipt file support';