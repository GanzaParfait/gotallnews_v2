<?php
include "php/header/top.php";
include "php/includes/VideoManager.php";

// Initialize VideoManager for shorts with correct paths
$videoManager = new VideoManager($con, 'uploads/videos/', 'images/video_thumbnails/');

// Get ALL shorts regardless of status to see what's available
$filters = ['videoType' => 'short']; // Removed status filter to see all shorts
$shortsData = $videoManager->getAllVideos(1, 50, $filters);
$shorts = $shortsData['videos'] ?? [];

// Debug: Let's see what we're getting
echo "<!-- Debug: Found " . count($shorts) . " shorts -->";
if (empty($shorts)) {
    echo "<!-- Debug: No shorts found. Checking database... -->";
    
    // Direct database query to see what's available
    $debugQuery = "SELECT VideoID, Title, videoType, Status, isDeleted FROM video_posts WHERE videoType = 'short' LIMIT 10";
    $debugResult = mysqli_query($con, $debugQuery);
    
    if ($debugResult) {
        echo "<!-- Debug: Database query results: -->";
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
    <title>Short Videos - Reels Style</title>
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <style>
        body {
            background: #000;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }

        .reels-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .reel-item {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: none;
        }

        .reel-item.active {
            display: block;
        }

        .reel-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            color: white;
        }

        .reel-actions {
            position: absolute;
            right: 20px;
            bottom: 120px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .action-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .action-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .reel-controls {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        .back-btn {
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
        }

        .reel-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            z-index: 10;
        }

        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: #ff6b6b;
            width: 0%;
            transition: width 0.1s linear;
        }

        /* Comments Modal Styles */
        .comments-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            display: none;
            overflow-y: auto;
        }

        .comments-modal.show {
            display: block;
        }

        .comments-content {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #1a1a1a;
            height: 100%;
            color: white;
        }

        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #333;
            background: #000;
        }

        .comments-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .close-comments {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .comments-list {
            padding: 20px;
            max-height: calc(100vh - 200px);
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
        }

        .comment-content {
            flex: 1;
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
        }

        .comment-time {
            font-size: 12px;
            color: #999;
        }

        .comment-text {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 8px;
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
        }

        .comment-like.liked {
            color: #ff6b6b;
        }

        .comment-reply {
            background: none;
            border: none;
            color: #999;
            font-size: 12px;
            cursor: pointer;
        }

        .comment-input-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: #000;
            border-top: 1px solid #333;
        }

        .comment-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comment-input {
            flex: 1;
            background: #333;
            border: none;
            border-radius: 20px;
            padding: 12px 20px;
            color: white;
            font-size: 14px;
        }

        .comment-input::placeholder {
            color: #999;
        }

        .comment-submit {
            background: #ff6b6b;
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }

        .comment-submit:hover {
            background: #ff5252;
        }

        /* Video display fixes */
        .reel-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        .reel-video[poster] {
            background: none;
        }

        /* Ensure video is visible */
        .reel-item.active .reel-video {
            display: block;
        }

        .reel-item.active {
            display: block !important;
        }
    </style>
</head>
<body>
    <div class="reels-container" id="reelsContainer">
        <?php if (empty($shorts)): ?>
            <div style="display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center;">
                <div>
                    <h2>🎬 No Short Videos Yet</h2>
                    <p>Be the first to create amazing short videos!</p>
                    <button onclick="window.location.href='video_shorts.php'" style="background: #ff6b6b; color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer;">
                        Create Your First Short
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($shorts as $index => $short): ?>
                <div class="reel-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>" data-video-id="<?= $short['VideoID'] ?>">
                    <div class="progress-bar" id="progress-<?= $short['VideoID'] ?>"></div>
                    
                    <?php
                    // Fix video file path
                    $videoPath = $short['VideoFile'];
                    if (!empty($videoPath) && !file_exists($videoPath)) {
                        // Try different path combinations
                        $possiblePaths = [
                            $videoPath,
                            "uploads/videos/" . basename($videoPath),
                            "gp-admin/admin/uploads/videos/" . basename($videoPath),
                            "../uploads/videos/" . basename($videoPath)
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
                            "images/video_thumbnails/" . basename($thumbnailPath),
                            "gp-admin/admin/images/video_thumbnails/" . basename($thumbnailPath),
                            "../images/video_thumbnails/" . basename($thumbnailPath)
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
                    
                    <video 
                        class="reel-video" 
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
                    
                    <div class="reel-overlay">
                        <h3><?= htmlspecialchars($short['Title']) ?></h3>
                        <p><?= htmlspecialchars($short['AuthorDisplayName'] ?? $short['AuthorName'] ?? 'Unknown') ?></p>
                    </div>
                    
                    <div class="reel-actions">
                        <button class="action-btn" onclick="toggleLike(<?= $short['VideoID'] ?>)">
                            <i class="icon-copy fa fa-heart"></i>
                        </button>
                        <button class="action-btn" onclick="showComments(<?= $short['VideoID'] ?>)">
                            <i class="icon-copy fa fa-comment"></i>
                        </button>
                        <button class="action-btn" onclick="shareVideo(<?= $short['VideoID'] ?>)">
                            <i class="icon-copy fa fa-share"></i>
                        </button>
                        <button class="action-btn" onclick="toggleSave(<?= $short['VideoID'] ?>)">
                            <i class="icon-copy fa fa-bookmark"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="reel-controls">
            <button class="back-btn" onclick="goBack()">
                <i class="icon-copy fa fa-arrow-left"></i> Back
            </button>
        </div>
        
        <div class="reel-info">
            <span id="currentVideo">1</span> / <?= count($shorts) ?>
        </div>
    </div>

    <!-- Comments Modal -->
    <div class="comments-modal" id="commentsModal">
        <div class="comments-content">
            <div class="comments-header">
                <h3>Comments</h3>
                <button class="close-comments" onclick="closeComments()">&times;</button>
            </div>
            
            <div class="comments-list" id="commentsList">
                <!-- Comments will be loaded here -->
            </div>
            
            <div class="comment-input-section">
                <div class="comment-input-wrapper">
                    <input type="text" class="comment-input" id="commentInput" placeholder="Add a comment..." maxlength="500">
                    <button class="comment-submit" onclick="submitComment()">Post</button>
                </div>
            </div>
        </div>
    </div>

    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    
    <script>
        class ShortsReels {
            constructor() {
                this.currentIndex = 0;
                this.totalVideos = <?= count($shorts) ?>;
                this.videoElements = document.querySelectorAll('.reel-video');
                this.progressBars = document.querySelectorAll('.progress-bar');
                this.isPlaying = false;
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.showVideo(0);
                this.trackVideoView(this.getCurrentVideoId());
            }
            
            setupEventListeners() {
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
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
                    }
                });
                
                // Touch/swipe events
                let startY = 0;
                document.addEventListener('touchstart', (e) => {
                    startY = e.touches[0].clientY;
                });
                
                document.addEventListener('touchend', (e) => {
                    const endY = e.changedTouches[0].clientY;
                    const deltaY = startY - endY;
                    
                    if (Math.abs(deltaY) > 50) {
                        if (deltaY > 0) {
                            this.nextVideo();
                        } else {
                            this.previousVideo();
                        }
                    }
                });
                
                // Video progress tracking
                this.videoElements.forEach((video, index) => {
                    video.addEventListener('timeupdate', () => {
                        this.updateProgress(video, index);
                    });
                    
                    video.addEventListener('ended', () => {
                        this.nextVideo();
                    });
                });
            }
            
            showVideo(index) {
                if (index < 0 || index >= this.totalVideos) return;
                
                // Hide all videos
                document.querySelectorAll('.reel-item').forEach(item => {
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
            }
            
            playCurrentVideo() {
                const currentVideo = document.querySelector('.reel-item.active video');
                if (currentVideo) {
                    currentVideo.play().then(() => {
                        this.isPlaying = true;
                    }).catch(err => {
                        console.log('Auto-play prevented:', err);
                        this.isPlaying = false;
                    });
                }
            }
            
            togglePlayPause() {
                const currentVideo = document.querySelector('.reel-item.active video');
                if (currentVideo) {
                    if (this.isPlaying) {
                        currentVideo.pause();
                        this.isPlaying = false;
                    } else {
                        currentVideo.play();
                        this.isPlaying = true;
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
                if (this.progressBars[index]) {
                    const progress = (video.currentTime / video.duration) * 100;
                    this.progressBars[index].style.width = progress + '%';
                }
            }
            
            getCurrentVideoId() {
                const activeItem = document.querySelector('.reel-item.active');
                return activeItem ? activeItem.getAttribute('data-video-id') : null;
            }
            
            async trackVideoView(videoId) {
                if (!videoId) return;
                
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
        }
        
        // Initialize reels
        let reels;
        document.addEventListener('DOMContentLoaded', () => {
            reels = new ShortsReels();
        });
        
        // Global functions
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
                    alert(data.liked ? 'Video liked!' : 'Video unliked!');
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
                    alert(data.saved ? 'Video saved!' : 'Video removed from saves!');
                }
            } catch (error) {
                console.error('Error toggling save:', error);
            }
        }
        
        // Comments functionality
        let currentVideoId = null;
        
        async function showComments(videoId) {
            currentVideoId = videoId;
            const modal = document.getElementById('commentsModal');
            const commentsList = document.getElementById('commentsList');
            
            // Show modal
            modal.classList.add('show');
            
            // Load comments
            await loadComments(videoId);
        }
        
        async function loadComments(videoId) {
            try {
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
            }
        }
        
        function displayComments(comments) {
            const commentsList = document.getElementById('commentsList');
            
            if (comments.length === 0) {
                commentsList.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">No comments yet. Be the first to comment!</p>';
                return;
            }
            
            commentsList.innerHTML = comments.map(comment => `
                <div class="comment-item" data-comment-id="${comment.commentID}">
                    <div class="comment-avatar">
                        ${comment.profilePicture ? `<img src="${comment.profilePicture}" alt="${comment.username}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` : comment.username.charAt(0).toUpperCase()}
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-username">${comment.displayName || comment.username}</span>
                            <span class="comment-time">${formatTimeAgo(comment.createdAt)}</span>
                        </div>
                        <div class="comment-text">${comment.text}</div>
                        <div class="comment-actions">
                            <button class="comment-like" onclick="toggleCommentLike(${comment.commentID})">
                                <i class="icon-copy fa fa-heart"></i>
                                <span>${comment.likes}</span>
                            </button>
                            <button class="comment-reply" onclick="replyToComment(${comment.commentID})">
                                Reply
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        async function submitComment() {
            const commentInput = document.getElementById('commentInput');
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
            }
        }
        
        function closeComments() {
            const modal = document.getElementById('commentsModal');
            modal.classList.remove('show');
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
        
        function shareVideo(videoId) {
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this short video!',
                    url: `${window.location.origin}/video_shorts_reels.php?video=${videoId}`
                });
            } else {
                navigator.clipboard.writeText(`${window.location.origin}/video_shorts_reels.php?video=${videoId}`);
                alert('Link copied to clipboard!');
            }
        }
        
        function goBack() {
            window.history.back();
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('commentsModal').addEventListener('click', function(e) {
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
        });
    </script>
</body>
</html>
