<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['videoId'])) {
        throw new Exception('Video ID is required');
    }

    $videoId = (int) $input['videoId'];

    // Get user info (if logged in) - Use log_uni_id for better tracking
    $userId = null;
    if (isset($_SESSION['log_uni_id'])) {
        $userId = $_SESSION['log_uni_id'];
    } else {
        // For anonymous users, use IP address as identifier
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userId = crc32($ipAddress) % 1000000;  // Generate a numeric ID from IP
    }

    $checkSql = 'SELECT LikeID FROM short_video_likes WHERE VideoID = ? AND UserID = ?';
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param('ii', $videoId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Unlike: remove the like
        $deleteSql = 'DELETE FROM short_video_likes WHERE VideoID = ? AND UserID = ?';
        $deleteStmt = $con->prepare($deleteSql);
        $deleteStmt->bind_param('ii', $videoId, $userId);

        if ($deleteStmt->execute()) {
            // Update video likes count
            $updateSql = 'UPDATE video_posts SET Likes = GREATEST(Likes - 1, 0) WHERE VideoID = ?';
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bind_param('i', $videoId);
            $updateStmt->execute();

            // Update user interaction
            $updateInteraction = 'UPDATE user_video_interactions SET HasLiked = 0 WHERE UserID = ? AND VideoID = ?';
            $interactionStmt = $con->prepare($updateInteraction);
            $interactionStmt->bind_param('ii', $userId, $videoId);
            $interactionStmt->execute();

            echo json_encode([
                'success' => true,
                'liked' => false,
                'message' => 'Video unliked'
            ]);
        } else {
            throw new Exception('Failed to unlike video');
        }
    } else {
        // Like: add the like
        $insertSql = 'INSERT INTO short_video_likes (VideoID, UserID, LikedAt) VALUES (?, ?, NOW())';
        $insertStmt = $con->prepare($insertSql);
        $insertStmt->bind_param('ii', $videoId, $userId);

        if ($insertStmt->execute()) {
            // Update video likes count
            $updateSql = 'UPDATE video_posts SET Likes = Likes + 1 WHERE VideoID = ?';
            $updateStmt = $con->prepare($updateSql);
            $updateStmt->bind_param('i', $videoId);
            $updateStmt->execute();

            // Update user interaction
            $updateInteraction = 'INSERT INTO user_video_interactions (UserID, VideoID, HasLiked) 
                                VALUES (?, ?, 1) 
                                ON DUPLICATE KEY UPDATE HasLiked = 1';
            $interactionStmt = $con->prepare($updateInteraction);
            $interactionStmt->bind_param('ii', $userId, $videoId);
            $interactionStmt->execute();

            echo json_encode([
                'success' => true,
                'liked' => true,
                'message' => 'Video liked'
            ]);
        } else {
            throw new Exception('Failed to like video');
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
