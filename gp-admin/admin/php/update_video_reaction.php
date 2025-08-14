<?php
// Include database connection - match the existing structure
include "config.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check if database connection is established
if (!isset($con) || !$con) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['videoId']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$videoId = (int) $input['videoId'];
$action = $input['action'];

// Validate action
$validActions = ['like', 'unlike', 'dislike', 'undislike'];
if (!in_array($action, $validActions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

try {
    // Get current video data
    $stmt = $con->prepare("SELECT Likes, Dislikes FROM video_posts WHERE VideoID = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare SELECT statement: ' . $con->error);
    }
    
    $stmt->bind_param('i', $videoId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute SELECT statement: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Video not found']);
        exit;
    }
    
    $video = $result->fetch_assoc();
    $currentLikes = (int) $video['Likes'];
    $currentDislikes = (int) $video['Dislikes'];
    
    // Update counts based on action
    switch ($action) {
        case 'like':
            $currentLikes++;
            break;
        case 'unlike':
            $currentLikes = max(0, $currentLikes - 1);
            break;
        case 'dislike':
            $currentDislikes++;
            break;
        case 'undislike':
            $currentDislikes = max(0, $currentDislikes - 1);
            break;
    }
    
    // Update database
    $updateStmt = $con->prepare("UPDATE video_posts SET Likes = ?, Dislikes = ?, Updated_at = NOW() WHERE VideoID = ?");
    if (!$updateStmt) {
        throw new Exception('Failed to prepare UPDATE statement: ' . $con->error);
    }
    
    $updateStmt->bind_param('iii', $currentLikes, $currentDislikes, $videoId);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'likes' => $currentLikes,
            'dislikes' => $currentDislikes,
            'message' => 'Reaction updated successfully'
        ]);
    } else {
        throw new Exception('Failed to execute UPDATE statement: ' . $updateStmt->error);
    }
    
} catch (Exception $e) {
    error_log('Video reaction update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
