-- Add duration column to investments table
-- Run this ONLY if you have an existing database
-- If column already exists, you'll get an error - that's expected and safe to ignore

ALTER TABLE investments 
ADD COLUMN duration VARCHAR(50) DEFAULT NULL 
COMMENT 'Project duration (e.g., 3 months, 1 year)' 
AFTER profit_percent_max;
