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
             scroll-behavior: smooth;
             -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
         }

        /* YouTube Shorts Style Container */
        .shorts-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
            background: #000;
            scroll-snap-type: y mandatory;
            scroll-behavior: smooth;
        }
        
        /* Desktop Layout - Maintain Phone Width */
        @media (min-width: 768px) {
            .shorts-container {
                width: 400px;
                height: 100vh;
                margin: 0 auto;
                box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
                border-radius: 20px;
                overflow: hidden;
            }
            
            /* Ensure videos maintain phone aspect ratio on desktop */
            .short-item {
                width: 100%;
                height: 100%;
            }
            
            .short-video {
                width: 100% !important;
                height: 100% !important;
                object-fit: contain !important; /* Changed from cover to contain to prevent zooming */
                background: #000;
                cursor: pointer;
                transition: opacity 0.2s ease;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                min-width: 100% !important;
                min-height: 100% !important;
                max-width: 100% !important;
                max-height: 100% !important;
            }
        }
        
        /* Fullscreen Styles - Maintain Shorts Design */
        .shorts-container:fullscreen,
        .shorts-container:-webkit-full-screen,
        .shorts-container:-moz-full-screen {
            width: 100vw !important;
            height: 100vh !important;
            max-width: none !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        
        /* Fullscreen body styles */
        body:fullscreen,
        body:-webkit-full-screen,
        body:-moz-full-screen {
            background: #000 !important;
            overflow: hidden !important;
        }
        
        /* Mobile Layout - Full Screen */
        @media (max-width: 767px) {
            .shorts-container {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
                box-shadow: none;
            }
            
            /* Ensure consistent spacing on mobile */
            .author-follow-row {
                gap: 8px;
            }
            
            .video-author {
                margin-right: 8px;
            }
        }
        
        /* Desktop Layout - Ensure consistent spacing */
        @media (min-width: 768px) {
            .author-follow-row {
                gap: 8px;
            }
            
            .video-author {
                margin-right: 8px;
            }
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform, opacity;
            overflow: hidden;
            /* Ensure comments section is contained within this item */
            position: relative;
        }

        .short-item.active {
            display: block;
        }

                 /* Video Player */
         .short-video {
             width: 100% !important;
             height: 100% !important;
             object-fit: contain !important; /* Changed from cover to contain to prevent zooming */
             background: #000;
             cursor: pointer;
             transition: opacity 0.2s ease;
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             min-width: 100% !important;
             min-height: 100% !important;
             max-width: 100% !important;
             max-height: 100% !important;
         }
         
         .short-video:hover {
             opacity: 0.95;
         }
         
         /* Click to play indicator */
         .short-video::after {
             content: '';
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             width: 80px;
             height: 80px;
             background: rgba(0,0,0,0.7);
             border-radius: 50%;
             opacity: 0;
             transition: opacity 0.3s ease;
             pointer-events: none;
             z-index: 5;
         }
         
         .short-video:hover::after {
             opacity: 1;
         }
         
         .short-video::before {
             content: '▶';
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             color: white;
             font-size: 24px;
             opacity: 0;
             transition: opacity 0.3s ease;
             pointer-events: none;
             z-index: 6;
         }
         
         .short-video:hover::before {
             opacity: 1;
         }

        .short-video[poster] {
            background: none;
        }
        
        /* Ensure videos fit properly without zooming */
        .short-video {
            min-height: 100%;
            min-width: 100%;
            object-position: center;
            object-fit: contain !important; /* Force contain to prevent zooming */
        }
        
        /* Force video to fit container without cropping */
        .short-video::-webkit-media-controls {
            display: none !important;
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
            z-index: 15; /* Lower than progress bar */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0; /* Remove gap to keep elements closer */
        }
         
         .video-info {
             width: 100%;
         }
         
         .author-follow-row {
             display: flex;
             align-items: center;
             /* justify-content: space-between; */
             width: 100%;
             /* margin-bottom: 8px; */
         }
         
         .follow-section {
             margin-left: auto;
             flex-shrink: 0; /* Prevent follow button from shrinking */
         }
         
         .follow-btn {
             background: #ff4757;
             color: white;
             border: none;
             padding: 8px 16px;
             border-radius: 20px;
             cursor: pointer;
             font-size: 14px;
             font-weight: 600;
             transition: background 0.2s ease;
             display: flex;
             align-items: center;
             gap: 6px;
         }
         
         .follow-btn:hover {
             background: #ff3742;
         }
         
         .follow-btn.following {
             background: #2ed573;
         }

        .video-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .video-author {
            font-size: 14px;
            color: white;
            margin-bottom: 0; /* Remove bottom margin to keep close to follow button */
            margin-right: 8px; /* Add right margin for spacing from follow button */
        }

        .video-description {
            font-size: 13px;
            color: #aaa;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        /* Right Side Actions (YouTube Shorts Style) */
        .right-actions {
            position: absolute;
            right: 16px;
            bottom: 120px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            z-index: 20; /* Lower than progress bar */
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

        /* Enhanced Progress Bar with Scrubbing - TikTok Style */
        .progress-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255,255,255,0.3);
            cursor: pointer;
            z-index: 1000; /* Ensure it's above all other elements */
            border-radius: 0;
            pointer-events: auto; /* Ensure clicks work */
            box-shadow: 0 0 10px rgba(255, 71, 87, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-bar {
            height: 100%;
            background: #ff4757;
            width: 0%;
            transition: width 0.1s linear;
            position: relative;
            border-radius: 0;
            pointer-events: none; /* Prevent interference with container clicks */
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            right: -4px;
            top: -2px;
            width: 8px;
            height: 8px;
            background: #ff4757;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s ease;
            box-shadow: 0 0 4px rgba(255, 71, 87, 0.5);
        }

        .progress-container:hover {
            height: 6px;
            background: rgba(255,255,255,0.4);
        }

        .progress-container:hover .progress-bar::after {
            opacity: 1;
        }
        
        /* Ensure progress bar is always visible and clickable */
        .short-item .progress-container {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Skip seconds indicators */
        .skip-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            z-index: 40;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .skip-indicator.show {
            opacity: 1;
        }

        .skip-indicator.left {
            left: 20px;
        }

        .skip-indicator.right {
            right: 20px;
        }

        /* Keyboard shortcuts help */
        .shortcuts-help {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 20px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 2000;
            display: none;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .shortcuts-help.show {
            display: block;
        }

        .shortcuts-help h3 {
            margin: 0 0 15px 0;
            color: #ff4757;
        }
        


        .shortcut-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .shortcut-key {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }

                 /* Navigation Controls */
         .nav-controls {
             position: absolute;
             top: 20px;
             left: 20px;
             z-index: 15;
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

                 /* Top Left Video Controls */
         .top-left-controls {
             position: absolute;
             top: 20px;
             left: 20px;
             right: 20px;
             display: flex;
             justify-content: space-between;
             align-items: center;
             z-index: 25; /* Lower than progress bar */
         }
         
         .left-controls {
             display: flex;
             gap: 12px;
         }
         
         .control-btn.top-control {
             background: rgba(0,0,0,0.7);
             color: white;
             width: 40px;
             height: 40px;
             border-radius: 50%;
             border: none;
             cursor: pointer;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 16px;
             transition: all 0.2s ease;
             backdrop-filter: blur(10px);
         }
         
         .control-btn.top-control:hover {
             background: rgba(0,0,0,0.9);
             transform: scale(1.1);
         }

        /* Comments Section (YouTube Shorts Style) */
        .comments-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1a1a1a;
            border-radius: 20px 20px 0 0;
            max-height: 60vh;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            /* Ensure it stays within the video container */
            max-width: 100%;
            box-sizing: border-box;
            /* Position within the shorts container, not full screen */
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            /* Ensure width matches the video container */
            width: 100%;
        }

        .comments-section.show {
            transform: translateY(0);
        }
        
        /* Ensure comments are contained within the shorts container */
        .shorts-container {
            position: relative;
        }
        
        .short-item {
            position: relative;
        }
        
        /* Desktop-specific comment container sizing */
        @media (min-width: 768px) {
            .comments-section {
                /* Ensure comments don't exceed the phone-width container on desktop */
                max-width: 400px;
                left: 50%;
                transform: translateX(-50%) translateY(100%);
            }
            
            .comments-section.show {
                transform: translateX(-50%) translateY(0);
            }
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
            max-height: 40vh;
            overflow-y: auto;
            /* Prevent comments from interfering with video scrolling */
            overscroll-behavior: contain;
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
            /* Prevent input section from interfering with video */
            position: relative;
            z-index: 1001;
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
             
             .top-left-controls {
                 top: 16px;
                 left: 16px;
                 right: 16px;
                 gap: 10px;
             }
             
             .left-controls {
                 gap: 8px;
             }
             
             .control-btn.top-control {
                 width: 36px;
                 height: 36px;
                 font-size: 14px;
             }
 
             .video-overlay {
                 padding: 16px;
                 flex-direction: column;
                 align-items: flex-start;
                 gap: 12px;
             }
             
             .follow-section {
                 margin-left: 0;
                 align-self: flex-end;
             }
             
             .follow-btn {
                 padding: 6px 12px;
                 font-size: 12px;
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
            
            /* Ensure progress bar is touch-friendly on mobile */
            .progress-container {
                height: 6px;
                background: rgba(255,255,255,0.4);
            }
            
            .progress-bar::after {
                width: 12px;
                height: 12px;
                right: -6px;
                top: -3px;
            }
        }
        
                         /* Ultra-wide screen optimizations */
        @media (min-width: 1920px) {
                        .shorts-container {
                max-width: 400px; /* Phone width for desktop */
                margin: 0 auto;
                scroll-behavior: smooth;
                box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
                border-radius: 20px;
                overflow: hidden;
            }
            
            /* Ensure videos fit properly on ultra-wide screens */
            .short-video {
                object-fit: contain !important; /* Force contain to prevent zooming */
            }
            
            /* Smooth directional scrolling transitions */
            .short-item {
                transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                will-change: transform, opacity;
            }
            
            .short-item.slide-up {
                transform: translateY(-100%);
                opacity: 0;
            }
            
            .short-item.slide-down {
                transform: translateY(100%);
                opacity: 0;
            }
            
            .short-item.slide-in {
                transform: translateY(0);
                opacity: 1;
            }
            
            /* Hide HTML5 default controls in fullscreen */
            video::-webkit-media-controls {
                display: none !important;
            }
            
            video::-webkit-media-controls-panel {
                display: none !important;
            }
            
            video::-webkit-media-controls-play-button {
                display: none !important;
            }
            
            video::-webkit-media-controls-volume-slider {
                display: none !important;
            }
            
            video::-webkit-media-controls-mute-button {
                display: none !important;
            }
            
            video::-webkit-media-controls-timeline {
                display: none !important;
            }
            
            video::-webkit-media-controls-current-time-display {
                display: none !important;
            }
            
            video::-webkit-media-controls-time-remaining-display {
                display: none !important;
            }
            
            video::-webkit-media-controls-fullscreen-button {
                display: none !important;
            }
             
             .right-actions {
                 right: 32px;
             }
             
             .top-left-controls {
                 left: 32px;
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

        /* Play overlay for when autoplay fails */
        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            cursor: pointer;
        }
        
        .play-button-large {
            background: rgba(255, 71, 87, 0.9);
            color: white;
            padding: 20px 30px;
            border-radius: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .play-button-large:hover {
            background: rgba(255, 71, 87, 1);
            transform: scale(1.05);
        }
        
        .play-button-large i {
            font-size: 32px;
        }
        
        .play-button-large span {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Mute feedback notification */
        .mute-feedback {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 16px 24px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            z-index: 3000;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeInOut 2s ease-in-out;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
            20% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            80% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        }
        
        .mute-feedback i {
            font-size: 20px;
            color: #ff4757;
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
                    <!-- Enhanced Progress Bar with Scrubbing -->
                    <div class="progress-container" id="progress-container-<?= $short['VideoID'] ?>">
                        <div class="progress-bar" id="progress-<?= $short['VideoID'] ?>"></div>
                    </div>
                    
                    <!-- Skip Indicators -->
                    <div class="skip-indicator left" id="skip-left-<?= $short['VideoID'] ?>">-10s</div>
                    <div class="skip-indicator right" id="skip-right-<?= $short['VideoID'] ?>">+10s</div>
                    
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
                playsinline
                controlslist="nodownload nofullscreen noremoteplayback"
                disablepictureinpicture
                poster="<?= htmlspecialchars($thumbnailPath) ?>"
                style="display: <?= $index === 0 ? 'block' : 'none' ?>; cursor: pointer;"
                onclick="handleVideoClick(<?= $short['VideoID'] ?>)"
            >
                         <?php if (!empty($videoPath) && file_exists($videoPath)): ?>
                             <source src="<?= htmlspecialchars($videoPath) ?>" type="video/mp4">
                         <?php endif; ?>
                         Your browser does not support the video tag.
                     </video>
                    
                                         <!-- Video Overlay -->
                     <div class="video-overlay">
                         <div class="author-follow-row">
                             <p class="video-author">@<?= htmlspecialchars($short['AuthorDisplayName'] ?? $short['AuthorName'] ?? 'Unknown') ?></p>
                             <div class="follow-section">
                                 <button class="follow-btn" onclick="toggleFollow(<?= $short['AuthorID'] ?? 0 ?>)">
                                     <i class="fas fa-user-plus"></i> Follow
                                 </button>
                             </div>
                         </div>
                         <h3 class="video-title" style="color: white;"><?= htmlspecialchars($short['Title']) ?></h3>
                         <?php if (!empty($short['Excerpt'])): ?>
                             <p class="video-description"><?= htmlspecialchars($short['Excerpt']) ?></p>
                         <?php endif; ?>
                     </div>
                    
                    <!-- Right Side Actions (YouTube Shorts Style) -->
                    <div class="right-actions">
                        <div class="action-group">
                            <button class="action-button like-btn" onclick="toggleLike(<?= $short['VideoID'] ?>)" title="Like (L)">
                                <i class="fas fa-heart"></i>
                            </button>
                            <div class="action-count" id="like-count-<?= $short['VideoID'] ?>">0</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button comment-btn" onclick="showComments(<?= $short['VideoID'] ?>)" title="Comments (C)">
                                <i class="fas fa-comment"></i>
                            </button>
                            <div class="action-count" id="comment-count-<?= $short['VideoID'] ?>">0</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button share-btn" onclick="toggleShareMenu(<?= $short['VideoID'] ?>)" title="Share">
                                <i class="fas fa-share"></i>
                            </button>
                            <div class="action-count">Share</div>
                        </div>
                        
                        <div class="action-group">
                            <button class="action-button save-btn" onclick="toggleSave(<?= $short['VideoID'] ?>)" title="Save (S)">
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
                    
                                         <!-- Top Video Controls -->
                     <div class="top-left-controls">
                         <div class="left-controls">
                             <button class="control-btn top-control" onclick="togglePlayPause(<?= $short['VideoID'] ?>)" title="Play/Pause (Space)">
                                 <i class="fas fa-play"></i>
                             </button>
                             <button class="control-btn top-control" onclick="toggleMute(<?= $short['VideoID'] ?>)" title="Mute/Unmute (M)">
                                 <i class="fas fa-volume-up"></i>
                             </button>
                         </div>
                         <button class="control-btn top-control" onclick="toggleFullscreen(<?= $short['VideoID'] ?>)" title="Fullscreen">
                             <i class="fas fa-expand"></i>
                         </button>
                     </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Navigation Controls -->
        <!-- <div class="nav-controls">
            <button class="nav-btn" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>         -->
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

    <!-- Keyboard Shortcuts Help -->
    <div class="shortcuts-help" id="shortcutsHelp">
        <h3>⌨️ Keyboard Shortcuts</h3>
        <div class="shortcut-item">
            <span>M</span>
            <span class="shortcut-key">Mute/Unmute</span>
        </div>
        <div class="shortcut-item">
            <span>L</span>
            <span class="shortcut-key">Toggle Like</span>
        </div>
        <div class="shortcut-item">
            <span>S</span>
            <span class="shortcut-key">Toggle Save</span>
        </div>
        <div class="shortcut-item">
            <span>C</span>
            <span class="shortcut-key">Toggle Comments</span>
        </div>
        <div class="shortcut-item">
            <span>← →</span>
            <span class="shortcut-key">Skip 10s</span>
        </div>
        <div class="shortcut-item">
            <span>↑ ↓</span>
            <span class="shortcut-key">Next/Previous Video</span>
        </div>
        <div class="shortcut-item">
            <span>Space</span>
            <span class="shortcut-key">Play/Pause</span>
        </div>
        <div class="shortcut-item">
            <span>h/H</span>
            <span class="shortcut-key">Toggle This Help</span>
        </div>
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
                this.isPlaying = false;
                this.currentVideoId = null;
                this.commentsVisible = false;
                this.shareMenuVisible = false;
                this.isMuted = false; // Track mute state globally
                
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
                
                // Ensure all videos are properly sized
                this.ensureAllVideosFit();
                
                // Initialize mute state for all videos
                this.initializeMuteState();
                
                // Test progress bar setup
                this.testProgressBarSetup();
                
                // Restore saved video position or start from beginning
                this.restoreVideoPosition();
                
                // Preload next video for smooth transitions
                this.preloadNextVideo();
                
                // Set up periodic mute state consistency check
                this.setupMuteStateMonitoring();
            }
            
            setupMuteStateMonitoring() {
                // Check mute state consistency every 5 seconds
                setInterval(() => {
                    this.ensureMuteStateConsistency();
                }, 5000);
            }
            
            initializeMuteState() {
                // Set initial mute state for all videos
                this.videoElements.forEach(video => {
                    video.muted = this.isMuted;
                });
                
                // Update all mute button icons to reflect current state
                document.querySelectorAll('.control-btn[onclick*="toggleMute"]').forEach(btn => {
                    this.updateMuteButtonIcon(null, this.isMuted, btn);
                });
                
                console.log('Mute state initialized:', this.isMuted);
            }
            
            testProgressBarSetup() {
                // Test if progress bars are properly set up
                const progressContainers = document.querySelectorAll('.progress-container');
                const progressBars = document.querySelectorAll('.progress-bar');
                
                console.log('Progress bar setup test:', {
                    containers: progressContainers.length,
                    bars: progressBars.length,
                    videos: this.videoElements.length
                });
                
                // Verify each video has a progress container
                this.videoElements.forEach((video, index) => {
                    const videoId = video.closest('.short-item').getAttribute('data-video-id');
                    const container = document.getElementById(`progress-container-${videoId}`);
                    const bar = container ? container.querySelector('.progress-bar') : null;
                    
                    console.log(`Video ${index} (ID: ${videoId}):`, {
                        hasContainer: !!container,
                        hasBar: !!bar,
                        containerId: container ? container.id : 'none'
                    });
                });
            }
            
            ensureAllVideosFit() {
                // Force all videos to fill their containers
                this.videoElements.forEach(video => {
                    this.ensureVideoFill(video);
                });
                
                // Also ensure progress bars are properly set up
                this.ensureProgressBarsSetup();
            }
            
            ensureProgressBarsSetup() {
                // Ensure all progress bars start at 0%
                document.querySelectorAll('.progress-bar').forEach(progressBar => {
                    progressBar.style.width = '0%';
                });
                
                // Verify progress containers are properly positioned
                document.querySelectorAll('.progress-container').forEach(container => {
                    container.style.display = 'block';
                    container.style.visibility = 'visible';
                    container.style.opacity = '1';
                });
                
                console.log('Progress bars setup verified:', {
                    containers: document.querySelectorAll('.progress-container').length,
                    bars: document.querySelectorAll('.progress-bar').length
                });
            }
            
            saveVideoPosition() {
                // Save current video index to localStorage
                localStorage.setItem('shorts_current_video', this.currentIndex.toString());
                localStorage.setItem('shorts_timestamp', Date.now().toString());
                
                // Also save mute state
                localStorage.setItem('shorts_mute_state', this.isMuted.toString());
            }
            
            restoreVideoPosition() {
                try {
                    const savedIndex = localStorage.getItem('shorts_current_video');
                    const savedTimestamp = localStorage.getItem('shorts_timestamp');
                    
                    if (savedIndex && savedTimestamp) {
                        const index = parseInt(savedIndex);
                        const timestamp = parseInt(savedTimestamp);
                        const now = Date.now();
                        
                        // Only restore if saved within last 24 hours
                        if (index >= 0 && index < this.totalVideos && (now - timestamp) < 24 * 60 * 60 * 1000) {
                            this.currentIndex = index;
                            this.showVideo(index);
                            this.trackVideoView(this.getCurrentVideoId());
                            
                            // Ensure mute state is maintained after restoration
                            this.ensureMuteStateConsistency();
                            return;
                        }
                    }
                } catch (error) {
                    console.log('Error restoring video position:', error);
                }
                
                // Fallback to first video
                this.showVideo(0);
                this.trackVideoView(this.getCurrentVideoId());
                
                // Ensure mute state is maintained
                this.ensureMuteStateConsistency();
            }
            
            ensureMuteStateConsistency() {
                // Ensure all videos have the correct mute state
                this.videoElements.forEach(video => {
                    if (video.muted !== this.isMuted) {
                        video.muted = this.isMuted;
                        console.log('Corrected mute state for video:', video.muted);
                    }
                });
                
                // Ensure all mute button icons are consistent
                document.querySelectorAll('.control-btn[onclick*="toggleMute"]').forEach(btn => {
                    this.updateMuteButtonIcon(null, this.isMuted, btn);
                });
                
                console.log('Mute state consistency verified:', this.isMuted);
            }
            
                                     setupEventListeners() {
                // Keyboard navigation - REMOVED passive: true to allow preventDefault
                document.addEventListener('keydown', this.handleKeyDown.bind(this));
                
                // Mouse wheel scrolling for desktop with smooth directional transitions
                document.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    if (Math.abs(e.deltaY) > 10) {
                        if (e.deltaY > 0) {
                            this.nextVideoWithTransition('down');
                        } else {
                            this.previousVideoWithTransition('up');
                        }
                    }
                }, { passive: false });
                
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
                
                // Video progress tracking with proper event binding
                this.videoElements.forEach((video, index) => {
                    // Remove any existing listeners first
                    video.removeEventListener('timeupdate', this.updateProgress.bind(this, video, index));
                    
                    // Add the timeupdate listener
                    video.addEventListener('timeupdate', () => {
                        this.updateProgress(video, index);
                    });
                    
                    video.addEventListener('ended', () => {
                        this.nextVideo();
                    });
                    
                    // Add loadedmetadata event to ensure duration is available
                    video.addEventListener('loadedmetadata', () => {
                        console.log(`Video ${index} loaded, duration:`, video.duration);
                    });
                });
                
                // Progress bar scrubbing
                this.setupProgressScrubbing();
                
                // Double-click skip functionality
                this.setupDoubleClickSkip();
                
                // Save position when user leaves page or refreshes
                window.addEventListener('beforeunload', () => {
                    this.saveVideoPosition();
                });
                
                // Save position when page becomes hidden
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.saveVideoPosition();
                    }
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
                    case 'm':
                    case 'M':
                        e.preventDefault();
                        this.toggleMute();
                        break;
                    case 'l':
                    case 'L':
                        e.preventDefault();
                        this.toggleLikeCurrent();
                        break;
                    case 's':
                    case 'S':
                        e.preventDefault();
                        this.toggleSaveCurrent();
                        break;
                    case 'c':
                    case 'C':
                        e.preventDefault();
                        this.toggleCommentsCurrent();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.skipSeconds(-10);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.skipSeconds(10);
                        break;
                    case 'h':
                    case 'H':
                        e.preventDefault();
                        this.toggleShortcutsHelp();
                        break;
                }
            }
            
                                     showVideo(index) {
                if (index < 0 || index >= this.totalVideos) return;
                
                // Pause all videos first and reset them
                document.querySelectorAll('.short-video').forEach(video => {
                    video.pause();
                    video.currentTime = 0;
                    video.style.display = 'none';
                });
                
                // Reset all progress bars
                document.querySelectorAll('.progress-bar').forEach(progressBar => {
                    progressBar.style.width = '0%';
                });
                
                // Hide all video items
                document.querySelectorAll('.short-item').forEach(item => {
                    item.classList.remove('active');
                    item.style.display = 'none';
                    item.style.opacity = '0';
                });
                
                // Show current video
                this.currentIndex = index;
                const currentItem = document.querySelector(`[data-index="${index}"]`);
                if (currentItem) {
                    currentItem.classList.add('active');
                    currentItem.style.display = 'block';
                    currentItem.style.opacity = '1';
                    
                    // Show the video element
                    const video = currentItem.querySelector('.short-video');
                    if (video) {
                        video.style.display = 'block';
                        // Ensure video is visible
                        video.style.opacity = '1';
                        video.style.visibility = 'visible';
                        
                        // Maintain global mute state across videos
                        video.muted = this.isMuted;
                        
                        // Update mute button icon to reflect current state
                        this.updateMuteButtonIcon(null, this.isMuted);
                    }
                }
                
                // Play current video
                this.playCurrentVideo();
                
                // Track view
                this.trackVideoView(this.getCurrentVideoId());
                
                // Save current video position
                this.saveVideoPosition();
                
                // Preload next video
                this.preloadNextVideo();
            }
            
            // Smooth directional transitions
            showVideoWithTransition(index, direction = 'down') {
                if (index < 0 || index >= this.totalVideos) return;
                
                const currentItem = document.querySelector('.short-item.active');
                const nextItem = document.querySelector(`[data-index="${index}"]`);
                
                if (currentItem && nextItem) {
                    // Prepare next item
                    nextItem.style.display = 'block';
                    nextItem.style.opacity = '0';
                    
                    // Add transition classes based on direction
                    if (direction === 'down') {
                        currentItem.classList.add('slide-up');
                        nextItem.classList.add('slide-down');
                    } else {
                        currentItem.classList.add('slide-down');
                        nextItem.classList.add('slide-up');
                    }
                    
                    // Force reflow to ensure transitions work
                    nextItem.offsetHeight;
                    
                    // After transition, show the video normally
                    setTimeout(() => {
                        this.showVideo(index);
                        // Remove transition classes
                        currentItem.classList.remove('slide-up', 'slide-down');
                        nextItem.classList.remove('slide-up', 'slide-down');
                    }, 300);
                } else {
                    this.showVideo(index);
                }
            }
            
            playCurrentVideo() {
                const currentVideo = document.querySelector('.short-item.active video');
                if (currentVideo) {
                    // Ensure video fills container completely
                    this.ensureVideoFill(currentVideo);
                    
                    // Start with muted autoplay to comply with Chrome's autoplay policy
                    currentVideo.muted = true;
                    
                    // Use requestAnimationFrame for smooth playback
                    requestAnimationFrame(() => {
                        // Try to play the video
                        const playPromise = currentVideo.play();
                        
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                console.log('Video autoplay started successfully (muted)');
                                this.isPlaying = true;
                                this.updatePlayButtonIcon(this.getCurrentVideoId(), true);
                            }).catch(err => {
                                console.log('Autoplay prevented:', err);
                                this.isPlaying = false;
                                this.updatePlayButtonIcon(this.getCurrentVideoId(), false);
                                
                                // Show play button since autoplay failed
                                this.showPlayButton();
                            });
                        }
                    });
                }
            }
            
            showPlayButton() {
                // Show a prominent play button when autoplay fails
                const currentItem = document.querySelector('.short-item.active');
                if (currentItem) {
                    let playOverlay = currentItem.querySelector('.play-overlay');
                    if (!playOverlay) {
                        playOverlay = document.createElement('div');
                        playOverlay.className = 'play-overlay';
                        playOverlay.innerHTML = `
                            <div class="play-button-large">
                                <i class="fas fa-play"></i>
                            </div>
                        `;
                        currentItem.appendChild(playOverlay);
                        
                        // Add click event to start playback
                        playOverlay.addEventListener('click', () => {
                            this.startPlaybackWithUserInteraction();
                        });
                    }
                    playOverlay.style.display = 'flex';
                }
            }
            
            startPlaybackWithUserInteraction() {
                const currentVideo = document.querySelector('.short-item.active video');
                if (currentVideo) {
                    // Now that user has interacted, we can unmute and play
                    currentVideo.muted = this.isMuted; // Restore user's mute preference
                    
                    const playPromise = currentVideo.play();
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                            console.log('Video playback started with user interaction');
                            this.isPlaying = true;
                            this.updatePlayButtonIcon(this.getCurrentVideoId(), true);
                            
                            // Hide the play overlay
                            const playOverlay = document.querySelector('.play-overlay');
                            if (playOverlay) {
                                playOverlay.style.display = 'none';
                            }
                            
                            // Ensure mute state is consistent
                            this.ensureMuteStateConsistency();
                        }).catch(err => {
                            console.error('Playback failed even with user interaction:', err);
                            this.isPlaying = false;
                        });
                    }
                }
            }
            
            ensureVideoFill(video) {
                // Force video to fit container properly without zooming
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'contain';
                video.style.objectPosition = 'center';
                
                // Remove any black bars
                video.style.position = 'absolute';
                video.style.top = '0';
                video.style.left = '0';
                video.style.right = '0';
                video.style.bottom = '0';
                
                // Force video dimensions
                video.setAttribute('width', '100%');
                video.setAttribute('height', '100%');
                
                // Ensure proper fitting without cropping
                video.style.minWidth = '100%';
                video.style.minHeight = '100%';
                video.style.maxWidth = '100%';
                video.style.maxHeight = '100%';
            }
            
            setupProgressScrubbing() {
                // Add click event listeners to progress containers for scrubbing
                document.querySelectorAll('.progress-container').forEach(container => {
                    // Click event for desktop
                    container.addEventListener('click', (e) => {
                        this.handleProgressScrub(e, container);
                    });
                    
                    // Touch events for mobile
                    container.addEventListener('touchstart', (e) => {
                        e.preventDefault();
                        this.handleProgressScrub(e, container);
                    });
                    
                    // Add visual feedback for hover
                    container.addEventListener('mouseenter', () => {
                        container.style.background = 'rgba(255,255,255,0.5)';
                    });
                    
                    container.addEventListener('mouseleave', () => {
                        container.style.background = 'rgba(255,255,255,0.3)';
                    });
                });
                
                console.log('Progress scrubbing setup complete. Found containers:', document.querySelectorAll('.progress-container').length); // Debug log
            }
            
            handleProgressScrub(e, container) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Progress bar clicked/touched!'); // Debug log
                
                const rect = container.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clickX = clientX - rect.left;
                const containerWidth = rect.width;
                const clickPercent = Math.max(0, Math.min(1, clickX / containerWidth));
                
                console.log('Click details:', { clickX, containerWidth, clickPercent }); // Debug log
                
                const videoId = container.id.replace('progress-container-', '');
                const video = document.getElementById(`video-${videoId}`);
                
                console.log('Video found:', video, 'Video ID:', videoId); // Debug log
                
                if (video && video.duration && !isNaN(video.duration)) {
                    const newTime = clickPercent * video.duration;
                    video.currentTime = newTime;
                    
                    console.log('Video time updated to:', newTime, 'seconds'); // Debug log
                    
                    // Update progress bar immediately
                    const progressBar = container.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = (clickPercent * 100) + '%';
                        console.log('Progress bar updated to:', (clickPercent * 100) + '%'); // Debug log
                    }
                } else {
                    console.log('Video not ready:', { video, duration: video?.duration }); // Debug log
                }
            }
            
            setupDoubleClickSkip() {
                // Add double-click event listeners to video containers
                document.querySelectorAll('.short-item').forEach(item => {
                    let lastClickTime = 0;
                    let clickCount = 0;
                    
                    item.addEventListener('click', (e) => {
                        const currentTime = Date.now();
                        const timeDiff = currentTime - lastClickTime;
                        
                        if (timeDiff < 300) { // Double click detected
                            clickCount++;
                            if (clickCount === 2) {
                                const rect = item.getBoundingClientRect();
                                const clickX = e.clientX - rect.left;
                                const containerWidth = rect.width;
                                
                                if (clickX < containerWidth / 2) {
                                    // Double click on left side - skip 10s back
                                    this.skipSeconds(-10);
                                } else {
                                    // Double click on right side - skip 10s forward
                                    this.skipSeconds(10);
                                }
                                clickCount = 0;
                            }
                        } else {
                            clickCount = 1;
                        }
                        
                        lastClickTime = currentTime;
                    });
                });
            }
            
            skipSeconds(seconds) {
                const currentVideo = document.querySelector('.short-item.active video');
                if (currentVideo && currentVideo.duration) {
                    const newTime = Math.max(0, Math.min(currentVideo.duration, currentVideo.currentTime + seconds));
                    currentVideo.currentTime = newTime;
                    
                    // Show skip indicator
                    this.showSkipIndicator(seconds > 0 ? 'right' : 'left', Math.abs(seconds));
                }
            }
            
            showSkipIndicator(direction, seconds) {
                const currentVideoId = this.getCurrentVideoId();
                if (!currentVideoId) return;
                
                const indicator = document.getElementById(`skip-${direction}-${currentVideoId}`);
                if (indicator) {
                    indicator.textContent = direction === 'right' ? `+${seconds}s` : `-${seconds}s`;
                    indicator.classList.add('show');
                    
                    setTimeout(() => {
                        indicator.classList.remove('show');
                    }, 1000);
                }
            }
            
            toggleLikeCurrent() {
                const currentVideoId = this.getCurrentVideoId();
                if (currentVideoId) {
                    toggleLike(currentVideoId);
                }
            }
            
            toggleSaveCurrent() {
                const currentVideoId = this.getCurrentVideoId();
                if (currentVideoId) {
                    toggleSave(currentVideoId);
                }
            }
            
            toggleCommentsCurrent() {
                const currentVideoId = this.getCurrentVideoId();
                if (currentVideoId) {
                    showComments(currentVideoId);
                }
            }
            
            toggleShortcutsHelp() {
                const helpPanel = document.getElementById('shortcutsHelp');
                if (helpPanel) {
                    helpPanel.classList.toggle('show');
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
                         this.updatePlayButtonIcon(videoId, false);
                     } else {
                         video.play();
                         this.isPlaying = true;
                         this.updatePlayButtonIcon(videoId, true);
                     }
                 }
             }
             
             // Method to handle video click events
             handleVideoClick(videoId) {
                 // If video is not playing, start playback with user interaction
                 const video = document.getElementById(`video-${videoId}`);
                 if (video && video.paused) {
                     this.startPlaybackWithUserInteraction();
                 } else {
                     this.togglePlayPause(videoId);
                 }
             }
            
                         updatePlayButtonIcon(videoId, isPlaying) {
                 // Find the play button for this video
                 const playBtn = videoId ? 
                     document.querySelector(`[onclick="togglePlayPause(${videoId})"]`) :
                     document.querySelector('.short-item.active .top-control[onclick*="togglePlayPause"]');
                 
                 if (playBtn) {
                     const icon = playBtn.querySelector('i');
                     if (icon) {
                         icon.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
                     }
                 }
             }
            
            toggleMute(videoId = null) {
                // Toggle mute state globally
                this.isMuted = !this.isMuted;
                
                // Update all videos to maintain consistency
                this.videoElements.forEach(v => {
                    v.muted = this.isMuted;
                });
                
                // Update the current video's mute button icon
                this.updateMuteButtonIcon(videoId, this.isMuted);
                
                // Also update all mute buttons across all videos to maintain consistency
                document.querySelectorAll('.control-btn[onclick*="toggleMute"]').forEach(btn => {
                    this.updateMuteButtonIcon(null, this.isMuted, btn);
                });
                
                // Save mute state to localStorage
                localStorage.setItem('shorts_mute_state', this.isMuted.toString());
                
                console.log('Global mute state changed to:', this.isMuted);
                
                // Show visual feedback
                this.showMuteStateFeedback();
            }
            
            showMuteStateFeedback() {
                // Show a brief visual feedback for mute state change
                const feedback = document.createElement('div');
                feedback.className = 'mute-feedback';
                feedback.innerHTML = `
                    <i class="fas fa-${this.isMuted ? 'volume-mute' : 'volume-up'}"></i>
                    <span>${this.isMuted ? 'Muted' : 'Unmuted'}</span>
                `;
                
                document.body.appendChild(feedback);
                
                // Remove after animation
                setTimeout(() => {
                    if (feedback.parentNode) {
                        feedback.parentNode.removeChild(feedback);
                    }
                }, 2000);
            }
            
            updateMuteButtonIcon(videoId, isMuted, specificButton = null) {
                // Find the mute button for this video or use the specific button provided
                let muteBtn = specificButton;
                
                if (!muteBtn) {
                    muteBtn = videoId ? 
                        document.querySelector(`[onclick="toggleMute(${videoId})"]`) :
                        document.querySelector('.short-item.active .top-control[onclick*="toggleMute"]');
                }
                
                if (muteBtn) {
                    const icon = muteBtn.querySelector('i');
                    if (icon) {
                        icon.className = isMuted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
                        console.log('Mute button icon updated:', isMuted ? 'muted' : 'unmuted');
                    }
                }
            }
            
            toggleFullscreen(videoId = null) {
                // Toggle fullscreen for the entire page/document instead of just the video
                if (!document.fullscreenElement && !document.webkitFullscreenElement && 
                    !document.mozFullScreenElement && !document.msFullscreenElement) {
                    // Enter fullscreen for the document
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen().catch(err => {
                            console.log('Fullscreen failed:', err);
                        });
                    } else if (document.documentElement.webkitRequestFullscreen) {
                        document.documentElement.webkitRequestFullscreen();
                    } else if (document.documentElement.mozRequestFullScreen) {
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.msRequestFullscreen) {
                        document.documentElement.msRequestFullscreen();
                    }
                    
                    // Add fullscreen event listeners
                    document.addEventListener('fullscreenchange', this.handleFullscreenChange.bind(this));
                    document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange.bind(this));
                    document.addEventListener('mozfullscreenchange', this.handleFullscreenChange.bind(this));
                    document.addEventListener('MSFullscreenChange', this.handleFullscreenChange.bind(this));
                } else {
                    // Exit fullscreen
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                }
            }
            
            handleFullscreenChange() {
                const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || 
                                     document.mozFullScreenElement || document.msFullscreenElement);
                
                // Ensure custom controls are always visible in fullscreen
                const controls = document.querySelector('.top-left-controls');
                if (controls) {
                    if (isFullscreen) {
                        controls.style.zIndex = '9999';
                        controls.style.position = 'fixed';
                        controls.style.top = '20px';
                        controls.style.left = '20px';
                    } else {
                        controls.style.zIndex = '25';
                        controls.style.position = 'absolute';
                        controls.style.top = '20px';
                        controls.style.left = '20px';
                    }
                }
                
                // Update fullscreen button icon
                const fullscreenBtn = document.querySelector('.top-left-controls .control-btn:last-child i');
                if (fullscreenBtn) {
                    fullscreenBtn.className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
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
            
            nextVideoWithTransition(direction = 'down') {
                if (this.currentIndex < this.totalVideos - 1) {
                    this.showVideoWithTransition(this.currentIndex + 1, direction);
                } else {
                    // Loop back to first video
                    this.showVideoWithTransition(0, direction);
                }
            }
            
            previousVideo() {
                if (this.currentIndex > 0) {
                    this.showVideo(this.currentIndex - 1);
                } else {
                    // Loop back to last video
                    this.showVideo(this.totalVideos - 1);
                }
            }
            
            previousVideoWithTransition(direction = 'up') {
                if (this.currentIndex > 0) {
                    this.showVideoWithTransition(this.currentIndex - 1, direction);
                } else {
                    // Loop back to last video
                    this.showVideoWithTransition(this.totalVideos - 1, direction);
                }
            }
            
            updateProgress(video, index) {
                // Find the progress bar for this video using the video ID
                const videoId = video.closest('.short-item').getAttribute('data-video-id');
                const progressContainer = document.getElementById(`progress-container-${videoId}`);
                const progressBar = progressContainer ? progressContainer.querySelector('.progress-bar') : null;
                
                if (progressBar && video.duration > 0 && !isNaN(video.duration)) {
                    const progress = (video.currentTime / video.duration) * 100;
                    progressBar.style.width = progress + '%';
                    
                    // Debug log every 2 seconds to avoid spam
                    if (Math.floor(video.currentTime) % 2 === 0 && video.currentTime > 0) {
                        console.log('Progress update:', { 
                            videoId, 
                            currentTime: video.currentTime.toFixed(1),
                            duration: video.duration.toFixed(1),
                            progress: progress.toFixed(1) + '%',
                            progressBarWidth: progressBar.style.width
                        });
                    }
                } else {
                    // Debug log when progress bar is not found
                    if (Math.floor(video.currentTime) % 5 === 0 && video.currentTime > 0) {
                        console.log('Progress bar not found or video not ready:', { 
                            videoId, 
                            progressContainer: !!progressContainer, 
                            progressBar: !!progressBar, 
                            duration: video.duration,
                            currentTime: video.currentTime
                        });
                    }
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
            
            restoreMuteState() {
                try {
                    const savedMuteState = localStorage.getItem('shorts_mute_state');
                    if (savedMuteState !== null) {
                        this.isMuted = savedMuteState === 'true';
                        console.log('Mute state restored from localStorage:', this.isMuted);
                    }
                } catch (error) {
                    console.log('Error restoring mute state:', error);
                }
            }
        }
        
        // Initialize player
        let shortsPlayer;
        document.addEventListener('DOMContentLoaded', () => {
            shortsPlayer = new ShortsPlayer();
            shortsPlayer.restoreMuteState();
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
                         // Increment like count
                         if (likeCount) {
                             const currentCount = parseInt(likeCount.textContent) || 0;
                             likeCount.textContent = currentCount + 1;
                         }
                     } else {
                         likeBtn.classList.remove('liked');
                         likeBtn.style.background = 'rgba(255,255,255,0.1)';
                         // Decrement like count
                         if (likeCount) {
                             const currentCount = parseInt(likeCount.textContent) || 0;
                             likeCount.textContent = Math.max(0, currentCount - 1);
                         }
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
                     const saveCount = document.querySelector(`[onclick="toggleSave(${videoId})"]`).nextElementSibling;
                     
                     if (data.saved) {
                         saveBtn.classList.add('saved');
                         saveBtn.style.background = '#ffa502';
                         // Update save count if available
                         if (saveCount && saveCount.textContent !== 'Save') {
                             const currentCount = parseInt(saveCount.textContent) || 0;
                             saveCount.textContent = currentCount + 1;
                         }
                     } else {
                         saveBtn.classList.remove('saved');
                         saveBtn.style.background = 'rgba(255,255,255,0.1)';
                         // Update save count if available
                         if (saveCount && saveCount.textContent !== 'Save') {
                             const currentCount = parseInt(saveCount.textContent) || 0;
                             saveCount.textContent = Math.max(0, currentCount - 1);
                         }
                     }
                 }
             } catch (error) {
                 console.error('Error toggling save:', error);
             }
         }
        
        // Video control functions for HTML onclick attributes
        function handleVideoClick(videoId) {
            if (shortsPlayer) {
                shortsPlayer.handleVideoClick(videoId);
            }
        }
        
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
                // Disable video scrolling when comments are open
                shortsPlayer.disableVideoScrolling();
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
                // Safely handle username and displayName with robust error handling
                let username = 'User';
                let displayName = 'User';
                
                try {
                    if (comment.username && typeof comment.username === 'string') {
                        username = comment.username;
                    } else if (comment.displayName && typeof comment.displayName === 'string') {
                        username = comment.displayName;
                    }
                    
                    if (comment.displayName && typeof comment.displayName === 'string') {
                        displayName = comment.displayName;
                    } else if (comment.username && typeof comment.username === 'string') {
                        displayName = comment.username;
                    }
                } catch (error) {
                    console.log('Error processing comment data:', error);
                    username = 'User';
                    displayName = 'User';
                }
                
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
         
         // Follow functionality
         async function toggleFollow(authorId) {
             if (!authorId) return;
             
             try {
                 const response = await fetch('api/follow_user.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({ authorId: authorId })
                 });
                 
                 const data = await response.json();
                 if (data.success) {
                     const followBtn = event.target.closest('.follow-btn');
                     if (followBtn) {
                         if (data.following) {
                             followBtn.classList.add('following');
                             followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
                         } else {
                             followBtn.classList.remove('following');
                             followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                         }
                     }
                 }
             } catch (error) {
                 console.error('Error toggling follow:', error);
             }
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
            
            // Close shortcuts help when clicking outside
            document.addEventListener('click', function(e) {
                const shortcutsHelp = document.getElementById('shortcutsHelp');
                if (shortcutsHelp && shortcutsHelp.classList.contains('show') && 
                    !shortcutsHelp.contains(e.target) && e.key !== '?') {
                    shortcutsHelp.classList.remove('show');
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