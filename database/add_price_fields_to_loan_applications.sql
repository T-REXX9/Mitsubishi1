-- Add price fields to loan_applications table to fix price mismatch issue
-- This ensures that the prices used during submission are preserved for approval validation

USE mitsubishi;

-- Add price fields to store vehicle pricing at time of application
ALTER TABLE loan_applications 
ADD COLUMN vehicle_base_price DECIMAL(15,2) DEFAULT 0 AFTER vehicle_id,
ADD COLUMN vehicle_promotional_price DECIMAL(15,2) DEFAULT NULL AFTER vehicle_base_price,
ADD COLUMN vehicle_effective_price DECIMAL(15,2) DEFAULT 0 AFTER vehicle_promotional_price;

-- Add indexes for better query performance
ALTER TABLE loan_applications 
ADD INDEX idx_vehicle_effective_price (vehicle_effective_price),
ADD INDEX idx_vehicle_base_price (vehicle_base_price);

-- Also add the same fields to the temp table for consistency
ALTER TABLE loan_applications_temp 
ADD COLUMN vehicle_base_price DECIMAL(15,2) DEFAULT 0 AFTER vehicle_id,
ADD COLUMN vehicle_promotional_price DECIMAL(15,2) DEFAULT NULL AFTER vehicle_base_price,
ADD COLUMN vehicle_effective_price DECIMAL(15,2) DEFAULT 0 AFTER vehicle_promotional_price;

COMMIT;

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'mitsubishi' 
AND TABLE_NAME IN ('loan_applications', 'loan_applications_temp')
AND COLUMN_NAME LIKE '%price%'
ORDER BY TABLE_NAME, ORDINAL_POSITION;