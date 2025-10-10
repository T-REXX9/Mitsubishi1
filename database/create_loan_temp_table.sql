-- Create temporary loan applications table to store application data before document submission
CREATE TABLE IF NOT EXISTS loan_applications_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    applicant_type ENUM('EMPLOYED', 'BUSINESS', 'OFW') NOT NULL DEFAULT 'EMPLOYED',
    loan_amount DECIMAL(15,2) DEFAULT 0,
    down_payment DECIMAL(15,2) DEFAULT 0,
    financing_term INT DEFAULT 12,
    monthly_payment DECIMAL(15,2) DEFAULT 0,
+    total_amount DECIMAL(15,2) DEFAULT 0,
+    interest_rate DECIMAL(5,2) DEFAULT 0,
    annual_income DECIMAL(15,2) DEFAULT 0,
    employment_status VARCHAR(100),
    employer_name VARCHAR(255),
    employment_years INT DEFAULT 0,
    monthly_income DECIMAL(15,2) DEFAULT 0,
    other_income DECIMAL(15,2) DEFAULT 0,
    monthly_expenses DECIMAL(15,2) DEFAULT 0,
    existing_loans DECIMAL(15,2) DEFAULT 0,
    credit_cards DECIMAL(15,2) DEFAULT 0,
    dependents INT DEFAULT 0,
    marital_status VARCHAR(50) DEFAULT 'Single',
    spouse_income DECIMAL(15,2) DEFAULT 0,
    home_ownership VARCHAR(50) DEFAULT 'Rented',
    years_current_address INT DEFAULT 0,
    reference_contacts TEXT,
    additional_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_customer_vehicle (customer_id, vehicle_id),
    
    INDEX idx_customer_id (customer_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_applicant_type (applicant_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add some sample data or migration logic if needed
-- This table will be used to temporarily store loan application data
-- before the user completes document submission