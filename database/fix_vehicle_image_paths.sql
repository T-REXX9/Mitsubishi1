-- SQL Script to Fix Vehicle Image Paths
-- This script converts absolute Windows paths to relative paths in the vehicles table
-- Run this script to fix existing records with absolute paths

-- Update main_image column: Convert absolute paths to relative paths
UPDATE vehicles 
SET main_image = CONCAT('uploads/', SUBSTRING_INDEX(main_image, 'uploads/', -1))
WHERE main_image LIKE '%:%'  -- Matches paths with drive letters (C:, D:, etc.)
  AND main_image LIKE '%uploads%';

-- Update additional_images column: Convert absolute paths to relative paths
UPDATE vehicles 
SET additional_images = CONCAT('uploads/', SUBSTRING_INDEX(additional_images, 'uploads/', -1))
WHERE additional_images LIKE '%:%'  -- Matches paths with drive letters
  AND additional_images LIKE '%uploads%';

-- Update view_360_images column: Convert absolute paths to relative paths
UPDATE vehicles 
SET view_360_images = CONCAT('uploads/', SUBSTRING_INDEX(view_360_images, 'uploads/', -1))
WHERE view_360_images LIKE '%:%'  -- Matches paths with drive letters
  AND view_360_images LIKE '%uploads%';

-- Verify the changes
SELECT 
    id,
    model_name,
    main_image,
    CASE 
        WHEN main_image LIKE '%:%' THEN 'STILL HAS ABSOLUTE PATH'
        WHEN main_image LIKE 'uploads/%' THEN 'FIXED - RELATIVE PATH'
        ELSE 'OTHER FORMAT'
    END as main_image_status
FROM vehicles
WHERE main_image IS NOT NULL AND main_image != ''
ORDER BY id;