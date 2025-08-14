-- =====================================================
-- SHORTS SYSTEM DATABASE TABLES
-- Optimized for performance with proper indexes
-- =====================================================

-- 1. Users table for website visitors
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

-- 2. Short video views tracking
CREATE TABLE IF NOT EXISTS `short_video_views` (
    `ViewID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) DEFAULT NULL, -- NULL for anonymous users
    `IPAddress` varchar(45) DEFAULT NULL,
    `UserAgent` text DEFAULT NULL,
    `ViewStartTime` timestamp DEFAULT CURRENT_TIMESTAMP,
    `ViewEndTime` timestamp NULL DEFAULT NULL,
    `DurationWatched` int(11) DEFAULT 0, -- in seconds
    `WatchPercentage` decimal(5,2) DEFAULT 0.00, -- 0.00 to 100.00
    `IsCompleted` tinyint(1) DEFAULT 0, -- watched 90% or more
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

-- 3. Short video likes
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

-- 4. Short video comments
CREATE TABLE IF NOT EXISTS `short_video_comments` (
    `CommentID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `ParentCommentID` int(11) DEFAULT NULL, -- for replies
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

-- 5. Short video shares
CREATE TABLE IF NOT EXISTS `short_video_shares` (
    `ShareID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `ShareType` enum('copy_link','social_media','email','whatsapp','telegram') DEFAULT 'copy_link',
    `ShareData` json DEFAULT NULL, -- additional share information
    `SharedAt` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ShareID`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_share_type` (`ShareType`),
    INDEX `idx_shared_at` (`SharedAt`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE,
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Short video saves/bookmarks
CREATE TABLE IF NOT EXISTS `short_video_saves` (
    `SaveID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `UserID` int(11) NOT NULL,
    `PlaylistID` int(11) DEFAULT NULL, -- for organizing saves
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

-- 7. User playlists for organizing saved videos
CREATE TABLE IF NOT EXISTS `user_playlists` (
    `PlaylistID` int(11) NOT NULL AUTO_INCREMENT,
    `UserID` int(11) NOT NULL,
    `PlaylistName` varchar(100) NOT NULL,
    `Description` text DEFAULT NULL,
    `IsPublic` tinyint(1) DEFAULT 0,
    `Thumbnail` varchar(255) DEFAULT NULL,
    `VideoCount` int(11) DEFAULT 0,
    `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`PlaylistID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_public` (`IsPublic`),
    INDEX `idx_created` (`Created_at`),
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Short video engagement analytics
CREATE TABLE IF NOT EXISTS `short_video_analytics` (
    `AnalyticsID` int(11) NOT NULL AUTO_INCREMENT,
    `VideoID` int(11) NOT NULL,
    `Date` date NOT NULL,
    `Views` int(11) DEFAULT 0,
    `UniqueViews` int(11) DEFAULT 0,
    `Likes` int(11) DEFAULT 0,
    `Comments` int(11) DEFAULT 0,
    `Shares` int(11) DEFAULT 0,
    `Saves` int(11) DEFAULT 0,
    `TotalWatchTime` int(11) DEFAULT 0, -- in seconds
    `AverageWatchTime` decimal(8,2) DEFAULT 0.00, -- in seconds
    `CompletionRate` decimal(5,2) DEFAULT 0.00, -- percentage
    `EngagementRate` decimal(5,2) DEFAULT 0.00, -- (likes + comments + shares) / views
    PRIMARY KEY (`AnalyticsID`),
    UNIQUE KEY `unique_video_date` (`VideoID`, `Date`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_date` (`Date`),
    INDEX `idx_views` (`Views`),
    INDEX `idx_engagement` (`EngagementRate`),
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. User video interactions summary (for quick queries)
CREATE TABLE IF NOT EXISTS `user_video_interactions` (
    `InteractionID` int(11) NOT NULL AUTO_INCREMENT,
    `UserID` int(11) NOT NULL,
    `VideoID` int(11) NOT NULL,
    `HasLiked` tinyint(1) DEFAULT 0,
    `HasCommented` tinyint(1) DEFAULT 0,
    `HasShared` tinyint(1) DEFAULT 0,
    `HasSaved` tinyint(1) DEFAULT 0,
    `LastInteraction` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`InteractionID`),
    UNIQUE KEY `unique_user_video` (`UserID`, `VideoID`),
    INDEX `idx_user` (`UserID`),
    INDEX `idx_video` (`VideoID`),
    INDEX `idx_liked` (`HasLiked`),
    INDEX `idx_saved` (`HasSaved`),
    INDEX `idx_last_interaction` (`LastInteraction`),
    FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE CASCADE,
    FOREIGN KEY (`VideoID`) REFERENCES `video_posts`(`VideoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Insert default playlist for each user
DELIMITER //
CREATE TRIGGER `create_default_playlist` 
AFTER INSERT ON `users` 
FOR EACH ROW 
BEGIN
    INSERT INTO `user_playlists` (`UserID`, `PlaylistName`, `Description`, `IsPublic`) 
    VALUES (NEW.UserID, 'Favorites', 'My favorite short videos', 0);
END//
DELIMITER ;

-- 11. Update video analytics when interactions change
DELIMITER //
CREATE TRIGGER `update_video_analytics_like` 
AFTER INSERT ON `short_video_likes` 
FOR EACH ROW 
BEGIN
    INSERT INTO `short_video_analytics` (`VideoID`, `Date`, `Likes`) 
    VALUES (NEW.VideoID, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE `Likes` = `Likes` + 1;
    
    UPDATE `user_video_interactions` 
    SET `HasLiked` = 1, `LastInteraction` = NOW() 
    WHERE `UserID` = NEW.UserID AND `VideoID` = NEW.VideoID
    ON DUPLICATE KEY UPDATE `HasLiked` = 1, `LastInteraction` = NOW();
END//
DELIMITER ;

-- 12. Update video analytics when likes are removed
DELIMITER //
CREATE TRIGGER `update_video_analytics_unlike` 
AFTER DELETE ON `short_video_likes` 
FOR EACH ROW 
BEGIN
    UPDATE `short_video_analytics` 
    SET `Likes` = GREATEST(`Likes` - 1, 0) 
    WHERE `VideoID` = OLD.VideoID AND `Date` = CURDATE();
    
    UPDATE `user_video_interactions` 
    SET `HasLiked` = 0, `LastInteraction` = NOW() 
    WHERE `UserID` = OLD.UserID AND `VideoID` = OLD.VideoID;
END//
DELIMITER ;

-- 13. Update video analytics when comments are added
DELIMITER //
CREATE TRIGGER `update_video_analytics_comment` 
AFTER INSERT ON `short_video_comments` 
FOR EACH ROW 
BEGIN
    INSERT INTO `short_video_analytics` (`VideoID`, `Date`, `Comments`) 
    VALUES (NEW.VideoID, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE `Comments` = `Comments` + 1;
    
    UPDATE `user_video_interactions` 
    SET `HasCommented` = 1, `LastInteraction` = NOW() 
    WHERE `UserID` = NEW.UserID AND `VideoID` = NEW.VideoID
    ON DUPLICATE KEY UPDATE `HasCommented` = 1, `LastInteraction` = NOW();
END//
DELIMITER ;

-- 14. Update video analytics when videos are saved
DELIMITER //
CREATE TRIGGER `update_video_analytics_save` 
AFTER INSERT ON `short_video_saves` 
FOR EACH ROW 
BEGIN
    INSERT INTO `short_video_analytics` (`VideoID`, `Date`, `Saves`) 
    VALUES (NEW.VideoID, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE `Saves` = `Saves` + 1;
    
    UPDATE `user_video_interactions` 
    SET `HasSaved` = 1, `LastInteraction` = NOW() 
    WHERE `UserID` = NEW.UserID AND `VideoID` = NEW.VideoID
    ON DUPLICATE KEY UPDATE `HasSaved` = 1, `LastInteraction` = NOW();
    
    UPDATE `user_playlists` 
    SET `VideoCount` = `VideoCount` + 1 
    WHERE `PlaylistID` = NEW.PlaylistID;
END//
DELIMITER ;

-- 15. Update video analytics when saves are removed
DELIMITER //
CREATE TRIGGER `update_video_analytics_unsave` 
AFTER DELETE ON `short_video_saves` 
FOR EACH ROW 
BEGIN
    UPDATE `short_video_analytics` 
    SET `Saves` = GREATEST(`Saves` - 1, 0) 
    WHERE `VideoID` = OLD.VideoID AND `Date` = CURDATE();
    
    UPDATE `user_video_interactions` 
    SET `HasSaved` = 0, `LastInteraction` = NOW() 
    WHERE `UserID` = OLD.UserID AND `VideoID` = OLD.VideoID;
    
    UPDATE `user_playlists` 
    SET `VideoCount` = GREATEST(`VideoCount` - 1, 0) 
    WHERE `PlaylistID` = OLD.PlaylistID;
END//
DELIMITER ;

-- 16. Update video analytics when shares occur
DELIMITER //
CREATE TRIGGER `update_video_analytics_share` 
AFTER INSERT ON `short_video_shares` 
FOR EACH ROW 
BEGIN
    INSERT INTO `short_video_analytics` (`VideoID`, `Date`, `Shares`) 
    VALUES (NEW.VideoID, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE `Shares` = `Shares` + 1;
    
    UPDATE `user_video_interactions` 
    SET `HasShared` = 1, `LastInteraction` = NOW() 
    WHERE `UserID` = NEW.UserID AND `VideoID` = NEW.VideoID
    ON DUPLICATE KEY UPDATE `HasShared` = 1, `LastInteraction` = NOW();
END//
DELIMITER ;

-- 17. Update video analytics when views are tracked
DELIMITER //
CREATE TRIGGER `update_video_analytics_view` 
AFTER UPDATE ON `short_video_views` 
FOR EACH ROW 
BEGIN
    IF NEW.ViewEndTime IS NOT NULL AND OLD.ViewEndTime IS NULL THEN
        INSERT INTO `short_video_analytics` (`VideoID`, `Date`, `Views`, `TotalWatchTime`, `AverageWatchTime`) 
        VALUES (NEW.VideoID, CURDATE(), 1, NEW.DurationWatched, NEW.DurationWatched)
        ON DUPLICATE KEY UPDATE 
            `Views` = `Views` + 1,
            `TotalWatchTime` = `TotalWatchTime` + NEW.DurationWatched,
            `AverageWatchTime` = (`TotalWatchTime` + NEW.DurationWatched) / (`Views` + 1);
    END IF;
END//
DELIMITER ;

-- 18. Create indexes for better performance
CREATE INDEX `idx_video_posts_videoType_status` ON `video_posts` (`videoType`, `Status`);
CREATE INDEX `idx_video_posts_created_status` ON `video_posts` (`Created_at`, `Status`);
CREATE INDEX `idx_video_posts_views_status` ON `video_posts` (`Views`, `Status`);

-- 19. Insert sample data for testing (optional)
INSERT INTO `users` (`Username`, `Email`, `Password`, `DisplayName`, `Status`) VALUES
('demo_user', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', 'active'),
('test_user', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'active');

-- 20. Create view for quick shorts analytics
CREATE VIEW `shorts_analytics_view` AS
SELECT 
    v.VideoID,
    v.Title,
    v.Slug,
    v.VideoThumbnail,
    v.VideoDuration,
    v.Views,
    v.Created_at,
    cp.DisplayName as AuthorName,
    cp.Username as AuthorUsername,
    COALESCE(va.Likes, 0) as TotalLikes,
    COALESCE(va.Comments, 0) as TotalComments,
    COALESCE(va.Shares, 0) as TotalShares,
    COALESCE(va.Saves, 0) as TotalSaves,
    COALESCE(va.AverageWatchTime, 0) as AvgWatchTime,
    COALESCE(va.CompletionRate, 0) as CompletionRate,
    COALESCE(va.EngagementRate, 0) as EngagementRate
FROM `video_posts` v
LEFT JOIN `creator_profiles` cp ON v.ProfileID = cp.ProfileID
LEFT JOIN `short_video_analytics` va ON v.VideoID = va.VideoID AND va.Date = CURDATE()
WHERE v.videoType = 'short' AND v.Status = 'published' AND v.isDeleted = 'notDeleted';

-- 21. Create view for user interaction summary
CREATE VIEW `user_interactions_summary` AS
SELECT 
    u.UserID,
    u.Username,
    u.DisplayName,
    COUNT(DISTINCT uvi.VideoID) as TotalVideosInteracted,
    SUM(uvi.HasLiked) as TotalLikes,
    SUM(uvi.HasCommented) as TotalComments,
    SUM(uvi.HasShared) as TotalShares,
    SUM(uvi.HasSaved) as TotalSaves,
    COUNT(DISTINCT sv.VideoID) as TotalVideosViewed,
    SUM(sv.DurationWatched) as TotalWatchTime
FROM `users` u
LEFT JOIN `user_video_interactions` uvi ON u.UserID = uvi.UserID
LEFT JOIN `short_video_views` sv ON u.UserID = sv.UserID
GROUP BY u.UserID;

-- 22. Performance optimization: Partition large tables by date
-- Note: This requires MySQL 8.0+ and proper partitioning setup
-- ALTER TABLE `short_video_views` PARTITION BY RANGE (YEAR(ViewStartTime)) (
--     PARTITION p2024 VALUES LESS THAN (2025),
--     PARTITION p2025 VALUES LESS THAN (2026),
--     PARTITION p2026 VALUES LESS THAN (2027)
-- );

-- 23. Create stored procedure for getting trending shorts
DELIMITER //
CREATE PROCEDURE `GetTrendingShorts`(
    IN p_limit INT DEFAULT 20,
    IN p_days INT DEFAULT 7
)
BEGIN
    SELECT 
        v.VideoID,
        v.Title,
        v.Slug,
        v.VideoThumbnail,
        v.VideoDuration,
        v.Views,
        v.Created_at,
        cp.DisplayName as AuthorName,
        cp.Username as AuthorUsername,
        COALESCE(va.Likes, 0) as TotalLikes,
        COALESCE(va.Comments, 0) as TotalComments,
        COALESCE(va.Shares, 0) as TotalShares,
        COALESCE(va.Saves, 0) as TotalSaves,
        COALESCE(va.EngagementRate, 0) as EngagementRate
    FROM `video_posts` v
    LEFT JOIN `creator_profiles` cp ON v.ProfileID = cp.ProfileID
    LEFT JOIN `short_video_analytics` va ON v.VideoID = va.VideoID AND va.Date >= DATE_SUB(CURDATE(), INTERVAL p_days DAY)
    WHERE v.videoType = 'short' 
        AND v.Status = 'published' 
        AND v.isDeleted = 'notDeleted'
        AND v.Created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    ORDER BY va.EngagementRate DESC, v.Views DESC, v.Created_at DESC
    LIMIT p_limit;
END//
DELIMITER ;

-- 24. Create stored procedure for getting user recommendations
DELIMITER //
CREATE PROCEDURE `GetUserRecommendations`(
    IN p_userID INT,
    IN p_limit INT DEFAULT 20
)
BEGIN
    SELECT DISTINCT
        v.VideoID,
        v.Title,
        v.Slug,
        v.VideoThumbnail,
        v.VideoDuration,
        v.Views,
        v.Created_at,
        cp.DisplayName as AuthorName,
        cp.Username as AuthorUsername,
        COALESCE(va.EngagementRate, 0) as EngagementRate
    FROM `video_posts` v
    LEFT JOIN `creator_profiles` cp ON v.ProfileID = cp.ProfileID
    LEFT JOIN `short_video_analytics` va ON v.VideoID = va.VideoID AND va.Date = CURDATE()
    WHERE v.videoType = 'short' 
        AND v.Status = 'published' 
        AND v.isDeleted = 'notDeleted'
        AND v.VideoID NOT IN (
            SELECT DISTINCT VideoID 
            FROM `short_video_views` 
            WHERE UserID = p_userID
        )
    ORDER BY va.EngagementRate DESC, v.Views DESC, v.Created_at DESC
    LIMIT p_limit;
END//
DELIMITER ;

-- 25. Create function to calculate engagement rate
DELIMITER //
CREATE FUNCTION `CalculateEngagementRate`(
    p_likes INT,
    p_comments INT,
    p_shares INT,
    p_views INT
) RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF p_views = 0 THEN
        RETURN 0.00;
    ELSE
        RETURN ROUND(((p_likes + p_comments + p_shares) / p_views) * 100, 2);
    END IF;
END//
DELIMITER ;

-- 26. Create function to format duration
DELIMITER //
CREATE FUNCTION `FormatDuration`(
    p_seconds INT
) RETURNS VARCHAR(10)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE hours INT;
    DECLARE minutes INT;
    DECLARE secs INT;
    
    SET hours = FLOOR(p_seconds / 3600);
    SET minutes = FLOOR((p_seconds % 3600) / 60);
    SET secs = p_seconds % 60;
    
    IF hours > 0 THEN
        RETURN CONCAT(hours, ':', LPAD(minutes, 2, '0'), ':', LPAD(secs, 2, '0'));
    ELSE
        RETURN CONCAT(minutes, ':', LPAD(secs, 2, '0'));
    END IF;
END//
DELIMITER ;

-- 27. Create event to update daily analytics
DELIMITER //
CREATE EVENT `UpdateDailyAnalytics`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Update engagement rates for all videos
    UPDATE `short_video_analytics` 
    SET `EngagementRate` = `CalculateEngagementRate`(`Likes`, `Comments`, `Shares`, `Views`)
    WHERE `Date` = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
    
    -- Update completion rates
    UPDATE `short_video_analytics` va
    JOIN (
        SELECT 
            VideoID,
            AVG(WatchPercentage) as avg_completion
        FROM `short_video_views`
        WHERE DATE(ViewStartTime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        GROUP BY VideoID
    ) vw ON va.VideoID = vw.VideoID
    SET va.CompletionRate = vw.avg_completion
    WHERE va.Date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
END//
DELIMITER ;

-- 28. Grant permissions (adjust as needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `shorts_system`.* TO 'your_user'@'localhost';

-- 29. Show table status
SHOW TABLE STATUS WHERE Name LIKE '%short%' OR Name LIKE '%user%';

-- 30. Show indexes for performance verification
SHOW INDEX FROM `short_video_views`;
SHOW INDEX FROM `short_video_likes`;
SHOW INDEX FROM `short_video_comments`;
SHOW INDEX FROM `short_video_saves`;

-- =====================================================
-- END OF SHORTS SYSTEM DATABASE TABLES
-- =====================================================