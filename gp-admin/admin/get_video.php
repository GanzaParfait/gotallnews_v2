<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Video ID is required');
    }
    
    $videoId = (int)$_GET['id'];
    
    // Initialize the video manager
    $videoManager = new VideoManager($con);
    
    // Get video data
    $video = $videoManager->getVideo($videoId);
    
    if (!$video) {
        throw new Exception('Video not found');
    }
    
    echo json_encode([
        'success' => true,
        'video' => $video
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
