<?php
// Test script for CreatorProfileManager
include "php/config.php";
include "php/includes/CreatorProfileManager.php";

echo "<h1>Creator Profile Manager Test</h1>";

try {
    echo "<h2>Initializing CreatorProfileManager...</h2>";
    $creatorManager = new CreatorProfileManager($con);
    echo "<p style='color: green;'>✓ CreatorProfileManager initialized successfully</p>";
    
    // Test getting all profiles
    echo "<h2>Testing getAllProfiles...</h2>";
    $profilesData = $creatorManager->getAllProfiles(1, 5);
    echo "<p>Total profiles found: " . $profilesData['total'] . "</p>";
    echo "<p>Profiles on current page: " . count($profilesData['profiles']) . "</p>";
    
    if (!empty($profilesData['profiles'])) {
        echo "<h3>First Profile Data:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($profilesData['profiles'][0], true)) . "</pre>";
        
        // Test getting a specific profile
        $firstProfileId = $profilesData['profiles'][0]['ProfileID'];
        echo "<h2>Testing getProfile for ProfileID: $firstProfileId</h2>";
        
        $profile = $creatorManager->getProfile($firstProfileId);
        if ($profile) {
            echo "<p style='color: green;'>✓ Profile retrieved successfully</p>";
            echo "<h3>Profile Data:</h3>";
            echo "<pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
            
            // Test getting articles
            echo "<h2>Testing getCreatorArticles...</h2>";
            $articlesData = $creatorManager->getCreatorArticles($firstProfileId, 1, 5);
            echo "<p>Total articles: " . $articlesData['total'] . "</p>";
            echo "<p>Articles on current page: " . count($articlesData['articles']) . "</p>";
            
            if (!empty($articlesData['articles'])) {
                echo "<h3>First Article Data:</h3>";
                echo "<pre>" . htmlspecialchars(print_r($articlesData['articles'][0], true)) . "</pre>";
            }
            
            // Test getting trending creators
            echo "<h2>Testing getTrendingCreators...</h2>";
            $trendingCreators = $creatorManager->getTrendingCreators(3);
            echo "<p>Trending creators found: " . count($trendingCreators) . "</p>";
            
            if (!empty($trendingCreators)) {
                echo "<h3>First Trending Creator:</h3>";
                echo "<pre>" . htmlspecialchars(print_r($trendingCreators[0], true)) . "</pre>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ Failed to retrieve profile</p>";
        }
    } else {
        echo "<p style='color: orange;'>No profiles found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Check database tables
echo "<h2>Database Tables Check</h2>";
$requiredTables = [
    'creator_profiles',
    'creator_social_links', 
    'creator_followers',
    'creator_statistics',
    'creator_achievements',
    'creator_categories'
];

foreach ($requiredTables as $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Count records
        $countResult = $con->query("SELECT COUNT(*) as count FROM $table");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
            echo "<p>  - Records: $count</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

// Check sample data in creator_profiles
echo "<h2>Sample Data in creator_profiles</h2>";
$result = $con->query("SELECT * FROM creator_profiles LIMIT 3");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<h3>Profile ID: " . $row['ProfileID'] . "</h3>";
        echo "<pre>" . htmlspecialchars(print_r($row, true)) . "</pre>";
    }
} else {
    echo "<p style='color: orange;'>No data found in creator_profiles table</p>";
}

echo "<h2>Test Complete</h2>";
?>
