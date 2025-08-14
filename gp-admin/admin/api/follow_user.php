<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['profileId'])) {
        throw new Exception('Profile ID is required');
    }
    
    $profileId = (int)$input['profileId'];
    
    // Get user info (if logged in)
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        throw new Exception('User must be logged in to follow users');
    }
    
    // Check if user is trying to follow themselves
    if ($userId == $profileId) {
        throw new Exception('You cannot follow yourself');
    }
    
    // Check if user already follows this profile
    $checkSql = "SELECT FollowID FROM user_follows WHERE FollowerID = ? AND FollowingID = ?";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param('ii', $userId, $profileId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unfollow: remove the follow
        $deleteSql = "DELETE FROM user_follows WHERE FollowerID = ? AND FollowingID = ?";
        $deleteStmt = $con->prepare($deleteSql);
        $deleteStmt->bind_param('ii', $userId, $profileId);
        
        if ($deleteStmt->execute()) {
            // Update follower counts
            $updateFollower = "UPDATE creator_profiles SET Followers = GREATEST(Followers - 1, 0) WHERE ProfileID = ?";
            $followerStmt = $con->prepare($updateFollower);
            $followerStmt->bind_param('i', $profileId);
            $followerStmt->execute();
            
            $updateFollowing = "UPDATE creator_profiles SET Following = GREATEST(Following - 1, 0) WHERE ProfileID = ?";
            $followingStmt = $con->prepare($updateFollowing);
            $followingStmt->bind_param('i', $userId);
            $followingStmt->execute();
            
            echo json_encode([
                'success' => true,
                'following' => false,
                'message' => 'User unfollowed'
            ]);
        } else {
            throw new Exception('Failed to unfollow user');
        }
    } else {
        // Follow: add the follow
        $insertSql = "INSERT INTO user_follows (FollowerID, FollowingID, FollowedAt) VALUES (?, ?, NOW())";
        $insertStmt = $con->prepare($insertSql);
        $insertStmt->bind_param('ii', $userId, $profileId);
        
        if ($insertStmt->execute()) {
            // Update follower counts
            $updateFollower = "UPDATE creator_profiles SET Followers = Followers + 1 WHERE ProfileID = ?";
            $followerStmt = $con->prepare($updateFollower);
            $followerStmt->bind_param('i', $profileId);
            $followerStmt->execute();
            
            $updateFollowing = "UPDATE creator_profiles SET Following = Following + 1 WHERE ProfileID = ?";
            $followingStmt = $con->prepare($updateFollowing);
            $followingStmt->bind_param('i', $userId);
            $followingStmt->execute();
            
            echo json_encode([
                'success' => true,
                'following' => true,
                'message' => 'User followed successfully'
            ]);
        } else {
            throw new Exception('Failed to follow user');
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
