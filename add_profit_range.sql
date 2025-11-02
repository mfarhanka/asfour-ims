-- Add profit percent range support
-- Allows investments to show profit ranges like "25% - 30%"

USE `asfour-ims`;

-- Add min and max profit percent columns to investments table
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `investments` ADD COLUMN `profit_percent_min` DECIMAL(5,2) DEFAULT NULL COMMENT ''Minimum profit percentage'' AFTER `profit_percent`',
    'SELECT ''Column profit_percent_min already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'investments' AND COLUMN_NAME = 'profit_percent_min');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `investments` ADD COLUMN `profit_percent_max` DECIMAL(5,2) DEFAULT NULL COMMENT ''Maximum profit percentage'' AFTER `profit_percent_min`',
    'SELECT ''Column profit_percent_max already exists'''
) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'asfour-ims' AND TABLE_NAME = 'investments' AND COLUMN_NAME = 'profit_percent_max');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to use the new range format
-- If profit_percent exists and min/max are NULL, set both min and max to profit_percent
UPDATE `investments` 
SET `profit_percent_min` = `profit_percent`,
    `profit_percent_max` = `profit_percent`
WHERE `profit_percent` IS NOT NULL 
  AND (`profit_percent_min` IS NULL OR `profit_percent_max` IS NULL);

-- Verification queries
SELECT 'Profit range columns added successfully!' as message;
SHOW COLUMNS FROM `investments` LIKE 'profit_percent%';

-- Sample query to check profit ranges
SELECT id, title, profit_percent, profit_percent_min, profit_percent_max
FROM investments
LIMIT 5;
