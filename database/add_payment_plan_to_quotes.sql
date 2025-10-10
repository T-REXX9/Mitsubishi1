-- Add payment plan fields to quotes table
ALTER TABLE quotes ADD COLUMN loan_amount DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN down_payment DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN financing_term INT DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN monthly_payment DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN total_amount DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN total_interest DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN payment_plan_calculated BOOLEAN DEFAULT FALSE;

-- Add index for better performance
CREATE INDEX idx_quotes_payment_calculated ON quotes(payment_plan_calculated);