<?php
include "php/config.php";

echo "<h2>🎬 Shorts System Setup Test</h2>";

// Check if tables exist
$tables = [
    'users',
    'short_video_comments',
    'short_video_likes',
    'short_video_saves',
    'short_video_shares',
    'short_video_views',
    'user_playlists',
    'short_video_analytics',
    'user_video_interactions'
];

echo "<h3>📋 Checking Tables:</h3>";
foreach ($tables as $table) {
    $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

// Check if users table has data
echo "<h3>👥 Checking Users:</h3>";
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Users count: " . $row['count'] . "<br>";
    
    if ($row['count'] == 0) {
        echo "⚠️ No users found. Adding demo user...<br>";
        
        // Add demo user
        $demoUser = "INSERT INTO users (Username, Email, Password, DisplayName, Status) VALUES 
                    ('demo_user', 'demo@example.com', 'demo123', 'Demo User', 'active')";
        
        if (mysqli_query($con, $demoUser)) {
            echo "✅ Demo user added successfully<br>";
        } else {
            echo "❌ Failed to add demo user: " . mysqli_error($con) . "<br>";
        }
    }
} else {
    echo "❌ Error checking users: " . mysqli_error($con) . "<br>";
}

// Check video_posts for shorts
echo "<h3>🎥 Checking Short Videos:</h3>";
$result = mysqli_query($con, "SELECT VideoID, Title, videoType, Status, isDeleted FROM video_posts WHERE videoType = 'short' LIMIT 10");
if ($result) {
    $count = mysqli_num_rows($result);
    echo "Found $count short videos:<br>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- VideoID: {$row['VideoID']}, Title: {$row['Title']}, Status: {$row['Status']}, isDeleted: {$row['isDeleted']}<br>";
    }
    
    if ($count == 0) {
        echo "⚠️ No short videos found. Please create some shorts first.<br>";
    }
} else {
    echo "❌ Error checking videos: " . mysqli_error($con) . "<br>";
}

// Check if comments table has data
echo "<h3>💬 Checking Comments:</h3>";
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM short_video_comments");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Comments count: " . $row['count'] . "<br>";
    
    if ($row['count'] == 0) {
        echo "⚠️ No comments found. Adding sample comment...<br>";
        
        // Get first short video
        $videoResult = mysqli_query($con, "SELECT VideoID FROM video_posts WHERE videoType = 'short' LIMIT 1");
        if ($videoResult && mysqli_num_rows($videoResult) > 0) {
            $videoRow = mysqli_fetch_assoc($videoResult);
            $videoId = $videoRow['VideoID'];
            
            // Get first user
            $userResult = mysqli_query($con, "SELECT UserID FROM users LIMIT 1");
            if ($userResult && mysqli_num_rows($userResult) > 0) {
                $userRow = mysqli_fetch_assoc($userResult);
                $userId = $userRow['UserID'];
                
                // Add sample comment
                $sampleComment = "INSERT INTO short_video_comments (VideoID, UserID, CommentText, Status, Created_at) VALUES 
                                ($videoId, $userId, 'This is a great short video! 🎬', 'approved', NOW())";
                
                if (mysqli_query($con, $sampleComment)) {
                    echo "✅ Sample comment added successfully<br>";
                } else {
                    echo "❌ Failed to add sample comment: " . mysqli_error($con) . "<br>";
                }
            }
        } else {
            echo "⚠️ No short videos available to add comment to<br>";
        }
    }
} else {
    echo "❌ Error checking comments: " . mysqli_error($con) . "<br>";
}

echo "<h3>🔧 Setup Complete!</h3>";
echo "<p>Now try accessing the video_shorts_reels.php page to see if the dark screen issue is resolved.</p>";
echo "<p>Click on the comment button to test the comments modal.</p>";
?>
