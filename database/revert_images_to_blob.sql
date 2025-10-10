-- Migration script to revert image storage from file paths back to BLOB for main and additional images
-- Leaves the 360/3D view column unchanged

USE mitsubishi;

-- Backup current data (optional)
-- CREATE TABLE vehicles_backup AS SELECT * FROM vehicles;

-- Revert main_image and additional_images columns to BLOB
ALTER TABLE vehicles 
MODIFY COLUMN main_image LONGBLOB DEFAULT NULL,
MODIFY COLUMN additional_images LONGBLOB DEFAULT NULL;

-- Optionally, clear incompatible data (file paths) if present
-- WARNING: This will remove all existing image data for these columns
-- UPDATE vehicles SET main_image = NULL, additional_images = NULL;

COMMIT;

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'mitsubishi' 
AND TABLE_NAME = 'vehicles'
AND COLUMN_NAME IN ('main_image', 'additional_images', 'view_360_images')
ORDER BY ORDINAL_POSITION;