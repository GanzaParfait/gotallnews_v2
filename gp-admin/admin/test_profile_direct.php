<?php
// Direct database test for profile ID 3
include "php/config.php";

echo "<h1>Direct Database Test for Profile ID 3</h1>";

if ($con) {
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Test 1: Direct query to creator_profiles table
    echo "<h2>Test 1: Direct creator_profiles Query</h2>";
    $sql1 = "SELECT * FROM creator_profiles WHERE ProfileID = 3";
    echo "<p>SQL: " . $sql1 . "</p>";
    
    $result1 = $con->query($sql1);
    if ($result1) {
        echo "<p>Query executed successfully</p>";
        echo "<p>Rows found: " . $result1->num_rows . "</p>";
        
        if ($result1->num_rows > 0) {
            $profile = $result1->fetch_assoc();
            echo "<h3>Raw Profile Data:</h3>";
            echo "<pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
            
            // Check specific fields
            echo "<h3>Field Check:</h3>";
            echo "<p>ProfileID: " . ($profile['ProfileID'] ?? 'NOT SET') . "</p>";
            echo "<p>DisplayName: " . ($profile['DisplayName'] ?? 'NOT SET') . "</p>";
            echo "<p>Username: " . ($profile['Username'] ?? 'NOT SET') . "</p>";
            echo "<p>Bio: " . ($profile['Bio'] ?? 'NOT SET') . "</p>";
            echo "<p>Location: " . ($profile['Location'] ?? 'NOT SET') . "</p>";
            echo "<p>Expertise: " . ($profile['Expertise'] ?? 'NOT SET') . "</p>";
            echo "<p>isDeleted: " . ($profile['isDeleted'] ?? 'NOT SET') . "</p>";
            echo "<p>Status: " . ($profile['Status'] ?? 'NOT SET') . "</p>";
            
        } else {
            echo "<p style='color: red;'>✗ No profile found with ID 3</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Query failed: " . $con->error . "</p>";
    }
    
    // Test 2: Check table structure
    echo "<h2>Test 2: Table Structure</h2>";
    $result2 = $con->query("DESCRIBE creator_profiles");
    if ($result2) {
        echo "<h3>creator_profiles Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result2->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Check all profiles
    echo "<h2>Test 3: All Available Profiles</h2>";
    $result3 = $con->query("SELECT ProfileID, DisplayName, Username, isDeleted, Status FROM creator_profiles ORDER BY ProfileID");
    if ($result3 && $result3->num_rows > 0) {
        echo "<h3>All Profiles:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>DisplayName</th><th>Username</th><th>isDeleted</th><th>Status</th></tr>";
        while ($row = $result3->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['ProfileID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['DisplayName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
            echo "<td>" . $row['isDeleted'] . "</td>";
            echo "<td>" . $row['Status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ No profiles found in table</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

echo "<h2>Test Complete</h2>";
?>
