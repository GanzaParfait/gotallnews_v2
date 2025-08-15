<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['videoId']) || !isset($input['commentText'])) {
        throw new Exception('Video ID and comment text are required');
    }
    
    $videoId = (int)$input['videoId'];
    $commentText = trim($input['commentText']);
    $parentCommentID = isset($input['parentCommentID']) ? (int)$input['parentCommentID'] : null;
    
    if (empty($commentText)) {
        throw new Exception('Comment text cannot be empty');
    }
    
    if (strlen($commentText) > 500) {
        throw new Exception('Comment text is too long (max 500 characters)');
    }
    
    // Get user info (if logged in) - Use log_uni_id for better tracking
    $userId = null;
    if (isset($_SESSION['log_uni_id'])) {
        $userId = $_SESSION['log_uni_id'];
    } else {
        // For demo purposes, create a temporary user ID
        // In production, you should require user authentication
        $userId = 1; // Default demo user
    }
    
    // Insert the comment
    $sql = "INSERT INTO short_video_comments (VideoID, UserID, ParentCommentID, CommentText, Status, Created_at) 
            VALUES (?, ?, ?, ?, 'approved', NOW())";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param('iiis', $videoId, $userId, $parentCommentID, $commentText);
    
    if ($stmt->execute()) {
        $commentId = $con->insert_id;
        
        // Get the newly created comment with dynamic user field selection
        $checkColumns = mysqli_query($con, "DESCRIBE users");
        $userColumns = [];
        while ($row = mysqli_fetch_assoc($checkColumns)) {
            $userColumns[] = $row['Field'];
        }
        
        // Build the query based on available columns
        $selectFields = [];
        if (in_array('Username', $userColumns)) {
            $selectFields[] = 'u.Username';
        }
        if (in_array('DisplayName', $userColumns)) {
            $selectFields[] = 'u.DisplayName';
        }
        if (in_array('ProfilePicture', $userColumns)) {
            $selectFields[] = 'u.ProfilePicture';
        }
        
        // If no user columns found, use basic ones
        if (empty($selectFields)) {
            $selectFields = ['u.UserID as Username', 'u.UserID as DisplayName', 'NULL as ProfilePicture'];
        }
        
        $selectFieldsStr = implode(', ', $selectFields);
        
        $getCommentSql = "SELECT 
                            c.CommentID,
                            c.CommentText,
                            c.Likes,
                            c.Created_at,
                            c.ParentCommentID,
                            $selectFieldsStr
                        FROM short_video_comments c
                        LEFT JOIN users u ON c.UserID = u.UserID
                        WHERE c.CommentID = ?";
        
        $getCommentStmt = $con->prepare($getCommentSql);
        $getCommentStmt->bind_param('i', $commentId);
        $getCommentStmt->execute();
        $commentResult = $getCommentStmt->get_result();
        $newComment = $commentResult->fetch_assoc();
        
        // Format the comment data
        $formattedComment = [
            'commentID' => $newComment['CommentID'],
            'text' => $newComment['CommentText'],
            'likes' => $newComment['Likes'],
            'createdAt' => $newComment['Created_at'],
            'parentCommentID' => $newComment['ParentCommentID'],
            'username' => $newComment['Username'] ?? 'User' . $newComment['CommentID'],
            'displayName' => $newComment['DisplayName'] ?? 'User' . $newComment['CommentID'],
            'profilePicture' => $newComment['ProfilePicture'] ?? null
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => $formattedComment
        ]);
    } else {
        throw new Exception('Failed to add comment');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
