-- Add ProfileID column to article table for proper creator tracking
-- This creates a foreign key relationship between articles and creator profiles

-- First, add the ProfileID column
ALTER TABLE `article` ADD COLUMN `ProfileID` INT(11) NULL AFTER `AdminId`;

-- Add index for better performance
ALTER TABLE `article` ADD INDEX `idx_profileid` (`ProfileID`);

-- Add foreign key constraint
ALTER TABLE `article` 
ADD CONSTRAINT `fk_article_creator_profile` 
FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Update existing articles to link them to creator profiles
-- This assumes that AdminId in article table corresponds to AdminId in creator_profiles table
UPDATE `article` a 
JOIN `creator_profiles` cp ON a.AdminId = cp.AdminId 
SET a.ProfileID = cp.ProfileID 
WHERE cp.isDeleted = 'notDeleted';

-- Create a trigger to automatically set ProfileID when new articles are inserted
DELIMITER $$
CREATE TRIGGER `set_profileid_on_article_insert` 
BEFORE INSERT ON `article` 
FOR EACH ROW 
BEGIN
    DECLARE profile_id INT;
    
    -- Get the ProfileID from creator_profiles based on AdminId
    SELECT ProfileID INTO profile_id 
    FROM creator_profiles 
    WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted' 
    LIMIT 1;
    
    -- Set the ProfileID if found
    IF profile_id IS NOT NULL THEN
        SET NEW.ProfileID = profile_id;
    END IF;
END$$
DELIMITER ;

-- Create a trigger to update ProfileID when AdminId changes
DELIMITER $$
CREATE TRIGGER `update_profileid_on_adminid_change` 
BEFORE UPDATE ON `article` 
FOR EACH ROW 
BEGIN
    DECLARE profile_id INT;
    
    -- Only update if AdminId changed
    IF OLD.AdminId != NEW.AdminId THEN
        -- Get the new ProfileID from creator_profiles based on new AdminId
        SELECT ProfileID INTO profile_id 
        FROM creator_profiles 
        WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted' 
        LIMIT 1;
        
        -- Set the ProfileID if found
        IF profile_id IS NOT NULL THEN
            SET NEW.ProfileID = profile_id;
        ELSE
            SET NEW.ProfileID = NULL;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Verify the changes
SELECT 'Article table structure after changes:' as info;
DESCRIBE `article`;

SELECT 'Sample articles with ProfileID:' as info;
SELECT a.ArticleID, a.Title, a.AdminId, a.ProfileID, cp.DisplayName as CreatorName
FROM `article` a 
LEFT JOIN `creator_profiles` cp ON a.ProfileID = cp.ProfileID 
LIMIT 10;

SELECT 'Foreign key constraints:' as info;
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND REFERENCED_TABLE_NAME = 'creator_profiles';

SELECT 'Triggers created:' as info;
SHOW TRIGGERS LIKE 'article';
