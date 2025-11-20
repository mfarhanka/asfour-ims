-- Add suspension functionality to clients table
-- This script adds the ability to suspend/unsuspend client accounts

ALTER TABLE `clients` 
MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending' COMMENT 'Account status',
ADD COLUMN `suspended_by` INT(11) NULL COMMENT 'Admin ID who suspended the account',
ADD COLUMN `suspended_at` TIMESTAMP NULL COMMENT 'When the account was suspended',
ADD COLUMN `suspension_reason` TEXT NULL COMMENT 'Reason for suspension',
ADD COLUMN `suspension_end_date` DATE NULL COMMENT 'End date for temporary suspension (NULL for permanent)';