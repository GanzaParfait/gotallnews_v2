<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';
include 'php/includes/VideoPlayer.php';
include 'php/includes/CreatorProfileManager.php';

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
    
    // Get creator profile information
    $creatorProfile = null;
    if (isset($video['AuthorID'])) {
        $creatorManager = new CreatorProfileManager($con);
        $creatorProfile = $creatorManager->getProfileByAdminId($video['AuthorID']);
    }
    
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
         body {
             background: #f8f9fa;
             font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
         }
         
         .main-container {
             max-width: 70%;
             margin: 0 auto;
             padding: 0 15px;
         }
         
         .video-page-header {
             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
             color: white;
             padding: 80px 0;
             margin-bottom: 40px;
             text-align: center;
             position: relative;
             overflow: hidden;
         }
         
         .video-page-header::before {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
             opacity: 0.3;
         }
         
         .header-content {
             position: relative;
             z-index: 2;
         }
         
         .header-title {
             font-size: 3.5rem;
             font-weight: 800;
             margin-bottom: 20px;
             text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
             line-height: 1.2;
         }
         
         .header-meta {
             font-size: 1.3rem;
             margin-bottom: 30px;
             opacity: 0.95;
             font-weight: 300;
         }
         
         .back-btn {
             background: rgba(255,255,255,0.2);
             border: 2px solid rgba(255,255,255,0.3);
             color: white;
             padding: 12px 30px;
             border-radius: 30px;
             text-decoration: none;
             transition: all 0.3s ease;
             display: inline-block;
             font-weight: 600;
         }
         
         .back-btn:hover {
             background: rgba(255,255,255,0.3);
             border-color: rgba(255,255,255,0.5);
             color: white;
             text-decoration: none;
             transform: translateY(-2px);
         }
         
         .video-player-wrapper {
             background: #000;
             border-radius: 20px;
             overflow: hidden;
             box-shadow: 0 20px 40px rgba(0,0,0,0.3);
             margin-bottom: 40px;
             position: relative;
         }
         
         .video-player-container {
             width: 100%;
             height: 0;
             padding-bottom: 56.25%; /* 16:9 aspect ratio */
             position: relative;
         }
         
         .video-player-container iframe,
         .video-player-container video {
             position: absolute;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             border: none;
         }
         
         .video-info-section {
             background: white;
             border-radius: 20px;
             padding: 40px;
             box-shadow: 0 10px 30px rgba(0,0,0,0.1);
             margin-bottom: 30px;
             border: 1px solid #e9ecef;
         }
         
         .section-title {
             font-size: 2.2rem;
             font-weight: 700;
             color: #1a1a1a;
             margin-bottom: 25px;
             border-bottom: 3px solid #667eea;
             padding-bottom: 15px;
             position: relative;
         }
         
         .section-title::after {
             content: '';
             position: absolute;
             bottom: -3px;
             left: 0;
             width: 60px;
             height: 3px;
             background: #764ba2;
         }
         
         .video-meta {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
             gap: 25px;
             margin: 30px 0;
             padding: 30px;
             background: linear-gradient(135deg, #f8f9fa, #e9ecef);
             border-radius: 15px;
             border: 1px solid #dee2e6;
         }
         
         .meta-item {
             display: flex;
             align-items: center;
             gap: 12px;
             color: #495057;
             font-weight: 600;
             font-size: 1.1rem;
         }
         
         .meta-item i {
             color: #667eea;
             font-size: 1.3rem;
             width: 20px;
             text-align: center;
         }
         
         .video-tags {
             margin: 30px 0;
         }
         
         .tag {
             display: inline-block;
             background: linear-gradient(135deg, #667eea, #764ba2);
             color: white;
             padding: 10px 20px;
             border-radius: 25px;
             margin: 8px;
             text-decoration: none;
             transition: all 0.3s ease;
             font-weight: 600;
             box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
         }
         
         .tag:hover {
             transform: translateY(-3px);
             box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
             color: white;
             text-decoration: none;
         }
         
         .video-description {
             line-height: 1.9;
             color: #495057;
             font-size: 1.1rem;
             background: #f8f9fa;
             padding: 25px;
             border-radius: 15px;
             border-left: 5px solid #667eea;
         }
         
         .video-stats {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
             gap: 25px;
             margin: 30px 0;
         }
         
         .stat-card {
             text-align: center;
             padding: 30px;
             background: linear-gradient(135deg, #ffffff, #f8f9fa);
             border-radius: 15px;
             border: 2px solid #e9ecef;
             transition: all 0.3s ease;
             position: relative;
             overflow: hidden;
         }
         
         .stat-card::before {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             height: 4px;
             background: linear-gradient(135deg, #667eea, #764ba2);
         }
         
         .stat-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 15px 35px rgba(0,0,0,0.1);
             border-color: #667eea;
         }
         
         .stat-number {
             font-size: 2.5rem;
             font-weight: 800;
             color: #667eea;
             margin-bottom: 10px;
             text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
         }
         
         .stat-label {
             color: #6c757d;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 1px;
             font-size: 0.9rem;
         }
         
         .share-buttons {
             display: flex;
             gap: 15px;
             margin: 30px 0;
             flex-wrap: wrap;
             justify-content: center;
         }
         
         .share-btn {
             padding: 12px 24px;
             border: none;
             border-radius: 25px;
             color: white;
             text-decoration: none;
             transition: all 0.3s ease;
             font-weight: 600;
             display: flex;
             align-items: center;
             gap: 8px;
             box-shadow: 0 4px 15px rgba(0,0,0,0.2);
         }
         
         .share-btn:hover {
             transform: translateY(-3px);
             box-shadow: 0 8px 25px rgba(0,0,0,0.3);
             color: white;
             text-decoration: none;
         }
         
         .share-facebook { background: linear-gradient(135deg, #1877f2, #0d6efd); }
         .share-twitter { background: linear-gradient(135deg, #1da1f2, #0d6efd); }
         .share-linkedin { background: linear-gradient(135deg, #0077b5, #0056b3); }
         .share-whatsapp { background: linear-gradient(135deg, #25d366, #20c997); }
         
         .author-info {
             display: flex;
             align-items: center;
             gap: 20px;
             padding: 25px;
             background: linear-gradient(135deg, #f8f9fa, #e9ecef);
             border-radius: 15px;
             border: 1px solid #dee2e6;
             margin: 25px 0;
         }
         
         .author-avatar {
             width: 80px;
             height: 80px;
             border-radius: 50%;
             background: linear-gradient(135deg, #667eea, #764ba2);
             display: flex;
             align-items: center;
             justify-content: center;
             color: white;
             font-size: 2rem;
             font-weight: 700;
             flex-shrink: 0;
         }
         
         .author-profile-image {
             width: 100%;
             height: 100%;
             border-radius: 50%;
             object-fit: cover;
             border: 3px solid white;
             box-shadow: 0 4px 15px rgba(0,0,0,0.1);
         }
         
         .author-details {
             flex: 1;
             min-width: 0;
         }
         
         .author-name {
             font-size: 1.4rem;
             font-weight: 700;
             color: #1a1a1a;
             margin-bottom: 10px;
             display: flex;
             align-items: center;
             gap: 10px;
         }
         
         .author-name i {
             font-size: 1.2rem;
         }
         
         .author-bio {
             color: #6c757d;
             margin-bottom: 15px;
             line-height: 1.5;
         }
         
         .author-stats {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
             gap: 15px;
             margin-bottom: 15px;
         }
         
         .stat-item {
             display: flex;
             align-items: center;
             gap: 8px;
             font-size: 0.9rem;
             color: #495057;
         }
         
         .stat-item i {
             color: #667eea;
             width: 16px;
         }
         
         .author-social-links {
             margin-top: 20px;
             padding-top: 20px;
             border-top: 1px solid #dee2e6;
         }
         
         .social-links-title {
             font-size: 1rem;
             font-weight: 600;
             color: #495057;
             margin-bottom: 15px;
         }
         
         .social-links-grid {
             display: flex;
             gap: 10px;
             flex-wrap: wrap;
         }
         
         .social-link-btn {
             display: flex;
             align-items: center;
             justify-content: center;
             width: 40px;
             height: 40px;
             border-radius: 50%;
             background: linear-gradient(135deg, #667eea, #764ba2);
             color: white;
             text-decoration: none;
             transition: all 0.3s ease;
             font-size: 1.1rem;
         }
         
         .social-link-btn:hover {
             transform: translateY(-2px);
             box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
             color: white;
         }
         
         .related-videos {
             background: white;
             border-radius: 20px;
             padding: 40px;
             box-shadow: 0 10px 30px rgba(0,0,0,0.1);
             border: 1px solid #e9ecef;
         }
         
         .related-video-card {
             border: none;
             border-radius: 15px;
             overflow: hidden;
             transition: all 0.3s ease;
             margin-bottom: 25px;
             box-shadow: 0 5px 15px rgba(0,0,0,0.1);
             border: 1px solid #e9ecef;
         }
         
         .related-video-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 15px 35px rgba(0,0,0,0.15);
         }
         
         .related-video-thumbnail {
             width: 100%;
             height: 140px;
             object-fit: cover;
         }
         
         .video-duration-badge {
             position: absolute;
             bottom: 10px;
             right: 10px;
             background: rgba(0,0,0,0.8);
             color: white;
             padding: 4px 8px;
             border-radius: 6px;
             font-size: 12px;
             font-weight: 600;
         }
         
         .card-body {
             padding: 20px;
         }
         
         .card-title a {
             color: #1a1a1a;
             text-decoration: none;
             font-weight: 600;
             transition: color 0.3s ease;
         }
         
         .card-title a:hover {
             color: #667eea;
         }
         
         @media (max-width: 1200px) {
             .main-container {
                 max-width: 85%;
             }
         }
         
         @media (max-width: 768px) {
             .main-container {
                 max-width: 95%;
                 padding: 0 10px;
             }
             
             .header-title {
                 font-size: 2.5rem;
             }
             
             .video-info-section {
                 padding: 25px;
             }
             
             .video-meta {
                 grid-template-columns: 1fr;
                 gap: 20px;
             }
             
             .video-stats {
                 grid-template-columns: 1fr;
             }
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
             <div class="header-content">
                 <h1 class="header-title"><?= htmlspecialchars($video['Title']) ?></h1>
                 <p class="header-meta">
                     <i class="fa fa-user"></i> 
                     <?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?>
                     <span class="mx-3">•</span>
                     <i class="fa fa-calendar"></i> 
                     <?= date('F j, Y', strtotime($video['Published_at'] ?: $video['Created_at'])) ?>
                 </p>
                 <a href="video_posts.php" class="back-btn">
                     <i class="fa fa-arrow-left"></i> Back to Videos
                 </a>
             </div>
         </div>
 
         <div class="main-container">
            <div class="row">
                <!-- Main Video Content -->
                <div class="col-lg-8">
                                         <!-- Video Player -->
                     <div class="video-player-wrapper">
                         <div class="video-player-container">
                             <?php
                             $videoPlayer = null; // Initialize variable
                             if ($video['VideoFormat'] === 'embed' && !empty($video['EmbedCode'])) {
                                 // For embedded videos, process and display the embed code
                                 $embedCode = $video['EmbedCode'];
                                 
                                 // If it's a YouTube URL, convert to proper embed
                                 if (strpos($embedCode, 'youtube.com/watch') !== false) {
                                     $videoId = '';
                                     if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
                                         $videoId = $matches[1];
                                         $embedCode = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                     }
                                 } elseif (strpos($embedCode, 'youtu.be/') !== false) {
                                     $videoId = '';
                                     if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
                                         $videoId = $matches[1];
                                         $embedCode = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                     }
                                 } elseif (strpos($embedCode, 'vimeo.com/') !== false) {
                                     $videoId = '';
                                     if (preg_match('/vimeo\.com\/(\d+)/', $embedCode, $matches)) {
                                         $videoId = $matches[1];
                                         $embedCode = '<iframe width="100%" height="100%" src="https://player.vimeo.com/video/' . $videoId . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                     }
                                 }
                                 
                                 // If it's already an iframe, use it as is
                                 if (strpos($embedCode, '<iframe') !== false) {
                                     echo $embedCode;
                                 } else {
                                     // If it's just a URL, create a basic iframe
                                     echo '<iframe width="100%" height="100%" src="' . htmlspecialchars($embedCode) . '" frameborder="0" allowfullscreen></iframe>';
                                 }
                             } else {
                                 // For uploaded videos, use the VideoPlayer class
                                 $videoPlayer = new VideoPlayer($video, [
                                     'controls' => true,
                                     'autoplay' => false,
                                     'responsive' => true
                                 ]);
                                 echo $videoPlayer->render();
                             }
                             ?>
                         </div>
                     </div>

                                         <!-- Video Information -->
                     <div class="video-info-section">
                         <h2 class="section-title"><?= htmlspecialchars($video['Title']) ?></h2>
                         
                         <?php if (!empty($video['Excerpt'])): ?>
                             <p class="lead" style="font-size: 1.3rem; color: #6c757d; margin-bottom: 25px;"><?= htmlspecialchars($video['Excerpt']) ?></p>
                         <?php endif; ?>
 
                         <!-- Video Meta Information -->
                         <div class="video-meta">
                             <div class="meta-item">
                                 <i class="fa fa-eye"></i>
                                 <span><?= number_format($video['Views']) ?> views</span>
                             </div>
                             <div class="meta-item">
                                 <i class="fa fa-clock-o"></i>
                                 <span><?= $video['VideoFormat'] === 'embed' ? 'Embedded Video' : ($videoPlayer->getFormattedDuration() ?? 'Unknown') ?></span>
                             </div>
                             <div class="meta-item">
                                 <i class="fa fa-expand"></i>
                                 <span><?= htmlspecialchars($video['VideoResolution']) ?></span>
                             </div>
                             <?php if ($video['VideoFormat'] !== 'embed'): ?>
                                 <div class="meta-item">
                                     <i class="fa fa-file-video-o"></i>
                                     <span><?= $videoPlayer->getFormattedFileSize() ?? 'Unknown' ?></span>
                                 </div>
                             <?php endif; ?>
                             <div class="meta-item">
                                 <i class="fa fa-calendar"></i>
                                 <span><?= date('M j, Y', strtotime($video['Published_at'] ?: $video['Created_at'])) ?></span>
                             </div>
                             <div class="meta-item">
                                 <i class="fa fa-tag"></i>
                                 <span><?= $video['VideoFormat'] === 'embed' ? 'Embedded' : 'Uploaded' ?></span>
                             </div>
                         </div>
 
                         <!-- Video Description -->
                         <?php if (!empty($video['Description'])): ?>
                             <div class="video-description">
                                 <h5 style="color: #1a1a1a; margin-bottom: 15px; font-weight: 600;">Description</h5>
                                 <p><?= nl2br(htmlspecialchars($video['Description'])) ?></p>
                             </div>
                         <?php endif; ?>
 
                         <!-- Video Tags -->
                         <?php if (!empty($video['tags'])): ?>
                             <div class="video-tags">
                                 <h6 style="color: #1a1a1a; margin-bottom: 15px; font-weight: 600;">Tags:</h6>
                                 <?php foreach ($video['tags'] as $tag): ?>
                                     <a href="video_posts.php?search=<?= urlencode($tag['TagName']) ?>" class="tag">
                                         #<?= htmlspecialchars($tag['TagName']) ?>
                                     </a>
                                 <?php endforeach; ?>
                             </div>
                         <?php endif; ?>
 
                         <!-- Share Buttons -->
                         <div class="share-buttons">
                             <h6 style="color: #1a1a1a; margin-bottom: 20px; font-weight: 600;">Share this video:</h6>
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
                             <button type="button" class="share-btn share-copy" onclick="copyVideoLink()">
                                 <i class="fa fa-copy"></i> Copy Link
                             </button>
                         </div>
                     </div>

                                         <!-- Video Statistics -->
                     <div class="video-info-section">
                         <h5 class="section-title">Video Statistics</h5>
                         <div class="video-stats">
                             <div class="stat-card">
                                 <div class="stat-number"><?= number_format($video['Views']) ?></div>
                                 <div class="stat-label">Total Views</div>
                             </div>
                             <div class="stat-card">
                                 <div class="stat-number"><?= number_format($video['Likes'] ?? 0) ?></div>
                                 <div class="stat-label">Likes</div>
                             </div>
                             <div class="stat-card">
                                 <div class="stat-number"><?= number_format($video['Comments'] ?? $video['commentsCount'] ?? 0) ?></div>
                                 <div class="stat-label">Comments</div>
                             </div>
                             <div class="stat-card">
                                 <div class="stat-number"><?= number_format($video['Shares'] ?? 0) ?></div>
                                 <div class="stat-label">Shares</div>
                             </div>
                         </div>
                     </div>
                </div>

                                 <!-- Sidebar -->
                 <div class="col-lg-4">
                     <!-- Author Information -->
                     <div class="video-info-section">
                         <h5 class="section-title">About the Creator</h5>
                         <div class="author-info">
                             <?php if ($creatorProfile && !empty($creatorProfile['ProfilePhoto'])): ?>
                                 <div class="author-avatar">
                                     <img src="<?= htmlspecialchars($creatorProfile['ProfilePhoto']) ?>" 
                                          alt="<?= htmlspecialchars($creatorProfile['DisplayName'] ?? $video['FirstName'] . ' ' . $video['LastName']) ?>" 
                                          class="author-profile-image"
                                          onerror="this.src='php/defaultavatar/avatar.png'">
                                 </div>
                             <?php else: ?>
                                 <div class="author-avatar">
                                     <?= strtoupper(substr($video['FirstName'] ?? 'A', 0, 1)) ?>
                                 </div>
                             <?php endif; ?>
                             
                             <div class="author-details">
                                 <h6 class="author-name">
                                     <?php if ($creatorProfile && !empty($creatorProfile['DisplayName'])): ?>
                                         <?= htmlspecialchars($creatorProfile['DisplayName']) ?>
                                         <?php if ($creatorProfile['IsVerified']): ?>
                                             <i class="fa fa-check-circle text-primary" title="Verified Creator"></i>
                                         <?php endif; ?>
                                         <?php if ($creatorProfile['IsFeatured']): ?>
                                             <i class="fa fa-star text-warning" title="Featured Creator"></i>
                                         <?php endif; ?>
                                     <?php else: ?>
                                         <?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?>
                                     <?php endif; ?>
                                 </h6>
                                 
                                 <?php if ($creatorProfile && !empty($creatorProfile['Bio'])): ?>
                                     <p class="author-bio"><?= htmlspecialchars($creatorProfile['Bio']) ?></p>
                                 <?php else: ?>
                                     <p>Video Creator & Content Producer</p>
                                 <?php endif; ?>
                                 
                                 <div class="author-stats">
                                     <?php if ($creatorProfile): ?>
                                         <div class="stat-item">
                                             <i class="fa fa-users"></i>
                                             <span><?= number_format($creatorProfile['FollowersCount'] ?? 0) ?> followers</span>
                                         </div>
                                         <div class="stat-item">
                                             <i class="fa fa-file-text"></i>
                                             <span><?= number_format($creatorProfile['TotalArticles'] ?? 0) ?> articles</span>
                                         </div>
                                         <?php if (!empty($creatorProfile['Expertise'])): ?>
                                             <div class="stat-item">
                                                 <i class="fa fa-lightbulb"></i>
                                                 <span><?= htmlspecialchars($creatorProfile['Expertise']) ?></span>
                                             </div>
                                         <?php endif; ?>
                                         <?php if (!empty($creatorProfile['Location'])): ?>
                                             <div class="stat-item">
                                                 <i class="fa fa-map-marker"></i>
                                                 <span><?= htmlspecialchars($creatorProfile['Location']) ?></span>
                                             </div>
                                         <?php endif; ?>
                                         <?php if (isset($creatorProfile['YearsExperience']) && $creatorProfile['YearsExperience'] > 0): ?>
                                             <div class="stat-item">
                                                 <i class="fa fa-clock-o"></i>
                                                 <span><?= $creatorProfile['YearsExperience'] ?> years experience</span>
                                             </div>
                                         <?php endif; ?>
                                     <?php endif; ?>
                                 </div>
                                 
                                 <small class="text-muted">
                                     Member since <?= date('M Y', strtotime($video['Created_at'])) ?>
                                 </small>
                                 
                                 <?php if ($creatorProfile && !empty($creatorProfile['socialLinks'])): ?>
                                     <div class="author-social-links">
                                         <h6 class="social-links-title">Follow on Social Media</h6>
                                         <div class="social-links-grid">
                                             <?php foreach ($creatorProfile['socialLinks'] as $socialLink): ?>
                                                 <a href="<?= htmlspecialchars($socialLink['URL']) ?>" 
                                                    target="_blank" 
                                                    class="social-link-btn"
                                                    title="<?= htmlspecialchars($socialLink['Platform']) ?>">
                                                     <i class="fa fa-<?= strtolower($socialLink['Platform']) ?>"></i>
                                                 </a>
                                             <?php endforeach; ?>
                                         </div>
                                     </div>
                                 <?php endif; ?>
                             </div>
                         </div>
                     </div>
 
                     <!-- Related Videos -->
                     <?php if (!empty($relatedVideos)): ?>
                         <div class="related-videos">
                             <h5 class="section-title">Related Videos</h5>
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
         // Copy video link functionality
         function copyVideoLink() {
             const videoUrl = window.location.href;
             navigator.clipboard.writeText(videoUrl).then(function() {
                 // Show success message
                 const copyBtn = document.querySelector('.share-copy');
                 const originalText = copyBtn.innerHTML;
                 copyBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
                 copyBtn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                 
                 setTimeout(function() {
                     copyBtn.innerHTML = originalText;
                     copyBtn.style.background = 'linear-gradient(135deg, #6c757d, #495057)';
                 }, 2000);
             }).catch(function(err) {
                 console.error('Could not copy text: ', err);
                 alert('Failed to copy link. Please copy manually: ' + videoUrl);
             });
         }
         
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
             
             // Add smooth scrolling for anchor links
             document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                 anchor.addEventListener('click', function (e) {
                     e.preventDefault();
                     const target = document.querySelector(this.getAttribute('href'));
                     if (target) {
                         target.scrollIntoView({
                             behavior: 'smooth',
                             block: 'start'
                         });
                     }
                 });
             });
         });
     </script>
</body>
</html>
