-- SQL Script to migrate roles from old system to new admin/reporter only system
-- Run this script to update your existing database

-- First, update existing user roles to the new system
-- Convert all non-admin roles to 'reporter'
UPDATE users 
SET role = 'reporter' 
WHERE role IN ('lgu_admin', 'lgu_staff', 'responder');

-- Update notifications target_role to new system
UPDATE notifications 
SET target_role = 'reporter' 
WHERE target_role IN ('moderator', 'lgu_user', 'citizen');

-- Now modify the enum columns to only allow admin and reporter
ALTER TABLE users 
MODIFY COLUMN role enum('admin','reporter') NOT NULL DEFAULT 'reporter';

ALTER TABLE notifications 
MODIFY COLUMN target_role enum('admin','reporter') DEFAULT NULL;

-- Verify the changes
SELECT 'User roles after migration:' as info;
SELECT role, COUNT(*) as count FROM users GROUP BY role;

SELECT 'Notification target roles after migration:' as info;  
SELECT target_role, COUNT(*) as count FROM notifications WHERE target_role IS NOT NULL GROUP BY target_role;