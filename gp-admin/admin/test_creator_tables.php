<?php
include "php/header/top.php";

echo "<h2>Testing Creator Tables and Data</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

// Check if creator_social_links table exists and has data
echo "<h3>Creator Social Links Table:</h3>";
$result = $con->query("SHOW TABLES LIKE 'creator_social_links'");
if ($result->num_rows === 0) {
    echo "<p>creator_social_links table does not exist!</p>";
} else {
    $result = $con->query("SELECT * FROM creator_social_links WHERE ProfileID = 3");
    if ($result->num_rows === 0) {
        echo "<p>No social links found for ProfileID 3</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>LinkID</th><th>ProfileID</th><th>Platform</th><th>URL</th><th>DisplayText</th><th>Icon</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['LinkID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProfileID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Platform']) . "</td>";
            echo "<td>" . htmlspecialchars($row['URL']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DisplayText']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Icon']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check if creator_achievements table exists and has data
echo "<h3>Creator Achievements Table:</h3>";
$result = $con->query("SHOW TABLES LIKE 'creator_achievements'");
if ($result->num_rows === 0) {
    echo "<p>creator_achievements table does not exist!</p>";
} else {
    $result = $con->query("SELECT * FROM creator_achievements WHERE ProfileID = 3");
    if ($result->num_rows === 0) {
        echo "<p>No achievements found for ProfileID 3</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>AchievementID</th><th>ProfileID</th><th>AchievementType</th><th>Title</th><th>Description</th><th>Icon</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['AchievementID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProfileID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['AchievementType']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Icon']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check if creator_categories table exists and has data
echo "<h3>Creator Categories Table:</h3>";
$result = $con->query("SHOW TABLES LIKE 'creator_categories'");
if ($result->num_rows === 0) {
    echo "<p>creator_categories table does not exist!</p>";
} else {
    $result = $con->query("SELECT * FROM creator_categories WHERE ProfileID = 3");
    if ($result->num_rows === 0) {
        echo "<p>No categories found for ProfileID 3</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>CategoryMapID</th><th>ProfileID</th><th>CategoryID</th><th>ExpertiseLevel</th><th>IsPrimary</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CategoryMapID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProfileID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CategoryID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ExpertiseLevel']) . "</td>";
            echo "<td>" . htmlspecialchars($row['IsPrimary']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check if creator_statistics table exists and has data
echo "<h3>Creator Statistics Table:</h3>";
$result = $con->query("SHOW TABLES LIKE 'creator_statistics'");
if ($result->num_rows === 0) {
    echo "<p>creator_statistics table does not exist!</p>";
} else {
    $result = $con->query("SELECT * FROM creator_statistics WHERE ProfileID = 3");
    if ($result->num_rows === 0) {
        echo "<p>No statistics found for ProfileID 3</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>StatID</th><th>ProfileID</th><th>Date</th><th>ArticlesPublished</th><th>TotalViews</th><th>TotalLikes</th><th>TotalComments</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['StatID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProfileID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ArticlesPublished']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TotalViews']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TotalLikes']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TotalComments']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check if profile photo file exists
echo "<h3>Profile Photo File Check:</h3>";
$profilePhoto = "images/creators/creator_1755016753_689b6e313c408.webp";
if (file_exists($profilePhoto)) {
    echo "<p>Profile photo file exists: $profilePhoto</p>";
    echo "<p>File size: " . filesize($profilePhoto) . " bytes</p>";
} else {
    echo "<p>Profile photo file NOT found: $profilePhoto</p>";
    
    // Check if directory exists
    $dir = "images/creators/";
    if (is_dir($dir)) {
        echo "<p>Directory exists: $dir</p>";
        $files = scandir($dir);
        echo "<p>Files in directory:</p><ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>Directory does not exist: $dir</p>";
    }
}
?>
