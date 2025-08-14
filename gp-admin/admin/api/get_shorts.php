<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';
require_once '../php/includes/VideoManager.php';

try {
    // Initialize VideoManager
    $videoManager = new VideoManager($con, 'uploads/videos/', 'images/video_thumbnails/');
    
    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
    
    // Build filters
    $filters = [
        'videoType' => 'short',
        'status' => 'published'
    ];
    
    if ($search) {
        $filters['search'] = $search;
    }
    
    if ($category) {
        $filters['categoryID'] = $category;
    }
    
    // Get videos
    $result = $videoManager->getAllVideos($page, $limit, $filters);
    
    // Format response
    $videos = [];
    foreach ($result['videos'] as $video) {
        $videos[] = [
            'VideoID' => $video['VideoID'],
            'Title' => $video['Title'],
            'Slug' => $video['Slug'],
            'Description' => $video['Description'],
            'VideoFile' => $video['VideoFile'],
            'VideoThumbnail' => $video['VideoThumbnail'],
            'VideoDuration' => $video['VideoDuration'],
            'Views' => $video['Views'],
            'Likes' => $video['Likes'],
            'Comments' => $video['Comments'],
            'Created_at' => $video['Created_at'],
            'AuthorName' => $video['AuthorName'],
            'AuthorDisplayName' => $video['AuthorDisplayName'],
            'ProfileID' => $video['ProfileID'],
            'CategoryName' => $video['CategoryName'],
            'videoType' => $video['videoType']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'pagination' => [
            'current_page' => $result['current_page'],
            'total_pages' => $result['pages'],
            'total_videos' => $result['total'],
            'per_page' => $limit
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