<?php
/**
 * Creator Profiles Installation Script (MariaDB Compatible)
 * Run this file to set up the database tables for the creator profiles system
 */

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gotahhqa_gpnews';

try {
    $con = new mysqli($host, $username, $password, $database);
    
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }
    
    echo "<h2>Creator Profiles System Installation (MariaDB Compatible)</h2>";
    echo "<p>Setting up database tables...</p>";
    
    // Step 1: Create tables (without foreign keys first)
    $tables = [
        "CREATE TABLE IF NOT EXISTS `creator_profiles` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `creator_social_links` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `creator_followers` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `creator_statistics` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `creator_achievements` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `creator_categories` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    echo "<h3>Step 1: Creating Tables</h3>";
    
    foreach ($tables as $tableSQL) {
        try {
            if ($con->query($tableSQL)) {
                $successCount++;
                echo "<p style='color: green;'>✓ Table created successfully</p>";
            } else {
                $errorCount++;
                echo "<p style='color: red;'>✗ Error creating table: " . $con->error . "</p>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
        }
    }
    
    // Step 2: Create view
    echo "<h3>Step 2: Creating View</h3>";
    
    $viewSQL = "CREATE OR REPLACE VIEW `creator_articles_summary` AS
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
        GROUP BY cp.ProfileID";
    
    if ($con->query($viewSQL)) {
        $successCount++;
        echo "<p style='color: green;'>✓ View created successfully</p>";
    } else {
        $errorCount++;
        echo "<p style='color: red;'>✗ Error creating view: " . $con->error . "</p>";
    }
    
    // Step 3: Create additional indexes
    echo "<h3>Step 3: Creating Additional Indexes</h3>";
    
    $indexes = [
        "CREATE INDEX `idx_creator_profiles_views` ON `creator_profiles` (`TotalViews`)",
        "CREATE INDEX `idx_creator_profiles_followers` ON `creator_profiles` (`FollowersCount`)",
        "CREATE INDEX `idx_creator_profiles_engagement` ON `creator_profiles` (`EngagementScore`)",
        "CREATE INDEX `idx_creator_profiles_created` ON `creator_profiles` (`Created_at`)",
        "CREATE INDEX `idx_creator_social_links_profile` ON `creator_social_links` (`ProfileID`, `IsActive`)",
        "CREATE INDEX `idx_creator_followers_profile` ON `creator_followers` (`FollowerID`, `FollowingID`, `Status`)",
        "CREATE INDEX `idx_creator_statistics_profile_date` ON `creator_statistics` (`ProfileID`, `Date`)",
        "CREATE INDEX `idx_creator_achievements_profile` ON `creator_achievements` (`ProfileID`, `IsActive`)",
        "CREATE INDEX `idx_creator_categories_profile` ON `creator_categories` (`ProfileID`, `IsPrimary`)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            if ($con->query($indexSQL)) {
                $successCount++;
                echo "<p style='color: green;'>✓ Index created successfully</p>";
            } else {
                $errorCount++;
                echo "<p style='color: red;'>✗ Error creating index: " . $con->error . "</p>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
        }
    }
    
    // Step 4: Add foreign key constraints
    echo "<h3>Step 4: Adding Foreign Key Constraints</h3>";
    
    $constraints = [
        "ALTER TABLE `creator_social_links` ADD CONSTRAINT `fk_social_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_followers` ADD CONSTRAINT `fk_follower_profile` FOREIGN KEY (`FollowerID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_followers` ADD CONSTRAINT `fk_following_profile` FOREIGN KEY (`FollowingID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_statistics` ADD CONSTRAINT `fk_stats_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_achievements` ADD CONSTRAINT `fk_achievement_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_categories` ADD CONSTRAINT `fk_category_profile` FOREIGN KEY (`ProfileID`) REFERENCES `creator_profiles` (`ProfileID`) ON DELETE CASCADE",
        "ALTER TABLE `creator_profiles` ADD CONSTRAINT `fk_creator_admin` FOREIGN KEY (`AdminId`) REFERENCES `admin` (`AdminId`) ON DELETE CASCADE"
    ];
    
    // Check if category table exists before adding category constraint
    $categoryExists = $con->query("SHOW TABLES LIKE 'category'");
    if ($categoryExists && $categoryExists->num_rows > 0) {
        $constraints[] = "ALTER TABLE `creator_categories` ADD CONSTRAINT `fk_category_category` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE CASCADE";
    }
    
    foreach ($constraints as $constraintSQL) {
        try {
            if ($con->query($constraintSQL)) {
                $successCount++;
                echo "<p style='color: green;'>✓ Foreign key constraint added successfully</p>";
            } else {
                $errorCount++;
                echo "<p style='color: red;'>✗ Error adding constraint: " . $con->error . "</p>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
        }
    }
    
    // Step 5: Create triggers (simplified for MariaDB)
    echo "<h3>Step 5: Creating Triggers (Simplified)</h3>";
    
    // Drop existing triggers if they exist
    $con->query("DROP TRIGGER IF EXISTS `update_creator_stats_on_article_publish`");
    $con->query("DROP TRIGGER IF EXISTS `update_follower_counts_on_follow`");
    $con->query("DROP TRIGGER IF EXISTS `update_follower_counts_on_unfollow`");
    
    // Create simplified triggers
    $trigger1 = "CREATE TRIGGER `update_creator_stats_on_article_publish`
        AFTER INSERT ON `article`
        FOR EACH ROW
        BEGIN
            DECLARE creator_profile_id INT;
            SELECT ProfileID INTO creator_profile_id 
            FROM creator_profiles 
            WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted';
            
            IF creator_profile_id IS NOT NULL THEN
                INSERT INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews)
                VALUES (creator_profile_id, CURDATE(), 1, 0)
                ON DUPLICATE KEY UPDATE
                ArticlesPublished = ArticlesPublished + 1;
                
                UPDATE creator_profiles 
                SET TotalArticles = TotalArticles + 1
                WHERE ProfileID = creator_profile_id;
            END IF;
        END";
    
    $trigger2 = "CREATE TRIGGER `update_follower_counts_on_follow`
        AFTER INSERT ON `creator_followers`
        FOR EACH ROW
        BEGIN
            IF NEW.Status = 'active' THEN
                UPDATE creator_profiles 
                SET FollowingCount = FollowingCount + 1
                WHERE ProfileID = NEW.FollowerID;
                
                UPDATE creator_profiles 
                SET FollowersCount = FollowersCount + 1
                WHERE ProfileID = NEW.FollowingID;
            END IF;
        END";
    
    $trigger3 = "CREATE TRIGGER `update_follower_counts_on_unfollow`
        AFTER UPDATE ON `creator_followers`
        FOR EACH ROW
        BEGIN
            IF OLD.Status = 'active' AND NEW.Status != 'active' THEN
                UPDATE creator_profiles 
                SET FollowingCount = FollowingCount - 1
                WHERE ProfileID = NEW.FollowerID;
                
                UPDATE creator_profiles 
                SET FollowersCount = FollowersCount - 1
                WHERE ProfileID = NEW.FollowingID;
            END IF;
        END";
    
    $triggers = [$trigger1, $trigger2, $trigger3];
    
    foreach ($triggers as $triggerSQL) {
        try {
            if ($con->query($triggerSQL)) {
                $successCount++;
                echo "<p style='color: green;'>✓ Trigger created successfully</p>";
            } else {
                $errorCount++;
                echo "<p style='color: red;'>✗ Error creating trigger: " . $con->error . "</p>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
        }
    }
    
    // Step 6: Create images directory
    echo "<h3>Step 6: Creating Directories</h3>";
    
    $uploadDir = 'images/creators/';
    if (!is_dir($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "<p style='color: green;'>✓ Created images/creators/ directory</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Warning: Could not create images/creators/ directory. Please create it manually.</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ images/creators/ directory already exists</p>";
    }
    
    echo "<hr>";
    echo "<h3>Installation Summary:</h3>";
    echo "<p><strong>Successful:</strong> $successCount operations</p>";
    echo "<p><strong>Errors:</strong> $errorCount operations</p>";
    
    if ($errorCount == 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🎉 Installation Complete!</h4>";
        echo "<p>The Creator Profiles system has been successfully installed.</p>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ol>";
        echo "<li>Go to <a href='creator_profiles.php'>Creator Profiles</a> to manage creators</li>";
        echo "<li>Create your first creator profile</li>";
        echo "<li>Explore the new features in your dashboard</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>⚠️ Installation Completed with Some Errors</h4>";
        echo "<p>Some database operations failed, but the core system may still work.</p>";
        echo "<p>Please check the error messages above and try to resolve them manually.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Installation Failed</h4>";
    echo "<p>An error occurred during installation: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Close connection
if (isset($con)) {
    $con->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Creator Profiles Installation (Fixed)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        h2, h3, h4 {
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        code {
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Creator Profiles System (Fixed for MariaDB)</h1>
        
        <div class="info">
            <h4>What's Fixed?</h4>
            <p>This version addresses MariaDB compatibility issues:</p>
            <ul>
                <li><strong>Index Creation:</strong> Fixed missing index names and removed IF NOT EXISTS</li>
                <li><strong>Foreign Keys:</strong> Added constraints after all tables are created</li>
                <li><strong>Trigger Syntax:</strong> Fixed DELIMITER and trigger creation syntax</li>
                <li><strong>Error Handling:</strong> Better error reporting and recovery</li>
                <li><strong>Step-by-Step:</strong> Installation broken into manageable steps</li>
                <li><strong>Directory Creation:</strong> Automatic creation of required folders</li>
            </ul>
        </div>
        
        <div class="warning">
            <h4>⚠️ Important Notes</h4>
            <ul>
                <li>This script creates tables first, then adds constraints and triggers separately</li>
                <li>Foreign keys are added after all tables exist to avoid dependency issues</li>
                <li>Triggers are simplified to avoid MariaDB syntax issues</li>
                <li>If some operations fail, the system may still work (manual updates needed)</li>
                <li>Ensure your database user has sufficient privileges</li>
            </ul>
        </div>
        
        <div class="success">
            <h4>✅ System Requirements</h4>
            <ul>
                <li>PHP 7.4+ with MySQLi extension</li>
                <li>MariaDB 10.2+ (or MySQL 5.7+)</li>
                <li>Existing database with admin and article tables</li>
                <li>Write permissions for the images/creators/ directory</li>
            </ul>
        </div>
        
        <hr>
        
        <h3>📋 Installation Steps</h3>
        <ol>
            <li><strong>Database Setup:</strong> Run this script to create all necessary tables</li>
            <li><strong>File Permissions:</strong> Ensure the images/creators/ directory is writable</li>
            <li><strong>Configuration:</strong> Update database connection details if needed</li>
            <li><strong>Testing:</strong> Visit the Creator Profiles section in your admin dashboard</li>
        </ol>
        
        <hr>
        
        <h3>🔧 Manual Installation (if automatic fails)</h3>
        <p>If the automatic installation fails, you can manually run the SQL commands:</p>
        <ol>
            <li>Open your database management tool (phpMyAdmin, MySQL Workbench, etc.)</li>
            <li>Select your database: <code>gotahhqa_gpnews</code></li>
            <li>Open the file: <code>creator_profiles_schema_fixed.sql</code></li>
            <li>Execute the SQL commands manually</li>
        </ol>
        
        <hr>
        
        <h3>📁 File Structure</h3>
        <p>After installation, you'll have these new files:</p>
        <ul>
            <li><code>php/includes/CreatorProfileManager.php</code> - Main class for managing profiles</li>
            <li><code>creator_profiles.php</code> - Main management page</li>
            <li><code>creator_profile_view.php</code> - Individual profile view</li>
            <li><code>images/creators/</code> - Directory for creator profile photos</li>
        </ul>
        
        <hr>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="creator_profiles.php" class="btn btn-success">🚀 Go to Creator Profiles</a>
            <a href="index.php" class="btn btn-warning">🏠 Back to Dashboard</a>
        </div>
        
        <div class="info">
            <h4>📞 Need Help?</h4>
            <p>If you encounter any issues during installation:</p>
            <ul>
                <li>Check the error messages above for specific database errors</li>
                <li>Verify your database connection details</li>
                <li>Ensure your database user has CREATE, ALTER, and INSERT privileges</li>
                <li>Check the server error logs for additional information</li>
            </ul>
        </div>
    </div>
</body>
</html>
