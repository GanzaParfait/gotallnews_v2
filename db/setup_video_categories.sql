-- Setup Video Categories Table
-- Run this script to create the video_categories table and populate it with default categories

-- Create video_categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS `video_categories` (
    `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
    `CategoryName` varchar(100) NOT NULL,
    `CategorySlug` varchar(100) NOT NULL,
    `Description` text,
    `CategoryIcon` varchar(50) DEFAULT 'fa-folder',
    `CategoryColor` varchar(7) DEFAULT '#4e73df',
    `ParentCategoryID` int(11) DEFAULT NULL,
    `SortOrder` int(11) DEFAULT 0,
    `Status` enum('active','inactive') DEFAULT 'active',
    `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
    `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`CategoryID`),
    UNIQUE KEY `CategorySlug` (`CategorySlug`),
    KEY `idx_category_status` (`Status`,`isDeleted`),
    KEY `idx_category_sort` (`SortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default video categories
INSERT IGNORE INTO `video_categories` (`CategoryName`, `CategorySlug`, `Description`, `CategoryIcon`, `CategoryColor`, `SortOrder`) VALUES
('News', 'news', 'Latest news and current events', 'fa-newspaper-o', '#dc3545', 1),
('Technology', 'technology', 'Tech reviews, tutorials, and insights', 'fa-laptop', '#007bff', 2),
('Entertainment', 'entertainment', 'Movies, music, and celebrity news', 'fa-film', '#6f42c1', 3),
('Sports', 'sports', 'Sports highlights and analysis', 'fa-futbol-o', '#28a745', 4),
('Business', 'business', 'Business news and financial insights', 'fa-briefcase', '#ffc107', 5),
('Health', 'health', 'Health tips and medical news', 'fa-heartbeat', '#e83e8c', 6),
('Education', 'education', 'Educational content and tutorials', 'fa-graduation-cap', '#17a2b8', 7),
('Lifestyle', 'lifestyle', 'Fashion, food, and lifestyle tips', 'fa-coffee', '#fd7e14', 8),
('Travel', 'travel', 'Travel guides and destination videos', 'fa-plane', '#20c997', 9),
('Gaming', 'gaming', 'Video game reviews and gameplay', 'fa-gamepad', '#6c757d', 10);

-- Update video_posts table to allow NULL CategoryID if constraint doesn't exist
-- This will fix the foreign key constraint issue
ALTER TABLE `video_posts` MODIFY `CategoryID` int(11) NULL;

-- Add foreign key constraint if it doesn't exist
-- Note: This will only work if the video_categories table exists and has data
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'video_posts'
    AND CONSTRAINT_NAME = 'fk_video_category'
);

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE `video_posts` ADD CONSTRAINT `fk_video_category` FOREIGN KEY (`CategoryID`) REFERENCES `video_categories` (`CategoryID`) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the setup
SELECT 'Video Categories Setup Complete' as status;
SELECT COUNT(*) as total_categories FROM video_categories;
SELECT CategoryName, CategorySlug FROM video_categories ORDER BY SortOrder;
