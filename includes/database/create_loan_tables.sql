CREATE TABLE
	IF NOT EXISTS loan_applications (
		id INT AUTO_INCREMENT PRIMARY KEY,
		customer_id INT NOT NULL,
		vehicle_id INT NOT NULL,
		application_date DATETIME NOT NULL,
		status ENUM (
			'Pending',
			'Under Review',
			'Approved',
			'Rejected',
			'Completed'
		) DEFAULT 'Pending',
		-- Document files
		valid_id_file LONGBLOB,
		valid_id_filename VARCHAR(255),
		valid_id_type VARCHAR(100),
		income_source_file LONGBLOB,
		income_source_filename VARCHAR(255),
		income_source_type VARCHAR(100),
		employment_certificate_file LONGBLOB,
		employment_certificate_filename VARCHAR(255),
		employment_certificate_type VARCHAR(100),
		payslip_file LONGBLOB,
		payslip_filename VARCHAR(255),
		payslip_type VARCHAR(100),
		company_id_file LONGBLOB,
		company_id_filename VARCHAR(255),
		company_id_type VARCHAR(100),
		-- Application details
		notes TEXT,
		reviewed_by INT,
		reviewed_at DATETIME,
		approval_notes TEXT,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		FOREIGN KEY (customer_id) REFERENCES accounts (Id) ON DELETE CASCADE,
		FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE,
		FOREIGN KEY (reviewed_by) REFERENCES accounts (Id) ON DELETE SET NULL,
		INDEX idx_customer_id (customer_id),
		INDEX idx_vehicle_id (vehicle_id),
		INDEX idx_status (status),
		INDEX idx_application_date (application_date)
	);