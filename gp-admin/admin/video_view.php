<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';
include 'php/includes/CreatorProfileManager.php';

// Helper function to get correct thumbnail path
function getThumbnailPath($thumbnailPath)
{
    if (empty($thumbnailPath)) {
        return 'php/defaultavatar/video-thumbnail.png';
    }

    // If thumbnail is a URL, return as is
    if (filter_var($thumbnailPath, FILTER_VALIDATE_URL)) {
        return $thumbnailPath;
    }

    // Check if file exists in the current directory
    if (file_exists($thumbnailPath)) {
        return $thumbnailPath;
    }

    // Try with images directory prefix
    $imagesPath = 'images/video_thumbnails/' . basename($thumbnailPath);
    if (file_exists($imagesPath)) {
        return $imagesPath;
    }

    // Return default if nothing works
    return 'php/defaultavatar/video-thumbnail.png';
}

// Helper function to get correct video file path
function getVideoFilePath($videoFile) {
    if (empty($videoFile)) {
        error_log("Video file is empty");
        return '';
    }
    if (filter_var($videoFile, FILTER_VALIDATE_URL)) {
        error_log("Video file is a URL: $videoFile");
        return $videoFile;
    }
    
    // Check if file exists at the original path
    if (file_exists($videoFile)) {
        error_log("Video file found at original path: $videoFile");
        return $videoFile;
    }
    
    // Check different possible path variations
    $possiblePaths = [
        $videoFile, // Original path
        'videos/' . basename($videoFile), // Relative to current directory
        'src/videos/' . basename($videoFile), // Alternative videos directory
        'php/videos/' . basename($videoFile), // PHP videos directory
        '../videos/' . basename($videoFile), // Parent directory videos
        '../../videos/' . basename($videoFile), // Two levels up videos
        'gp-admin/admin/videos/' . basename($videoFile), // Full path from root
        'gp-admin/admin/src/videos/' . basename($videoFile), // Full path from root alternative
        'admin/videos/' . basename($videoFile), // Admin videos directory
        'admin/src/videos/' . basename($videoFile) // Admin src videos directory
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            error_log("Video file found at: $path");
            return $path;
        }
    }
    
    // If no file found, log the issue and return the original path
    error_log("Video file not found at any location: $videoFile");
    error_log("Current working directory: " . getcwd());
    error_log("Checked paths: " . implode(', ', $possiblePaths));
    
    return $videoFile; // Return original if no valid path found
}

// Helper function to format duration in MM:SS format
function formatDuration($seconds)
{
    if (!$seconds || $seconds <= 0) {
        return '0:00';
    }

    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}

// Helper function to format file size
function formatFileSize($bytes, $precision = 2)
{
    if ($bytes === 0) {
        return '0 Bytes';
    }
    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Get video ID from URL
$videoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$videoId) {
    header('Location: video_posts.php');
    exit;
}

try {
    // Initialize the video manager with correct paths
    $videoManager = new VideoManager($con, 'videos/', 'images/video_thumbnails/');

    // Get video data
    $video = $videoManager->getVideo($videoId);

    if (!$video) {
        throw new Exception('Video not found');
    }

    // Check if video is published
    if ($video['Status'] !== 'published') {
        throw new Exception('This video is not available for viewing');
    }

    // Record video view
    try {
        $videoManager->recordVideoView($videoId);
    } catch (Exception $e) {
        // Log error but don't stop execution
        error_log('Failed to record video view: ' . $e->getMessage());
    }

    // Get related videos
    $relatedVideos = $videoManager->getRelatedVideos($videoId, $video['CategoryID'], 6);

    // Get creator profile information
    $creatorProfile = null;
    if (isset($creatorManager)) {
        $creatorProfile = $creatorManager->getProfileByProfileId($video['ProfileID']);
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($video['Title']) ?> - Video View - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Video Player Container */
        .video-container {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .video-player-wrapper {
            position: relative;
            width: 100%;
            background: #000;
            cursor: pointer;
        }
        
        /* Main Video Element */
        .main-video-element {
            width: 100%;
            height: auto;
            display: block;
            background: #000;
        }
        
        /* Thumbnail Overlay */
        .thumbnail-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: opacity 0.3s ease;
        }
        
        .thumbnail-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .play-button-container {
            text-align: center;
            color: white;
        }
        
        .play-button {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            font-size: 2rem;
            color: #000;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .play-button:hover {
            background: white;
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
        }
        
        .video-duration {
            font-size: 1.2rem;
            font-weight: 500;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        
        /* Video Controls */
        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 5;
        }
        
        .video-player-wrapper:hover .video-controls,
        .video-controls.visible {
            opacity: 1;
        }
        
        .progress-container {
            margin-bottom: 1rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
        }
        
        .progress-filled {
            height: 100%;
            background: #ff0000;
            border-radius: 3px;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .progress-hover {
            position: absolute;
            top: -10px;
            height: 26px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .progress-tooltip {
            position: absolute;
            top: -40px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            white-space: nowrap;
        }
        
        .controls-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .controls-left, .controls-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .control-btn.active {
            background: rgba(255, 255, 255, 0.3);
            color: #ff0000;
        }
        
        .time-display {
            font-family: monospace;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .time-separator {
            margin: 0 0.5rem;
            opacity: 0.7;
        }
        
        .volume-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .volume-slider-container {
            width: 80px;
        }
        
        .volume-slider {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            outline: none;
            -webkit-appearance: none;
        }
        
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            cursor: pointer;
        }
        
        /* Settings Panel */
        .settings-panel {
            position: absolute;
            bottom: 80px;
            right: 1rem;
            background: rgba(0, 0, 0, 0.9);
            border-radius: 8px;
            padding: 1rem;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 20;
        }
        
        .settings-panel.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            color: white;
        }
        
        .settings-item:last-child {
            margin-bottom: 0;
        }
        
        .settings-label {
            font-size: 0.9rem;
        }
        
        .settings-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quality-selector {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .playback-speed {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        /* Video Information Section */
        .video-info-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .video-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .video-excerpt {
            font-size: 1.3rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        /* Video Meta Information */
        .video-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .meta-item:hover {
            transform: translateY(-2px);
        }
        
        .meta-item i {
            font-size: 1.5rem;
            color: #4e73df;
            width: 24px;
            text-align: center;
        }
        
        .meta-item span {
            font-weight: 500;
            color: #495057;
        }
        
        /* Video Description */
        .video-description {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .description-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .description-content {
            color: #495057;
            line-height: 1.7;
            font-size: 1.1rem;
        }
        
        /* Video Tags */
        .video-tags {
            margin-bottom: 2rem;
        }
        
        .tags-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 0.75rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .tag:hover {
            background: #1976d2;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Related Videos Section */
        .related-videos-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .related-videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .related-video-card {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .related-video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .related-video-thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #e9ecef;
        }
        
        .related-video-info {
            padding: 1.5rem;
        }
        
        .related-video-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .related-video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Back Button */
        .back-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .back-button:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .video-title {
                font-size: 2rem;
            }
            
            .video-meta {
                grid-template-columns: 1fr;
            }
            
            .related-videos-grid {
                grid-template-columns: 1fr;
            }
            
            .controls-main {
                flex-direction: column;
                gap: 1rem;
            }
            
            .controls-left, .controls-right {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Loading State */
        .video-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 400px;
            background: #f8f9fa;
            border-radius: 12px;
            color: #6c757d;
            font-size: 1.2rem;
        }
        
        /* Error State */
        .video-error {
            background: #f8d7da;
            color: #721c24;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        /* Featured Badge */
        .featured-badge {
            background: #f6c23e;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 1rem;
        }
        
        /* Category Badge */
        .category-badge {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        /* Video Stats */
        .video-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4e73df;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        /* Video Actions */
        .video-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn.primary {
            background: #4e73df;
            color: white;
        }
        
        .action-btn.primary:hover {
            background: #2e59d9;
            transform: translateY(-2px);
        }
        
        .action-btn.secondary {
            background: #6c757d;
            color: white;
        }
        
        .action-btn.secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .action-btn.success {
            background: #1cc88a;
            color: white;
        }
        
        .action-btn.success:hover {
            background: #17a673;
            transform: translateY(-2px);
        }
        
        /* Video Quality Indicator */
        .quality-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 15;
        }
        
        /* Buffering Indicator */
        .buffering-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            z-index: 20;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .buffering-indicator.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="whole-content-container">
        <?php include "php/includes/header.php"; ?>

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
                                <span class="micon"><i class="icon-copy fa fa-newspaper-o" aria-hidden="true"></i></span><span class="mtext">Article</span>
                            </a>
                            <ul class="submenu">
                                <li><a href="new_article.php">New</a></li>
                                <li><a href="view_article.php">Manage</a></li>
                            </ul>
                        </li>
                        <li class="dropdown active">
                            <a href="javascript:;" class="dropdown-toggle">
                                <span class="micon"><i class="icon-copy fa fa-play-circle" aria-hidden="true"></i></span><span class="mtext">Videos</span>
                            </a>
                            <ul class="submenu">
                                <li><a href="video_posts.php">Manage Videos</a></li>
                                <li><a href="video_analytics.php">Analytics</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:;" class="dropdown-toggle">
                                <span class="micon"><i class="icon-copy fa fa-object-ungroup" aria-hidden="true"></i></span><span class="mtext">Category</span>
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
                                <span class="micon"><i class="icon-copy fa fa-cogs" aria-hidden="true"></i></span><span class="mtext">Settings</span>
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

        <div class="main-container">
            <div class="pd-ltr-20 xs-pd-20-10">
                <!-- Back Button -->
                <a href="video_posts.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Video Posts
                </a>

                <?php if (isset($error_message)): ?>
                    <div class="video-error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Error</h4>
                        <p><?= htmlspecialchars($error_message) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($video): ?>
                    <!-- Video Player Section -->
                    <div class="video-container">
                        <div class="video-player-wrapper" id="youtubeStylePlayer">
                            <?php if ($video['VideoFormat'] === 'embed'): ?>
                                <!-- Embedded Video -->
                                <div class="embed-responsive embed-responsive-16by9">
                                    <?= $video['EmbedCode'] ?>
                                </div>
                            <?php else: ?>
                                <!-- HTML5 Video Player -->
                                <?php
                                $videoFile = getVideoFilePath($video['VideoFile']);
                                $thumbnailPath = getThumbnailPath($video['VideoThumbnail']);
                                
                                if (!empty($videoFile)) {
                                    $extension = strtolower(pathinfo($videoFile, PATHINFO_EXTENSION));
                                    switch ($extension) {
                                        case 'mp4':
                                            $videoType = 'video/mp4';
                                            break;
                                        case 'webm':
                                            $videoType = 'video/webm';
                                            break;
                                        case 'ogg':
                                            $videoType = 'video/ogg';
                                            break;
                                        case 'avi':
                                            $videoType = 'video/x-msvideo';
                                            break;
                                        case 'mov':
                                            $videoType = 'video/quicktime';
                                            break;
                                        default:
                                            $videoType = 'video/mp4';
                                    }
                                }
                                
                                // Debug video file path
                                error_log("Video file path: " . $videoFile);
                                error_log("Video type: " . $videoType);
                                error_log("File exists: " . (file_exists($videoFile) ? 'Yes' : 'No'));
                                ?>
                                
                                <!-- Video Element -->
                                <video id="mainVideo" 
                                       class="main-video-element" 
                                       preload="metadata"
                                       poster="<?= htmlspecialchars($thumbnailPath) ?>">
                                    <source src="<?= htmlspecialchars($videoFile) ?>" type="<?= $videoType ?>">
                                    Your browser does not support the video tag.
                                </video>
                                
                                <!-- Quality Indicator -->
                                <div class="quality-indicator" id="qualityIndicator">
                                    <?= htmlspecialchars($video['VideoResolution'] ?? 'HD') ?>
                                </div>
                                
                                <!-- Buffering Indicator -->
                                <div class="buffering-indicator" id="bufferingIndicator">
                                    <div class="spinner"></div>
                                    Buffering...
                                </div>
                                
                                <!-- Thumbnail Overlay (shown before play) -->
                                <?php if (!empty($thumbnailPath) && file_exists($thumbnailPath)): ?>
                                <div class="thumbnail-overlay" id="thumbnailOverlay">
                                    <div class="play-button-container">
                                        <button class="play-button" id="playButton">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <div class="video-duration">
                                            <?= formatDuration($video['VideoDuration'] ?? 0) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Fallback overlay when no thumbnail -->
                                <div class="thumbnail-overlay" id="thumbnailOverlay">
                                    <div class="play-button-container">
                                        <button class="play-button" id="playButton">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <div class="video-duration">
                                            <?= formatDuration($video['VideoDuration'] ?? 0) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Video Controls -->
                                <div class="video-controls" id="videoControls">
                                    <!-- Progress Bar -->
                                    <div class="progress-container">
                                        <div class="progress-bar" id="progressBar">
                                            <div class="progress-filled" id="progressFilled"></div>
                                            <div class="progress-hover" id="progressHover"></div>
                                            <div class="progress-tooltip" id="progressTooltip">0:00</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Control Buttons -->
                                    <div class="controls-main">
                                        <div class="controls-left">
                                            <button class="control-btn" id="playPauseBtn" title="Play/Pause (Space)">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            
                                            <div class="time-display">
                                                <span id="currentTime">0:00</span>
                                                <span class="time-separator">/</span>
                                                <span id="totalTime">0:00</span>
                                            </div>
                                        </div>
                                        
                                        <div class="controls-right">
                                            <div class="volume-control">
                                                <button class="control-btn" id="muteBtn" title="Mute/Unmute (M)">
                                                    <i class="fas fa-volume-up"></i>
                                                </button>
                                                <div class="volume-slider-container">
                                                    <input type="range" class="volume-slider" id="volumeSlider" 
                                                           min="0" max="100" value="100" step="1"
                                                           title="Volume (Up/Down arrows)">
                                                </div>
                                            </div>
                                            
                                            <button class="control-btn" id="settingsBtn" title="Settings (S)">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            
                                            <button class="control-btn" id="fullscreenBtn" title="Fullscreen (F)">
                                                <i class="fas fa-expand"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Settings Panel -->
                                <div class="settings-panel" id="settingsPanel">
                                    <div class="settings-item">
                                        <span class="settings-label">Quality</span>
                                        <div class="settings-control">
                                            <select class="quality-selector" id="qualitySelector">
                                                <option value="auto">Auto</option>
                                                <option value="1080p">1080p</option>
                                                <option value="720p">720p</option>
                                                <option value="480p">480p</option>
                                                <option value="360p">360p</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="settings-item">
                                        <span class="settings-label">Playback Speed</span>
                                        <div class="settings-control">
                                            <select class="playback-speed" id="playbackSpeed">
                                                <option value="0.5">0.5x</option>
                                                <option value="0.75">0.75x</option>
                                                <option value="1" selected>1x</option>
                                                <option value="1.25">1.25x</option>
                                                <option value="1.5">1.5x</option>
                                                <option value="2">2x</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="settings-item">
                                        <span class="settings-label">Subtitles</span>
                                        <div class="settings-control">
                                            <input type="checkbox" id="subtitlesToggle">
                                        </div>
                                    </div>
                                    <div class="settings-item">
                                        <span class="settings-label">Loop</span>
                                        <div class="settings-control">
                                            <input type="checkbox" id="loopToggle">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Video Information Section -->
                    <div class="video-info-section">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h1 class="video-title">
                                    <?= htmlspecialchars($video['Title']) ?>
                                    <?php if ($video['Featured']): ?>
                                        <span class="featured-badge">★ Featured</span>
                                    <?php endif; ?>
                                </h1>
                                
                                <?php if (!empty($video['CategoryName'])): ?>
                                    <span class="category-badge">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($video['CategoryName']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($video['Excerpt'])): ?>
                            <p class="video-excerpt"><?= htmlspecialchars($video['Excerpt']) ?></p>
                        <?php endif; ?>

                        <!-- Video Meta Information -->
                        <div class="video-meta">
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?= number_format($video['Views']) ?> views</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?= $video['VideoFormat'] === 'embed' ? 'Embedded Video' : formatDuration($video['VideoDuration'] ?? 0) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-expand"></i>
                                <span><?= htmlspecialchars($video['VideoResolution']) ?></span>
                            </div>
                            <?php if ($video['VideoFormat'] !== 'embed'): ?>
                                <div class="meta-item">
                                    <i class="fas fa-file-video"></i>
                                    <span><?= formatFileSize($video['VideoSize'] ?? 0) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('M j, Y', strtotime($video['Published_at'] ?: $video['Created_at'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?= htmlspecialchars($video['AuthorName'] ?? 'Unknown Author') ?></span>
                            </div>
                        </div>

                        <!-- Video Stats -->
                        <div class="video-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($video['Views']) ?></div>
                                <div class="stat-label">Total Views</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($video['Likes'] ?? 0) ?></div>
                                <div class="stat-label">Likes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($video['Dislikes'] ?? 0) ?></div>
                                <div class="stat-label">Dislikes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($video['Shares'] ?? 0) ?></div>
                                <div class="stat-label">Shares</div>
                            </div>
                        </div>

                        <!-- Video Actions -->
                        <div class="video-actions">
                            <button class="action-btn primary" id="likeBtn">
                                <i class="fas fa-thumbs-up"></i> Like
                            </button>
                            <button class="action-btn secondary" id="dislikeBtn">
                                <i class="fas fa-thumbs-down"></i> Dislike
                            </button>
                            <button class="action-btn success" id="shareBtn">
                                <i class="fas fa-share"></i> Share
                            </button>
                            <button class="action-btn secondary" id="downloadBtn">
                                <i class="fas fa-download"></i> Download
                            </button>
                        </div>

                        <!-- Video Description -->
                        <?php if (!empty($video['Description'])): ?>
                            <div class="video-description">
                                <h3 class="description-title">
                                    <i class="fas fa-info-circle"></i> Description
                                </h3>
                                <div class="description-content">
                                    <?= nl2br(htmlspecialchars($video['Description'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Video Tags -->
                        <?php if (!empty($video['Tags'])): ?>
                            <div class="video-tags">
                                <h4 class="tags-title">
                                    <i class="fas fa-tags"></i> Tags
                                </h4>
                                <?php 
                                $tags = explode(',', $video['Tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Related Videos Section -->
                    <div class="related-videos-section">
                        <h3 class="section-title">
                            <i class="fas fa-play-circle"></i> Related Videos
                        </h3>
                        <div class="related-videos-grid">
                            <?php
                            // Get related videos (same category, excluding current video)
                            $relatedVideos = $videoManager->getRelatedVideos($video['VideoID'], $video['CategoryID'], 6);
                            
                            if (!empty($relatedVideos)):
                                foreach ($relatedVideos as $relatedVideo):
                                    $relatedThumbnailPath = getThumbnailPath($relatedVideo['VideoThumbnail']);
                            ?>
                                <div class="related-video-card">
                                    <a href="video_view.php?id=<?= $relatedVideo['VideoID'] ?>" style="text-decoration: none; color: inherit;">
                                        <img src="<?= htmlspecialchars($relatedThumbnailPath) ?>" 
                                             alt="<?= htmlspecialchars($relatedVideo['Title']) ?>" 
                                             class="related-video-thumbnail"
                                             onerror="this.src='php/defaultavatar/avatar.png';">
                                        <div class="related-video-info">
                                            <h4 class="related-video-title"><?= htmlspecialchars($relatedVideo['Title']) ?></h4>
                                            <div class="related-video-meta">
                                                <span><i class="fas fa-eye"></i> <?= number_format($relatedVideo['Views']) ?></span>
                                                <span><i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($relatedVideo['Created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <p class="text-muted">No related videos found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="video-error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Video Not Found</h4>
                        <p>The requested video could not be found or is not available for viewing.</p>
                        <a href="video_posts.php" class="btn btn-primary">Back to Video Posts</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="right-sidebar">
            <div class="sidebar-title">
                <h3 class="weight-600 font-16 text-blue">
                    Layout Settings
                    <span class="btn-block font-weight-400 font-12">User Interface Settings</span>
                </h3>
                <div class="close-sidebar" data-toggle="right-sidebar-close">
                    <i class="icon-copy ion-close-round"></i>
                </div>
            </div>
            <div class="right-sidebar-body customscroll">
                <div class="right-sidebar-body-content">
                    <h4 class="weight-600 font-18 pb-10">Header Background</h4>
                    <div class="sidebar-btn-group pb-30 mb-10">
                        <a href="javascript:void(0);" class="btn btn-outline-primary header-white active">White</a>
                        <a href="javascript:void(0);" class="btn btn-outline-primary header-dark">Dark</a>
                    </div>
                    <h4 class="weight-600 font-18 pb-10">Sidebar Background</h4>
                    <div class="sidebar-btn-group pb-30 mb-10">
                        <a href="javascript:void(0);" class="btn btn-outline-primary sidebar-light active">White</a>
                        <a href="javascript:void(0);" class="btn btn-outline-primary sidebar-dark">Dark</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('mainVideo');
            const thumbnailOverlay = document.getElementById('thumbnailOverlay');
            const playButton = document.getElementById('playButton');
            const playPauseBtn = document.getElementById('playPauseBtn');
            const progressBar = document.getElementById('progressBar');
            const progressFilled = document.getElementById('progressFilled');
            const progressHover = document.getElementById('progressHover');
            const progressTooltip = document.getElementById('progressTooltip');
            const currentTime = document.getElementById('currentTime');
            const totalTime = document.getElementById('totalTime');
            const muteBtn = document.getElementById('muteBtn');
            const volumeSlider = document.getElementById('volumeSlider');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const videoControls = document.getElementById('videoControls');
            const settingsBtn = document.getElementById('settingsBtn');
            const settingsPanel = document.getElementById('settingsPanel');
            const qualitySelector = document.getElementById('qualitySelector');
            const playbackSpeed = document.getElementById('playbackSpeed');
            const subtitlesToggle = document.getElementById('subtitlesToggle');
            const loopToggle = document.getElementById('loopToggle');
            const bufferingIndicator = document.getElementById('bufferingIndicator');
            const likeBtn = document.getElementById('likeBtn');
            const dislikeBtn = document.getElementById('dislikeBtn');
            const shareBtn = document.getElementById('shareBtn');
            const downloadBtn = document.getElementById('downloadBtn');

            let isPlaying = false;
            let controlsTimeout;
            let videoDuration = 0;

            // Click on video to play/pause
            if (video) {
                video.addEventListener('click', function() {
                    if (isPlaying) {
                        video.pause();
                    } else {
                        video.play();
                    }
                });
            }

            // Play button click handler
            if (playButton) {
                playButton.addEventListener('click', function() {
                    video.play();
                    thumbnailOverlay.classList.add('hidden');
                });
            }

            // Play/Pause button
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', function() {
                    if (isPlaying) {
                        video.pause();
                    } else {
                        video.play();
                    }
                });
            }

            // Video event listeners
            video.addEventListener('play', function() {
                isPlaying = true;
                if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                thumbnailOverlay.classList.add('hidden');
                showControls();
            });

            video.addEventListener('pause', function() {
                isPlaying = false;
                if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                hideControls();
            });

            video.addEventListener('ended', function() {
                isPlaying = false;
                if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                thumbnailOverlay.classList.remove('hidden');
                hideControls();
            });

            // Buffering events
            video.addEventListener('waiting', function() {
                if (bufferingIndicator) bufferingIndicator.classList.add('visible');
            });

            video.addEventListener('canplay', function() {
                if (bufferingIndicator) bufferingIndicator.classList.remove('visible');
            });

            // Progress bar
            video.addEventListener('timeupdate', function() {
                if (videoDuration > 0) {
                    const percent = (video.currentTime / videoDuration) * 100;
                    if (progressFilled) progressFilled.style.width = percent + '%';
                    
                    if (currentTime) {
                        currentTime.textContent = formatTime(video.currentTime);
                    }
                }
            });

            // Click on progress bar
            if (progressBar) {
                progressBar.addEventListener('click', function(e) {
                    const rect = this.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    video.currentTime = percent * videoDuration;
                });

                // Progress bar hover effects
                progressBar.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const hoverTime = percent * videoDuration;
                    
                    if (progressHover) {
                        progressHover.style.left = percent * 100 + '%';
                        progressHover.style.width = '2px';
                        progressHover.style.opacity = '1';
                    }
                    
                    if (progressTooltip) {
                        progressTooltip.style.left = percent * 100 + '%';
                        progressTooltip.textContent = formatTime(hoverTime);
                        progressTooltip.style.opacity = '1';
                    }
                });

                progressBar.addEventListener('mouseleave', function() {
                    if (progressHover) progressHover.style.opacity = '0';
                    if (progressTooltip) progressTooltip.style.opacity = '0';
                });
            }

            // Volume control
            if (muteBtn) {
                muteBtn.addEventListener('click', function() {
                    if (video.muted) {
                        video.muted = false;
                        this.innerHTML = '<i class="fas fa-volume-up"></i>';
                        this.classList.remove('active');
                    } else {
                        video.muted = true;
                        this.innerHTML = '<i class="fas fa-volume-mute"></i>';
                        this.classList.add('active');
                    }
                });
            }

            if (volumeSlider) {
                volumeSlider.addEventListener('input', function() {
                    video.volume = this.value / 100;
                    if (this.value == 0) {
                        if (muteBtn) {
                            muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                            muteBtn.classList.add('active');
                        }
                    } else {
                        if (muteBtn) {
                            muteBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                            muteBtn.classList.remove('active');
                        }
                    }
                });
            }

            // Settings button
            if (settingsBtn) {
                settingsBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (settingsPanel) {
                        settingsPanel.classList.toggle('visible');
                    }
                });
            }

            // Close settings when clicking outside
            document.addEventListener('click', function(e) {
                if (settingsPanel && !settingsPanel.contains(e.target) && !settingsBtn.contains(e.target)) {
                    settingsPanel.classList.remove('visible');
                }
            });

            // Quality selector
            if (qualitySelector) {
                qualitySelector.addEventListener('change', function() {
                    const quality = this.value;
                    // Update quality indicator
                    const qualityIndicator = document.getElementById('qualityIndicator');
                    if (qualityIndicator) {
                        qualityIndicator.textContent = quality;
                    }
                });
            }

            // Playback speed selector
            if (playbackSpeed) {
                playbackSpeed.addEventListener('change', function() {
                    const speed = parseFloat(this.value);
                    video.playbackRate = speed;
                });
            }

            // Subtitles toggle
            if (subtitlesToggle) {
                subtitlesToggle.addEventListener('change', function() {
                    // Implementation for subtitles
                    console.log('Subtitles:', this.checked);
                });
            }

            // Loop toggle
            if (loopToggle) {
                loopToggle.addEventListener('change', function() {
                    video.loop = this.checked;
                });
            }

            // Fullscreen
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', function() {
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        video.requestFullscreen();
                    }
                });
            }

            // Set total time when metadata is loaded
            video.addEventListener('loadedmetadata', function() {
                videoDuration = video.duration;
                if (totalTime) {
                    totalTime.textContent = formatTime(videoDuration);
                }
                // Set initial current time
                if (currentTime) {
                    currentTime.textContent = formatTime(0);
                }
            });

            // Video action buttons
            if (likeBtn) {
                likeBtn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    if (this.classList.contains('active')) {
                        this.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                        this.style.background = '#1cc88a';
                    } else {
                        this.innerHTML = '<i class="fas fa-thumbs-up"></i> Like';
                        this.style.background = '#4e73df';
                    }
                });
            }

            if (dislikeBtn) {
                dislikeBtn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    if (this.classList.contains('active')) {
                        this.innerHTML = '<i class="fas fa-thumbs-down"></i> Disliked';
                        this.style.background = '#e74a3b';
                    } else {
                        this.innerHTML = '<i class="fas fa-thumbs-down"></i> Dislike';
                        this.style.background = '#6c757d';
                    }
                });
            }

            if (shareBtn) {
                shareBtn.addEventListener('click', function() {
                    if (navigator.share) {
                        navigator.share({
                            title: document.title,
                            url: window.location.href
                        });
                    } else {
                        // Fallback: copy to clipboard
                        navigator.clipboard.writeText(window.location.href).then(function() {
                            alert('Link copied to clipboard!');
                        });
                    }
                });
            }

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    const videoSrc = video.querySelector('source').src;
                    const link = document.createElement('a');
                    link.href = videoSrc;
                    link.download = '<?= htmlspecialchars($video['Title']) ?>.mp4';
                    link.click();
                });
            }

            // Keyboard shortcuts with focus management
            document.addEventListener('keydown', function(e) {
                // Only handle keys when video is focused or when video controls are visible
                if (e.target === video || e.target.closest('.video-container') || videoControls.classList.contains('visible')) {
                    e.preventDefault();
                    
                    switch(e.code) {
                        case 'Space':
                            if (isPlaying) video.pause();
                            else video.play();
                            break;
                        case 'ArrowLeft':
                            video.currentTime -= 10;
                            break;
                        case 'ArrowRight':
                            video.currentTime += 10;
                            break;
                        case 'ArrowUp':
                            video.volume = Math.min(1, video.volume + 0.1);
                            if (volumeSlider) volumeSlider.value = video.volume * 100;
                            break;
                        case 'ArrowDown':
                            video.volume = Math.max(0, video.volume - 0.1);
                            if (volumeSlider) volumeSlider.value = video.volume * 100;
                            break;
                        case 'KeyM':
                            if (muteBtn) muteBtn.click();
                            break;
                        case 'KeyF':
                            if (fullscreenBtn) fullscreenBtn.click();
                            break;
                        case 'KeyS':
                            if (settingsBtn) settingsBtn.click();
                            break;
                    }
                }
            });

            // Format time function
            function formatTime(seconds) {
                if (isNaN(seconds) || seconds === Infinity) return '0:00';
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }

            // Show/hide controls
            function showControls() {
                if (videoControls) {
                    videoControls.classList.add('visible');
                    clearTimeout(controlsTimeout);
                    controlsTimeout = setTimeout(hideControls, 3000);
                }
            }

            function hideControls() {
                if (videoControls && !isPlaying) return;
                if (videoControls) {
                    videoControls.classList.remove('visible');
                }
            }

            // Show controls on mouse move
            video.addEventListener('mousemove', function() {
                showControls();
            });

            // Show controls when hovering over video container
            const videoContainer = document.querySelector('.video-container');
            if (videoContainer) {
                videoContainer.addEventListener('mouseenter', function() {
                    showControls();
                });
                
                videoContainer.addEventListener('mouseleave', function() {
                    hideControls();
                });
            }

            // Prevent page scrolling when using arrow keys on video
            video.addEventListener('keydown', function(e) {
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Space'].includes(e.code)) {
                    e.preventDefault();
                }
            });

            // Focus video when clicking on it
            video.addEventListener('click', function() {
                this.focus();
            });
        });
    </script>
</body>
</html>