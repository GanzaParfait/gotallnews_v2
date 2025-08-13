-- =====================================================
-- CREATOR PROFILES SYSTEM - DATABASE SCHEMA (MariaDB Compatible)
-- =====================================================

-- 1. CREATOR PROFILES TABLE
-- Extends the existing admin table with creator-specific information
CREATE TABLE IF NOT EXISTS `creator_profiles` (
  `ProfileID` int(11) NOT NULL AUTO_INCREMENT,
  `AdminId` int(11) NOT NULL,
  `Username` varchar(100) UNIQUE NOT NULL,
  `DisplayName` varchar(255) NOT NULL,
  `Bio` text DEFAULT NULL,
  `ProfilePhoto` varchar(255) DEFAULT NULL,
  `CoverPhoto` varchar(255) DEFAULT NULL,
  `Website` varchar(255) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Expertise` text DEFAULT NULL,
  `YearsExperience` int(11) DEFAULT NULL,
  `TotalArticles` int(11) DEFAULT 0,
  `TotalViews` bigint(20) DEFAULT 0,
  `FollowersCount` int(11) DEFAULT 0,
  `FollowingCount` int(11) DEFAULT 0,
  `EngagementScore` decimal(5,2) DEFAULT 0.00,
  `IsVerified` tinyint(1) DEFAULT 0,
  `IsFeatured` tinyint(1) DEFAULT 0,
  `Status` enum('active','inactive','suspended') DEFAULT 'active',
  `isDeleted` enum('deleted','notDeleted') DEFAULT 'notDeleted',
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ProfileID`),
  KEY `idx_admin_id` (`AdminId`),
  KEY `idx_username` (`Username`),
  KEY `idx_status` (`Status`),
  KEY `idx_verified` (`IsVerified`),
  KEY `idx_featured` (`IsFeatured`),
  KEY `idx_deleted` (`isDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CREATOR SOCIAL LINKS TABLE
-- Stores social media platform links for each creator
CREATE TABLE IF NOT EXISTS `creator_social_links` (
  `LinkID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `Platform` varchar(50) NOT NULL,
  `URL` varchar(500) NOT NULL,
  `DisplayText` varchar(100) DEFAULT NULL,
  `Icon` varchar(100) DEFAULT NULL,
  `OrderIndex` int(11) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`LinkID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_platform` (`Platform`),
  KEY `idx_active` (`IsActive`),
  KEY `idx_order` (`OrderIndex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CREATOR FOLLOWERS TABLE
-- Manages follower relationships between creators
CREATE TABLE IF NOT EXISTS `creator_followers` (
  `FollowID` int(11) NOT NULL AUTO_INCREMENT,
  `FollowerID` int(11) NOT NULL,
  `FollowingID` int(11) NOT NULL,
  `Status` enum('active','blocked','muted') DEFAULT 'active',
  `FollowDate` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`FollowID`),
  UNIQUE KEY `unique_follow` (`FollowerID`, `FollowingID`),
  KEY `idx_follower` (`FollowerID`),
  KEY `idx_following` (`FollowingID`),
  KEY `idx_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CREATOR STATISTICS TABLE
-- Daily performance metrics for each creator
CREATE TABLE IF NOT EXISTS `creator_statistics` (
  `StatID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `ArticlesPublished` int(11) DEFAULT 0,
  `TotalViews` bigint(20) DEFAULT 0,
  `TotalLikes` int(11) DEFAULT 0,
  `TotalComments` int(11) DEFAULT 0,
  `TotalShares` int(11) DEFAULT 0,
  `NewFollowers` int(11) DEFAULT 0,
  `EngagementRate` decimal(5,2) DEFAULT 0.00,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`StatID`),
  UNIQUE KEY `unique_profile_date` (`ProfileID`, `Date`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_date` (`Date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CREATOR ACHIEVEMENTS TABLE
-- Achievement system for creators
CREATE TABLE IF NOT EXISTS `creator_achievements` (
  `AchievementID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `AchievementType` varchar(100) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `Icon` varchar(100) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `AchievedDate` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`AchievementID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_type` (`AchievementType`),
  KEY `idx_active` (`IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CREATOR CATEGORIES TABLE
-- Maps creators to their expertise categories
CREATE TABLE IF NOT EXISTS `creator_categories` (
  `CategoryMapID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `ExpertiseLevel` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `IsPrimary` tinyint(1) DEFAULT 0,
  `Created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CategoryMapID`),
  UNIQUE KEY `unique_profile_category` (`ProfileID`, `CategoryID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_category_id` (`CategoryID`),
  KEY `idx_primary` (`IsPrimary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CREATE VIEW FOR CREATOR ARTICLES SUMMARY
CREATE OR REPLACE VIEW `creator_articles_summary` AS
SELECT 
    cp.ProfileID,
    cp.Username,
    cp.DisplayName,
    cp.ProfilePhoto,
    cp.IsVerified,
    cp.IsFeatured,
    COUNT(a.ArticleID) as TotalArticles,
    SUM(COALESCE(a.Engagement_score, 0)) as TotalEngagement,
    MAX(a.Date) as LastArticleDate
FROM creator_profiles cp
LEFT JOIN article a ON cp.AdminId = a.AdminId AND a.Published = 'published'
WHERE cp.isDeleted = 'notDeleted'
GROUP BY cp.ProfileID;

-- 8. CREATE ADDITIONAL INDEXES FOR PERFORMANCE
-- Note: These indexes are created separately to avoid conflicts
CREATE INDEX `idx_creator_profiles_views` ON `creator_profiles` (`TotalViews`);
CREATE INDEX `idx_creator_profiles_followers` ON `creator_profiles` (`FollowersCount`);
CREATE INDEX `idx_creator_profiles_engagement` ON `creator_profiles` (`EngagementScore`);
CREATE INDEX `idx_creator_profiles_created` ON `creator_profiles` (`Created_at`);

CREATE INDEX `idx_creator_social_links_profile` ON `creator_social_links` (`ProfileID`, `IsActive`);
CREATE INDEX `idx_creator_followers_profile` ON `creator_followers` (`FollowerID`, `FollowingID`, `Status`);
CREATE INDEX `idx_creator_statistics_profile_date` ON `creator_statistics` (`ProfileID`, `Date`);
CREATE INDEX `idx_creator_achievements_profile` ON `creator_achievements` (`ProfileID`, `IsActive`);
CREATE INDEX `idx_creator_categories_profile` ON `creator_categories` (`ProfileID`, `IsPrimary`);

-- 9. INSERT DEFAULT DATA
-- Insert default social media platforms (only if profile exists)
INSERT IGNORE INTO `creator_social_links` (`ProfileID`, `Platform`, `URL`, `DisplayText`, `Icon`, `OrderIndex`) VALUES
(1, 'website', 'https://gotallnews.com', 'Official Website', 'fas fa-globe', 1),
(1, 'twitter', '#', 'Twitter', 'fab fa-twitter', 2),
(1, 'linkedin', '#', 'LinkedIn', 'fab fa-linkedin', 3);

-- 10. ADD FOREIGN KEY CONSTRAINTS AFTER ALL TABLES ARE CREATED
-- This ensures all tables exist before adding constraints
ALTER TABLE `creator_social_links` 
ADD CONSTRAINT `fk_social_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `creator_followers` 
ADD CONSTRAINT `fk_follower_profile` FOREIGN KEY (`FollowerID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_following_profile` FOREIGN KEY (`FollowingID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `creator_statistics` 
ADD CONSTRAINT `fk_stats_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `creator_achievements` 
ADD CONSTRAINT `fk_achievement_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE;

ALTER TABLE `creator_categories` 
ADD CONSTRAINT `fk_category_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_category_category` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE CASCADE;

ALTER TABLE `creator_profiles` 
ADD CONSTRAINT `fk_creator_admin` FOREIGN KEY (`AdminId`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE;

-- 11. CREATE TRIGGERS FOR AUTOMATIC UPDATES
-- Note: Triggers are created without DELIMITER for MariaDB compatibility

-- Trigger to update creator statistics when articles are published
CREATE TRIGGER `update_creator_stats_on_article_publish`
AFTER INSERT ON `article`
FOR EACH ROW
BEGIN
    DECLARE creator_profile_id INT;
    
    -- Get the creator profile ID
    SELECT ProfileID INTO creator_profile_id 
    FROM creator_profiles 
    WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted';
    
    -- If creator profile exists, update statistics
    IF creator_profile_id IS NOT NULL THEN
        INSERT INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews)
        VALUES (creator_profile_id, CURDATE(), 1, 0)
        ON DUPLICATE KEY UPDATE
        ArticlesPublished = ArticlesPublished + 1;
        
        -- Update creator profile total articles count
        UPDATE creator_profiles 
        SET TotalArticles = TotalArticles + 1
        WHERE ProfileID = creator_profile_id;
    END IF;
END;

-- Trigger to update follower counts when someone follows
CREATE TRIGGER `update_follower_counts_on_follow`
AFTER INSERT ON `creator_followers`
FOR EACH ROW
BEGIN
    IF NEW.Status = 'active' THEN
        -- Increment following count for follower
        UPDATE creator_profiles 
        SET FollowingCount = FollowingCount + 1
        WHERE ProfileID = NEW.FollowerID;
        
        -- Increment followers count for person being followed
        UPDATE creator_profiles 
        SET FollowersCount = FollowersCount + 1
        WHERE ProfileID = NEW.FollowingID;
    END IF;
END;

-- Trigger to update follower counts when someone unfollows
CREATE TRIGGER `update_follower_counts_on_unfollow`
AFTER UPDATE ON `creator_followers`
FOR EACH ROW
BEGIN
    IF OLD.Status = 'active' AND NEW.Status != 'active' THEN
        -- Decrement following count for follower
        UPDATE creator_profiles 
        SET FollowingCount = FollowingCount - 1
        WHERE ProfileID = NEW.FollowerID;
        
        -- Decrement followers count for person being followed
        UPDATE creator_profiles 
        SET FollowersCount = FollowersCount - 1
        WHERE ProfileID = NEW.FollowingID;
    END IF;
END;

-- 12. FINAL COMMIT
COMMIT;

-- =====================================================
-- INSTALLATION COMPLETE
-- =====================================================
-- 
-- The Creator Profiles System has been successfully installed!
-- 
-- Next steps:
-- 1. Go to Creator Profiles in your admin dashboard
-- 2. Create your first creator profile
-- 3. Explore the new features
-- 
-- =====================================================
