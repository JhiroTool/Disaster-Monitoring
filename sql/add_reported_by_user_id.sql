-- Add reported_by_user_id column to disasters table
-- This links disasters to the users who reported them for proper tracking

-- Add the column
ALTER TABLE disasters 
ADD COLUMN reported_by_user_id INT(11) NULL 
AFTER assigned_user_id,
ADD KEY idx_reported_by_user (reported_by_user_id);

-- Add foreign key constraint
ALTER TABLE disasters 
ADD CONSTRAINT fk_disasters_reported_by_user 
FOREIGN KEY (reported_by_user_id) 
REFERENCES users(user_id) 
ON DELETE SET NULL;

-- Update existing disasters to link them to their reporters
-- This is based on matching reporter names and phone numbers
-- You may need to adjust these queries based on your specific data

-- Update disasters with reporter_name 'jhiro' to user ID 3 (example)
-- UPDATE disasters SET reported_by_user_id = 3 WHERE reporter_name = 'jhiro';

-- Update disasters with specific phone numbers (example)
-- UPDATE disasters SET reported_by_user_id = 3 WHERE reporter_phone IN ('09151046166', '09151046167', '09151046169') AND reported_by_user_id IS NULL;

-- Note: The above UPDATE statements are commented out because they should be 
-- run carefully after reviewing the data to ensure correct user mapping.