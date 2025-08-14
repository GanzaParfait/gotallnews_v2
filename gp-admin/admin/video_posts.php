<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

// Helper function to truncate text
function truncateText($text, $length = 100)
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Helper function to get thumbnail path
function getThumbnailPath($thumbnail)
{
    if (empty($thumbnail)) {
        return 'images/default-video-thumbnail.jpg';
    }

    // Check multiple possible paths
    $paths = [
        'images/video_thumbnails/' . $thumbnail,
        'php/saved_images/' . $thumbnail,
        $thumbnail
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return 'images/default-video-thumbnail.jpg';
}

// Initialize the video manager
$videoManager = null;
try {
    // Check if database connection is available
    if (!isset($con) || !$con) {
        throw new Exception('Database connection not available');
    }

    // Initialize VideoManager with correct paths
    $videoManager = new VideoManager($con, 'videos/', 'images/video_thumbnails/');
    $systemReady = true;
} catch (Exception $e) {
    $error_message = 'Failed to initialize Video Manager: ' . $e->getMessage();
    error_log('VideoManager initialization error: ' . $e->getMessage());
    $systemReady = false;
}

// Only proceed with operations if the system is ready
if ($systemReady) {
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'create':
                    if (isset($_POST['create_video'])) {
                        try {
                            error_log('Video Creation Started - POST data: ' . print_r($_POST, true));
                            error_log('Video Creation - User unique ID: ' . ($user_uniqueid ?? 'NOT SET'));

                            // Prepare video data
                            $videoData = [
                                'title' => $_POST['title'],
                                'slug' => $_POST['slug'],
                                'excerpt' => $_POST['excerpt'] ?? '',
                                'description' => $_POST['description'] ?? '',
                                'categoryID' => $_POST['categoryID'] ?? null,
                                'tags' => $_POST['tags'] ?? '',
                                'status' => $_POST['status'],
                                'publishDate' => $_POST['publishDate'] ?? null,
                                'featured' => isset($_POST['featured']) ? 1 : 0,
                                'allowComments' => isset($_POST['allowComments']) ? 1 : 0,
                                'metaTitle' => $_POST['metaTitle'] ?? $_POST['title'],
                                'metaDescription' => $_POST['metaDescription'] ?? $_POST['excerpt'] ?? '',
                                'metaKeywords' => $_POST['metaKeywords'] ?? $_POST['tags'] ?? '',
                                'embedCode' => $_POST['embedCode'] ?? '',
                                'embedSource' => $_POST['embedSource'] ?? '',
                                'embedVideoID' => $_POST['embedVideoID'] ?? ''
                            ];

                            // Handle video file upload
                            if (!empty($_FILES['videoFile']['name'])) {
                                $videoData['videoFile'] = $_FILES['videoFile'];
                                $videoData['videoFormat'] = 'upload';
                                error_log('Video Creation - Video file uploaded: ' . $_FILES['videoFile']['name']);
                            } elseif (!empty($_POST['embedCode'])) {
                                $videoData['videoFormat'] = 'embed';
                                error_log('Video Creation - Embed code provided: ' . substr($_POST['embedCode'], 0, 100));
                            }

                            // Handle thumbnail upload
                            if (!empty($_FILES['videoThumbnail']['name'])) {
                                $videoData['videoThumbnail'] = $_FILES['videoThumbnail'];
                                error_log('Video Creation - Thumbnail uploaded: ' . $_FILES['videoThumbnail']['name']);
                                error_log('Video Creation - Thumbnail file details: ' . print_r($_FILES['videoThumbnail'], true));
                            } else {
                                error_log('Video Creation - No thumbnail uploaded');
                            }

                            // Get author ID from form
                            $profileId = $_POST['profileId'] ?? $user_profileid;
                            
                            error_log('Video Creation - Profile ID: ' . ($profileId ?? 'NULL'));
                            
                            if (empty($profileId)) {
                                throw new Exception('Profile ID is required');
                            }
                            
                            // Create video
                            $videoId = $videoManager->createVideo($profileId, $videoData);

                            if ($videoId) {
                                $success_message = "Video created successfully! Video ID: $videoId";
                                error_log("Video Creation Success - Video ID: $videoId");
                            } else {
                                throw new Exception('Failed to create video');
                            }
                        } catch (Exception $e) {
                            $error_message = 'Error creating video: ' . $e->getMessage();
                            error_log('Video Creation Error: ' . $e->getMessage());
                        }
                    }
                    break;

                case 'update':
                    if (isset($_POST['update_video'])) {
                        $videoId = $_POST['video_id'];
                        $videoData = [
                            'title' => $_POST['title'],
                            'slug' => $_POST['slug'],
                            'excerpt' => $_POST['excerpt'] ?? '',
                            'description' => $_POST['description'] ?? '',
                            'categoryID' => $_POST['categoryID'] ?? null,
                            'tags' => $_POST['tags'] ?? '',
                            'status' => $_POST['status'],
                            'publishDate' => $_POST['publishDate'] ?? null,
                            'featured' => isset($_POST['featured']) ? 1 : 0,
                            'allowComments' => isset($_POST['allowComments']) ? 1 : 0,
                            'embedCode' => $_POST['embedCode'] ?? '',
                            'embedSource' => $_POST['embedSource'] ?? '',
                            'embedVideoID' => $_POST['embedVideoID'] ?? '',
                            'metaTitle' => $_POST['metaTitle'] ?? $_POST['title'],
                            'metaDescription' => $_POST['metaDescription'] ?? $_POST['excerpt'] ?? '',
                            'metaKeywords' => $_POST['metaKeywords'] ?? $_POST['tags'] ?? ''
                        ];

                        $videoManager->updateVideo($videoId, $videoData);
                        $success_message = 'Video post updated successfully!';
                    }
                    break;

                case 'delete':
                    if (isset($_POST['delete_video'])) {
                        $videoId = $_POST['video_id'];
                        $videoManager->deleteVideo($videoId);
                        $success_message = 'Video post deleted successfully!';
                    }
                    break;

                case 'restore':
                    if (isset($_POST['restore_video'])) {
                        $videoId = $_POST['video_id'];
                        $videoManager->restoreVideo($videoId);
                        $success_message = 'Video post restored successfully!';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }

    // Get current page and filters
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $filters = [
        'status' => $_GET['status'] ?? '',
        'category' => $_GET['category'] ?? '',
        'author' => $_GET['author'] ?? '',
        'search' => $_GET['search'] ?? '',
        'dateFrom' => $_GET['dateFrom'] ?? '',
        'dateTo' => $_GET['dateTo'] ?? ''
    ];

    // Get videos with filters
    $videos = [];
    $totalPages = 0;
    $currentPage = $page;
    $categories = [];
    $tags = [];
    $publishedCount = 0;

    try {
        // Get videos with pagination (filter by videoType = 'video' for regular videos only)
        $filters['videoType'] = 'video';
        $videosData = $videoManager->getAllVideos($page, 20, $filters);
        $videos = $videosData['videos'];
        $totalPages = $videosData['pages'];
        $currentPage = $videosData['current_page'];

        // Get categories and tags for forms
        $categories = $videoManager->getCategories();
        $tags = $videoManager->getTags();

        // Publish scheduled videos (run this on every page load)
        $publishedCount = $videoManager->publishScheduledVideos();
        if ($publishedCount > 0) {
            $info_message = "$publishedCount scheduled video(s) have been published.";
        }
    } catch (Exception $e) {
        $error_message = 'Error loading video data: ' . $e->getMessage();
    }
} else {
    // System not ready, set default values
    $videos = [];
    $totalPages = 0;
    $currentPage = 1;
    $categories = [];
    $tags = [];
    $publishedCount = 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Regular Videos Management - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <style>
        .video-thumbnail {
            width: 120px;
            height: 67px;
            object-fit: cover;
            border-radius: 4px;
        }
        .video-duration {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        .video-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background: #f8f9fa; color: #6c757d; }
        .status-published { background: #d4edda; color: #155724; }
        .status-scheduled { background: #fff3cd; color: #856404; }
        .status-archived { background: #f8d7da; color: #721c24; }
        .filter-section {
            background: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
        }
        
        .pagination-info {
            font-size: 0.9rem;
        }
        .page-link {
            color: #4e73df;
            background-color: #fff;
            border: 1px solid #d1d3e2;
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 0.35rem;
            text-decoration: none;
        }
        .page-link:hover {
            background-color: #eaecf4;
            border-color: #d1d3e2;
        }
        .page-link.active {
            background-color: #4e73df;
            border-color: #4e73df;
            color: white;
        }
        
        /* Tooltip styles */
        [title] {
            position: relative;
            cursor: help;
        }
        
        /* Truncated text styles */
        .text-truncated {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Alert styles for form validation */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            margin-top: 8px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Table improvements */
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .table td {
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-group .btn {
            margin-right: 2px;
        }
        
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        /* Form validation styles */
        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #dc3545;
        }
        
        .valid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #28a745;
        }
        
        /* Required field indicator */
        .text-danger {
            color: #dc3545 !important;
        }
        
        /* Form group improvements */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        /* Alert improvements */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            margin-top: 8px;
            font-size: 14px;
        }
        
        .alert i {
            margin-right: 8px;
        }
        
        /* Progress bar improvements */
        .progress-container {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #4e73df 0%, #6f42c1 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Upload modal animations */
        .upload-progress-modal {
            backdrop-filter: blur(5px);
        }
        
        .upload-progress-content {
            transform: scale(0.9);
            transition: transform 0.3s ease-in-out;
        }
        
        .upload-progress-modal.show .upload-progress-content {
            transform: scale(1);
        }
        
        /* Button states */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        /* Upload Progress Modal Styles */
        .upload-progress-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        
        .upload-progress-modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .upload-progress-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 400px;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .upload-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .upload-progress-header h5 {
            margin: 0;
            color: #333;
        }
        
        .upload-progress-body {
            margin-bottom: 20px;
        }
        
        .progress-container {
            width: 100%;
            height: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4e73df 0%, #6f42c1 100%);
            border-radius: 5px;
            transition: width 0.3s ease-in-out;
        }
        
        .progress-text {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
        
        .upload-status {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'php/includes/header.php'; ?>

    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <img src="images/logo.png" width="200" alt="logo">
            </a>
            <div class="close-sidebar" data-toggle="left-sidebar-close">
                <i class="ion-close-round"></i>
            </div>
        </div>
        <div class="menu-block customscroll">
            <div class="sidebar-menu">
                <ul id="accordion-menu">
                    <li>
                        <a href="index.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-house"></span><span class="mtext">Home</span>
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-newspaper-o" aria-hidden="true"></i></span>
                            <span class="mtext">Article</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="new_article.php">New</a></li>
                            <li><a href="view_article.php">Manage</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-users" aria-hidden="true"></i></span>
                            <span class="mtext">Creators</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="creator_profiles.php">Profiles</a></li>
                            <li><a href="creator_analytics.php">Analytics</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle active">
                            <span class="micon"><i class="icon-copy fa fa-video-camera" aria-hidden="true"></i></span>
                            <span class="mtext">Videos</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="video_posts.php" class="active">Posts</a></li>
                            <li><a href="video_shorts.php">Shorts</a></li>
                            <li><a href="video_analytics.php">Analytics</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-object-ungroup" aria-hidden="true"></i></span>
                            <span class="mtext">Category</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="new_category.php">New</a></li>
                            <li><a href="view_category.php">Manage</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="view_received_message.php" class="dropdown-toggle no-arrow">
                            <span class="micon icon-copy fa fa-inbox"></span><span class="mtext">Messages</span>
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-cogs" aria-hidden="true"></i></span>
                            <span class="mtext">Settings</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="php/extras/logout.php">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="pd-20 card-box mb-30">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Regular Videos Management</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Regular Videos</li>
                                </ol>
                            </nav>
                            <small class="text-muted">Manage regular video content (horizontal format). For short videos, go to Video Shorts.</small>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#createVideoModal">
                                <i class="icon-copy fa fa-plus"></i> Create Video/Short
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($info_message)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= $info_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- System Status Message -->
                <?php if (!$systemReady): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5><i class="icon-copy fa fa-exclamation-triangle"></i> System Not Ready</h5>
                        <p class="mb-2"><?= htmlspecialchars($error_message) ?></p>
                        <p class="mb-0">
                            <strong>To fix this:</strong> Please check the database connection and VideoManager configuration.
                        </p>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Filters Section -->
                <?php if ($systemReady): ?>
                <div class="filter-section">
                    <form method="GET" class="row">
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="scheduled" <?= $filters['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="archived" <?= $filters['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['CategoryID'] ?>" <?= $filters['category'] == $category['CategoryID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['CategoryName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control" placeholder="Search videos..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="dateFrom" class="form-control" value="<?= htmlspecialchars($filters['dateFrom']) ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="dateTo" class="form-control" value="<?= htmlspecialchars($filters['dateTo']) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                            <a href="video_posts.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Videos List -->
                <?php if ($systemReady): ?>
                <div class="row">
                    <?php if (empty($videos)): ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-info">
                                <i class="icon-copy fa fa-video-camera fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No videos found</h5>
                                <p class="text-muted">Create your first video post to get started.</p>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#createVideoModal">
                                    <i class="icon-copy fa fa-plus"></i> Create Video/Short
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <!-- Info about truncated text -->
                            <div class="alert alert-info mb-3">
                                <i class="icon-copy fa fa-info-circle"></i>
                                <strong>Note:</strong> Text in the table is truncated for better display. Hover over truncated text to see the full content, or click the <i class="icon-copy fa fa-eye"></i> button to view the complete video details.
                            </div>
                            
                            <!-- Pagination Info -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="pagination-info">
                                    <small class="text-muted">
                                        Showing <?= (($currentPage - 1) * 20) + 1 ?> to <?= min($currentPage * 20, $videosData['total'] ?? 0) ?> 
                                        of <?= $videosData['total'] ?? 0 ?> videos
                                    </small>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Video</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Author</th>
                                            <th>Status</th>
                                            <th>Views</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($videos as $video): ?>
                                            <tr data-video-id="<?= $video['VideoID'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php
                                                        $thumbnailSrc = getThumbnailPath($video['VideoThumbnail']);
                                                        ?>
                                                        <img src="<?= htmlspecialchars($thumbnailSrc) ?>" 
                                                             alt="Video thumbnail" 
                                                             class="mr-3" 
                                                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;"
                                                             onerror="this.src='images/default-video-thumbnail.jpg';">
                                                        <div>
                                                            <h6 class="mb-1" title="<?= htmlspecialchars($video['Title']) ?>">
                                                                <?= htmlspecialchars(truncateText($video['Title'], 40)) ?>
                                                            </h6>
                                                            <small class="text-muted" title="<?= htmlspecialchars($video['Slug']) ?>">
                                                                <?= htmlspecialchars(truncateText($video['Slug'], 30)) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($video['Title']) ?></strong>
                                                    <?php if ($video['Featured']): ?>
                                                        <span class="badge badge-warning ml-2">Featured</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted" title="<?= htmlspecialchars($video['Excerpt'] ?: 'No excerpt') ?>">
                                                        <?= htmlspecialchars(truncateText($video['Excerpt'] ?: 'No excerpt', 80)) ?>
                                                    </small>
                                                </td>
                                                <td><?= htmlspecialchars($video['CategoryName'] ?? 'Uncategorized') ?></td>
                                                <td><?= htmlspecialchars($video['AuthorName'] ?? $video['DisplayName'] ?? 'Unknown Author') ?></td>
                                                <td>
                                                    <span class="video-status status-<?= $video['Status'] ?>">
                                                        <?= ucfirst($video['Status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($video['Views']) ?></td>
                                                <td><?= date('M j, Y', strtotime($video['Created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewVideo(<?= $video['VideoID'] ?>)">
                                                            <i class="icon-copy fa fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary" onclick="editVideo(<?= $video['VideoID'] ?>)">
                                                            <i class="icon-copy fa fa-edit"></i>
                                                        </button>
                                                        <?php if ($video['isDeleted'] === 'deleted'): ?>
                                                            <button class="btn btn-sm btn-outline-success" onclick="restoreVideo(<?= $video['VideoID'] ?>)">
                                                                <i class="icon-copy fa fa-undo"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteVideo(<?= $video['VideoID'] ?>)">
                                                                <i class="icon-copy fa fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination-wrapper">
                                    <nav aria-label="Video pagination">
                                        <ul class="pagination">
                                            <?php if ($currentPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $currentPage - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($currentPage < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $currentPage + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Video Modal -->
    <?php if ($systemReady): ?>
    <div class="modal fade" id="createVideoModal" tabindex="-1" role="dialog" aria-labelledby="createVideoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createVideoModalLabel">Create New Video Post</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="profileId" value="<?= $user_profileid ?>">
                    <div class="modal-body">
                        <!-- AJAX Upload Info -->
                        <div class="alert alert-info">
                            <i class="icon-copy fa fa-info-circle"></i>
                            <strong>Professional Upload:</strong> This form uses AJAX upload with progress tracking. No page reload required, and you'll see real-time upload progress for large video files.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Title *</label>
                                    <input type="text" class="form-control" name="title" placeholder="Enter video title..." required>
                                </div>
                                <div class="form-group">
                                    <label>Slug *</label>
                                    <input type="text" class="form-control" name="slug" id="videoSlug" placeholder="video-title-slug" required>
                                    <small class="text-muted">Auto-generated from title, or customize manually</small>
                                </div>
                                <div class="form-group">
                                    <label>Excerpt</label>
                                    <textarea class="form-control" name="excerpt" rows="3" placeholder="Brief summary of the video content..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="5" placeholder="Detailed description of the video content..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="categoryID" class="form-control">
                                        <option value="">Uncategorized</option>
                                        <?php if (isset($categories) && is_array($categories)):
                                            foreach ($categories as $category): ?>
                                            <option value="<?= $category['CategoryID'] ?>"><?= htmlspecialchars($category['CategoryName']) ?></option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Video Type</label>
                                    <select name="videoType" class="form-control" required onchange="toggleVideoTypeFields(this.value)">
                                        <option value="">Select Type</option>
                                        <option value="video" selected>Regular Video</option>
                                        <option value="short">Short Video (TikTok/Reels)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required onchange="togglePublishDate(this.value)">
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div class="form-group" id="publishDateGroup" style="display: none;">
                                    <label>Publish Date *</label>
                                    <input type="datetime-local" name="publishDate" class="form-control" required>
                                    <small class="text-muted">Select when this video should be published</small>
                                </div>
                                <div class="form-group">
                                    <label>Tags</label>
                                    <input type="text" class="form-control" name="tags" placeholder="Enter tags separated by commas (e.g., technology, tutorial, news)">
                                    <small class="text-muted">Use relevant tags to help users find your video</small>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="featured" id="featured">
                                        <label class="custom-control-label" for="featured">Featured Video</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="allowComments" id="allowComments" checked>
                                        <label class="custom-control-label" for="allowComments">Allow Comments</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Video Content</h6>
                                <div class="alert alert-warning">
                                    <i class="icon-copy fa fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> You must provide either a video file OR embed code, not both. The system will automatically disable the other option when one is selected.
                                </div>
                                <div class="form-group">
                                    <label>Video File <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="videoFile" accept="video/*" onchange="handleVideoFileChange(this)">
                                    <small class="text-muted">Upload MP4, MOV, or AVI file (max 100MB)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Video Thumbnail</label>
                                    <input type="file" class="form-control" name="videoThumbnail" accept="image/*">
                                    <small class="text-muted">Upload JPG, PNG, or GIF (max 2MB). Will be automatically compressed.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Or Embed Code/URL <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="embedCode" rows="4" placeholder="Paste YouTube, Vimeo, or other video embed code here..." onchange="handleEmbedCodeChange(this)"></textarea>
                                    <small class="text-muted">Leave empty if uploading a video file. Supports YouTube, Vimeo, Facebook, Instagram, TikTok, and custom embed codes.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>SEO Settings</h6>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" class="form-control" name="metaTitle" placeholder="SEO title for search engines...">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea class="form-control" name="metaDescription" rows="3" placeholder="SEO description for search engines..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Keywords</label>
                                    <input type="text" class="form-control" name="metaKeywords" placeholder="SEO keywords separated by commas...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_video" class="btn btn-primary" id="createVideoBtn">
                            <span class="btn-text">Create Video</span>
                            <span class="btn-loader" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Creating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Video Modal -->
    <div class="modal fade" id="editVideoModal" tabindex="-1" role="dialog" aria-labelledby="editVideoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVideoModalLabel">Edit Video Post</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="video_id" id="edit_video_id">
                    <div class="modal-body" id="editVideoModalBody">
                        <!-- AJAX Upload Info -->
                        <div class="alert alert-info">
                            <i class="icon-copy fa fa-info-circle"></i>
                            <strong>Professional Update:</strong> This form uses AJAX upload with progress tracking. No page reload required, and you'll see real-time upload progress for large video files.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" value="${video.Title || ''}" placeholder="Enter video title..." required>
                                </div>
                                <div class="form-group">
                                    <label>Slug <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="slug" value="${video.Slug || ''}" placeholder="video-title-slug" required>
                                </div>
                                <div class="form-group">
                                    <label>Excerpt</label>
                                    <textarea class="form-control" name="excerpt" rows="3" placeholder="Brief summary of the video content...">${video.Excerpt || ''}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="5" placeholder="Detailed description of the video content...">${video.Description || ''}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="categoryID" class="form-control">
                                        <option value="">Uncategorized</option>
                                        <?php if (isset($categories)):
                                            foreach ($categories as $category): ?>
                                        <option value="<?= $category['CategoryID'] ?>" ${video.CategoryID == <?= $category['CategoryID'] ?> ? 'selected' : ''}><?= htmlspecialchars($category['CategoryName']) ?></option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                                                <div class="form-group">
                                    <label>Video Type</label>
                                    <select name="videoType" class="form-control" required onchange="toggleEditVideoTypeFields(this.value)">
                                        <option value="">Select Type</option>
                                        <option value="video" ${video.videoType === 'video' ? 'selected' : ''}>Regular Video</option>
                                        <option value="short" ${video.videoType === 'short' ? 'selected' : ''}>Short Video (TikTok/Reels)</option>
                                    </select>
                            </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required onchange="toggleEditPublishDate(this.value)">
                                        <option value="draft" ${video.Status === 'draft' ? 'selected' : ''}>Draft</option>
                                        <option value="published" ${video.Status === 'published' ? 'selected' : ''}>Published</option>
                                        <option value="scheduled" ${video.Status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                                        <option value="archived" ${video.Status === 'archived' ? 'selected' : ''}>Archived</option>
                                    </select>
                                </div>
                                <div class="form-group" id="editPublishDateGroup" style="display: ${video.Status === 'scheduled' ? 'block' : 'none'};">
                                    <label>Publish Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="publishDate" class="form-control" value="${video.PublishDate ? video.PublishDate.replace(' ', 'T') : ''}" required>
                                    <small class="text-muted">Select when this video should be published</small>
                                </div>
                                <div class="form-group">
                                    <label>Tags</label>
                                    <input type="text" class="form-control" name="tags" value="${video.Tags || ''}" placeholder="Enter tags separated by commas">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="featured" id="edit_featured" ${video.Featured ? 'checked' : ''}>
                                        <label class="custom-control-label" for="edit_featured">Featured Video</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="allowComments" id="edit_allowComments" ${video.AllowComments ? 'checked' : ''}>
                                        <label class="custom-control-label" for="edit_allowComments">Allow Comments</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Video Content</h6>
                                <div class="alert alert-warning">
                                    <i class="icon-copy fa fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> You must provide either a video file OR embed code, not both. The system will automatically disable the other option when one is selected.
                                </div>
                                <div class="form-group">
                                    <label>Video File</label>
                                    <input type="file" class="form-control" name="videoFile" accept="video/*" onchange="handleVideoFileChange(this)">
                                    <small class="text-muted">Current: ${video.VideoFile || 'No file'}</small>
                                </div>
                                <div class="form-group">
                                    <label>Or Embed Code/URL <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste embed code or URL here..." onchange="handleEmbedCodeChange(this)">${video.EmbedCode || ''}</textarea>
                                    <small class="text-muted">Current: ${video.EmbedCode ? 'Embed code set' : 'No embed code'}</small>
                                </div>
                                <div class="form-group">
                                    <label>Video Thumbnail</label>
                                    <input type="file" class="form-control" name="videoThumbnail" accept="image/*">
                                    <small class="text-muted">Current: ${video.VideoThumbnail || 'No thumbnail'}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>SEO Settings</h6>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" class="form-control" name="metaTitle" value="${video.MetaTitle || video.Title || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea class="form-control" name="metaDescription" rows="3">${video.MetaDescription || video.Excerpt || ''}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Keywords</label>
                                    <input type="text" class="form-control" name="metaKeywords" value="${video.MetaKeywords || ''}" placeholder="Enter keywords separated by commas">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_video" class="btn btn-primary">
                            <span class="btn-text">Update Video</span>
                            <span class="btn-loader" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Video Modal -->
    <div class="modal fade" id="deleteVideoModal" tabindex="-1" role="dialog" aria-labelledby="deleteVideoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteVideoModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the video "<span id="delete_video_title"></span>"?</p>
                    <p class="text-warning"><small>This action will soft delete the video and can be restored later.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="video_id" id="delete_video_id">
                        <button type="submit" name="delete_video" class="btn btn-danger">Delete Video</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Video Modal -->
    <div class="modal fade" id="restoreVideoModal" tabindex="-1" role="dialog" aria-labelledby="restoreVideoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreVideoModalLabel">Confirm Restore</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore this video?</p>
                    <p class="text-info"><small>This will reactivate the video and make it visible again.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="video_id" id="restore_video_id">
                        <button type="submit" name="restore_video" class="btn btn-success">Restore Video</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
    <script src="src/plugins/sweetalert2/sweetalert2.all.js"></script>
    
    <script>
        // Auto-generate slug from title
        document.querySelector('input[name="title"]').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            document.getElementById('videoSlug').value = slug;
        });

        // Show/hide publish date based on status
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            const publishDateGroup = document.getElementById('publishDateGroup');
            if (this.value === 'scheduled') {
                publishDateGroup.style.display = 'block';
            } else {
                publishDateGroup.style.display = 'none';
            }
        });

        // Enhanced form validation with URL validation
        function validateUrl(url) {
            // Basic URL validation for common video platforms
            const videoPlatforms = [
                /^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+/i,
                /^https?:\/\/(www\.)?youtu\.be\/[\w-]+/i,
                /^https?:\/\/(www\.)?vimeo\.com\/\d+/i,
                /^https?:\/\/(www\.)?dailymotion\.com\/video\/[\w-]+/i,
                /^https?:\/\/(www\.)?facebook\.com\/.*\/videos\/\d+/i,
                /^https?:\/\/(www\.)?instagram\.com\/p\/[\w-]+\/?/i,
                /^https?:\/\/(www\.)?tiktok\.com\/@[\w-]+\/video\/\d+/i
            ];
            
            // Check if it's a valid URL
            try {
                new URL(url);
            } catch {
                return false;
            }
            
            // Check if it matches any video platform pattern
            return videoPlatforms.some(pattern => pattern.test(url));
        }

        function validateEmbedCode(embedCode) {
            // Check if it's a valid embed code (iframe, object, or video tag)
            const embedPatterns = [
                /<iframe[^>]*src=["'][^"']+["'][^>]*>/i,
                /<object[^>]*>/i,
                /<video[^>]*>/i,
                /<embed[^>]*>/i
            ];
            
            return embedPatterns.some(pattern => pattern.test(embedCode));
        }

        // Handle video file and embed code mutual exclusivity with validation
        function handleVideoFileChange(fileInput) {
            const embedCodeTextarea = document.querySelector('textarea[name="embedCode"]');
            const videoFileInput = document.querySelector('input[name="videoFile"]');
            const submitBtn = document.querySelector('#createVideoBtn, button[name="update_video"]');
            
            if (fileInput.files.length > 0) {
                // Clear and disable embed code
                embedCodeTextarea.value = '';
                embedCodeTextarea.disabled = true;
                embedCodeTextarea.placeholder = 'Embed code disabled - video file selected';
                
                // Remove existing indicators
                removeFormatIndicators();
                
                // Add success indicator
                const formatIndicator = document.createElement('div');
                formatIndicator.className = 'alert alert-success mt-2';
                formatIndicator.innerHTML = '<i class="fa fa-check-circle"></i> Video file selected. Embed code will be ignored.';
                fileInput.parentNode.appendChild(formatIndicator);
                
                // Enable video file input and submit button
                videoFileInput.disabled = false;
                videoFileInput.placeholder = 'Upload MP4, MOV, or AVI file (max 100MB)';
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-primary');
                }
            } else {
                // Re-enable embed code if no file selected
                embedCodeTextarea.disabled = false;
                embedCodeTextarea.placeholder = 'Paste YouTube, Vimeo, or other video embed code here...';
                removeFormatIndicators();
                
                // Check if embed code is filled
                if (!embedCodeTextarea.value.trim()) {
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.classList.add('btn-secondary');
                    }
                }
            }
        }

        function handleEmbedCodeChange(textarea) {
            const videoFileInput = document.querySelector('input[name="videoFile"]');
            const embedCodeTextarea = document.querySelector('textarea[name="embedCode"]');
            const submitBtn = document.querySelector('#createVideoBtn, button[name="update_video"]');
            const inputValue = textarea.value.trim();
            
            if (inputValue) {
                // Validate the input
                let isValid = false;
                let validationMessage = '';
                
                if (inputValue.startsWith('http')) {
                    // It's a URL
                    if (validateUrl(inputValue)) {
                        isValid = true;
                        validationMessage = 'Valid video URL detected. Video file will be ignored.';
                    } else {
                        isValid = false;
                        validationMessage = 'Invalid video URL. Please enter a valid YouTube, Vimeo, or other video platform URL.';
                    }
                } else {
                    // It's embed code
                    if (validateEmbedCode(inputValue)) {
                        isValid = true;
                        validationMessage = 'Valid embed code detected. Video file will be ignored.';
                    } else {
                        isValid = false;
                        validationMessage = 'Invalid embed code. Please enter valid HTML embed code (iframe, object, or video tag).';
                    }
                }
                
                if (isValid) {
                    // Clear and disable video file input
                videoFileInput.value = '';
                videoFileInput.disabled = true;
                videoFileInput.placeholder = 'Video file disabled - embed code entered';
                
                    // Remove existing indicators
                    removeFormatIndicators();
                    
                    // Add success indicator
                const formatIndicator = document.createElement('div');
                    formatIndicator.className = 'alert alert-success mt-2';
                    formatIndicator.innerHTML = `<i class="fa fa-check-circle"></i> ${validationMessage}`;
                    textarea.parentNode.appendChild(formatIndicator);
                    
                    // Enable submit button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-secondary');
                        submitBtn.classList.add('btn-primary');
                    }
                } else {
                    // Show error indicator
                    removeFormatIndicators();
                    const errorIndicator = document.createElement('div');
                    errorIndicator.className = 'alert alert-danger mt-2';
                    errorIndicator.innerHTML = `<i class="fa fa-exclamation-triangle"></i> ${validationMessage}`;
                    textarea.parentNode.appendChild(errorIndicator);
                    
                    // Disable submit button
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.classList.add('btn-secondary');
                    }
                }
            } else {
                // Re-enable video file input if no embed code
                videoFileInput.disabled = false;
                videoFileInput.placeholder = 'Upload MP4, MOV, or AVI file (max 100MB)';
                removeFormatIndicators();
                
                // Check if video file is selected
                if (!videoFileInput.files.length) {
                    if (submitBtn) {
            submitBtn.disabled = true;
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.classList.add('btn-secondary');
                    }
                }
            }
        }

        function removeFormatIndicators() {
            const indicators = document.querySelectorAll('.alert');
            indicators.forEach(indicator => {
                if (indicator.textContent.includes('will be ignored')) {
                    indicator.remove();
                }
            });
        }

        // Enhanced form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // This is now handled by AJAX upload for create form
            // Other forms will submit normally
            console.log('Form submission - handled by AJAX for create form');
        });

        // Global form submission prevention
        document.addEventListener('submit', function(e) {
            const form = e.target;
            console.log('Global form submission detected:', form);
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('Form ID:', form.id);
            console.log('Form class:', form.className);
            
            // Check if this is one of our video forms
            if (form.closest('#createVideoModal') || form.closest('#editVideoModal')) {
                console.log('Video form submission detected - preventing default');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Determine action type
                const action = form.closest('#createVideoModal') ? 'create' : 'update';
                console.log('Handling as', action, 'form');
                
                // Handle AJAX upload
                handleAjaxVideoUpload(form, action);
                return false;
            }
        }, true); // Use capture phase to intercept early

        // Handle delete confirmation
        function deleteVideo(videoId) {
            const videoRow = document.querySelector(`tr[data-video-id="${videoId}"]`);
            if (videoRow) {
                const videoTitle = videoRow.querySelector('td:nth-child(2) strong')?.textContent || 'Unknown Video';
                document.getElementById('delete_video_title').textContent = videoTitle;
                document.getElementById('delete_video_id').value = videoId;
                $('#deleteVideoModal').modal('show');
            } else {
                alert('Video not found. Please refresh the page and try again.');
            }
        }

        // Handle restore confirmation
        function restoreVideo(videoId) {
            document.getElementById('restore_video_id').value = videoId;
            $('#restoreVideoModal').modal('show');
        }

        // Handle view video
        function viewVideo(videoId) {
            window.open('video_view.php?id=' + videoId, '_blank');
        }

        // Handle edit video modal
        function editVideo(videoId) {
            // Fetch video data via AJAX
            fetch(`get_video.php?id=${videoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(video => {
                    if (video && video.VideoID) {
                        // Populate form fields
                        document.getElementById('edit_video_id').value = video.VideoID;
                        
                        // Update modal body with video data
                        const modalBody = document.getElementById('editVideoModalBody');
                        modalBody.innerHTML = `
                            <!-- AJAX Upload Info -->
                            <div class="alert alert-info">
                                <i class="icon-copy fa fa-info-circle"></i>
                                <strong>Professional Update:</strong> This form uses AJAX upload with progress tracking. No page reload required, and you'll see real-time upload progress for large video files.
                            </div>
                            

                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" value="${video.Title || ''}" placeholder="Enter video title..." required>
                                    </div>
                                    <div class="form-group">
                                        <label>Slug <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="slug" value="${video.Slug || ''}" placeholder="video-title-slug" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Excerpt</label>
                                        <textarea class="form-control" name="excerpt" rows="3" placeholder="Brief summary of the video content...">${video.Excerpt || ''}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="description" rows="5" placeholder="Detailed description of the video content...">${video.Description || ''}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="categoryID" class="form-control">
                                            <option value="">Uncategorized</option>
                                            <?php if (isset($categories)):
                                                foreach ($categories as $category): ?>
                                                <option value="<?= $category['CategoryID'] ?>" ${video.CategoryID == <?= $category['CategoryID'] ?> ? 'selected' : ''}><?= htmlspecialchars($category['CategoryName']) ?></option>
                                            <?php endforeach;
                                            endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control" required onchange="toggleEditPublishDate(this.value)">
                                            <option value="draft" ${video.Status === 'draft' ? 'selected' : ''}>Draft</option>
                                            <option value="published" ${video.Status === 'published' ? 'selected' : ''}>Published</option>
                                            <option value="scheduled" ${video.Status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                                            <option value="archived" ${video.Status === 'archived' ? 'selected' : ''}>Archived</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="editPublishDateGroup" style="display: ${video.Status === 'scheduled' ? 'block' : 'none'};">
                                        <label>Publish Date <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="publishDate" class="form-control" value="${video.PublishDate ? video.PublishDate.replace(' ', 'T') : ''}" required>
                                        <small class="text-muted">Select when this video should be published</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Tags</label>
                                        <input type="text" class="form-control" name="tags" value="${video.Tags || ''}" placeholder="Enter tags separated by commas">
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="featured" id="edit_featured" ${video.Featured ? 'checked' : ''}>
                                            <label class="custom-control-label" for="edit_featured">Featured Video</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="allowComments" id="edit_allowComments" ${video.AllowComments ? 'checked' : ''}>
                                            <label class="custom-control-label" for="edit_allowComments">Allow Comments</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Video Content</h6>
                                    <div class="alert alert-warning">
                                        <i class="icon-copy fa fa-exclamation-triangle"></i>
                                        <strong>Important:</strong> You must provide either a video file OR embed code, not both. The system will automatically disable the other option when one is selected.
                                    </div>
                                    <div class="form-group">
                                        <label>Video File</label>
                                        <input type="file" class="form-control" name="videoFile" accept="video/*" onchange="handleVideoFileChange(this)">
                                        <small class="text-muted">Current: ${video.VideoFile || 'No file'}</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Or Embed Code/URL <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste embed code or URL here..." onchange="handleEmbedCodeChange(this)">${video.EmbedCode || ''}</textarea>
                                        <small class="text-muted">Current: ${video.EmbedCode ? 'Embed code set' : 'No embed code'}</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Video Thumbnail</label>
                                        <input type="file" class="form-control" name="videoThumbnail" accept="image/*">
                                        <small class="text-muted">Current: ${video.VideoThumbnail || 'No thumbnail'}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>SEO Settings</h6>
                                    <div class="form-group">
                                        <label>Meta Title</label>
                                        <input type="text" class="form-control" name="metaTitle" value="${video.MetaTitle || video.Title || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label>Meta Description</label>
                                        <textarea class="form-control" name="metaDescription" rows="3">${video.MetaDescription || video.Excerpt || ''}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Meta Keywords</label>
                                        <input type="text" class="form-control" name="metaKeywords" value="${video.MetaKeywords || ''}" placeholder="Enter keywords separated by commas">
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Show the modal
                        $('#editVideoModal').modal('show');
                        
                        // Re-initialize AJAX upload for the edit form
                        setTimeout(() => {
                            initializeAjaxUpload();
                        }, 100);
                    } else {
                        alert('Failed to load video data. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error loading video:', error);
                    alert('Error loading video data. Please try again.');
                });
        }

        // Toggle publish date for edit form
        function toggleEditPublishDate(status) {
            const publishDateGroup = document.getElementById('editPublishDateGroup');
            if (publishDateGroup) {
                if (status === 'scheduled') {
                    publishDateGroup.style.display = 'block';
                } else {
                    publishDateGroup.style.display = 'none';
                }
            }
        }

        // AJAX Upload functionality with progress bar
        function initializeAjaxUpload() {
            // Get both create and update forms - use more specific selectors
            const createForm = document.querySelector('#createVideoModal form');
            const updateForm = document.querySelector('#editVideoModal form');
            
            console.log('Initializing AJAX upload...');
            console.log('Create form found:', !!createForm);
            console.log('Update form found:', !!updateForm);
            
            if (createForm) {
                // Remove any existing listeners to prevent duplicates
                createForm.removeEventListener('submit', createFormSubmitHandler);
                createForm.addEventListener('submit', createFormSubmitHandler);
                console.log('Create form AJAX handler attached');
            } else {
                console.warn('Create form not found! Check the modal structure.');
            }
            
            if (updateForm) {
                // Remove any existing listeners to prevent duplicates
                updateForm.removeEventListener('submit', updateFormSubmitHandler);
                updateForm.addEventListener('submit', updateFormSubmitHandler);
                console.log('Update form AJAX handler attached');
            } else {
                console.warn('Update form not found! Check the modal structure.');
            }
        }

        // Create form submit handler
        function createFormSubmitHandler(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('Create form submitted via AJAX - PREVENTED DEFAULT');
            console.log('Form element:', this);
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            debugFormSubmission(this);
            handleAjaxVideoUpload(this, 'create');
            return false;
        }

        // Update form submit handler
        function updateFormSubmitHandler(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('Update form submitted via AJAX - PREVENTED DEFAULT');
            console.log('Form element:', this);
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            debugFormSubmission(this);
            handleAjaxVideoUpload(this, 'update');
            return false;
        }

        function handleAjaxVideoUpload(form, action) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text') || submitBtn;
            const btnLoader = submitBtn.querySelector('.btn-loader');
            
            console.log('Starting AJAX upload for:', action);
            console.log('Form data:', Object.fromEntries(formData));
            
            // Validate form before upload
            if (!validateFormBeforeUpload(form, action)) {
                return;
            }
            
            // Show upload progress modal
            showUploadProgressModal();
            
            // Disable submit button
            submitBtn.disabled = true;
            if (btnLoader) {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-block';
            }
            
            // Create XMLHttpRequest for upload with progress
            const xhr = new XMLHttpRequest();
            
            // Upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    updateUploadProgress(percentComplete, 'Uploading video file...');
                    console.log('Upload progress:', percentComplete + '%');
                }
            });
            
            // Response handling
            xhr.addEventListener('load', function() {
                console.log('XHR response received:', xhr.status, xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            updateUploadProgress(100, 'Upload completed successfully!');
                            setTimeout(() => {
                                hideUploadProgressModal();
                                
                                // Show subtle success notification instead of popup
                                showSubtleSuccessNotification(response.message || 'Video ' + (action === 'create' ? 'created' : 'updated') + ' successfully!');
                                
                                // Close modal and reload page
                                if (action === 'create') {
                                    $('#createVideoModal').modal('hide');
                                } else {
                                    $('#editVideoModal').modal('hide');
                                }
                                setTimeout(() => location.reload(), 1000);
                            }, 1500);
                        } else {
                            hideUploadProgressModal();
                            
                            // Handle specific errors gracefully without annoying alerts
                            if (response.error && response.error.includes('Slug already exists')) {
                                // For slug conflicts, highlight the slug field and show helper text
                                const slugInput = form.querySelector('input[name="slug"]');
                                if (slugInput) {
                                    slugInput.style.borderColor = '#dc3545';
                                    slugInput.style.backgroundColor = '#fff5f5';
                                    // Add helper text below the slug field
                                    let helperText = slugInput.parentNode.querySelector('.slug-helper');
                                    if (!helperText) {
                                        helperText = document.createElement('small');
                                        helperText.className = 'text-danger slug-helper';
                                        slugInput.parentNode.appendChild(helperText);
                                    }
                                    helperText.textContent = 'This slug is already in use. Please choose a different one.';
                                }
                                console.log('Slug conflict detected, user can fix it');
                            } else {
                                // For other errors, just log to console instead of showing alerts
                                console.log('Upload failed:', response.error);
                            }
                        }
                    } catch (e) {
                        hideUploadProgressModal();
                        console.log('Server response issue, please try again');
                        console.error('JSON parse error:', e);
                    }
                } else {
                    hideUploadProgressModal();
                    console.log('Server error occurred, please try again');
                    console.error('HTTP error:', xhr.status, xhr.responseText);
                }
                
                // Re-enable submit button
                submitBtn.disabled = false;
                if (btnLoader) {
                    btnText.style.display = 'inline-block';
                    btnLoader.style.display = 'none';
                }
            });
            
            // Error handling
            xhr.addEventListener('error', function() {
                hideUploadProgressModal();
                showErrorMessage('Upload failed. Network error occurred.');
                console.error('Network error occurred');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                if (btnLoader) {
                    btnText.style.display = 'inline-block';
                    btnLoader.style.display = 'none';
                }
            });
            
            // Timeout handling
            xhr.timeout = 300000; // 5 minutes
            xhr.addEventListener('timeout', function() {
                hideUploadProgressModal();
                showErrorMessage('Upload timed out. Please try again with a smaller file or check your connection.');
                console.error('Upload timeout');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                if (btnLoader) {
                    btnText.style.display = 'inline-block';
                    btnLoader.style.display = 'none';
                }
            });
            
            // Send request to appropriate endpoint
            const endpoint = action === 'create' ? 'ajax_create_video.php' : 'ajax_update_video.php';
            console.log('Sending request to:', endpoint);
            
            xhr.open('POST', endpoint, true);
            xhr.send(formData);
        }

        function validateFormBeforeUpload(form, action) {
            const title = form.querySelector('input[name="title"]').value.trim();
            const slug = form.querySelector('input[name="slug"]').value.trim();
            const status = form.querySelector('select[name="status"]').value;
            const videoFile = form.querySelector('input[name="videoFile"]').files[0];
            const embedCode = form.querySelector('textarea[name="embedCode"]').value.trim();
            
            // Clear any previous validation styling
            clearValidationStyling(form);
            
            let isValid = true;
            
            // Basic validation with inline feedback
            if (!title) {
                highlightFieldError(form.querySelector('input[name="title"]'), 'Title is required');
                isValid = false;
            }
            
            if (!slug) {
                highlightFieldError(form.querySelector('input[name="slug"]'), 'Slug is required');
                isValid = false;
            }
            
            // Only require video/embed for new videos, not updates
            if (action === 'create' && !videoFile && !embedCode) {
                const videoFileInput = form.querySelector('input[name="videoFile"]');
                const embedCodeInput = form.querySelector('textarea[name="embedCode"]');
                highlightFieldError(videoFileInput, 'Please provide either video file or embed code');
                highlightFieldError(embedCodeInput, 'Please provide either video file or embed code');
                isValid = false;
            }
            
            if (status === 'scheduled') {
                const publishDate = form.querySelector('input[name="publishDate"]').value;
                if (!publishDate) {
                    highlightFieldError(form.querySelector('input[name="publishDate"]'), 'Publish date is required for scheduled videos');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function highlightFieldError(field, message) {
            if (field) {
                field.style.borderColor = '#dc3545';
                field.style.backgroundColor = '#fff5f5';
                
                // Add helper text below the field
                let helperText = field.parentNode.querySelector('.field-helper');
                if (!helperText) {
                    helperText = document.createElement('small');
                    helperText.className = 'text-danger field-helper';
                    field.parentNode.appendChild(helperText);
                }
                helperText.textContent = message;
            }
        }
        
        function clearValidationStyling(form) {
            // Clear all validation styling
            const fields = form.querySelectorAll('input, textarea, select');
            fields.forEach(field => {
                field.style.borderColor = '';
                field.style.backgroundColor = '';
            });
            
            // Remove all helper text
            const helpers = form.querySelectorAll('.field-helper, .slug-helper');
            helpers.forEach(helper => helper.remove());
        }
        
        function addValidationClearing() {
            // Clear validation styling when user starts typing
            document.addEventListener('input', function(e) {
                if (e.target.matches('input, textarea, select')) {
                    e.target.style.borderColor = '';
                    e.target.style.backgroundColor = '';
                    
                    // Remove helper text for this field
                    const helperText = e.target.parentNode.querySelector('.field-helper, .slug-helper');
                    if (helperText) {
                        helperText.remove();
                    }
                }
            });
        }

        function showUploadProgressModal() {
            console.log('Showing upload progress modal...');
            
            // Remove any existing modal first
            const existingModal = document.querySelector('.upload-progress-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.className = 'upload-progress-modal';
            modal.innerHTML = `
                <div class="upload-progress-content">
                    <div class="upload-progress-header">
                        <h5><i class="fa fa-cloud-upload"></i> Uploading Video</h5>
                        <button type="button" class="close" onclick="hideUploadProgressModal()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="upload-progress-body">
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <div class="progress-text" id="progressText">Preparing upload...</div>
                        </div>
                        <div class="upload-status" id="uploadStatus">
                            <i class="fa fa-spinner fa-spin"></i> Initializing upload...
                        </div>
                        <div class="debug-info mt-3">
                            <small class="text-muted">...</small>
                        </div>
                    </div>
                </div>
            `;
            
            // Add enhanced styles for the progress modal
            if (!document.querySelector('#progress-modal-styles')) {
                const styles = document.createElement('style');
                styles.id = 'progress-modal-styles';
                styles.textContent = `
                    .upload-progress-modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.85);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                        opacity: 0;
                        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                        backdrop-filter: blur(5px);
                    }
                    .upload-progress-modal.show {
                        opacity: 1;
                    }
                    .upload-progress-content {
                        background: white;
                        border-radius: 8px;
                        border: 2px solid #007bff;
                        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
                        width: 90%;
                        max-width: 400px;
                        overflow: hidden;
                        transform: scale(0.9);
                        transition: transform 0.3s ease;
                    }
                    .upload-progress-modal.show .upload-progress-content {
                        transform: scale(1);
                    }
                    .upload-progress-header {
                        background: #007bff;
                        color: white;
                        padding: 15px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 1px solid #0056b3;
                    }
                    .upload-progress-header h5 {
                        margin: 0;
                        font-size: 16px;
                        font-weight: 600;
                    }
                    .upload-progress-header h5 i {
                        margin-right: 8px;
                        font-size: 18px;
                    }
                    .upload-progress-header .close {
                        background: rgba(255, 255, 255, 0.2);
                        border: none;
                        color: white;
                        font-size: 16px;
                        cursor: pointer;
                        padding: 4px;
                        width: 28px;
                        height: 28px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 4px;
                        transition: all 0.2s ease;
                    }
                    .upload-progress-header .close:hover {
                        background: rgba(255, 255, 255, 0.3);
                    }
                    .upload-progress-body {
                        padding: 25px 20px;
                        background: #ffffff;
                    }
                    .progress-container {
                        margin-bottom: 20px;
                    }
                    .progress-bar {
                        width: 100%;
                        height: 20px;
                        background: #f8f9fa;
                        border-radius: 10px;
                        overflow: hidden;
                        margin-bottom: 10px;
                        border: 2px solid #e9ecef;
                        position: relative;
                    }
                    .progress-fill {
                        height: 100%;
                        background: #007bff;
                        width: 0%;
                        transition: width 0.3s ease;
                        border-radius: 8px;
                        position: relative;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: 600;
                        font-size: 12px;
                        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
                    }
                    .progress-text {
                        text-align: center;
                        font-weight: 600;
                        color: #007bff;
                        font-size: 14px;
                        margin-bottom: 5px;
                    }
                    .upload-status {
                        text-align: center;
                        color: #6c757d;
                        font-size: 13px;
                        font-weight: 500;
                        padding: 10px;
                        background: #f8f9fa;
                        border-radius: 6px;
                        border: 1px solid #e9ecef;
                    }
                    .upload-status i {
                        margin-right: 8px;
                        color: #007bff;
                        font-size: 14px;
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(modal);
            console.log('Upload modal added to DOM');
            
            // Force a reflow to ensure styles are applied
            modal.offsetHeight;
            
            setTimeout(() => {
                modal.classList.add('show');
                console.log('Upload modal shown');
            }, 50);
        }

        function hideUploadProgressModal() {
            const modal = document.querySelector('.upload-progress-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.remove();
                    }
                }, 400);
            }
        }

        function updateUploadProgress(percent, status) {
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const uploadStatus = document.getElementById('uploadStatus');
            
            if (progressFill) {
                progressFill.style.width = percent + '%';
                // Show percentage inside the progress bar
                progressFill.textContent = percent + '%';
                
                if (percent === 0) {
                    progressText.textContent = 'Preparing upload...';
                } else if (percent < 100) {
                    progressText.textContent = `${percent}% Complete`;
                } else {
                    progressText.textContent = 'Upload Complete!';
                }
            }
            
            if (uploadStatus) {
                let statusMessage = '';
                let icon = '';
                if (percent === 0) {
                    statusMessage = 'Initializing upload process...';
                    icon = 'fa fa-cog fa-spin';
                } else if (percent < 25) {
                    statusMessage = 'Starting upload...';
                    icon = 'fa fa-upload';
                } else if (percent < 50) {
                    statusMessage = 'Uploading video file...';
                    icon = 'fa fa-upload';
                } else if (percent < 75) {
                    statusMessage = 'Processing video data...';
                    icon = 'fa fa-cog fa-spin';
                } else if (percent < 100) {
                    statusMessage = 'Finalizing upload...';
                    icon = 'fa fa-check-circle';
                } else {
                    statusMessage = 'Upload completed successfully!';
                    icon = 'fa fa-check-circle';
                }
                uploadStatus.innerHTML = `<i class="fa ${icon}"></i> ${statusMessage}`;
            }
        }







        // Initialize AJAX upload when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing AJAX upload...');
            initializeAjaxUpload();
            
            // Also add button click handlers as backup
            setTimeout(() => {
                addButtonClickHandlers();
            }, 500);
            
            // Add validation clearing on input
            addValidationClearing();
        });

        // Also initialize when modals are shown (for dynamic content)
        $(document).on('shown.bs.modal', function() {
            console.log('Modal shown, re-initializing AJAX upload...');
            setTimeout(() => {
                initializeAjaxUpload();
                addButtonClickHandlers();
            }, 100);
        });

        // Add button click handlers as backup
        function addButtonClickHandlers() {
            console.log('Adding button click handlers...');
            
            // Create video button
            const createBtn = document.querySelector('#createVideoBtn');
            if (createBtn) {
                createBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Create button clicked - preventing default');
                    
                    const form = this.closest('form');
                    if (form) {
                        console.log('Form found, handling AJAX upload...');
                        handleAjaxVideoUpload(form, 'create');
                    } else {
                        console.error('Form not found for create button!');
                    }
                    return false;
                };
                console.log('Create button handler attached');
            }
            
            // Update video button
            const updateBtn = document.querySelector('button[name="update_video"]');
            if (updateBtn) {
                updateBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Update button clicked - preventing default');
                    
                    const form = this.closest('form');
                    if (form) {
                        console.log('Form found, handling AJAX upload...');
                        handleAjaxVideoUpload(form, 'update');
                    } else {
                        console.error('Form not found for update button!');
                    }
                    return false;
                };
                console.log('Update button handler attached');
            }
        }





        function validateVideoForm(form, action = 'create') {
            const title = form.querySelector('input[name="title"]').value.trim();
            const slug = form.querySelector('input[name="slug"]').value.trim();
            const status = form.querySelector('select[name="status"]').value;
            const videoFile = form.querySelector('input[name="videoFile"]').files[0];
            const embedCode = form.querySelector('textarea[name="embedCode"]').value.trim();
            
            // Basic validation
            if (!title || !slug) {
                showErrorMessage('Please fill in all required fields (Title and Slug).');
                return false;
            }
            
            // Only require video/embed for new videos, not updates
            if (action === 'create' && !videoFile && !embedCode) {
                showErrorMessage('Please either upload a video file OR provide embed code.');
                return false;
            }
            
            if (status === 'scheduled') {
                const publishDate = form.querySelector('input[name="publishDate"]').value;
                if (!publishDate) {
                    showErrorMessage('Please select a publish date for scheduled videos.');
                    return false;
                }
            }
            
            return true;
        }

        function showSubtleSuccessNotification(message) {
            // Create a subtle success notification that appears at the top of the page
            const notification = document.createElement('div');
            notification.className = 'success-notification';
            notification.innerHTML = `
                <div class="success-notification-content">
                    <i class="fa fa-check-circle text-success"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Add styles for the notification
            if (!document.querySelector('#success-notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'success-notification-styles';
                styles.textContent = `
                    .success-notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                        border-radius: 4px;
                        padding: 12px 20px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        z-index: 9999;
                        transform: translateX(100%);
                        transition: transform 0.3s ease-in-out;
                        max-width: 400px;
                    }
                    .success-notification.show {
                        transform: translateX(0);
                    }
                    .success-notification-content {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }
                    .success-notification i {
                        font-size: 18px;
                    }
                `;
                document.head.appendChild(styles);
            }
            
            // Add to page
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
    <!-- Custom JavaScript -->
    <script>
        // Show/hide publish date field based on status
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.querySelector('select[name="status"]');
            const publishDateGroup = document.getElementById('publishDateGroup');
            
            if (statusSelect && publishDateGroup) {
                statusSelect.addEventListener('change', function() {
                    if (this.value === 'scheduled') {
                        publishDateGroup.style.display = 'block';
                        publishDateGroup.querySelector('input[name="publishDate"]').required = true;
                    } else {
                        publishDateGroup.style.display = 'none';
                        publishDateGroup.querySelector('input[name="publishDate"]').required = false;
                    }
                });
            }
        });

        // Function to toggle publish date field in create form
        function togglePublishDate(status) {
            const publishDateGroup = document.getElementById('publishDateGroup');
            const publishDateInput = publishDateGroup.querySelector('input[name="publishDate"]');
            
            if (status === 'scheduled') {
                publishDateGroup.style.display = 'block';
                publishDateInput.required = true;
            } else {
                publishDateGroup.style.display = 'none';
                publishDateInput.required = false;
            }
        }

        // Function to toggle publish date field in edit form
        function toggleEditPublishDate(status) {
            const publishDateGroup = document.getElementById('editPublishDateGroup');
            const publishDateInput = publishDateGroup.querySelector('input[name="publishDate"]');
            
            if (status === 'scheduled') {
                publishDateGroup.style.display = 'block';
                publishDateInput.required = true;
            } else {
                publishDateGroup.style.display = 'none';
                publishDateInput.required = false;
            }
        }
        
        // Function to toggle fields based on video type in edit form
        function toggleEditVideoTypeFields(videoType) {
            const videoFileGroup = document.querySelector('#editVideoModal .form-group:has(input[name="videoFile"])');
            const embedCodeGroup = document.querySelector('#editVideoModal .form-group:has(textarea[name="embedCode"])');
            const videoFileInput = document.querySelector('#editVideoModal input[name="videoFile"]');
            
            if (videoType === 'short') {
                // For shorts, show video file upload and hide embed code
                if (videoFileGroup) videoFileGroup.style.display = 'block';
                if (embedCodeGroup) embedCodeGroup.style.display = 'none';
                
                // Update labels for shorts
                const videoFileLabel = document.querySelector('#editVideoModal label:has(+ input[name="videoFile"])');
                if (videoFileLabel) videoFileLabel.innerHTML = 'Short Video File <span class="text-danger">*</span>';
                
                const videoFileHelp = document.querySelector('#editVideoModal input[name="videoFile"] + small');
                if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong>';
                
                // Add validation for short video dimensions
                if (videoFileInput) {
                    videoFileInput.addEventListener('change', validateShortVideoDimensions);
                }
            } else if (videoType === 'video') {
                // For regular videos, show both options
                if (videoFileGroup) videoFileGroup.style.display = 'block';
                if (embedCodeGroup) embedCodeGroup.style.display = 'block';
                
                // Update labels for regular videos
                const videoFileLabel = document.querySelector('#editVideoModal label:has(+ input[name="videoFile"])');
                if (videoFileLabel) videoFileLabel.innerHTML = 'Video File <span class="text-danger">*</span>';
                
                const videoFileHelp = document.querySelector('#editVideoModal input[name="videoFile"] + small');
                if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB)';
                
                // Remove short video validation
                if (videoFileInput) {
                    videoFileInput.removeEventListener('change', validateShortVideoDimensions);
                }
            }
        }
        
        // Function to toggle fields based on video type
        function toggleVideoTypeFields(videoType) {
            const videoFileGroup = document.querySelector('.form-group:has(input[name="videoFile"])');
            const embedCodeGroup = document.querySelector('.form-group:has(textarea[name="embedCode"])');
            const videoFileInput = document.querySelector('input[name="videoFile"]');
            
            if (videoType === 'short') {
                // For shorts, show video file upload and hide embed code
                if (videoFileGroup) videoFileGroup.style.display = 'block';
                if (embedCodeGroup) embedCodeGroup.style.display = 'none';
                
                // Update labels for shorts
                const videoFileLabel = document.querySelector('label:has(+ input[name="videoFile"])');
                if (videoFileLabel) videoFileLabel.innerHTML = 'Short Video File <span class="text-danger">*</span>';
                
                const videoFileHelp = document.querySelector('input[name="videoFile"] + small');
                if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong>';
                
                // Add validation for short video dimensions
                if (videoFileInput) {
                    videoFileInput.addEventListener('change', validateShortVideoDimensions);
                }
            } else if (videoType === 'video') {
                // For regular videos, show both options
                if (videoFileGroup) videoFileGroup.style.display = 'block';
                if (embedCodeGroup) embedCodeGroup.style.display = 'block';
                
                // Update labels for regular videos
                const videoFileLabel = document.querySelector('label:has(+ input[name="videoFile"])');
                if (videoFileLabel) videoFileLabel.innerHTML = 'Video File <span class="text-danger">*</span>';
                
                const videoFileHelp = document.querySelector('input[name="videoFile"] + small');
                if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB)';
                
                // Remove short video validation
                if (videoFileInput) {
                    videoFileInput.removeEventListener('change', validateShortVideoDimensions);
                }
            }
        }
        
        // Function to validate short video dimensions
        function validateShortVideoDimensions(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const video = document.createElement('video');
            video.preload = 'metadata';
            
            video.onloadedmetadata = function() {
                const width = this.videoWidth;
                const height = this.videoHeight;
                const aspectRatio = width / height;
                
                // Check if it's close to 9:16 aspect ratio (0.5625)
                const isShortFormat = Math.abs(aspectRatio - 0.5625) < 0.1;
                
                if (!isShortFormat) {
                    // Show warning
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'alert alert-warning mt-2';
                    warningDiv.innerHTML = `
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This video has dimensions ${width}x${height} (aspect ratio: ${aspectRatio.toFixed(2)}).
                        <br>For short videos, we recommend 1080x1920 (9:16 aspect ratio).
                        <br>Are you sure you want to continue?
                    `;
                    
                    // Remove existing warning
                    const existingWarning = event.target.parentNode.querySelector('.alert-warning');
                    if (existingWarning) existingWarning.remove();
                    
                    // Add new warning
                    event.target.parentNode.appendChild(warningDiv);
                    
                    // Add confirmation checkbox
                    const confirmCheckbox = document.createElement('div');
                    confirmCheckbox.className = 'form-check mt-2';
                    confirmCheckbox.innerHTML = `
                        <input type="checkbox" class="form-check-input" id="confirmShortFormat">
                        <label class="form-check-label" for="confirmShortFormat">
                            I understand this video doesn't match the recommended short format
                        </label>
                    `;
                    event.target.parentNode.appendChild(confirmCheckbox);
                    
                    // Disable submit button until confirmed
                    const submitBtn = document.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.title = 'Please confirm the video format warning';
                    }
                    
                    // Enable submit button when confirmed
                    document.getElementById('confirmShortFormat').addEventListener('change', function() {
                        if (submitBtn) {
                            submitBtn.disabled = !this.checked;
                            submitBtn.title = this.checked ? '' : 'Please confirm the video format warning';
                        }
                    });
                } else {
                    // Remove any existing warnings
                    const existingWarning = event.target.parentNode.querySelector('.alert-warning');
                    if (existingWarning) existingWarning.remove();
                    
                    const existingCheckbox = event.target.parentNode.querySelector('.form-check');
                    if (existingCheckbox) existingCheckbox.remove();
                    
                    // Enable submit button
                    const submitBtn = document.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.title = '';
                    }
                }
                
                // Clean up
                URL.revokeObjectURL(video.src);
            };
            
            video.src = URL.createObjectURL(file);
        }
    </script>
</body>
</html>

