<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';

try {
    if (!isset($_GET['videoId'])) {
        throw new Exception('Video ID is required');
    }
    
    $videoId = (int)$_GET['videoId'];
    
    // First, let's check what columns the users table actually has
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
    
    // Get comments for the video
    $sql = "SELECT 
                c.CommentID,
                c.CommentText,
                c.Likes,
                c.Created_at,
                c.ParentCommentID,
                $selectFieldsStr
            FROM short_video_comments c
            LEFT JOIN users u ON c.UserID = u.UserID
            WHERE c.VideoID = ? AND c.Status = 'approved'
            ORDER BY c.Created_at DESC";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'commentID' => $row['CommentID'],
            'text' => $row['CommentText'],
            'likes' => $row['Likes'],
            'createdAt' => $row['Created_at'],
            'parentCommentID' => $row['ParentCommentID'],
            'username' => $row['Username'] ?? 'User' . $row['CommentID'],
            'displayName' => $row['DisplayName'] ?? 'User' . $row['CommentID'],
            'profilePicture' => $row['ProfilePicture'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total' => count($comments),
        'debug' => [
            'userColumns' => $userColumns,
            'sql' => $sql
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
