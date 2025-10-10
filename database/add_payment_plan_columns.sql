-- Add payment plan columns to loan_applications table
-- This script adds the missing payment plan fields that exist in loan_applications_temp

USE mitsubishi;

-- Add payment plan columns to the main loan_applications table
ALTER TABLE loan_applications 
ADD COLUMN down_payment DECIMAL(15,2) DEFAULT 0 AFTER applicant_type,
ADD COLUMN financing_term INT DEFAULT 12 AFTER down_payment,
ADD COLUMN monthly_payment DECIMAL(15,2) DEFAULT 0 AFTER financing_term,
ADD COLUMN total_amount DECIMAL(15,2) DEFAULT 0 AFTER monthly_payment,
ADD COLUMN interest_rate DECIMAL(5,2) DEFAULT 0 AFTER total_amount;

-- Add indexes for better query performance
ALTER TABLE loan_applications 
ADD INDEX idx_financing_term (financing_term),
ADD INDEX idx_down_payment (down_payment);

COMMIT;

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'mitsubishi' 
AND TABLE_NAME = 'loan_applications' 
AND COLUMN_NAME IN ('down_payment', 'financing_term', 'monthly_payment', 'total_amount', 'interest_rate')
ORDER BY ORDINAL_POSITION;