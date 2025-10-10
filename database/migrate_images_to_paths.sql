-- Migration script to change image storage from BLOB to file paths
-- This script converts the vehicles table to store file paths instead of binary data

USE mitsubishi;

-- First, backup existing data (optional - uncomment if needed)
-- CREATE TABLE vehicles_backup AS SELECT * FROM vehicles;

-- Alter the image columns to store file paths instead of BLOB data
ALTER TABLE vehicles 
MODIFY COLUMN main_image TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
MODIFY COLUMN additional_images TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
MODIFY COLUMN view_360_images TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

-- Clear existing BLOB data since it's incompatible with the new structure
-- WARNING: This will remove all existing image data
UPDATE vehicles SET 
main_image = NULL,
additional_images = NULL,
view_360_images = NULL;

-- Add comments to document the new structure
ALTER TABLE vehicles 
MODIFY COLUMN main_image TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Absolute file path to main vehicle image',
MODIFY COLUMN additional_images TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'JSON array of absolute file paths to additional images',
MODIFY COLUMN view_360_images TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'JSON array of absolute file paths to 360/3D view files';

COMMIT;

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'mitsubishi' 
AND TABLE_NAME = 'vehicles'
AND COLUMN_NAME IN ('main_image', 'additional_images', 'view_360_images')
ORDER BY ORDINAL_POSITION;