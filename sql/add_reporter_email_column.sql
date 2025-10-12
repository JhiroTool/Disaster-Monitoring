-- Add reporter_email column to disasters table
-- This script adds the reporter_email column if it doesn't exist

-- Check if the column exists and add it if it doesn't
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'disasters' 
         AND COLUMN_NAME = 'reporter_email') = 0,
        'ALTER TABLE disasters ADD COLUMN reporter_email VARCHAR(255) NULL AFTER reporter_phone',
        'SELECT "Column reporter_email already exists" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show the current structure to verify
DESCRIBE disasters;

