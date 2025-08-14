<?php
// Script to add videoType column to video_posts table
include 'php/config.php';

echo "<h2>Adding videoType Column to video_posts Table</h2>";

try {
    // Check if column already exists
    $checkColumn = mysqli_query($con, "SHOW COLUMNS FROM video_posts LIKE 'videoType'");
    
    if (mysqli_num_rows($checkColumn) == 0) {
        // Add the videoType column (NOT videoFormat to avoid conflicts)
        $sql = "ALTER TABLE video_posts ADD COLUMN videoType ENUM('video', 'short') DEFAULT 'video' AFTER VideoFormat";
        
        if (mysqli_query($con, $sql)) {
            echo "<p style='color: green;'>✅ Successfully added videoType column to video_posts table</p>";
            
            // Update existing videos to have default 'video' type
            $updateSql = "UPDATE video_posts SET videoType = 'video' WHERE videoType IS NULL OR videoType = ''";
            if (mysqli_query($con, $updateSql)) {
                echo "<p style='color: green;'>✅ Updated existing videos to have 'video' type</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Warning: Could not update existing videos: " . mysqli_error($con) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . mysqli_error($con) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ videoType column already exists</p>";
    }
    
    // Show current table structure
    echo "<h3>Current video_posts Table Structure:</h3>";
    $result = mysqli_query($con, "DESCRIBE video_posts");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='video_posts.php'>← Back to Video Posts</a></p>";
?>
