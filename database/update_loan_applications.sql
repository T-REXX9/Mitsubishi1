-- Database update script to enhance loan_applications table for SFM Sales Corp requirements
-- This script adds applicant type and additional document fields

USE mitsubishi;

-- Add applicant_type column to differentiate between EMPLOYED, BUSINESS, and OFW
ALTER TABLE loan_applications 
ADD COLUMN applicant_type ENUM('EMPLOYED', 'BUSINESS', 'OFW') NOT NULL DEFAULT 'EMPLOYED' AFTER vehicle_id;

-- Add additional document fields for comprehensive requirements

-- ITR (Income Tax Return) documents
ALTER TABLE loan_applications 
ADD COLUMN itr_file LONGBLOB AFTER company_id_type,
ADD COLUMN itr_filename VARCHAR(255) AFTER itr_file,
ADD COLUMN itr_type VARCHAR(100) AFTER itr_filename;

-- Proof of Billing (Original)
ALTER TABLE loan_applications 
ADD COLUMN proof_billing_file LONGBLOB AFTER itr_type,
ADD COLUMN proof_billing_filename VARCHAR(255) AFTER proof_billing_file,
ADD COLUMN proof_billing_type VARCHAR(100) AFTER proof_billing_filename;

-- ADA/PDC (Authorized Dealer Agreement/Post Dated Checks)
ALTER TABLE loan_applications 
ADD COLUMN ada_pdc_file LONGBLOB AFTER proof_billing_type,
ADD COLUMN ada_pdc_filename VARCHAR(255) AFTER ada_pdc_file,
ADD COLUMN ada_pdc_type VARCHAR(100) AFTER ada_pdc_filename;

-- Bank Statement (for BUSINESS applicants)
ALTER TABLE loan_applications 
ADD COLUMN bank_statement_file LONGBLOB AFTER ada_pdc_type,
ADD COLUMN bank_statement_filename VARCHAR(255) AFTER bank_statement_file,
ADD COLUMN bank_statement_type VARCHAR(100) AFTER bank_statement_filename;

-- DTI Permit (for BUSINESS applicants)
ALTER TABLE loan_applications 
ADD COLUMN dti_permit_file LONGBLOB AFTER bank_statement_type,
ADD COLUMN dti_permit_filename VARCHAR(255) AFTER dti_permit_file,
ADD COLUMN dti_permit_type VARCHAR(100) AFTER dti_permit_filename;

-- Proof of Remittance (for OFW applicants)
ALTER TABLE loan_applications 
ADD COLUMN remittance_proof_file LONGBLOB AFTER dti_permit_type,
ADD COLUMN remittance_proof_filename VARCHAR(255) AFTER remittance_proof_file,
ADD COLUMN remittance_proof_type VARCHAR(100) AFTER remittance_proof_filename;

-- Latest Contract (for OFW applicants)
ALTER TABLE loan_applications 
ADD COLUMN contract_file LONGBLOB AFTER remittance_proof_type,
ADD COLUMN contract_filename VARCHAR(255) AFTER contract_file,
ADD COLUMN contract_type VARCHAR(100) AFTER contract_filename;

-- SPA (Special Power of Attorney for OFW applicants)
ALTER TABLE loan_applications 
ADD COLUMN spa_file LONGBLOB AFTER contract_type,
ADD COLUMN spa_filename VARCHAR(255) AFTER spa_file,
ADD COLUMN spa_type VARCHAR(100) AFTER spa_filename;

-- Update valid_id field description to reflect "2 Valid IDs" requirement
ALTER TABLE loan_applications 
MODIFY COLUMN valid_id_filename VARCHAR(255) COMMENT '2 Government-issued IDs with photos and signatures';

-- Add index for applicant_type for better query performance
ALTER TABLE loan_applications 
ADD INDEX idx_applicant_type (applicant_type);

COMMIT;