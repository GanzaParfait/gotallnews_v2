<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';
include 'php/includes/VideoPlayer.php';

// Get video ID from URL
$videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$videoId) {
    header('Location: video_posts.php');
    exit;
}

try {
    // Initialize the video manager
    $videoManager = new VideoManager($con);
    
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
    $videoManager->recordVideoView($videoId);
    
    // Get related videos
    $relatedVideos = $videoManager->getRelatedVideos($videoId, $video['CategoryID'], 6);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['Title']) ?> - Video</title>
    
    <!-- Bootstrap CSS -->
    <link href="src/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="src/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .video-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .video-player-wrapper {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }
        .video-info-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .video-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        .meta-item i {
            color: #667eea;
        }
        .video-tags {
            margin: 20px 0;
        }
        .tag {
            display: inline-block;
            background: #f8f9fa;
            color: #495057;
            padding: 6px 12px;
            border-radius: 20px;
            margin: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .tag:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
        .related-videos {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .related-video-card {
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .related-video-card:hover {
            transform: translateY(-5px);
        }
        .related-video-thumbnail {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        .video-duration-badge {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .author-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .share-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .share-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        .share-btn:hover {
            opacity: 0.8;
            text-decoration: none;
            color: white;
        }
        .share-facebook { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-linkedin { background: #0077b5; }
        .share-whatsapp { background: #25d366; }
        .video-description {
            line-height: 1.8;
            color: #555;
        }
        .video-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php if (isset($error_message)): ?>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4>Error</h4>
                <p><?= htmlspecialchars($error_message) ?></p>
                <a href="video_posts.php" class="btn btn-primary">Back to Videos</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Video Page Header -->
        <div class="video-page-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-4 mb-3"><?= htmlspecialchars($video['Title']) ?></h1>
                        <p class="lead mb-0">
                            <i class="fa fa-user"></i> 
                            <?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?>
                            <span class="mx-3">•</span>
                            <i class="fa fa-calendar"></i> 
                            <?= date('F j, Y', strtotime($video['Published_at'] ?: $video['Created_at'])) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <a href="video_posts.php" class="btn btn-outline-light">
                            <i class="fa fa-arrow-left"></i> Back to Videos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <!-- Main Video Content -->
                <div class="col-lg-8">
                    <!-- Video Player -->
                    <div class="video-player-wrapper">
                        <?php
                        $videoPlayer = new VideoPlayer($video, [
                            'controls' => true,
                            'autoplay' => false,
                            'responsive' => true
                        ]);
                        echo $videoPlayer->render();
                        ?>
                    </div>

                    <!-- Video Information -->
                    <div class="video-info-section">
                        <h2><?= htmlspecialchars($video['Title']) ?></h2>
                        
                        <?php if (!empty($video['Excerpt'])): ?>
                            <p class="lead"><?= htmlspecialchars($video['Excerpt']) ?></p>
                        <?php endif; ?>

                        <!-- Video Meta Information -->
                        <div class="video-meta">
                            <div class="meta-item">
                                <i class="fa fa-eye"></i>
                                <span><?= number_format($video['Views']) ?> views</span>
                            </div>
                            <div class="meta-item">
                                <i class="fa fa-clock-o"></i>
                                <span><?= $videoPlayer->getFormattedDuration() ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fa fa-expand"></i>
                                <span><?= htmlspecialchars($video['VideoResolution']) ?></span>
                            </div>
                            <?php if ($video['VideoFormat'] !== 'embed'): ?>
                                <div class="meta-item">
                                    <i class="fa fa-file-video-o"></i>
                                    <span><?= $videoPlayer->getFormattedFileSize() ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <i class="fa fa-calendar"></i>
                                <span><?= date('M j, Y', strtotime($video['Published_at'] ?: $video['Created_at'])) ?></span>
                            </div>
                        </div>

                        <!-- Video Description -->
                        <?php if (!empty($video['Description'])): ?>
                            <div class="video-description">
                                <h5>Description</h5>
                                <p><?= nl2br(htmlspecialchars($video['Description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Video Tags -->
                        <?php if (!empty($video['tags'])): ?>
                            <div class="video-tags">
                                <h6>Tags:</h6>
                                <?php foreach ($video['tags'] as $tag): ?>
                                    <a href="video_posts.php?search=<?= urlencode($tag['TagName']) ?>" class="tag">
                                        #<?= htmlspecialchars($tag['TagName']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Share Buttons -->
                        <div class="share-buttons">
                            <h6>Share this video:</h6>
                            <?php
                            $videoUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                            $videoTitle = urlencode($video['Title']);
                            ?>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($videoUrl) ?>" 
                               class="share-btn share-facebook" target="_blank">
                                <i class="fa fa-facebook"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($videoUrl) ?>&text=<?= $videoTitle ?>" 
                               class="share-btn share-twitter" target="_blank">
                                <i class="fa fa-twitter"></i> Twitter
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($videoUrl) ?>" 
                               class="share-btn share-linkedin" target="_blank">
                                <i class="fa fa-linkedin"></i> LinkedIn
                            </a>
                            <a href="https://wa.me/?text=<?= $videoTitle ?>%20<?= urlencode($videoUrl) ?>" 
                               class="share-btn share-whatsapp" target="_blank">
                                <i class="fa fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Video Statistics -->
                    <div class="video-info-section">
                        <h5>Video Statistics</h5>
                        <div class="video-stats">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($video['Views']) ?></div>
                                <div class="stat-label">Total Views</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($video['Likes']) ?></div>
                                <div class="stat-label">Likes</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($video['Comments']) ?></div>
                                <div class="stat-label">Comments</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($video['Shares']) ?></div>
                                <div class="stat-label">Shares</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Author Information -->
                    <div class="video-info-section">
                        <h5>About the Creator</h5>
                        <div class="author-info">
                            <img src="php/defaultavatar/avatar.png" alt="Author Avatar" class="author-avatar">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?></h6>
                                <p class="text-muted mb-0">Video Creator</p>
                                <small class="text-muted">
                                    Member since <?= date('M Y', strtotime($video['Created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Related Videos -->
                    <?php if (!empty($relatedVideos)): ?>
                        <div class="related-videos">
                            <h5>Related Videos</h5>
                            <?php foreach ($relatedVideos as $relatedVideo): ?>
                                <div class="card related-video-card">
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($relatedVideo['VideoThumbnail'] ?: 'php/defaultavatar/video-thumbnail.png') ?>" 
                                             alt="<?= htmlspecialchars($relatedVideo['Title']) ?>" 
                                             class="related-video-thumbnail">
                                        <?php if ($relatedVideo['VideoDuration'] > 0): ?>
                                            <span class="video-duration-badge">
                                                <?= gmdate('i:s', $relatedVideo['VideoDuration']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="video_view.php?id=<?= $relatedVideo['VideoID'] ?>" 
                                               class="text-decoration-none">
                                                <?= htmlspecialchars($relatedVideo['Title']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fa fa-eye"></i> <?= number_format($relatedVideo['Views']) ?> views
                                            <span class="mx-2">•</span>
                                            <i class="fa fa-calendar"></i> <?= date('M j', strtotime($relatedVideo['Created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="src/scripts/jquery.min.js"></script>
    <script src="src/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Custom video controls functionality
        function togglePlayPause(button) {
            const video = button.closest('.video-player-container').querySelector('video');
            if (video.paused) {
                video.play();
                button.innerHTML = '<i class="fa fa-pause"></i>';
            } else {
                video.pause();
                button.innerHTML = '<i class="fa fa-play"></i>';
            }
        }
        
        function seekVideo(value) {
            const video = document.querySelector('.video-player');
            if (video) {
                const seekTime = (value / 100) * video.duration;
                video.currentTime = seekTime;
            }
        }
        
        function toggleMute(button) {
            const video = button.closest('.video-player-container').querySelector('video');
            video.muted = !video.muted;
            button.innerHTML = video.muted ? '<i class="fa fa-volume-off"></i>' : '<i class="fa fa-volume-up"></i>';
        }
        
        // Update progress bar
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.video-player');
            if (video) {
                video.addEventListener('timeupdate', function() {
                    const progress = document.querySelector('.video-progress');
                    if (progress) {
                        const value = (video.currentTime / video.duration) * 100;
                        progress.value = value;
                    }
                });
            }
        });
    </script>
</body>
</html>
