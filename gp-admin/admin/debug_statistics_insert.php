<?php
include "php/header/top.php";

echo "<h2>Debugging Statistics Insertion</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

try {
    $profileId = 3;
    $date = '2025-08-13';
    $articlesPublished = 1;
    $totalViews = 100;
    $totalLikes = 10;
    $totalComments = 5;
    $totalShares = 2;
    $newFollowers = 1;
    $engagementRate = 0.25;
    
    echo "<h3>Test Values:</h3>";
    echo "<p>ProfileID: $profileId</p>";
    echo "<p>Date: $date</p>";
    echo "<p>ArticlesPublished: $articlesPublished</p>";
    echo "<p>TotalViews: $totalViews</p>";
    echo "<p>TotalLikes: $totalLikes</p>";
    echo "<p>TotalComments: $totalComments</p>";
    echo "<p>TotalShares: $totalShares</p>";
    echo "<p>NewFollowers: $newFollowers</p>";
    echo "<p>EngagementRate: $engagementRate</p>";
    
    echo "<h3>Testing SQL Statement:</h3>";
    
    // Test the SQL statement
    $sql = "INSERT IGNORE INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews, TotalLikes, TotalComments, TotalShares, NewFollowers, EngagementRate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
    echo "<p><strong>Placeholders:</strong> " . substr_count($sql, '?') . "</p>";
    
    $stmt = $con->prepare($sql);
    if ($stmt) {
        echo "<p>✓ Statement prepared successfully</p>";
        
        // Test binding parameters
        $bindResult = $stmt->bind_param("isiiiiid", $profileId, $date, $articlesPublished, $totalViews, $totalLikes, $totalComments, $totalShares, $newFollowers, $engagementRate);
        
        if ($bindResult) {
            echo "<p>✓ Parameters bound successfully</p>";
            
            // Test execution
            if ($stmt->execute()) {
                echo "<p>✓ Insert executed successfully</p>";
                echo "<p>Insert ID: " . $stmt->insert_id . "</p>";
            } else {
                echo "<p>✗ Execute failed: " . $stmt->error . "</p>";
            }
        } else {
            echo "<p>✗ Bind parameters failed: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>✗ Prepare failed: " . $con->error . "</p>";
    }
    
    echo "<h3>Verification:</h3>";
    
    // Check if the record was inserted
    $checkResult = $con->query("SELECT * FROM creator_statistics WHERE ProfileID = 3 AND Date = '2025-08-13' ORDER BY StatID DESC LIMIT 1");
    if ($checkResult && $checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        echo "<p>✓ Record found in database:</p>";
        echo "<pre>" . print_r($row, true) . "</pre>";
    } else {
        echo "<p>✗ No record found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
