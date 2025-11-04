-- Create withdrawals table for managing profit withdrawals
-- Run this SQL in your asfour-ims database

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `withdrawal_id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_investment_id` INT(11) NOT NULL,
  `client_id` INT(11) NOT NULL,
  `investment_id` INT(11) NOT NULL,
  `withdrawal_amount` DECIMAL(15,2) NOT NULL,
  `request_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'approved', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
  `withdrawal_proof` VARCHAR(255) DEFAULT NULL COMMENT 'Filename of uploaded transfer proof',
  `processed_date` DATETIME DEFAULT NULL,
  `processed_by` INT(11) DEFAULT NULL COMMENT 'Admin user ID who processed',
  `admin_notes` TEXT DEFAULT NULL,
  `client_notes` TEXT DEFAULT NULL COMMENT 'Client bank details or withdrawal instructions',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`withdrawal_id`),
  KEY `idx_client_investment` (`client_investment_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_investment_id` (`investment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`),
  CONSTRAINT `fk_withdrawal_client_investment` FOREIGN KEY (`client_investment_id`) REFERENCES `client_investments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_withdrawal_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_withdrawal_investment` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores client profit withdrawal requests and admin processing';
