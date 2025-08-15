-- Fix Foreign Key Constraints for Short Video Tables
-- This script updates the foreign key constraints to reference creator_profiles.Unique_id
-- instead of users.UserId, since your session uses creator_profiles.Unique_id

-- First, drop the existing foreign key constraints
ALTER TABLE `short_video_likes` DROP FOREIGN KEY `short_video_likes_ibfk_2`;
ALTER TABLE `short_video_saves` DROP FOREIGN KEY `short_video_saves_ibfk_2`;

-- Now add new foreign key constraints that reference creator_profiles.Unique_id
ALTER TABLE `short_video_likes` 
ADD CONSTRAINT `short_video_likes_ibfk_2` 
FOREIGN KEY (`UserID`) REFERENCES `creator_profiles`(`Unique_id`) ON DELETE CASCADE;

ALTER TABLE `short_video_saves` 
ADD CONSTRAINT `short_video_saves_ibfk_2` 
FOREIGN KEY (`UserID`) REFERENCES `creator_profiles`(`Unique_id`) ON DELETE CASCADE;

-- Verify the changes
SHOW CREATE TABLE `short_video_likes`;
SHOW CREATE TABLE `short_video_saves`;
