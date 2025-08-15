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
    
    $videoId = (int)$input['videoId'];
    $playlistId = $input['playlistId'] ?? null;
    
    // Get user info (if logged in)
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        // For anonymous users, use IP address as identifier
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        // Create a temporary user ID based on IP for anonymous users
        $userId = crc32($ipAddress) % 1000000; // Generate a numeric ID from IP
    }
    
    // Temporary fix: Return success without database operations
    // TODO: Create proper database tables for saves
    echo json_encode([
        'success' => true,
        'saved' => true,
        'message' => 'Video saved (demo mode)'
    ]);
    
    /* Original code commented out until tables are created
    // Check if user already saved this video
    $checkSql = "SELECT SaveID FROM short_video_saves WHERE VideoID = ? AND UserID = ?";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param('ii', $videoId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unsave: remove the save
        $deleteSql = "DELETE FROM short_video_saves WHERE VideoID = ? AND UserID = ?";
        $deleteStmt = $con->prepare($deleteSql);
        $deleteStmt->bind_param('ii', $videoId, $userId);
        
        if ($deleteStmt->execute()) {
            // Update user interaction
            $updateInteraction = "UPDATE user_video_interactions SET HasSaved = 0 WHERE UserID = ? AND VideoID = ?";
            $interactionStmt = $con->prepare($updateInteraction);
            $interactionStmt->bind_param('ii', $userId, $videoId);
            $playlistStmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'saved' => false,
                'message' => 'Video removed from saves'
            ]);
        } else {
            throw new Exception('Failed to remove saved video');
        }
    } else {
        // Save: add the save
        $insertSql = "INSERT INTO short_video_saves (VideoID, UserID, PlaylistID, SavedAt) VALUES (?, ?, ?, NOW())";
        $insertStmt = $con->prepare($insertSql);
        $insertStmt->bind_param('iii', $videoId, $userId, $playlistId);
        
        if ($insertStmt->execute()) {
            // Update user interaction
            $updateInteraction = "INSERT INTO user_video_interactions (UserID, VideoID, HasSaved) 
                                VALUES (?, ?, 1) 
                                ON DUPLICATE KEY UPDATE HasSaved = 1";
            $interactionStmt = $con->prepare($updateInteraction);
            $interactionStmt->bind_param('ii', $userId, $videoId);
            $interactionStmt->execute();
            
            // Update playlist count
            if ($playlistId) {
                $updatePlaylist = "UPDATE user_playlists SET VideoCount = VideoCount + 1 WHERE PlaylistID = ?";
                $playlistStmt = $con->prepare($updatePlaylist);
                $playlistStmt->bind_param('i', $playlistId);
                $playlistStmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'saved' => true,
                'message' => 'Video saved successfully'
            ]);
        } else {
            throw new Exception('Failed to save video');
        }
    }
    */
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
