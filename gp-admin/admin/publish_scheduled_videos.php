<?php
/**
 * Script to automatically publish scheduled videos
 * This should be run via cron job every few minutes
 * Example cron: 5 * * * * php /path/to/publish_scheduled_videos.php
*/

require_once 'php/config.php';
require_once 'php/includes/VideoManager.php';

try {
    // Get current timestamp
    $currentTime = date('Y-m-d H:i:s');
    
    // Find videos that are scheduled and should be published
    $sql = "SELECT VideoID, Title, PublishDate FROM video_posts 
            WHERE Status = 'scheduled'
            AND PublishDate <= ? 
            AND isDeleted = 'notDeleted'";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param('s', $currentTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $publishedCount = 0;
    
    while ($video = $result->fetch_assoc()) {
        // Update video status to published
        $updateSql = "UPDATE video_posts SET 
                      Status = 'published', 
                      Published_at = NOW(), 
                      Updated_at = NOW() 
                      WHERE VideoID = ?";
        
        $updateStmt = $con->prepare($updateSql);
        $updateStmt->bind_param('i', $video['VideoID']);
        
        if ($updateStmt->execute()) {
            $publishedCount++;
            echo "✅ Published video: {$video['Title']} (ID: {$video['VideoID']})\n";
        } else {
            echo "❌ Failed to publish video: {$video['Title']} (ID: {$video['VideoID']}) - {$con->error}\n";
        }
        
        $updateStmt->close();
    }
    
    if ($publishedCount > 0) {
        echo "\n🎉 Successfully published $publishedCount scheduled videos\n";
    } else {
        echo "\nℹ️ No scheduled videos to publish at this time\n";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$con->close();
echo "\nScript completed at " . date('Y-m-d H:i:s') . "\n";
?>
