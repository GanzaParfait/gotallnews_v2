<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

header('Content-Type: application/json');

try {
    // Get video ID from request
    $videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$videoId) {
        throw new Exception('Video ID is required');
    }
    
    // Initialize video manager
    $videoManager = new VideoManager($con);
    
    // Get video data
    $video = $videoManager->getVideo($videoId);
    
    if (!$video) {
        throw new Exception('Video not found');
    }
    
    // Return video data as JSON
    echo json_encode($video);
    
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
