-- Run this SQL script to create all the necessary tables for the shorts system
-- Copy and paste this into your MySQL/MariaDB client or phpMyAdmin

-- First, let's check what tables already exist
SHOW TABLES LIKE '%short%';
SHOW TABLES LIKE '%user%';

-- If the tables don't exist, run the create_shorts_tables.sql script
-- You can find it in the db/ folder

-- For now, let's just create the essential tables if they don't exist:

-- 1. Users table (if not exists)
CREATE TABLE IF NOT EXISTS `users` (
    `UserID` int(11) NOT NULL AUTO_INCREMENT,
    `Username` varchar(50) UNIQUE NOT NULL,
    `Email` varchar(100) UNIQUE NOT NULL,
    `Password` varchar(255) NOT NULL,
    `DisplayName` varchar(100) DEFAULT NULL,
    `ProfilePicture` varchar(255) DEFAULT NULL,
    `Bio` text DEFAULT NULL,
    `IsVerified` tinyint(1) DEFAULT 0,
    `Status` enum('active','inactive','banned') DEFAULT 'active',
    `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `LastLogin` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`UserID`),
    INDEX `idx_username` (`Username`),
    INDEX `idx_email` (`Email`),
    INDEX `idx_status` (`Status`),
    INDEX `idx_created` (`Created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Short video comments (if not exists)
CREATE TABLE IF NOT EXISTS `short_video_comments` (
    `CommentID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `ParentCommentID` int(11) DEFAULT NULL,
    `CommentText` text NOT NULL,
    `Likes` int(11) DEFAULT 0,
    `Status` enum('pending','approved','rejected') DEFAULT 'pending',
    `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`CommentID`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_parent` (`ParentCommentID`),
    INDEX `idx_status` (`Status`),
    INDEX `idx_created` (`Created_at`),
    INDEX `idx_likes` (`Likes`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE,
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE,
    FOREIGN KEY (`ParentCommentID`) REFERENCES `short_video_comments`(`CommentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Short video likes (if not exists)
CREATE TABLE IF NOT EXISTS `short_video_likes` (
    `LikeID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `LikedAt` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`LikeID`),
    UNIQUE KEY `unique_video_user` (`VideoID`, `UserID`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_liked_at` (`LikedAt`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE,
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Short video saves (if not exists)
CREATE TABLE IF NOT EXISTS `short_video_saves` (
    `SaveID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `PlaylistID` int(11) DEFAULT NULL,
    `SavedAt` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`SaveID`),
    UNIQUE KEY `unique_video_user` (`VideoID`, `UserID`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_playlist` (`PlaylistID`),
    INDEX `idx_saved_at` (`SavedAt`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE,
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Short video views (if not exists)
CREATE TABLE IF NOT EXISTS `short_video_views` (
    `ViewID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) DEFAULT NULL,
    `IPAddress` varchar(45) DEFAULT NULL,
    `UserAgent` text DEFAULT NULL,
    `ViewStartTime` timestamp DEFAULT CURRENT_TIMESTAMP,
    `ViewEndTime` timestamp NULL DEFAULT NULL,
    `DurationWatched` int(11) DEFAULT 0,
    `WatchPercentage` decimal(5,2) DEFAULT 0.00,
    `IsCompleted` tinyint(1) DEFAULT 0,
    `DeviceType` enum('desktop','mobile','tablet') DEFAULT 'desktop',
    `Country` varchar(100) DEFAULT NULL,
    `City` varchar(100) DEFAULT NULL,
    PRIMARY KEY (`ViewID`),
    INDEX `idx_video_user` (`VideoID`, `UserID`),
    INDEX `idx_video_time` (`VideoID`, `ViewStartTime`),
    INDEX `idx_user_time` (`UserID`, `ViewStartTime`),
    INDEX `idx_completed` (`IsCompleted`),
    INDEX `idx_duration` (`DurationWatched`),
    INDEX `idx_device` (`DeviceType`),
    INDEX `idx_location` (`Country`, `City`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE,
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now let's add a demo user and some sample data
INSERT IGNORE INTO `users` (`Username`, `Email`, `Password`, `DisplayName`, `Status`) VALUES
('demo_user', 'demo@example.com', 'demo123', 'Demo User', 'active'),
('test_user', 'test@example.com', 'test123', 'Test User', 'active');

-- Check what we have now
SELECT 'Tables created successfully!' as Status;
SHOW TABLES LIKE '%short%';
SHOW TABLES LIKE '%user%';
