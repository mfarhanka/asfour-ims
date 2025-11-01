-- Add partial payment support and agreement requirement
-- Run this SQL script after add_payment_verification.sql

USE `asfour-ims`;

-- Create payment_transactions table to track individual payments
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_investment_id` INT(11) NOT NULL,
  `payment_amount` DECIMAL(15,2) NOT NULL,
  `payment_proof` VARCHAR(255) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_notes` TEXT DEFAULT NULL,
  `status` ENUM('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_by` INT(11) DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_investment_id` (`client_investment_id`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `fk_payment_transaction_investment` FOREIGN KEY (`client_investment_id`) REFERENCES `client_investments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_transaction_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add partial payment tracking fields to client_investments (with existence checks)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD COLUMN `total_paid` DECIMAL(15,2) DEFAULT 0.00 COMMENT ''Total amount paid so far'' AFTER `invested_amount`',
    'SELECT ''Column total_paid already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'client_investments' AND COLUMN_NAME = 'total_paid');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD COLUMN `remaining_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT ''Remaining amount to be paid'' AFTER `total_paid`',
    'SELECT ''Column remaining_amount already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'client_investments' AND COLUMN_NAME = 'remaining_amount');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD COLUMN `is_fully_paid` TINYINT(1) DEFAULT 0 COMMENT ''1 if fully paid, 0 otherwise'' AFTER `remaining_amount`',
    'SELECT ''Column is_fully_paid already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'client_investments' AND COLUMN_NAME = 'is_fully_paid');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD COLUMN `agreement_uploaded` TINYINT(1) DEFAULT 0 COMMENT ''1 if agreement uploaded, 0 otherwise'' AFTER `agreement_document`',
    'SELECT ''Column agreement_uploaded already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'client_investments' AND COLUMN_NAME = 'agreement_uploaded');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to set initial values
UPDATE `client_investments` SET `remaining_amount` = `invested_amount` WHERE `remaining_amount` = 0;

-- Update status enum to include 'payment_partial' status (safely update if needed)
-- Check current enum values and update only if necessary
SET @sql = (SELECT IF(
    COLUMN_TYPE LIKE '%payment_partial%',
    'SELECT ''Status enum already includes payment_partial''',
    'ALTER TABLE `client_investments` MODIFY COLUMN `status` ENUM(''pending'',''approved'',''payment_pending'',''payment_partial'',''rejected'',''active'',''completed'') DEFAULT ''pending'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'client_investments' AND COLUMN_NAME = 'status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add agreement_required field to investments table (optional - can be set per project)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `investments` ADD COLUMN `agreement_required` TINYINT(1) DEFAULT 1 COMMENT ''1 if agreement is required, 0 otherwise'' AFTER `end_date`',
    'SELECT ''Column agreement_required already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'investments' AND COLUMN_NAME = 'agreement_required');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create view for investment payment summary
CREATE OR REPLACE VIEW `v_investment_payment_summary` AS
SELECT 
    ci.id,
    ci.client_id,
    ci.investment_id,
    ci.invested_amount,
    ci.total_paid,
    ci.remaining_amount,
    ci.is_fully_paid,
    ci.agreement_uploaded,
    ci.status,
    c.name as client_name,
    i.title as investment_title,
    COUNT(pt.id) as payment_count,
    SUM(CASE WHEN pt.status = 'verified' THEN pt.payment_amount ELSE 0 END) as verified_payments,
    SUM(CASE WHEN pt.status = 'pending' THEN pt.payment_amount ELSE 0 END) as pending_payments
FROM client_investments ci
LEFT JOIN clients c ON ci.client_id = c.id
LEFT JOIN investments i ON ci.investment_id = i.id
LEFT JOIN payment_transactions pt ON ci.id = pt.client_investment_id
GROUP BY ci.id;

-- Verification queries
SELECT 'Partial payment tables created successfully!' as message;
SHOW COLUMNS FROM `payment_transactions`;
SHOW COLUMNS FROM `client_investments` LIKE '%paid%';
SHOW COLUMNS FROM `client_investments` LIKE 'agreement_uploaded';

-- Sample query to check payment progress
SELECT * FROM v_investment_payment_summary LIMIT 5;
