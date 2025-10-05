-- Alter disasters table status column to have only ON GOING, IN PROGRESS, COMPLETED

-- First, change the column to VARCHAR temporarily to allow updates
ALTER TABLE `disasters`
MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'ON GOING';

-- Update existing records to map to new status values
UPDATE `disasters`
SET `status` = CASE
    WHEN `status` IN ('pending', 'assigned', 'acknowledged', 'escalated') THEN 'ON GOING'
    WHEN `status` = 'in_progress' THEN 'IN PROGRESS'
    WHEN `status` IN ('resolved', 'closed') THEN 'COMPLETED'
    ELSE 'ON GOING' -- fallback for any unexpected values
END;

-- Alter the status column back to ENUM with new values
ALTER TABLE `disasters`
MODIFY COLUMN `status` ENUM('ON GOING', 'IN PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'ON GOING';