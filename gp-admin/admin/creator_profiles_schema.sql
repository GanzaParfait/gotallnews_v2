-- =====================================================
-- CREATOR PROFILES SYSTEM - DATABASE SCHEMA
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
  `IsVerified` tinyint(1) DEFAULT 0,
  `IsFeatured` tinyint(1) DEFAULT 0,
  `Status` enum('active', 'inactive', 'suspended') DEFAULT 'active',
  `isDeleted` enum('notDeleted', 'deleted') DEFAULT 'notDeleted',
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ProfileID`),
  KEY `idx_admin_id` (`AdminId`),
  KEY `idx_username` (`Username`),
  KEY `idx_status` (`Status`),
  KEY `idx_is_deleted` (`isDeleted`),
  CONSTRAINT `fk_creator_admin` FOREIGN KEY (`AdminId`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. SOCIAL MEDIA LINKS TABLE
CREATE TABLE IF NOT EXISTS `creator_social_links` (
  `LinkID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `Platform` enum('facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'website', 'blog', 'other') NOT NULL,
  `URL` varchar(500) NOT NULL,
  `DisplayText` varchar(255) DEFAULT NULL,
  `Icon` varchar(100) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `OrderIndex` int(11) DEFAULT 0,
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`LinkID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_platform` (`Platform`),
  KEY `idx_is_active` (`IsActive`),
  CONSTRAINT `fk_social_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. CREATOR FOLLOWERS TABLE
CREATE TABLE IF NOT EXISTS `creator_followers` (
  `FollowID` int(11) NOT NULL AUTO_INCREMENT,
  `FollowerID` int(11) NOT NULL,
  `FollowingID` int(11) NOT NULL,
  `FollowDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('active', 'blocked') DEFAULT 'active',
  PRIMARY KEY (`FollowID`),
  UNIQUE KEY `unique_follow` (`FollowerID`, `FollowingID`),
  KEY `idx_follower` (`FollowerID`),
  KEY `idx_following` (`FollowingID`),
  KEY `idx_status` (`Status`),
  CONSTRAINT `fk_follower_user` FOREIGN KEY (`FollowerID`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  CONSTRAINT `fk_following_creator` FOREIGN KEY (`FollowingID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. CREATOR STATISTICS TABLE
CREATE TABLE IF NOT EXISTS `creator_statistics` (
  `StatID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `ArticlesPublished` int(11) DEFAULT 0,
  `TotalViews` int(11) DEFAULT 0,
  `TotalLikes` int(11) DEFAULT 0,
  `TotalComments` int(11) DEFAULT 0,
  `TotalShares` int(11) DEFAULT 0,
  `NewFollowers` int(11) DEFAULT 0,
  `EngagementRate` decimal(5,2) DEFAULT 0.00,
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`StatID`),
  UNIQUE KEY `unique_profile_date` (`ProfileID`, `Date`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_date` (`Date`),
  CONSTRAINT `fk_stats_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. CREATOR ACHIEVEMENTS TABLE
CREATE TABLE IF NOT EXISTS `creator_achievements` (
  `AchievementID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `AchievementType` enum('first_article', 'milestone_views', 'milestone_followers', 'featured_creator', 'top_performer', 'community_contributor', 'expert_writer', 'viral_article') NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `Icon` varchar(255) DEFAULT NULL,
  `AchievedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`AchievementID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_achievement_type` (`AchievementType`),
  KEY `idx_is_active` (`IsActive`),
  CONSTRAINT `fk_achievement_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. CREATOR CATEGORIES TABLE (for expertise areas)
CREATE TABLE IF NOT EXISTS `creator_categories` (
  `CreatorCategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `ProfileID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `IsPrimary` tinyint(1) DEFAULT 0,
  `ExpertiseLevel` enum('beginner', 'intermediate', 'expert', 'master') DEFAULT 'intermediate',
  `AddedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`CreatorCategoryID`),
  UNIQUE KEY `unique_creator_category` (`ProfileID`, `CategoryID`),
  KEY `idx_profile_id` (`ProfileID`),
  KEY `idx_category_id` (`CategoryID`),
  KEY `idx_is_primary` (`IsPrimary`),
  CONSTRAINT `fk_creator_cat_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE,
  CONSTRAINT `fk_creator_cat_category` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. CREATOR ARTICLES SUMMARY VIEW (for quick access)
CREATE OR REPLACE VIEW `creator_articles_summary` AS
SELECT 
    cp.ProfileID,
    cp.AdminId,
    cp.Username,
    cp.DisplayName,
    cp.ProfilePhoto,
    cp.Bio,
    cp.TotalArticles,
    cp.TotalViews,
    cp.FollowersCount,
    cp.IsVerified,
    cp.IsFeatured,
    cp.Status,
    COUNT(a.ArticleID) as PublishedArticles,
    SUM(a.Engagement_score) as TotalEngagement,
    AVG(a.Engagement_score) as AvgEngagement,
    MAX(a.Created_at) as LastArticleDate
FROM creator_profiles cp
LEFT JOIN article a ON cp.AdminId = a.AdminId AND a.Published = 'published'
WHERE cp.isDeleted = 'notDeleted' AND cp.Status = 'active'
GROUP BY cp.ProfileID;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default creator profile for existing admin
INSERT IGNORE INTO `creator_profiles` (
    `AdminId`, 
    `Username`, 
    `DisplayName`, 
    `Bio`, 
    `ProfilePhoto`, 
    `IsVerified`, 
    `IsFeatured`
) 
SELECT 
    a.AdminId,
    LOWER(CONCAT(a.FirstName, '.', a.LastName)),
    CONCAT(a.FirstName, ' ', a.LastName),
    'Experienced content creator and journalist',
    a.Profile,
    1,
    1
FROM admin a 
WHERE a.isDeleted = 'notDeleted' 
AND NOT EXISTS (
    SELECT 1 FROM creator_profiles cp WHERE cp.AdminId = a.AdminId
);

-- Insert default social links for existing creators
INSERT IGNORE INTO `creator_social_links` (
    `ProfileID`, 
    `Platform`, 
    `URL`, 
    `DisplayText`, 
    `Icon`
)
SELECT 
    cp.ProfileID,
    'website',
    'https://gotallnews.com',
    'Official Website',
    'fas fa-globe'
FROM creator_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM creator_social_links csl WHERE csl.ProfileID = cp.ProfileID
);

-- Insert default achievements for existing creators
INSERT IGNORE INTO `creator_achievements` (
    `ProfileID`, 
    `AchievementType`, 
    `Title`, 
    `Description`, 
    `Icon`
)
SELECT 
    cp.ProfileID,
    'first_article',
    'First Article Published',
    'Successfully published your first article on the platform',
    'fas fa-star'
FROM creator_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM creator_achievements ca WHERE ca.ProfileID = cp.ProfileID
);

-- =====================================================
-- UPDATE EXISTING TABLES
-- =====================================================

-- Add creator profile reference to article table if not exists
ALTER TABLE `article` 
ADD COLUMN IF NOT EXISTS `CreatorProfileID` int(11) NULL AFTER `AdminId`,
ADD KEY IF NOT EXISTS `idx_creator_profile` (`CreatorProfileID`),
ADD CONSTRAINT IF NOT EXISTS `fk_article_creator_profile` 
    FOREIGN KEY (`CreatorProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE SET NULL;

-- Update existing articles with creator profile IDs
UPDATE article a 
JOIN creator_profiles cp ON a.AdminId = cp.AdminId 
SET a.CreatorProfileID = cp.ProfileID 
WHERE a.CreatorProfileID IS NULL;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_creator_profiles_status_deleted` ON `creator_profiles` (`Status`, `isDeleted`);
CREATE INDEX IF NOT EXISTS `idx_creator_profiles_verified_featured` ON `creator_profiles` (`IsVerified`, `IsFeatured`);
CREATE INDEX IF NOT EXISTS `idx_creator_social_links_order` ON `creator_social_links` (`ProfileID`, `OrderIndex`, `IsActive`);
CREATE INDEX IF NOT EXISTS `idx_creator_followers_date` ON `creator_followers` (`FollowingID`, `FollowDate`, `Status`);
CREATE INDEX IF NOT EXISTS `idx_creator_statistics_monthly` ON `creator_statistics` (`ProfileID`, `Date`);

-- =====================================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =====================================================

DELIMITER //

-- Trigger to update creator statistics when article is published
CREATE TRIGGER IF NOT EXISTS `update_creator_stats_on_article_publish`
AFTER UPDATE ON `article`
FOR EACH ROW
BEGIN
    IF NEW.Published = 'published' AND OLD.Published != 'published' THEN
        INSERT INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews)
        SELECT cp.ProfileID, CURDATE(), 1, NEW.Engagement_score
        FROM creator_profiles cp 
        WHERE cp.AdminId = NEW.AdminId
        ON DUPLICATE KEY UPDATE
            ArticlesPublished = ArticlesPublished + 1,
            TotalViews = TotalViews + NEW.Engagement_score;
            
        -- Update creator profile totals
        UPDATE creator_profiles cp
        SET TotalArticles = TotalArticles + 1,
            TotalViews = TotalViews + NEW.Engagement_score
        WHERE cp.AdminId = NEW.AdminId;
    END IF;
END//

-- Trigger to update follower counts
CREATE TRIGGER IF NOT EXISTS `update_follower_counts_on_follow`
AFTER INSERT ON `creator_followers`
FOR EACH ROW
BEGIN
    IF NEW.Status = 'active' THEN
        -- Update following count for follower
        UPDATE creator_profiles cp
        SET FollowingCount = FollowingCount + 1
        WHERE cp.AdminId = (
            SELECT AdminId FROM users u WHERE u.UserId = NEW.FollowerID
        );
        
        -- Update follower count for creator
        UPDATE creator_profiles cp
        SET FollowersCount = FollowersCount + 1
        WHERE cp.ProfileID = NEW.FollowingID;
    END IF;
END//

-- Trigger to update follower counts on unfollow
CREATE TRIGGER IF NOT EXISTS `update_follower_counts_on_unfollow`
AFTER UPDATE ON `creator_followers`
FOR EACH ROW
BEGIN
    IF NEW.Status = 'blocked' AND OLD.Status = 'active' THEN
        -- Decrease following count for follower
        UPDATE creator_profiles cp
        SET FollowingCount = FollowingCount - 1
        WHERE cp.AdminId = (
            SELECT AdminId FROM users u WHERE u.UserId = NEW.FollowerID
        );
        
        -- Decrease follower count for creator
        UPDATE creator_profiles cp
        SET FollowersCount = FollowersCount - 1
        WHERE cp.ProfileID = NEW.FollowingID;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- COMMENTS
-- =====================================================

/*
This schema provides a comprehensive creator profiles system with:

1. **Creator Profiles**: Extended information for content creators
2. **Social Media Links**: Multiple social platforms support
3. **Follower System**: Follow/unfollow functionality
4. **Statistics Tracking**: Daily performance metrics
5. **Achievements System**: Gamification and recognition
6. **Category Expertise**: Creator specialization areas
7. **Performance Views**: Quick access to creator data
8. **Automatic Updates**: Triggers for real-time statistics
9. **Soft Delete Support**: All tables support soft deletion
10. **Performance Indexes**: Optimized for fast queries

The system integrates seamlessly with existing admin and article tables
while providing a foundation for advanced creator features.
*/
