<?php
// Explicit test to count parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gotahhqa_gpnews';

try {
    $con = new mysqli($host, $username, $password, $database);
    
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }
    
    echo "<h2>Explicit Parameter Count Test</h2>";
    
    // Test values
    $profileId = 3;
    $date = '2025-08-13';
    $articlesPublished = 1;
    $totalViews = 100;
    $totalLikes = 10;
    $totalComments = 5;
    $totalShares = 2;
    $newFollowers = 1;
    $engagementRate = 0.25;
    
    echo "<h3>Parameters:</h3>";
    echo "<p>1. ProfileID: $profileId (integer)</p>";
    echo "<p>2. Date: $date (string)</p>";
    echo "<p>3. ArticlesPublished: $articlesPublished (integer)</p>";
    echo "<p>4. TotalViews: $totalViews (integer)</p>";
    echo "<p>5. TotalLikes: $totalLikes (integer)</p>";
    echo "<p>6. TotalComments: $totalComments (integer)</p>";
    echo "<p>7. TotalShares: $totalShares (integer)</p>";
    echo "<p>8. NewFollowers: $newFollowers (integer)</p>";
    echo "<p>9. EngagementRate: $engagementRate (decimal)</p>";
    
    echo "<h3>Type String Analysis:</h3>";
    $typeString = "isiiiiid";
    echo "<p><strong>Type String:</strong> '$typeString'</p>";
    echo "<p><strong>Length:</strong> " . strlen($typeString) . "</p>";
    echo "<p><strong>Characters:</strong></p>";
    for ($i = 0; $i < strlen($typeString); $i++) {
        echo "<p>" . ($i + 1) . ". '" . $typeString[$i] . "'</p>";
    }
    
    echo "<h3>SQL Statement:</h3>";
    $sql = "INSERT IGNORE INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews, TotalLikes, TotalComments, TotalShares, NewFollowers, EngagementRate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
    echo "<p><strong>Placeholders:</strong> " . substr_count($sql, '?') . "</p>";
    
    $stmt = $con->prepare($sql);
    if ($stmt) {
        echo "<p>✓ Statement prepared successfully</p>";
        
        // Try binding with explicit counting
        echo "<h3>Binding Parameters:</h3>";
        
        // Method 1: Count each parameter explicitly
        $param1 = $profileId;
        $param2 = $date;
        $param3 = $articlesPublished;
        $param4 = $totalViews;
        $param5 = $totalLikes;
        $param6 = $totalComments;
        $param7 = $totalShares;
        $param8 = $newFollowers;
        $param9 = $engagementRate;
        
        echo "<p>Binding 9 parameters...</p>";
        
        $bindResult = $stmt->bind_param("isiiiiid", 
            $param1, $param2, $param3, $param4, $param5, 
            $param6, $param7, $param8, $param9);
        
        if ($bindResult) {
            echo "<p>✓ Parameters bound successfully</p>";
            
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
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
