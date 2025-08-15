-- Create missing database tables for shorts functionality
-- Run this script in your MySQL database

-- Table for video likes
CREATE TABLE IF NOT EXISTS `short_video_likes` (
    `LikeID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
  `LikedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`LikeID`),
  UNIQUE KEY `unique_like` (`VideoID`, `UserID`),
  KEY `VideoID` (`VideoID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for video saves
CREATE TABLE IF NOT EXISTS `short_video_saves` (
  `SaveID` int(11) NOT NULL AUTO_INCREMENT,
  `VideoID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `PlaylistID` int(11) DEFAULT NULL,
  `SavedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`SaveID`),
  UNIQUE KEY `unique_save` (`VideoID`, `UserID`),
  KEY `VideoID` (`VideoID`),
  KEY `UserID` (`UserID`),
  KEY `PlaylistID` (`PlaylistID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for video comments
CREATE TABLE IF NOT EXISTS `short_video_comments` (
    `CommentID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
  `ParentCommentID` int(11) DEFAULT NULL,
    `CommentText` text NOT NULL,
  `Status` varchar(20) DEFAULT 'approved',
    `Likes` int(11) DEFAULT 0,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`CommentID`),
  KEY `VideoID` (`VideoID`),
  KEY `UserID` (`UserID`),
  KEY `ParentCommentID` (`ParentCommentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for video views tracking
CREATE TABLE IF NOT EXISTS `short_video_views` (
  `ViewID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` text DEFAULT NULL,
  `DeviceType` varchar(20) DEFAULT 'desktop',
  `ViewStartTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ViewEndTime` timestamp NULL DEFAULT NULL,
  `DurationWatched` int(11) DEFAULT 0,
  `WatchPercentage` decimal(5,2) DEFAULT 0.00,
  `IsCompleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`ViewID`),
  KEY `VideoID` (`VideoID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for user video interactions
CREATE TABLE IF NOT EXISTS `user_video_interactions` (
    `InteractionID` int(11) NOT NULL AUTO_INCREMENT,
    `UserID` int(11) NOT NULL,
    `VideoID` int(11) NOT NULL,
    `HasLiked` tinyint(1) DEFAULT 0,
  `HasSaved` tinyint(1) DEFAULT 0,
    `HasShared` tinyint(1) DEFAULT 0,
  `LastInteraction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`InteractionID`),
  UNIQUE KEY `unique_interaction` (`UserID`, `VideoID`),
  KEY `UserID` (`UserID`),
  KEY `VideoID` (`VideoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for user playlists (optional)
CREATE TABLE IF NOT EXISTS `user_playlists` (
  `PlaylistID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Description` text,
  `VideoCount` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PlaylistID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for user follows
CREATE TABLE IF NOT EXISTS `user_follows` (
  `FollowID` int(11) NOT NULL AUTO_INCREMENT,
  `FollowerID` int(11) NOT NULL,
  `FollowingID` int(11) NOT NULL,
  `FollowedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FollowID`),
  UNIQUE KEY `unique_follow` (`FollowerID`, `FollowingID`),
  KEY `FollowerID` (`FollowerID`),
  KEY `FollowingID` (`FollowingID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes to existing video_posts table if they don't exist
ALTER TABLE `video_posts` ADD INDEX IF NOT EXISTS `idx_video_type` (`videoType`);
ALTER TABLE `video_posts` ADD INDEX IF NOT EXISTS `idx_status` (`Status`);

-- Insert sample data for testing (optional)
INSERT IGNORE INTO `short_video_comments` (`VideoID`, `UserID`, `CommentText`) VALUES 
(1, 1, 'Great video!'),
(1, 2, 'Amazing content!'),
(2, 1, 'Keep it up!');

-- Update video_posts table to ensure Likes column exists
ALTER TABLE `video_posts` ADD COLUMN IF NOT EXISTS `Likes` int(11) DEFAULT 0;
ALTER TABLE `video_posts` ADD COLUMN IF NOT EXISTS `Saves` int(11) DEFAULT 0;
ALTER TABLE `video_posts` ADD COLUMN IF NOT EXISTS `Comments` int(11) DEFAULT 0;

-- Create triggers to update counts automatically
DELIMITER $$

-- Trigger to update video likes count
CREATE TRIGGER IF NOT EXISTS `update_video_likes_count` 
AFTER INSERT ON `short_video_likes` 
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Likes` = `Likes` + 1 WHERE `VideoID` = NEW.VideoID;
END$$

-- Trigger to update video likes count on delete
CREATE TRIGGER IF NOT EXISTS `update_video_likes_count_delete` 
AFTER DELETE ON `short_video_likes` 
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Likes` = GREATEST(`Likes` - 1, 0) WHERE `VideoID` = OLD.VideoID;
END$$

-- Trigger to update video saves count
CREATE TRIGGER IF NOT EXISTS `update_video_saves_count` 
AFTER INSERT ON `short_video_saves` 
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Saves` = `Saves` + 1 WHERE `VideoID` = NEW.VideoID;
END$$

-- Trigger to update video saves count on delete
CREATE TRIGGER IF NOT EXISTS `update_video_saves_count_delete` 
AFTER DELETE ON `short_video_saves` 
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Saves` = GREATEST(`Saves` - 1, 0) WHERE `VideoID` = OLD.VideoID;
END$$

-- Trigger to update video comments count
CREATE TRIGGER IF NOT EXISTS `update_video_comments_count` 
AFTER INSERT ON `short_video_comments`
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Comments` = `Comments` + 1 WHERE `VideoID` = NEW.VideoID;
END$$

-- Trigger to update video comments count on delete
CREATE TRIGGER IF NOT EXISTS `update_video_comments_count_delete` 
AFTER DELETE ON `short_video_comments`
FOR EACH ROW 
BEGIN
    UPDATE `video_posts` SET `Comments` = GREATEST(`Comments` - 1, 0) WHERE `VideoID` = OLD.VideoID;
END$$

DELIMITER ;

-- Show success message
SELECT 'Shorts database tables created successfully!' as Status;
