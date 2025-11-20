-- Add approval status column to clients table
-- This script adds approval functionality for new client registrations

ALTER TABLE `clients` 
ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT 'Account approval status',
ADD COLUMN `approved_by` INT(11) NULL COMMENT 'Admin ID who approved the account',
ADD COLUMN `approved_at` TIMESTAMP NULL COMMENT 'When the account was approved',
ADD COLUMN `rejection_reason` TEXT NULL COMMENT 'Reason for rejection if status is rejected';