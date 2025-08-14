<?php
include "php/config.php";

echo "<h2>🔍 Debug File Paths</h2>";

// Check video_posts table structure
echo "<h3>📋 Video Posts Table Structure:</h3>";
$result = mysqli_query($con, "DESCRIBE video_posts");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($con);
}

// Check actual data in video_posts
echo "<h3>🎥 Sample Video Data:</h3>";
$result = mysqli_query($con, "SELECT VideoID, Title, VideoFile, VideoThumbnail, videoType, Status FROM video_posts WHERE videoType = 'short' LIMIT 5");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>VideoID</th><th>Title</th><th>VideoFile</th><th>VideoThumbnail</th><th>videoType</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['VideoID']}</td>";
        echo "<td>{$row['Title']}</td>";
        echo "<td>{$row['VideoFile']}</td>";
        echo "<td>{$row['VideoThumbnail']}</td>";
        echo "<td>{$row['videoType']}</td>";
        echo "<td>{$row['Status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No short videos found or error: " . mysqli_error($con);
}

// Check if files actually exist
echo "<h3>📁 File Existence Check:</h3>";
$result = mysqli_query($con, "SELECT VideoFile, VideoThumbnail FROM video_posts WHERE videoType = 'short' LIMIT 3");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $videoPath = $row['VideoFile'];
        $thumbPath = $row['VideoThumbnail'];
        
        echo "<p><strong>Video:</strong> {$videoPath}</p>";
        if (file_exists($videoPath)) {
            echo "✅ Video file exists<br>";
        } else {
            echo "❌ Video file NOT found<br>";
        }
        
        echo "<p><strong>Thumbnail:</strong> {$thumbPath}</p>";
        if (file_exists($thumbPath)) {
            echo "✅ Thumbnail exists<br>";
        } else {
            echo "❌ Thumbnail NOT found<br>";
        }
        
        // Check with different path combinations
        $possiblePaths = [
            $videoPath,
            "uploads/videos/" . basename($videoPath),
            "gp-admin/admin/uploads/videos/" . basename($videoPath),
            "gp-admin/admin/" . $videoPath
        ];
        
        echo "<p><strong>Possible paths:</strong></p>";
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                echo "✅ Found: {$path}<br>";
            } else {
                echo "❌ Not found: {$path}<br>";
            }
        }
        echo "<hr>";
    }
} else {
    echo "No videos to check";
}

// Check current working directory
echo "<h3>📍 Current Directory Info:</h3>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Script location: " . __FILE__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Check if uploads directory exists
echo "<h3>📂 Directory Check:</h3>";
$dirs = [
    'uploads',
    'uploads/videos',
    'images',
    'images/video_thumbnails',
    '../uploads',
    '../uploads/videos',
    '../images',
    '../images/video_thumbnails'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: {$dir}<br>";
        if (is_readable($dir)) {
            echo "   - Readable: Yes<br>";
        } else {
            echo "   - Readable: No<br>";
        }
        if (is_writable($dir)) {
            echo "   - Writable: Yes<br>";
        } else {
            echo "   - Writable: No<br>";
        }
    } else {
        echo "❌ Directory missing: {$dir}<br>";
    }
}
?>
