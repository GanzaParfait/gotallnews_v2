<?php
// Simple database connection test
include "php/config.php";

echo "<h1>Database Connection Test</h1>";

if ($con) {
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    
    // Test if we can query the creator_profiles table
    $result = $con->query("SELECT * FROM creator_profiles WHERE ProfileID = 3");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Profile ID 3 found</p>";
        $profile = $result->fetch_assoc();
        echo "<h3>Profile Data:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
        
        // Check if the profile is not deleted
        if ($profile['isDeleted'] === 'notDeleted') {
            echo "<p style='color: green;'>✓ Profile is not deleted</p>";
        } else {
            echo "<p style='color: red;'>✗ Profile is deleted (isDeleted = " . $profile['isDeleted'] . ")</p>";
        }
        
        // Check DisplayName
        if (!empty($profile['DisplayName'])) {
            echo "<p style='color: green;'>✓ DisplayName: " . htmlspecialchars($profile['DisplayName']) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ DisplayName is empty</p>";
        }
        
        // Check Username
        if (!empty($profile['Username'])) {
            echo "<p style='color: green;'>✓ Username: " . htmlspecialchars($profile['Username']) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Username is empty</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Profile ID 3 not found</p>";
        
        // Check what profiles exist
        $allProfiles = $con->query("SELECT ProfileID, DisplayName, Username, isDeleted FROM creator_profiles LIMIT 5");
        if ($allProfiles && $allProfiles->num_rows > 0) {
            echo "<h3>Available Profiles:</h3>";
            while ($row = $allProfiles->fetch_assoc()) {
                echo "<p>ID: " . $row['ProfileID'] . ", Name: " . htmlspecialchars($row['DisplayName']) . ", Username: " . htmlspecialchars($row['Username']) . ", Deleted: " . $row['isDeleted'] . "</p>";
            }
        }
    }
    
    // Check if required tables exist
    echo "<h2>Required Tables Check</h2>";
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
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

echo "<h2>Test Complete</h2>";
?>
