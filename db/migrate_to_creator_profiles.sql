-- =====================================================
-- MIGRATION SCRIPT: Restructure Database to Use creator_profiles
-- =====================================================
-- This script will:
-- 1. Add authentication fields to creator_profiles
-- 2. Migrate data from admin table to creator_profiles
-- 3. Update all foreign key references
-- 4. Remove AdminId columns and replace with ProfileID
-- 5. Drop the admin table

-- Start transaction
START TRANSACTION;

-- =====================================================
-- STEP 1: Add authentication fields to creator_profiles
-- =====================================================

-- Add authentication fields to creator_profiles (without constraints first)
ALTER TABLE `creator_profiles` 
ADD COLUMN `Email` varchar(255) NULL AFTER `DisplayName`,
ADD COLUMN `Password` varchar(255) NULL AFTER `Email`,
ADD COLUMN `PhoneNumber` varchar(15) NULL AFTER `Password`,
ADD COLUMN `Gender` varchar(25) NULL DEFAULT 'Not Specified' AFTER `PhoneNumber`,
ADD COLUMN `Access` varchar(50) NULL DEFAULT 'Granted' AFTER `Gender`,
ADD COLUMN `Unique_id` varchar(255) NULL AFTER `ProfileID`;

-- =====================================================
-- STEP 2: Migrate data from admin to creator_profiles
-- =====================================================

-- First, clean up any existing data in creator_profiles that might conflict
UPDATE `creator_profiles` SET `Email` = NULL WHERE `Email` = '' OR `Email` IS NULL;
UPDATE `creator_profiles` SET `Username` = NULL WHERE `Username` = '' OR `Username` IS NULL;
UPDATE `creator_profiles` SET `Unique_id` = NULL WHERE `Unique_id` = '' OR `Unique_id` IS NULL;

-- Insert admin data into creator_profiles
INSERT INTO `creator_profiles` (
    `ProfileID`, `Unique_id`, `AdminId`, `Username`, `DisplayName`, 
    `Email`, `Password`, `PhoneNumber`, `Gender`, `Access`, 
    `Bio`, `ProfilePhoto`, `Status`, `isDeleted`, `Created_at`, `Updated_at`
)
SELECT 
    `AdminId`, `Unique_id`, `AdminId`, 
    COALESCE(`FirstName`, CONCAT('user_', `AdminId`)) as Username,
    CONCAT(`FirstName`, ' ', `LastName`) as DisplayName,
    `Email`, `Password`, `PhoneNumber`, `Gender`, `Access`,
    'Migrated from admin table' as Bio,
    `Profile` as ProfilePhoto,
    'active' as Status,
    `isDeleted`, `DateCreated`, `DateCreated`
FROM `admin`
ON DUPLICATE KEY UPDATE
    `Email` = VALUES(`Email`),
    `Password` = VALUES(`Password`),
    `PhoneNumber` = VALUES(`PhoneNumber`),
    `Gender` = VALUES(`Gender`),
    `Access` = VALUES(`Access`),
    `Updated_at` = CURRENT_TIMESTAMP;

-- =====================================================
-- STEP 3: Clean up data and add constraints
-- =====================================================

-- Clean up any empty or duplicate values
UPDATE `creator_profiles` SET `Email` = CONCAT('user_', ProfileID, '@migrated.local') WHERE `Email` = '' OR `Email` IS NULL;
UPDATE `creator_profiles` SET `Username` = CONCAT('user_', ProfileID) WHERE `Username` = '' OR `Username` IS NULL;
UPDATE `creator_profiles` SET `Unique_id` = CONCAT('uid_', ProfileID) WHERE `Unique_id` = '' OR `Unique_id` IS NULL;
UPDATE `creator_profiles` SET `Password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE `Password` = '' OR `Password` IS NULL; -- Default password: 'password'
UPDATE `creator_profiles` SET `PhoneNumber` = '0000000000' WHERE `PhoneNumber` = '' OR `PhoneNumber` IS NULL;
UPDATE `creator_profiles` SET `Gender` = 'Not Specified' WHERE `Gender` = '' OR `Gender` IS NULL;
UPDATE `creator_profiles` SET `Access` = 'Granted' WHERE `Access` = '' OR `Access` IS NULL;

-- Now make fields NOT NULL
ALTER TABLE `creator_profiles` 
MODIFY `Email` varchar(255) NOT NULL,
MODIFY `Password` varchar(255) NOT NULL,
MODIFY `PhoneNumber` varchar(15) NOT NULL,
MODIFY `Gender` varchar(25) NOT NULL DEFAULT 'Not Specified',
MODIFY `Access` varchar(50) NOT NULL DEFAULT 'Granted',
MODIFY `Unique_id` varchar(255) NOT NULL;

-- Add unique constraints after data is clean
ALTER TABLE `creator_profiles` 
ADD UNIQUE KEY `uk_email` (`Email`),
ADD UNIQUE KEY `uk_username` (`Username`),
ADD UNIQUE KEY `uk_unique_id` (`Unique_id`);

-- =====================================================
-- STEP 4: Update article table to use ProfileID instead of AdminId
-- =====================================================

-- First, ensure ProfileID is populated for all articles
UPDATE `article` a
JOIN `creator_profiles` cp ON a.AdminId = cp.AdminId
SET a.ProfileID = cp.ProfileID
WHERE a.ProfileID IS NULL;

-- Now remove AdminId column and make ProfileID NOT NULL
ALTER TABLE `article` 
DROP COLUMN `AdminId`,
MODIFY `ProfileID` int(11) NOT NULL;

-- =====================================================
-- STEP 5: Update category table to use ProfileID instead of AdminId
-- =====================================================

-- Add ProfileID column if it doesn't exist
ALTER TABLE `category` 
ADD COLUMN IF NOT EXISTS `ProfileID` int(11) NULL AFTER `CategoryID`;

-- Populate ProfileID from creator_profiles
UPDATE `category` c
JOIN `creator_profiles` cp ON c.AdminId = cp.AdminId
SET c.ProfileID = cp.ProfileID
WHERE c.ProfileID IS NULL;

-- Remove AdminId column and make ProfileID NOT NULL
ALTER TABLE `category` 
DROP COLUMN `AdminId`,
MODIFY `ProfileID` int(11) NOT NULL;

-- =====================================================
-- STEP 6: Update video_posts table to use ProfileID instead of AuthorID
-- =====================================================

-- Add ProfileID column if it doesn't exist
ALTER TABLE `video_posts` 
ADD COLUMN IF NOT EXISTS `ProfileID` int(11) NULL AFTER `AuthorID`;

-- Populate ProfileID from creator_profiles
UPDATE `video_posts` v
JOIN `creator_profiles` cp ON v.AuthorID = cp.AdminId
SET v.ProfileID = cp.ProfileID
WHERE v.ProfileID IS NULL;

-- Remove AuthorID column and make ProfileID NOT NULL
ALTER TABLE `video_posts` 
DROP COLUMN `AuthorID`,
MODIFY `ProfileID` int(11) NOT NULL;

-- =====================================================
-- STEP 7: Update users table to use ProfileID instead of AdminId
-- =====================================================

-- Add ProfileID column if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `ProfileID` int(11) NULL AFTER `AdminId`;

-- Populate ProfileID from creator_profiles
UPDATE `users` u
JOIN `creator_profiles` cp ON u.AdminId = cp.AdminId
SET u.ProfileID = cp.ProfileID
WHERE u.ProfileID IS NULL;

-- Remove AdminId column and make ProfileID NOT NULL
ALTER TABLE `users` 
DROP COLUMN `AdminId`,
MODIFY `ProfileID` int(11) NOT NULL;

-- =====================================================
-- STEP 8: Update creator_profiles to remove AdminId dependency
-- =====================================================

-- Remove AdminId column from creator_profiles
ALTER TABLE `creator_profiles` 
DROP COLUMN `AdminId`;

-- =====================================================
-- STEP 9: Update foreign key constraints
-- =====================================================

-- Drop existing foreign key constraints
ALTER TABLE `article` DROP FOREIGN KEY IF EXISTS `article_ibfk_2`;
ALTER TABLE `category` DROP FOREIGN KEY IF EXISTS `category_ibfk_1`;
ALTER TABLE `creator_profiles` DROP FOREIGN KEY IF EXISTS `fk_creator_admin`;
ALTER TABLE `video_playlists` DROP FOREIGN KEY IF EXISTS `fk_playlist_author`;
ALTER TABLE `video_posts` DROP FOREIGN KEY IF EXISTS `fk_video_author`;
ALTER TABLE `video_subscriptions` DROP FOREIGN KEY IF EXISTS `fk_subscription_channel`;

-- Add new foreign key constraints
ALTER TABLE `article` 
ADD CONSTRAINT `fk_article_creator` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `category` 
ADD CONSTRAINT `fk_category_creator` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `video_posts` 
ADD CONSTRAINT `fk_video_creator` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `users` 
ADD CONSTRAINT `fk_user_creator` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

-- =====================================================
-- STEP 10: Update indexes
-- =====================================================

-- Drop old indexes
ALTER TABLE `article` DROP INDEX IF EXISTS `idx_adminid`;
ALTER TABLE `category` DROP INDEX IF EXISTS `idx_adminid`;
ALTER TABLE `users` DROP INDEX IF EXISTS `idx_adminid`;
ALTER TABLE `creator_profiles` DROP INDEX IF EXISTS `idx_admin_id`;

-- Add new indexes
ALTER TABLE `article` ADD INDEX `idx_profileid` (`ProfileID`);
ALTER TABLE `category` ADD INDEX `idx_profileid` (`ProfileID`);
ALTER TABLE `video_posts` ADD INDEX `idx_profileid` (`ProfileID`);
ALTER TABLE `users` ADD INDEX `idx_profileid` (`ProfileID`);

-- =====================================================
-- STEP 11: Drop the admin table
-- =====================================================

-- Finally, drop the admin table
DROP TABLE IF EXISTS `admin`;

-- =====================================================
-- STEP 12: Update any remaining references
-- =====================================================

-- Update any views or stored procedures that reference admin table
-- (This would need to be done manually based on your specific database objects)

-- Commit the transaction
COMMIT;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check that all tables now use ProfileID
SELECT 'article' as table_name, COUNT(*) as total_records, COUNT(ProfileID) as profileid_count FROM article
UNION ALL
SELECT 'category' as table_name, COUNT(*) as total_records, COUNT(ProfileID) as profileid_count FROM category
UNION ALL
SELECT 'video_posts' as table_name, COUNT(*) as total_records, COUNT(ProfileID) as profileid_count FROM video_posts
UNION ALL
SELECT 'users' as table_name, COUNT(*) as total_records, COUNT(ProfileID) as profileid_count FROM users
UNION ALL
SELECT 'creator_profiles' as table_name, COUNT(*) as total_records, COUNT(ProfileID) as profileid_count FROM creator_profiles;

-- Check foreign key constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME = 'creator_profiles' 
AND TABLE_SCHEMA = DATABASE();

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- The database has been restructured to use creator_profiles as the main table.
-- All AdminId references have been replaced with ProfileID.
-- The admin table has been dropped.
-- 
-- Next steps:
-- 1. Update your PHP code to use ProfileID instead of AdminId
-- 2. Update authentication logic to use creator_profiles table
-- 3. Test all functionality to ensure it works correctly
