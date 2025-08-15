<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

// Initialize VideoManager for shorts with correct paths
$videoManager = new VideoManager($con, 'uploads/videos/', 'images/video_thumbnails/');

// Get ALL shorts regardless of status to see what's available
$filters = ['videoType' => 'short'];  // Removed status filter to see all shorts
$shortsData = $videoManager->getAllVideos(1, 50, $filters);
$shorts = $shortsData['videos'] ?? [];

// Debug: Let's see what we're getting
echo '<!-- Debug: Found ' . count($shorts) . ' shorts -->';
if (empty($shorts)) {
    echo '<!-- Debug: No shorts found. Checking database... -->';

    // Direct database query to see what's available
    $debugQuery = "SELECT VideoID, Title, videoType, Status, isDeleted FROM video_posts WHERE videoType = 'short' LIMIT 10";
    $debugResult = mysqli_query($con, $debugQuery);

    if ($debugResult) {
        echo '<!-- Debug: Database query results: -->';
        while ($row = mysqli_fetch_assoc($debugResult)) {
            echo "<!-- VideoID: {$row['VideoID']}, Title: {$row['Title']}, Status: {$row['Status']}, isDeleted: {$row['isDeleted']} -->";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short Videos - YouTube Shorts Style</title>
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* YouTube Shorts Style Container */
        .shorts-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
            background: #000;
        }

        /* Video Item */
        .short-item {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: none;
            background: #000;
        }

        .short-item.active {
            display: block;
        }

        /* Video Player */
        .short-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        .short-video[poster] {
            background: none;
        }

        /* Video Overlay */
        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            color: white;
            z-index: 10;
        }

        .video-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .video-author {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 8px;
        }

        .video-description {
            font-size: 13px;
            color: #aaa;
            line-height: 1.4;
        }

        /* Right Side Actions (YouTube Shorts Style) */
        .right-actions {
            position: absolute;
            right: 16px;
            bottom: 120px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            z-index: 20;
        }

        .action-button {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .action-button:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }

        .action-button.liked {
            background: #ff4757;
            color: white;
        }

        .action-button.saved {
            background: #ffa502;
            color: white;
        }

        .action-count {
            font-size: 12px;
            text-align: center;
            margin-top: 4px;
            color: #ccc;
        }

        /* Progress Bar */
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: #ff4757;
            width: 0%;
            transition: width 0.1s linear;
            z-index: 30;
        }

        /* Navigation Controls */
        .nav-controls {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 20;
        }

        .nav-btn {
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(0,0,0,0.9);
            transform: scale(1.05);
        }

        /* Video Counter */
        .video-counter {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            z-index: 20;
            background: rgba(0,0,0,0.7);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        /* Comments Section (YouTube Shorts Style) */
        .comments-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1a1a1a;
            border-radius: 20px 20px 0 0;
            max-height: 70vh;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .comments-section.show {
            transform: translateY(0);
        }

        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #333;
            background: #000;
        }

        .comments-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .close-comments {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }

        .close-comments:hover {
            background: rgba(255,255,255,0.1);
        }

        .comments-list {
            padding: 20px;
            max-height: 50vh;
            overflow-y: auto;
        }

        .comment-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
            min-width: 0;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .comment-username {
            font-weight: 600;
            font-size: 14px;
            color: white;
        }

        .comment-time {
            font-size: 12px;
            color: #999;
        }

        .comment-text {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 8px;
            color: #ccc;
            word-wrap: break-word;
        }

        .comment-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .comment-like {
            background: none;
            border: none;
            color: #999;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s ease;
        }

        .comment-like:hover {
            color: #ff4757;
        }

        .comment-like.liked {
            color: #ff4757;
        }

        .comment-reply {
            background: none;
            border: none;
            color: #999;
            font-size: 12px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .comment-reply:hover {
            color: white;
        }

        /* Comment Input Section */
        .comment-input-section {
            padding: 20px;
            background: #000;
            border-top: 1px solid #333;
        }

        .comment-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .comment-input {
            flex: 1;
            background: #333;
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            color: white;
            font-size: 14px;
            outline: none;
        }

        .comment-input::placeholder {
            color: #999;
        }

        .comment-submit {
            background: #ff4757;
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .comment-submit:hover {
            background: #ff3742;
        }

        .comment-submit:disabled {
            background: #666;
            cursor: not-allowed;
        }

        /* Loading States */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Performance Optimizations */
        .short-item:not(.active) {
            display: none !important;
        }

        .short-item.active .short-video {
            display: block;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .right-actions {
                right: 12px;
                bottom: 100px;
                gap: 16px;
            }

            .action-button {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }

            .video-overlay {
                padding: 16px;
            }

            .video-title {
                font-size: 16px;
            }

            .comments-section {
                max-height: 80vh;
            }

            .comments-header {
                padding: 16px;
            }

            .comments-list {
                padding: 16px;
                max-height: 60vh;
            }

            .comment-input-section {
                padding: 16px;
            }
        }

        /* Ultra-wide screens */
        @media (min-width: 1440px) {
            .shorts-container {
                max-width: 1440px;
                margin: 0 auto;
            }
        }

        /* Performance: Reduce motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .action-button {
                border: 2px solid white;
            }
            
            .progress-bar {
                border: 1px solid white;
            }
        }

        /* Dark mode optimizations */
        @media (prefers-color-scheme: dark) {
            .comments-section {
                background: #0a0a0a;
            }
            
            .comment-input {
                background: #1a1a1a;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            color: white;
            font-size: 18px;
        }

        /* Error states */
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,0,0,0.9);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 40;
        }

        /* Video quality indicator */
        .quality-indicator {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            z-index: 20;
            backdrop-filter: blur(10px);
        }

        /* Share menu */
        .share-menu {
            position: absolute;
            bottom: 200px;
            right: 16px;
            background: rgba(0,0,0,0.9);
            border-radius: 12px;
            padding: 16px;
            display: none;
            flex-direction: column;
            gap: 12px;
            z-index: 30;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .share-menu.show {
            display: flex;
        }

        .share-option {
            background: none;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease;
            font-size: 14px;
        }

        .share-option:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Video controls overlay */
        .video-controls-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 15;
        }

        .short-item:hover .video-controls-overlay {
            opacity: 1;
        }

        .control-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }

        .control-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s ease;
        }

        .control-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Keyboard navigation support */
        .keyboard-navigation .action-button:focus,
        .keyboard-navigation .control-btn:focus,
        .keyboard-navigation .nav-btn:focus {
            outline: 3px solid #ff4757;
            outline-offset: 2px;
        }
        
        /* Performance optimizations */
        .short-item:not(.active) {
            pointer-events: none;
        }
        
        .short-item:not(.active) video {
            display: none !important;
        }
        
        /* Smooth scrolling for comments */
        .comments-list {
            scroll-behavior: smooth;
        }
        
        /* Optimized animations */
        .short-item {
            will-change: transform, opacity;
        }
        
        .action-button {
            will-change: transform, background-color;
        }
        
        /* Mobile touch optimizations */
        @media (max-width: 768px) {
            .action-button {
                touch-action: manipulation;
            }
            
            .short-video {
                touch-action: pan-y;
            }
        }
        
        /* Ultra-wide screen optimizations */
        @media (min-width: 1920px) {
            .shorts-container {
                max-width: 1920px;
                margin: 0 auto;
            }
            
            .right-actions {
                right: 32px;
            }
            
            .nav-controls {
                left: 32px;
            }
        }
        
        /* Focus management for accessibility */
        .action-button:focus-visible,
        .control-btn:focus-visible,
        .nav-btn:focus-visible {
            outline: 3px solid #ff4757;
            outline-offset: 2px;
        }
        
        /* Loading states */
        .loading .action-button {
            pointer-events: none;
            opacity: 0.6;
        }
        
        /* Success states */
        .success .action-button.liked {
            animation: pulse 0.6s ease-in-out;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        /* Network status indicator */
        .network-status {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 2000;
            display: none;
        }
        
        .network-status.show {
            display: block;
        }
        
        .network-status.slow {
            background: rgba(255, 165, 2, 0.9);
        }
        
        .network-status.offline {
            background: rgba(255, 71, 87, 0.9);
        }
        
        .network-status.online {
            background: rgba(46, 213, 115, 0.9);
        }
        
        .network-status.medium {
            background: rgba(255, 165, 2, 0.9);
        }
        
        .network-status.fast {
            background: rgba(46, 213, 115, 0.9);
        }
    </style>
</head>
<body>
    <!-- YouTube Shorts Style Container -->
    <div class="shorts-container" id="shortsContainer">
        <?php if (empty($shorts)): ?>
            <div style="display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center;">
                <div>
                    <h2>🎬 No Short Videos Yet</h2>
                    <p>Be the first to create amazing short videos!</p>
                    <button onclick="window.location.href='video_shorts.php'" style="background: #ff4757; color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer;">
                        Create Your First Short
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($shorts as $index => $short): ?>
                <div class="short-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>" data-video-id="<?= $short['VideoID'] ?>">
                    <!-- Progress Bar -->
                    <div class="progress-bar" id="progress-<?= $short['VideoID'] ?>"></div>
                    
                    <?php
                    // Fix video file path
                    $videoPath = $short['VideoFile'];
                    if (!empty($videoPath) && !file_exists($videoPath)) {
                        // Try different path combinations
                        $possiblePaths = [
                            $videoPath,
                            'uploads/videos/' . basename($videoPath),
                            'gp-admin/admin/uploads/videos/' . basename($videoPath),
                            '../uploads/videos/' . basename($videoPath)
                        ];

                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $videoPath = $path;
                                break;
                            }
                        }
                    }

                    // Fix thumbnail path
                    $thumbnailPath = $short['VideoThumbnail'];
                    if (!empty($thumbnailPath) && !file_exists($thumbnailPath)) {
                        // Try different path combinations
                        $possibleThumbPaths = [
                            $thumbnailPath,
                            'images/video_thumbnails/' . basename($thumbnailPath),
                            'gp-admin/admin/images/video_thumbnails/' . basename($thumbnailPath),
                            '../images/video_thumbnails/' . basename($thumbnailPath)
                        ];

                        foreach ($possibleThumbPaths as $path) {
                            if (file_exists($path)) {
                                $thumbnailPath = $path;
                                break;
                            }
                        }
                    }

                    // Fallback thumbnail if none found
                    if (empty($thumbnailPath) || !file_exists($thumbnailPath)) {
                        $thumbnailPath = 'images/default-video-thumbnail.jpg';
                    }
                    ?>
                    
                    <!-- Video Element -->
                    <video 
                        class="short-video" 
                        id="video-<?= $short['VideoID'] ?>"
                        preload="metadata"
                        loop
                        muted
                        playsinline
                        poster="<?= htmlspecialchars($thumbnailPath) ?>"
                        style="display: <?= $index === 0 ? 'block' : 'none' ?>;"
                    >
                        <?php if (!empty($videoPath) && file_exists($videoPath)): ?>
                            <source src="<?= htmlspecialchars($videoPath) ?>" type="video/mp4">
                        <?php endif; ?>
                        Your browser does not support the video tag.
                    </video>
                    
                    <!-- Video Overlay -->
                    <div class="video-overlay">
                        <h3 class="video-title"><?= htmlspecialchars($short['Title']) ?></h3>
                        <p class="video-author">@<?= htmlspecialchars($short['AuthorDisplayName'] ?? $short['AuthorName'] ?? 'Unknown') ?></p>
                        <?php if (!empty($short['Excerpt'])): ?>
                            <p class="video-description"><?= htmlspecialchars($short['Excerpt']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Side Actions (YouTube Shorts Style) -->
                    <div class="right-actions">
                        <div class="action-group">
                            <button class="action-button like-btn" onclick="toggleLike(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-heart"></i>
                            </button>
                            <div class="action-count" id="like-count-<?= $short['VideoID'] ?>">0</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button comment-btn" onclick="showComments(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-comment"></i>
                            </button>
                            <div class="action-count" id="comment-count-<?= $short['VideoID'] ?>">0</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button share-btn" onclick="toggleShareMenu(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-share"></i>
                            </button>
                            <div class="action-count">Share</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button save-btn" onclick="toggleSave(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-bookmark"></i>
                            </button>
                            <div class="action-count">Save</div>
                        </div>
                    </div>
                    
                    <!-- Share Menu -->
                    <div class="share-menu" id="share-menu-<?= $short['VideoID'] ?>">
                        <button class="share-option" onclick="shareVideo(<?= $short['VideoID'] ?>, 'copy')">
                            <i class="fas fa-link"></i> Copy Link
                        </button>
                        <button class="share-option" onclick="shareVideo(<?= $short['VideoID'] ?>, 'whatsapp')">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                        <button class="share-option" onclick="shareVideo(<?= $short['VideoID'] ?>, 'telegram')">
                            <i class="fab fa-telegram"></i> Telegram
                        </button>
                        <button class="share-option" onclick="shareVideo(<?= $short['VideoID'] ?>, 'twitter')">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                    </div>
                    
                    <!-- Video Controls Overlay -->
                    <div class="video-controls-overlay">
                        <div class="control-buttons">
                            <button class="control-btn" onclick="togglePlayPause(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-play"></i> Play/Pause
                            </button>
                            <button class="control-btn" onclick="toggleMute(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-volume-up"></i> Mute
                            </button>
                            <button class="control-btn" onclick="toggleFullscreen(<?= $short['VideoID'] ?>)">
                                <i class="fas fa-expand"></i> Fullscreen
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quality Indicator -->
                    <div class="quality-indicator">
                        <?= htmlspecialchars($short['VideoResolution'] ?? 'HD') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Navigation Controls -->
        <div class="nav-controls">
            <button class="nav-btn" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
        
        <!-- Video Counter -->
        <div class="video-counter">
            <span id="currentVideo">1</span> / <?= count($shorts) ?>
        </div>
    </div>

    <!-- Comments Section (YouTube Shorts Style) -->
    <div class="comments-section" id="commentsSection">
        <div class="comments-header">
            <h3 class="comments-title">Comments</h3>
            <button class="close-comments" onclick="closeComments()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="comments-list" id="commentsList">
            <!-- Comments will be loaded here -->
        </div>
        
        <div class="comment-input-section">
            <div class="comment-input-wrapper">
                <input type="text" class="comment-input" id="commentInput" placeholder="Add a comment..." maxlength="500">
                <button class="comment-submit" id="commentSubmit" onclick="submitComment()">
                    <span class="submit-text">Post</span>
                    <span class="loading-spinner" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div>
            <div class="loading-spinner"></div>
            <div>Loading...</div>
        </div>
    </div>
    
    <!-- Network Status Indicator -->
    <div class="network-status" id="networkStatus">
        <i class="fas fa-wifi"></i>
        <span id="networkStatusText">Connected</span>
    </div>

    <!-- Scripts -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    
    <script>
        // Performance-optimized YouTube Shorts Style Player
        class ShortsPlayer {
            constructor() {
                this.currentIndex = 0;
                this.totalVideos = <?= count($shorts) ?>;
                this.videoElements = document.querySelectorAll('.short-video');
                this.progressBars = document.querySelectorAll('.progress-bar');
                this.isPlaying = false;
                this.currentVideoId = null;
                this.commentsVisible = false;
                this.shareMenuVisible = false;
                
                // Performance optimizations
                this.intersectionObserver = null;
                this.resizeObserver = null;
                this.debounceTimer = null;
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.setupIntersectionObserver();
                this.setupResizeObserver();
                this.showVideo(0);
                this.trackVideoView(this.getCurrentVideoId());
                
                // Preload next video for smooth transitions
                this.preloadNextVideo();
            }
            
            setupEventListeners() {
                // Keyboard navigation - REMOVED passive: true to allow preventDefault
                document.addEventListener('keydown', this.handleKeyDown.bind(this));
                
                // Touch/swipe events with passive listeners
                let startY = 0;
                let startTime = 0;
                
                document.addEventListener('touchstart', (e) => {
                    startY = e.touches[0].clientY;
                    startTime = Date.now();
                }, { passive: true });
                
                document.addEventListener('touchend', (e) => {
                    const endY = e.changedTouches[0].clientY;
                    const endTime = Date.now();
                    const deltaY = startY - endY;
                    const deltaTime = endTime - startTime;
                    
                    // Only trigger if swipe is significant and fast
                    if (Math.abs(deltaY) > 50 && deltaTime < 300) {
                        if (deltaY > 0) {
                            this.nextVideo();
                        } else {
                            this.previousVideo();
                        }
                    }
                }, { passive: true });
                
                // Video progress tracking with throttling
                this.videoElements.forEach((video, index) => {
                    video.addEventListener('timeupdate', this.throttle.bind(this, () => {
                        this.updateProgress(video, index);
                    }, 100));
                    
                    video.addEventListener('ended', () => {
                        this.nextVideo();
                    });
                });
            }
            
            setupIntersectionObserver() {
                // Only create observer if supported
                if ('IntersectionObserver' in window) {
                    this.intersectionObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const video = entry.target;
                                const videoId = video.closest('.short-item').dataset.videoId;
                                this.trackVideoView(videoId);
                            }
                        });
                    }, { threshold: 0.5 });
                    
                    this.videoElements.forEach(video => {
                        this.intersectionObserver.observe(video);
                    });
                }
            }
            
            setupResizeObserver() {
                // Only create observer if supported
                if ('ResizeObserver' in window) {
                    this.resizeObserver = new ResizeObserver(this.debounce.bind(this, () => {
                        this.handleResize();
                    }, 250));
                    
                    this.resizeObserver.observe(document.body);
                }
            }
            
            handleResize() {
                // Optimize video sizing on resize
                this.videoElements.forEach(video => {
                    if (video.offsetWidth > 0) {
                        video.style.height = 'auto';
                    }
                });
            }
            
            handleKeyDown(e) {
                switch(e.key) {
                    case 'ArrowUp':
                        e.preventDefault();
                        this.previousVideo();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.nextVideo();
                        break;
                    case ' ':
                        e.preventDefault();
                        this.togglePlayPause();
                        break;
                    case 'Escape':
                        if (this.commentsVisible) {
                            this.closeComments();
                        }
                        if (this.shareMenuVisible) {
                            this.hideAllShareMenus();
                        }
                        break;
                }
            }
            
            showVideo(index) {
                if (index < 0 || index >= this.totalVideos) return;
                
                // Hide all videos efficiently
                document.querySelectorAll('.short-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Show current video
                this.currentIndex = index;
                const currentItem = document.querySelector(`[data-index="${index}"]`);
                currentItem.classList.add('active');
                
                // Update counter
                document.getElementById('currentVideo').textContent = index + 1;
                
                // Play current video
                this.playCurrentVideo();
                
                // Track view
                this.trackVideoView(this.getCurrentVideoId());
                
                // Preload next video
                this.preloadNextVideo();
            }
            
            playCurrentVideo() {
                const currentVideo = document.querySelector('.short-item.active video');
                if (currentVideo) {
                    // Use requestAnimationFrame for smooth playback
                    requestAnimationFrame(() => {
                        currentVideo.play().then(() => {
                            this.isPlaying = true;
                        }).catch(err => {
                            console.log('Auto-play prevented:', err);
                            this.isPlaying = false;
                        });
                    });
                }
            }
            
            togglePlayPause(videoId = null) {
                const video = videoId ? 
                    document.getElementById(`video-${videoId}`) : 
                    document.querySelector('.short-item.active video');
                
                if (video) {
                    if (this.isPlaying) {
                        video.pause();
                        this.isPlaying = false;
                    } else {
                        video.play();
                        this.isPlaying = true;
                    }
                }
            }
            
            toggleMute(videoId = null) {
                const video = videoId ? 
                    document.getElementById(`video-${videoId}`) : 
                    document.querySelector('.short-item.active video');
                
                if (video) {
                    video.muted = !video.muted;
                }
            }
            
            toggleFullscreen(videoId = null) {
                const video = videoId ? 
                    document.getElementById(`video-${videoId}`) : 
                    document.querySelector('.short-item.active video');
                
                if (video) {
                    if (!document.fullscreenElement) {
                        video.requestFullscreen().catch(err => {
                            console.log('Fullscreen failed:', err);
                        });
                    } else {
                        document.exitFullscreen();
                    }
                }
            }
            
            nextVideo() {
                if (this.currentIndex < this.totalVideos - 1) {
                    this.showVideo(this.currentIndex + 1);
                } else {
                    // Loop back to first video
                    this.showVideo(0);
                }
            }
            
            previousVideo() {
                if (this.currentIndex > 0) {
                    this.showVideo(this.currentIndex - 1);
                } else {
                    // Loop to last video
                    this.showVideo(this.totalVideos - 1);
                }
            }
            
            updateProgress(video, index) {
                if (this.progressBars[index] && video.duration > 0) {
                    const progress = (video.currentTime / video.duration) * 100;
                    this.progressBars[index].style.width = progress + '%';
                }
            }
            
            getCurrentVideoId() {
                const activeItem = document.querySelector('.short-item.active');
                return activeItem ? activeItem.getAttribute('data-video-id') : null;
            }
            
            preloadNextVideo() {
                const nextIndex = (this.currentIndex + 1) % this.totalVideos;
                const nextVideo = document.querySelector(`[data-index="${nextIndex}"] video`);
                if (nextVideo) {
                    nextVideo.preload = 'metadata';
                }
            }
            
            async trackVideoView(videoId) {
                if (!videoId || videoId === this.currentVideoId) return;
                
                this.currentVideoId = videoId;
                
                try {
                    await fetch('api/track_video_view.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            videoId: videoId,
                            action: 'start'
                        })
                    });
                } catch (error) {
                    console.error('Error tracking view:', error);
                }
            }
            
            // Performance utility methods
            throttle(func, limit) {
                let inThrottle;
                return function() {
                    const args = arguments;
                    const context = this;
                    if (!inThrottle) {
                        func.apply(context, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                }
            }
            
            debounce(func, wait) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(func, wait);
            }
            
            // Cleanup method
            destroy() {
                if (this.intersectionObserver) {
                    this.intersectionObserver.disconnect();
                }
                if (this.resizeObserver) {
                    this.resizeObserver.disconnect();
                }
                if (this.debounceTimer) {
                    clearTimeout(this.debounceTimer);
                }
            }
            
            // Network monitoring
            setupNetworkMonitoring() {
                // Online/offline detection
                window.addEventListener('online', () => {
                    this.showNetworkStatus('online', 'Connected');
                });
                
                window.addEventListener('offline', () => {
                    this.showNetworkStatus('offline', 'Offline');
                });
                
                // Connection quality monitoring
                if ('navigator' in window && 'connection' in navigator) {
                    const connection = navigator.connection;
                    
                    connection.addEventListener('change', () => {
                        const effectiveType = connection.effectiveType;
                        const downlink = connection.downlink;
                        
                        if (effectiveType === 'slow-2g' || effectiveType === '2g') {
                            this.showNetworkStatus('slow', 'Slow Connection');
                        } else if (effectiveType === '3g') {
                            this.showNetworkStatus('medium', '3G Connection');
                        } else if (effectiveType === '4g') {
                            this.showNetworkStatus('fast', '4G Connection');
                        } else if (effectiveType === '5g') {
                            this.showNetworkStatus('fast', '5G Connection');
                        }
                    });
                }
            }
            
            showNetworkStatus(type, message) {
                const networkStatus = document.getElementById('networkStatus');
                const networkStatusText = document.getElementById('networkStatusText');
                
                if (networkStatus && networkStatusText) {
                    networkStatus.className = `network-status ${type}`;
                    networkStatusText.textContent = message;
                    networkStatus.classList.add('show');
                    
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        networkStatus.classList.remove('show');
                    }, 3000);
                }
            }
        }
        
        // Initialize player
        let shortsPlayer;
        document.addEventListener('DOMContentLoaded', () => {
            shortsPlayer = new ShortsPlayer();
        });
        
        // Global functions for UI interactions
        async function toggleLike(videoId) {
            try {
                const response = await fetch('api/toggle_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ videoId: videoId })
                });
                
                const data = await response.json();
                if (data.success) {
                    const likeBtn = document.querySelector(`[onclick="toggleLike(${videoId})"]`);
                    const likeCount = document.getElementById(`like-count-${videoId}`);
                    
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                        likeBtn.style.background = '#ff4757';
                    } else {
                        likeBtn.classList.remove('liked');
                        likeBtn.style.background = 'rgba(255,255,255,0.1)';
                    }
                    
                    // Update like count if available
                    if (likeCount) {
                        likeCount.textContent = data.likes || 0;
                    }
                }
            } catch (error) {
                console.error('Error toggling like:', error);
            }
        }
        
        async function toggleSave(videoId) {
            try {
                const response = await fetch('api/toggle_save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ videoId: videoId })
                });
                
                const data = await response.json();
                if (data.success) {
                    const saveBtn = document.querySelector(`[onclick="toggleSave(${videoId})"]`);
                    
                    if (data.saved) {
                        saveBtn.classList.add('saved');
                        saveBtn.style.background = '#ffa502';
                    } else {
                        saveBtn.classList.remove('saved');
                        saveBtn.style.background = 'rgba(255,255,255,0.1)';
                    }
                }
            } catch (error) {
                console.error('Error toggling save:', error);
            }
        }
        
        // Video control functions for HTML onclick attributes
        function togglePlayPause(videoId) {
            if (shortsPlayer) {
                shortsPlayer.togglePlayPause(videoId);
            }
        }
        
        function toggleMute(videoId) {
            if (shortsPlayer) {
                shortsPlayer.toggleMute(videoId);
            }
        }
        
        function toggleFullscreen(videoId) {
            if (shortsPlayer) {
                shortsPlayer.toggleFullscreen(videoId);
            }
        }
        
        // Comments functionality
        let currentVideoId = null;
        
        async function showComments(videoId) {
            currentVideoId = videoId;
            const modal = document.getElementById('commentsSection');
            const commentsList = document.getElementById('commentsList');
            
            // Show modal
            modal.classList.add('show');
            if (shortsPlayer) {
                shortsPlayer.commentsVisible = true;
            }
            
            // Load comments
            await loadComments(videoId);
        }
        
        async function loadComments(videoId) {
            try {
                showLoading(true);
                const response = await fetch(`api/get_comments.php?videoId=${videoId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayComments(data.comments);
                } else {
                    displayComments([]);
                }
            } catch (error) {
                console.error('Error loading comments:', error);
                displayComments([]);
            } finally {
                showLoading(false);
            }
        }
        
        function displayComments(comments) {
            const commentsList = document.getElementById('commentsList');
            
            if (comments.length === 0) {
                commentsList.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">No comments yet. Be the first to comment!</p>';
                return;
            }
            
            commentsList.innerHTML = comments.map(comment => {
                // Safely handle username and displayName
                const username = comment.username || comment.displayName || 'User';
                const displayName = comment.displayName || comment.username || 'User';
                const firstLetter = username.charAt(0).toUpperCase();
                
                return `
                <div class="comment-item" data-comment-id="${comment.commentID}">
                    <div class="comment-avatar">
                        ${comment.profilePicture ? `<img src="${comment.profilePicture}" alt="${username}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` : firstLetter}
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-username">${displayName}</span>
                            <span class="comment-time">${formatTimeAgo(comment.createdAt)}</span>
                        </div>
                        <div class="comment-text">${comment.text}</div>
                        <div class="comment-actions">
                            <button class="comment-like" onclick="toggleCommentLike(${comment.commentID})">
                                <i class="fas fa-heart"></i>
                                <span>${comment.likes || 0}</span>
                            </button>
                            <button class="comment-reply" onclick="replyToComment(${comment.commentID})">
                                Reply
                            </button>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }
        
        async function submitComment() {
            const commentInput = document.getElementById('commentInput');
            const commentSubmit = document.getElementById('commentSubmit');
            const commentText = commentInput.value.trim();
            
            if (!commentText) {
                alert('Please enter a comment');
                return;
            }
            
            if (!currentVideoId) {
                alert('No video selected');
                return;
            }
            
            try {
                // Show loading state
                commentSubmit.disabled = true;
                commentSubmit.querySelector('.submit-text').style.display = 'none';
                commentSubmit.querySelector('.loading-spinner').style.display = 'inline-block';
                
                const response = await fetch('api/add_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        videoId: currentVideoId,
                        commentText: commentText
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    commentInput.value = '';
                    // Reload comments to show the new one
                    await loadComments(currentVideoId);
                } else {
                    alert('Error adding comment: ' + data.error);
                }
            } catch (error) {
                console.error('Error submitting comment:', error);
                alert('Error submitting comment');
            } finally {
                // Reset loading state
                commentSubmit.disabled = false;
                commentSubmit.querySelector('.submit-text').style.display = 'inline';
                commentSubmit.querySelector('.loading-spinner').style.display = 'none';
            }
        }
        
        function closeComments() {
            const modal = document.getElementById('commentsSection');
            modal.classList.remove('show');
            if (shortsPlayer) {
                shortsPlayer.commentsVisible = false;
            }
            currentVideoId = null;
        }
        
        function toggleCommentLike(commentId) {
            // TODO: Implement comment like functionality
            alert('Comment like functionality coming soon!');
        }
        
        function replyToComment(commentId) {
            // TODO: Implement reply functionality
            alert('Reply functionality coming soon!');
        }
        
        // Share functionality
        function toggleShareMenu(videoId) {
            // Hide all other share menus first
            hideAllShareMenus();
            
            const shareMenu = document.getElementById(`share-menu-${videoId}`);
            if (shareMenu) {
                shareMenu.classList.toggle('show');
                if (shortsPlayer) {
                    shortsPlayer.shareMenuVisible = shareMenu.classList.contains('show');
                }
            }
        }
        
        function hideAllShareMenus() {
            document.querySelectorAll('.share-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            if (shortsPlayer) {
                shortsPlayer.shareMenuVisible = false;
            }
        }
        
        async function shareVideo(videoId, platform) {
            const videoUrl = `${window.location.origin}/video_shorts_reels.php?video=${videoId}`;
            
            switch (platform) {
                case 'copy':
                    try {
                        await navigator.clipboard.writeText(videoUrl);
                        alert('Link copied to clipboard!');
                    } catch (error) {
                        // Fallback for older browsers
                        const textArea = document.createElement('textarea');
                        textArea.value = videoUrl;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        alert('Link copied to clipboard!');
                    }
                    break;
                    
                case 'whatsapp':
                    window.open(`https://wa.me/?text=${encodeURIComponent('Check out this short video: ' + videoUrl)}`);
                    break;
                    
                case 'telegram':
                    window.open(`https://t.me/share/url?url=${encodeURIComponent(videoUrl)}&text=${encodeURIComponent('Check out this short video!')}`);
                    break;
                    
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out this short video!')}&url=${encodeURIComponent(videoUrl)}`);
                    break;
                    
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(videoUrl)}`);
                    break;
                    
                case 'email':
                    window.open(`mailto:?subject=${encodeURIComponent('Check out this short video!')}&body=${encodeURIComponent('I found this amazing short video: ' + videoUrl)}`);
                    break;
            }
            
            // Hide share menu after sharing
            hideAllShareMenus();
        }
        
        // Utility functions
        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + 'd ago';
            if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + 'mo ago';
            return Math.floor(diffInSeconds / 31536000) + 'y ago';
        }
        
        function showLoading(show) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = show ? 'flex' : 'none';
            }
        }
        
        function goBack() {
            window.history.back();
        }
        
        // Close modals when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            // Close comments when clicking outside
            document.getElementById('commentsSection').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeComments();
                }
            });
            
            // Handle Enter key in comment input
            document.getElementById('commentInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    submitComment();
                }
            });
            
            // Close share menus when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.share-menu') && !e.target.closest('.share-btn')) {
                    hideAllShareMenus();
                }
            });
            
            // Performance optimizations for mobile
            // Service Worker registration removed - file doesn't exist
            // if ('serviceWorker' in navigator) {
            //     navigator.serviceWorker.register('/sw.js').catch(err => {
            //         console.log('Service Worker registration failed:', err);
            //     });
            // }
            
            // Preload critical resources
            const criticalVideos = document.querySelectorAll('.short-item:first-child video');
            criticalVideos.forEach(video => {
                video.preload = 'metadata';
            });
            
            // Lazy load non-critical videos
            const lazyVideos = document.querySelectorAll('.short-item:not(:first-child) video');
            lazyVideos.forEach(video => {
                video.preload = 'none';
            });
        });
        
        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', () => {
                try {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData && perfData.loadEventEnd && perfData.loadEventStart) {
                        const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
                        console.log('Page load time:', loadTime, 'ms');
                        
                        // Report performance metrics
                        if (loadTime > 3000) {
                            console.warn('Slow page load detected. Consider optimizing video preloading.');
                        }
                    }
                } catch (error) {
                    console.log('Performance monitoring error:', error);
                }
            });
        }
        
        // Memory management for long sessions
        let memoryCheckInterval = setInterval(() => {
            if ('memory' in performance) {
                const memory = performance.memory;
                if (memory.usedJSHeapSize > 100 * 1024 * 1024) { // 100MB threshold
                    console.warn('High memory usage detected. Cleaning up...');
                    
                    // Clean up non-active videos
                    document.querySelectorAll('.short-item:not(.active) video').forEach(video => {
                        video.src = '';
                        video.load();
                    });
                    
                    // Force garbage collection if available
                    if (window.gc) {
                        window.gc();
                    }
                }
            }
        }, 30000); // Check every 30 seconds
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (shortsPlayer) {
                shortsPlayer.destroy();
            }
            clearInterval(memoryCheckInterval);
        });
        
        // Handle visibility change for performance
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Pause all videos when tab is not visible
                document.querySelectorAll('.short-video').forEach(video => {
                    if (!video.paused) {
                        video.pause();
                    }
                });
            } else {
                // Resume current video when tab becomes visible
                const currentVideo = document.querySelector('.short-item.active video');
                if (currentVideo && shortsPlayer && shortsPlayer.isPlaying) {
                    currentVideo.play();
                }
            }
        });
        
        // Network status monitoring
        if ('navigator' in window && 'connection' in navigator) {
            navigator.connection.addEventListener('change', () => {
                const connection = navigator.connection;
                if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
                    // Reduce video quality for slow connections
                    document.querySelectorAll('.short-video').forEach(video => {
                        video.preload = 'none';
                    });
                    console.log('Slow connection detected. Reduced video preloading.');
                    if (shortsPlayer) {
                        shortsPlayer.showNetworkStatus('slow', 'Slow Connection');
                    }
                }
            });
        }
        
        // Enhanced network status monitoring
        if (shortsPlayer) {
            shortsPlayer.setupNetworkMonitoring();
        }
        
        // Error handling for video loading
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'VIDEO') {
                console.error('Video loading error:', e.target.src);
                // Show fallback content
                const videoContainer = e.target.closest('.short-item');
                if (videoContainer) {
                    videoContainer.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center;">
                            <div>
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff4757; margin-bottom: 16px;"></i>
                                <h3>Video Unavailable</h3>
                                <p>This video could not be loaded.</p>
                            </div>
                        </div>
                    `;
                }
            }
        }, true);
        
        // Accessibility improvements
        document.addEventListener('keydown', (e) => {
            // Screen reader support
            if (e.key === 'Tab') {
                // Ensure focus is visible
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        // Remove keyboard navigation class when mouse is used
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
        
        // High contrast mode support
        if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
            document.body.classList.add('high-contrast');
        }
        
        // Reduced motion support
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
        }
    </script>
</body>
</html>