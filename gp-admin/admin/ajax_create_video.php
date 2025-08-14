<?php
// AJAX Video Creation Handler
header('Content-Type: application/json');

// Include necessary files
include 'php/config.php';
include 'php/header/top.php'; // This includes the session and user data
include 'php/includes/VideoManager.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check for the correct session variable used in this system
$isAuthenticated = false;
if (isset($_SESSION['log_uni_id'])) {
    $isAuthenticated = true;
}

if (!$isAuthenticated) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}
    
    try {
        // Initialize VideoManager
        $videoManager = new VideoManager($con, 'videos/', 'images/video_thumbnails/');
        
        // Get form data
        $action = $_POST['action'] ?? '';
        $profileId = $_POST['profileId'] ?? '';
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $description = $_POST['description'] ?? '';
        $categoryID = $_POST['categoryID'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $publishDate = $_POST['publishDate'] ?? null;
        $tags = $_POST['tags'] ?? '';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $allowComments = isset($_POST['allowComments']) ? 1 : 0;
        $metaTitle = $_POST['metaTitle'] ?? '';
        $metaDescription = $_POST['metaDescription'] ?? '';
        $metaKeywords = $_POST['metaKeywords'] ?? '';
        
        // Validate required fields
        if (empty($title) || empty($slug)) {
            echo json_encode(['success' => false, 'error' => 'Title and Slug are required']);
            exit;
        }
    
    // Check if either video file or embed code is provided
    $videoFile = $_FILES['videoFile'] ?? null;
    $embedCode = $_POST['embedCode'] ?? '';
    
    if (empty($videoFile['name']) && empty($embedCode)) {
        echo json_encode(['success' => false, 'error' => 'Either video file or embed code is required']);
        exit;
    }
    
    // Prepare video data
    $videoData = [
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'description' => $description,
        'categoryID' => $categoryID ?: null,
        'status' => $status,
        'publishDate' => $publishDate,
        'tags' => $tags,
        'featured' => $featured,
        'allowComments' => $allowComments,
        'metaTitle' => $metaTitle,
        'metaDescription' => $metaDescription,
        'metaKeywords' => $metaKeywords,
        'embedCode' => $embedCode
    ];
    
    // Handle video file upload
    if (!empty($videoFile['name'])) {
        $videoData['videoFile'] = $videoFile;
        $videoData['embedCode'] = ''; // Clear embed code if video file is uploaded
    } else {
        $videoData['embedCode'] = $embedCode;
        $videoData['videoFile'] = null;
    }
    

    
    // Handle thumbnail upload
    $thumbnailFile = $_FILES['videoThumbnail'] ?? null;
    
    // Create video
    $videoId = $videoManager->createVideo($profileId, $videoData);
    
    if ($videoId) {
        echo json_encode([
            'success' => true,
            'message' => 'Video created successfully!',
            'videoId' => $videoId,
            'redirect' => 'video_posts.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create video']);
    }
    
} catch (Exception $e) {
    error_log('AJAX Video Creation Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
