<?php
// Test script to check profile ID 3
include "php/config.php";

echo "<h1>Profile ID 3 Test</h1>";

if ($con) {
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Check if profile ID 3 exists
    $sql = "SELECT * FROM creator_profiles WHERE ProfileID = 3";
    echo "<p>SQL: " . $sql . "</p>";
    
    $result = $con->query($sql);
    if ($result) {
        echo "<p>Query executed successfully</p>";
        echo "<p>Rows found: " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            $profile = $result->fetch_assoc();
            echo "<h3>Profile Data:</h3>";
            echo "<pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
            
            // Check specific fields
            echo "<h3>Field Check:</h3>";
            echo "<p>ProfileID: " . ($profile['ProfileID'] ?? 'NOT SET') . "</p>";
            echo "<p>DisplayName: " . ($profile['DisplayName'] ?? 'NOT SET') . "</p>";
            echo "<p>Username: " . ($profile['Username'] ?? 'NOT SET') . "</p>";
            echo "<p>isDeleted: " . ($profile['isDeleted'] ?? 'NOT SET') . "</p>";
            echo "<p>Status: " . ($profile['Status'] ?? 'NOT SET') . "</p>";
            
            // Check if profile should be visible
            if ($profile['isDeleted'] === 'notDeleted') {
                echo "<p style='color: green;'>✓ Profile is not deleted</p>";
            } else {
                echo "<p style='color: red;'>✗ Profile is deleted</p>";
            }
            
            if ($profile['Status'] === 'active') {
                echo "<p style='color: green;'>✓ Profile is active</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Profile is not active (Status: " . $profile['Status'] . ")</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ No profile found with ID 3</p>";
            
            // Check what profiles exist
            $allProfiles = $con->query("SELECT ProfileID, DisplayName, Username, isDeleted, Status FROM creator_profiles ORDER BY ProfileID");
            if ($allProfiles && $allProfiles->num_rows > 0) {
                echo "<h3>All Available Profiles:</h3>";
                while ($row = $allProfiles->fetch_assoc()) {
                    echo "<p>ID: " . $row['ProfileID'] . ", Name: " . htmlspecialchars($row['DisplayName']) . ", Username: " . htmlspecialchars($row['Username']) . ", Deleted: " . $row['isDeleted'] . ", Status: " . $row['Status'] . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>✗ Query failed: " . $con->error . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

echo "<h2>Test Complete</h2>";
?>
