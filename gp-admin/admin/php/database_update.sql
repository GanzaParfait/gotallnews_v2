-- Database update script for multiple image sizes support
-- Run this script to update your existing database

-- Add new columns to the article table for different image sizes
ALTER TABLE `article` 
ADD COLUMN `image_large` VARCHAR(255) NULL AFTER `Image`,
ADD COLUMN `image_medium` VARCHAR(255) NULL AFTER `image_large`,
ADD COLUMN `image_small` VARCHAR(255) NULL AFTER `image_medium`,
ADD COLUMN `image_thumbnail` VARCHAR(255) NULL AFTER `image_small`;

-- Update existing records to have default values
UPDATE `article` SET 
    `image_large` = `Image`,
    `image_medium` = `Image`,
    `image_small` = `Image`,
    `image_thumbnail` = `Image`
WHERE `image_large` IS NULL;

-- Create index for better performance
CREATE INDEX `idx_image_sizes` ON `article` (`image_large`, `image_medium`, `image_small`, `image_thumbnail`);
