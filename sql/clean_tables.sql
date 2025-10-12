-- Clean Database Script
-- This script will empty all tables except users and disaster_types
-- Run this to reset the database while preserving users and disaster types

-- Disable foreign key checks to allow deletion
SET FOREIGN_KEY_CHECKS = 0;

-- Clean child tables first (tables that reference other tables)
DELETE FROM `notification_recipients`;
DELETE FROM `disaster_updates`;
DELETE FROM `disaster_resources`;
DELETE FROM `notifications`;

-- Clean parent tables
DELETE FROM `disasters`;
DELETE FROM `announcements`;
DELETE FROM `lgus`;
DELETE FROM `resources`;
DELETE FROM `activity_logs`;

-- Clean system settings (optional - uncomment if you want to reset settings)
-- DELETE FROM `system_settings`;

-- Reset auto-increment counters
ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `announcements` AUTO_INCREMENT = 1;
ALTER TABLE `disasters` AUTO_INCREMENT = 1;
ALTER TABLE `disaster_resources` AUTO_INCREMENT = 1;
ALTER TABLE `disaster_updates` AUTO_INCREMENT = 1;
ALTER TABLE `lgus` AUTO_INCREMENT = 1;
ALTER TABLE `notifications` AUTO_INCREMENT = 1;
ALTER TABLE `notification_recipients` AUTO_INCREMENT = 1;
ALTER TABLE `resources` AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Confirmation message
SELECT 'Database cleaned successfully! Users and disaster types preserved.' AS Status;
