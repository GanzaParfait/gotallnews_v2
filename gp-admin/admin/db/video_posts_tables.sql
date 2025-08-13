-- Video Posts Management System Tables
-- This file creates all necessary tables for video posts functionality

-- 1. Video Posts Table
CREATE TABLE IF NOT EXISTS `video_posts` (
  `VideoID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `Slug` varchar(255) NOT NULL UNIQUE,
  `Excerpt` text,
  `Description` longtext,
  `VideoFile` varchar(500) NOT NULL,
  `VideoThumbnail` varchar(500),
  `VideoDuration` int(11) DEFAULT 0 COMMENT 'Duration in seconds',
  `VideoSize` bigint(20) DEFAULT 0 COMMENT 'File size in bytes',
  `VideoFormat` varchar(20) DEFAULT 'mp4',
  `VideoResolution` varchar(20) DEFAULT '720p',
  `VideoBitrate` int(11) DEFAULT 0,
  `VideoFPS` int(11) DEFAULT 30,
  `EmbedCode` text COMMENT 'For external video embeds (YouTube, Vimeo, etc.)',
  `EmbedSource` varchar(100) DEFAULT NULL COMMENT 'youtube, vimeo, custom, etc.',
  `EmbedVideoID` varchar(100) DEFAULT NULL COMMENT 'External video ID',
  `CategoryID` int(11) DEFAULT NULL,
  `Tags` text COMMENT 'Comma-separated tags',
  `AuthorID` int(11) NOT NULL,
  `Status` enum('draft','published','scheduled','archived') DEFAULT 'draft',
  `PublishDate` datetime DEFAULT NULL,
  `Featured` tinyint(1) DEFAULT 0,
  `AllowComments` tinyint(1) DEFAULT 1,
  `Views` int(11) DEFAULT 0,
  `Likes` int(11) DEFAULT 0,
  `Dislikes` int(11) DEFAULT 0,
  `Shares` int(11) DEFAULT 0,
  `MetaTitle` varchar(255),
  `MetaDescription` text,
  `MetaKeywords` text,
  `SEO_Score` int(11) DEFAULT 0,
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`VideoID`),
  KEY `idx_status` (`Status`),
  KEY `idx_author` (`AuthorID`),
  KEY `idx_category` (`CategoryID`),
  KEY `idx_publish_date` (`PublishDate`),
  KEY `idx_slug` (`Slug`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Video Categories Table
CREATE TABLE IF NOT EXISTS `video_categories` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  `CategorySlug` varchar(100) NOT NULL UNIQUE,
  `Description` text,
  `ParentCategoryID` int(11) DEFAULT NULL,
  `CategoryIcon` varchar(100),
  `CategoryColor` varchar(7) DEFAULT '#007bff',
  `SortOrder` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CategoryID`),
  KEY `idx_parent` (`ParentCategoryID`),
  KEY `idx_active` (`isActive`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Video Tags Table
CREATE TABLE IF NOT EXISTS `video_tags` (
  `TagID` int(11) NOT NULL AUTO_INCREMENT,
  `TagName` varchar(100) NOT NULL UNIQUE,
  `TagSlug` varchar(100) NOT NULL UNIQUE,
  `Description` text,
  `TagColor` varchar(7) DEFAULT '#6c757d',
  `UsageCount` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`TagID`),
  KEY `idx_active` (`isActive`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Video-Tag Relationships Table
CREATE TABLE IF NOT EXISTS `video_tag_relationships` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `VideoID` int(11) NOT NULL,
  `TagID` int(11) NOT NULL,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_video_tag` (`VideoID`, `TagID`),
  KEY `idx_video` (`VideoID`),
  KEY `idx_tag` (`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Video Comments Table
CREATE TABLE IF NOT EXISTS `video_comments` (
  `CommentID` int(11) NOT NULL AUTO_INCREMENT,
  `VideoID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `UserType` enum('admin','user','guest') DEFAULT 'guest',
  `ParentCommentID` int(11) DEFAULT NULL,
  `Comment` text NOT NULL,
  `Status` enum('pending','approved','spam','deleted') DEFAULT 'pending',
  `Likes` int(11) DEFAULT 0,
  `Dislikes` int(11) DEFAULT 0,
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `idx_video` (`VideoID`),
  KEY `idx_user` (`UserID`),
  KEY `idx_parent` (`ParentCommentID`),
  KEY `idx_status` (`Status`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Video Views/Statistics Table
CREATE TABLE IF NOT EXISTS `video_statistics` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `VideoID` int(11) NOT NULL,
  `ViewDate` date NOT NULL,
  `Views` int(11) DEFAULT 0,
  `UniqueViews` int(11) DEFAULT 0,
  `Likes` int(11) DEFAULT 0,
  `Dislikes` int(11) DEFAULT 0,
  `Shares` int(11) DEFAULT 0,
  `Comments` int(11) DEFAULT 0,
  `WatchTime` bigint(20) DEFAULT 0 COMMENT 'Total watch time in seconds',
  `BounceRate` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage of users who left early',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_video_date` (`VideoID`, `ViewDate`),
  KEY `idx_video` (`VideoID`),
  KEY `idx_date` (`ViewDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Video Playlists Table
CREATE TABLE IF NOT EXISTS `video_playlists` (
  `PlaylistID` int(11) NOT NULL AUTO_INCREMENT,
  `PlaylistName` varchar(255) NOT NULL,
  `PlaylistSlug` varchar(255) NOT NULL UNIQUE,
  `Description` text,
  `PlaylistThumbnail` varchar(500),
  `AuthorID` int(11) NOT NULL,
  `isPublic` tinyint(1) DEFAULT 1,
  `SortOrder` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PlaylistID`),
  KEY `idx_author` (`AuthorID`),
  KEY `idx_public` (`isPublic`),
  KEY `idx_active` (`isActive`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Video Playlist Items Table
CREATE TABLE IF NOT EXISTS `video_playlist_items` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PlaylistID` int(11) NOT NULL,
  `VideoID` int(11) NOT NULL,
  `SortOrder` int(11) DEFAULT 0,
  `AddedDate` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_playlist_video` (`PlaylistID`, `VideoID`),
  KEY `idx_playlist` (`PlaylistID`),
  KEY `idx_video` (`VideoID`),
  KEY `idx_sort` (`SortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Video Subscriptions Table
CREATE TABLE IF NOT EXISTS `video_subscriptions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SubscriberID` int(11) NOT NULL,
  `SubscriberType` enum('admin','user','guest') DEFAULT 'guest',
  `ChannelID` int(11) NOT NULL COMMENT 'Author/Creator ID',
  `NotificationPreferences` json DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_subscription` (`SubscriberID`, `SubscriberType`, `ChannelID`),
  KEY `idx_subscriber` (`SubscriberID`, `SubscriberType`),
  KEY `idx_channel` (`ChannelID`),
  KEY `idx_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Video Favorites Table
CREATE TABLE IF NOT EXISTS `video_favorites` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `UserType` enum('admin','user','guest') DEFAULT 'guest',
  `VideoID` int(11) NOT NULL,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_favorite` (`UserID`, `UserType`, `VideoID`),
  KEY `idx_user` (`UserID`, `UserType`),
  KEY `idx_video` (`VideoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default video categories
INSERT INTO `video_categories` (`CategoryName`, `CategorySlug`, `Description`, `CategoryIcon`, `CategoryColor`, `SortOrder`) VALUES
('News', 'news', 'Latest news and current events', 'fa-newspaper-o', '#dc3545', 1),
('Entertainment', 'entertainment', 'Movies, music, and celebrity news', 'fa-film', '#ffc107', 2),
('Technology', 'technology', 'Tech reviews, tutorials, and industry news', 'fa-laptop', '#17a2b8', 3),
('Sports', 'sports', 'Sports highlights, analysis, and news', 'fa-futbol-o', '#28a745', 4),
('Education', 'education', 'Educational content and tutorials', 'fa-graduation-cap', '#6f42c1', 5),
('Lifestyle', 'lifestyle', 'Health, fitness, and lifestyle tips', 'fa-heart', '#e83e8c', 6),
('Business', 'business', 'Business news, tips, and analysis', 'fa-briefcase', '#fd7e14', 7),
('Travel', 'travel', 'Travel guides, destinations, and tips', 'fa-plane', '#20c997', 8);

-- Insert default video tags
INSERT INTO `video_tags` (`TagName`, `TagSlug`, `Description`, `TagColor`) VALUES
('Breaking News', 'breaking-news', 'Latest breaking news stories', '#dc3545'),
('Tutorial', 'tutorial', 'How-to and educational content', '#17a2b8'),
('Review', 'review', 'Product and service reviews', '#ffc107'),
('Interview', 'interview', 'Exclusive interviews and conversations', '#6f42c1'),
('Live', 'live', 'Live streaming content', '#e83e8c'),
('Exclusive', 'exclusive', 'Exclusive content and behind-the-scenes', '#fd7e14'),
('Trending', 'trending', 'Currently trending topics', '#20c997'),
('Analysis', 'analysis', 'In-depth analysis and commentary', '#6c757d');

-- Add foreign key constraints
ALTER TABLE `video_posts` 
ADD CONSTRAINT `fk_video_author` FOREIGN KEY (`AuthorID`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_video_category` FOREIGN KEY (`CategoryID`) REFERENCES `video_categories` (`CategoryID`) ON DELETE SET NULL;

ALTER TABLE `video_categories` 
ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`ParentCategoryID`) REFERENCES `video_categories` (`CategoryID`) ON DELETE SET NULL;

ALTER TABLE `video_tag_relationships` 
ADD CONSTRAINT `fk_vtr_video` FOREIGN KEY (`VideoID`) REFERENCES `video_posts` (`VideoID`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_vtr_tag` FOREIGN KEY (`TagID`) REFERENCES `video_tags` (`TagID`) ON DELETE CASCADE;

ALTER TABLE `video_comments` 
ADD CONSTRAINT `fk_comment_video` FOREIGN KEY (`VideoID`) REFERENCES `video_posts` (`VideoID`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_comment_parent` FOREIGN KEY (`ParentCommentID`) REFERENCES `video_comments` (`CommentID`) ON DELETE CASCADE;

ALTER TABLE `video_statistics` 
ADD CONSTRAINT `fk_stats_video` FOREIGN KEY (`VideoID`) REFERENCES `video_posts` (`VideoID`) ON DELETE CASCADE;

ALTER TABLE `video_playlists` 
ADD CONSTRAINT `fk_playlist_author` FOREIGN KEY (`AuthorID`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE;

ALTER TABLE `video_playlist_items` 
ADD CONSTRAINT `fk_playlist_item_playlist` FOREIGN KEY (`PlaylistID`) REFERENCES `video_playlists` (`PlaylistID`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_playlist_item_video` FOREIGN KEY (`VideoID`) REFERENCES `video_posts` (`VideoID`) ON DELETE CASCADE;

ALTER TABLE `video_subscriptions` 
ADD CONSTRAINT `fk_subscription_channel` FOREIGN KEY (`ChannelID`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE;

ALTER TABLE `video_favorites` 
ADD CONSTRAINT `fk_favorite_video` FOREIGN KEY (`VideoID`) REFERENCES `video_posts` (`VideoID`) ON DELETE CASCADE;
