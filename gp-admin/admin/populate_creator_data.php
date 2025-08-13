<?php
include "php/header/top.php";

echo "<h2>Populating Creator Data for Profile ID 3</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

try {
    // 1. Add social links for Profile ID 3
    echo "<h3>Adding Social Links...</h3>";
    $socialLinks = [
        ['Platform' => 'website', 'URL' => 'https://lerony.com', 'DisplayText' => 'Official Website', 'Icon' => 'fas fa-globe', 'OrderIndex' => 1],
        ['Platform' => 'twitter', 'URL' => 'https://twitter.com/princeganza', 'DisplayText' => 'Twitter', 'Icon' => 'fab fa-twitter', 'OrderIndex' => 2],
        ['Platform' => 'linkedin', 'URL' => 'https://linkedin.com/in/princeganza', 'DisplayText' => 'LinkedIn', 'Icon' => 'fab fa-linkedin', 'OrderIndex' => 3],
        ['Platform' => 'instagram', 'URL' => 'https://instagram.com/princeganza', 'DisplayText' => 'Instagram', 'Icon' => 'fab fa-instagram', 'OrderIndex' => 4]
    ];
    
    foreach ($socialLinks as $link) {
        $sql = "INSERT IGNORE INTO creator_social_links (ProfileID, Platform, URL, DisplayText, Icon, OrderIndex, IsActive) 
                VALUES (3, ?, ?, ?, ?, ?, 1)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssi", $link['Platform'], $link['URL'], $link['DisplayText'], $link['Icon'], $link['OrderIndex']);
        if ($stmt->execute()) {
            echo "<p>✓ Added social link: {$link['Platform']}</p>";
        } else {
            echo "<p>✗ Failed to add social link: {$link['Platform']} - " . $stmt->error . "</p>";
        }
    }
    
    // 2. Add achievements for Profile ID 3
    echo "<h3>Adding Achievements...</h3>";
    $achievements = [
        ['AchievementType' => 'content', 'Title' => 'First Article Published', 'Description' => 'Successfully published your first article on the platform', 'Icon' => 'star'],
        ['AchievementType' => 'engagement', 'Title' => 'Community Builder', 'Description' => 'Built an engaged community of readers and followers', 'Icon' => 'users'],
        ['AchievementType' => 'expertise', 'Title' => 'Politics Expert', 'Description' => 'Recognized as an expert in political content and analysis', 'Icon' => 'trophy'],
        ['AchievementType' => 'consistency', 'Title' => 'Regular Contributor', 'Description' => 'Maintained consistent content creation for 2+ years', 'Icon' => 'calendar']
    ];
    
    foreach ($achievements as $achievement) {
        $sql = "INSERT IGNORE INTO creator_achievements (ProfileID, AchievementType, Title, Description, Icon, IsActive, AchievedDate) 
                VALUES (3, ?, ?, ?, ?, 1, NOW())";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssss", $achievement['AchievementType'], $achievement['Title'], $achievement['Description'], $achievement['Icon']);
        if ($stmt->execute()) {
            echo "<p>✓ Added achievement: {$achievement['Title']}</p>";
        } else {
            echo "<p>✗ Failed to add achievement: {$achievement['Title']} - " . $stmt->error . "</p>";
        }
    }
    
    // 3. Add category expertise for Profile ID 3
    echo "<h3>Adding Category Expertise...</h3>";
    
    // First, get the category ID for Politics
    $categoryResult = $con->query("SELECT CategoryID FROM category WHERE Category LIKE '%Politics%' OR Category LIKE '%Political%' LIMIT 1");
    if ($categoryResult && $categoryResult->num_rows > 0) {
        $categoryRow = $categoryResult->fetch_assoc();
        $politicsCategoryId = $categoryRow['CategoryID'];
        
        $sql = "INSERT IGNORE INTO creator_categories (ProfileID, CategoryID, ExpertiseLevel, IsPrimary) VALUES (3, ?, 'expert', 1)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $politicsCategoryId);
        if ($stmt->execute()) {
            echo "<p>✓ Added category expertise: Politics (Expert Level)</p>";
        } else {
            echo "<p>✗ Failed to add category expertise: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>⚠ No Politics category found in category table</p>";
    }
    
    // 4. Add some statistics for Profile ID 3
    echo "<h3>Adding Statistics...</h3>";
    
    // Add statistics for the last 30 days
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $articlesPublished = ($i % 7 == 0) ? 1 : 0; // Publish article every 7 days
        $totalViews = rand(50, 500);
        $totalLikes = rand(5, 50);
        $totalComments = rand(1, 20);
        $totalShares = rand(1, 10);
        $newFollowers = ($i % 3 == 0) ? rand(1, 5) : 0;
        $engagementRate = rand(25, 75) / 100;
        
        $sql = "INSERT IGNORE INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews, TotalLikes, TotalComments, TotalShares, NewFollowers, EngagementRate) 
                VALUES (3, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("siiiiid", $date, $articlesPublished, $totalViews, $totalLikes, $totalComments, $totalShares, $newFollowers, $engagementRate);
        if ($stmt->execute()) {
            if ($i < 5) { // Only show first 5 for brevity
                echo "<p>✓ Added statistics for: $date</p>";
            }
        } else {
            echo "<p>✗ Failed to add statistics for: $date - " . $stmt->error . "</p>";
        }
    }
    
    // 5. Update the creator profile with calculated totals
    echo "<h3>Updating Profile Totals...</h3>";
    
    // Get total articles
    $articleResult = $con->query("SELECT COUNT(*) as total FROM article WHERE AdminId = 1 AND Published = 'published'");
    $totalArticles = $articleResult->fetch_assoc()['total'];
    
    // Get total views from statistics
    $viewsResult = $con->query("SELECT SUM(TotalViews) as total FROM creator_statistics WHERE ProfileID = 3");
    $totalViews = $viewsResult->fetch_assoc()['total'] ?? 0;
    
    // Update profile
    $updateSql = "UPDATE creator_profiles SET TotalArticles = ?, TotalViews = ? WHERE ProfileID = 3";
    $updateStmt = $con->prepare($updateSql);
    $updateStmt->bind_param("ii", $totalArticles, $totalViews);
    if ($updateStmt->execute()) {
        echo "<p>✓ Updated profile totals: $totalArticles articles, $totalViews views</p>";
    } else {
        echo "<p>✗ Failed to update profile totals: " . $updateStmt->error . "</p>";
    }
    
    echo "<h3>Data Population Complete!</h3>";
    echo "<p>Now when you view the creator profile, you should see:</p>";
    echo "<ul>";
    echo "<li>✓ Social links (Website, Twitter, LinkedIn, Instagram)</li>";
    echo "<li>✓ Achievements (4 different achievements)</li>";
    echo "<li>✓ Category expertise (Politics - Expert Level)</li>";
    echo "<li>✓ Statistics (30 days of data)</li>";
    echo "<li>✓ Updated profile totals</li>";
    echo "</ul>";
    echo "<p><a href='creator_profile_view.php?id=3' class='btn btn-primary'>View Updated Profile</a></p>";
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
