-- Add payment verification fields to client_investments table
-- Run this SQL script to add the new fields for the payment verification process

USE `asfour-ims`;

-- Add payment proof and verification tracking fields (only if they don't exist)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `payment_proof` VARCHAR(255) DEFAULT NULL COMMENT ''Payment proof document filename'' AFTER `agreement_document`',
    'SELECT ''Column payment_proof already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'payment_proof');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `payment_proof_uploaded_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''When client uploaded payment proof'' AFTER `payment_proof`',
    'SELECT ''Column payment_proof_uploaded_at already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'payment_proof_uploaded_at');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `approved_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''When admin approved the investment request'' AFTER `payment_proof_uploaded_at`',
    'SELECT ''Column approved_at already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'approved_at');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `approved_by` INT(11) DEFAULT NULL COMMENT ''Admin ID who approved'' AFTER `approved_at`',
    'SELECT ''Column approved_by already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'approved_by');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `payment_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''When payment proof was verified'' AFTER `approved_by`',
    'SELECT ''Column payment_verified_at already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'payment_verified_at');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `payment_verified_by` INT(11) DEFAULT NULL COMMENT ''Admin ID who verified payment'' AFTER `payment_verified_at`',
    'SELECT ''Column payment_verified_by already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'payment_verified_by');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` 
     ADD COLUMN `rejection_reason` TEXT DEFAULT NULL COMMENT ''Reason for rejection if status is rejected'' AFTER `payment_verified_by`',
    'SELECT ''Column rejection_reason already exists'' as message'
) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND COLUMN_NAME = 'rejection_reason');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraints (only if they don't exist)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL',
    'SELECT ''Constraint fk_approved_by already exists'' as message'
) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND CONSTRAINT_NAME = 'fk_approved_by');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `client_investments` ADD CONSTRAINT `fk_payment_verified_by` FOREIGN KEY (`payment_verified_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL',
    'SELECT ''Constraint fk_payment_verified_by already exists'' as message'
) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'asfour-ims' 
AND TABLE_NAME = 'client_investments' 
AND CONSTRAINT_NAME = 'fk_payment_verified_by');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include 'payment_pending' and 'payment_partial' status
ALTER TABLE `client_investments` 
MODIFY COLUMN `status` ENUM('pending','approved','payment_pending','payment_partial','rejected','active','completed') DEFAULT 'pending';

-- Verification query
SELECT 'Payment verification fields added successfully!' as message;
SHOW COLUMNS FROM `client_investments`;
