-- Create client bank information table
-- This script adds bank account information storage for clients

CREATE TABLE IF NOT EXISTS `client_bank_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL COMMENT 'Name of the bank',
  `account_number` varchar(50) DEFAULT NULL COMMENT 'Bank account number',
  `account_holder` varchar(100) DEFAULT NULL COMMENT 'Account holder name',
  `iban_swift` varchar(50) DEFAULT NULL COMMENT 'IBAN or SWIFT code for international transfers',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_client` (`client_id`),
  CONSTRAINT `fk_bank_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Client bank account information for withdrawals';