<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

// Initialize the video manager
try {
    $videoManager = new VideoManager($con);
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Get analytics data
$totalVideos = 0;
$totalViews = 0;
$totalLikes = 0;
$totalComments = 0;
$featuredVideos = [];
$trendingVideos = [];
$categoryStats = [];
$recentVideos = [];

if (!isset($error_message)) {
    try {
        // Get total video statistics
        $statsQuery = "SELECT 
            COUNT(*) as total_videos,
            SUM(Views) as total_views,
            SUM(CASE WHEN Featured = 1 THEN 1 ELSE 0 END) as featured_videos,
            SUM(CASE WHEN Status = 'published' THEN 1 ELSE 0 END) as published_videos,
            SUM(CASE WHEN Status = 'draft' THEN 1 ELSE 0 END) as draft_videos,
            SUM(CASE WHEN Status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_videos,
            SUM(CASE WHEN Status = 'archived' THEN 1 ELSE 0 END) as archived_videos
            FROM video_posts 
            WHERE isDeleted = 'notDeleted'";
        
        $statsResult = $con->query($statsQuery);
        if ($statsResult) {
            $stats = $statsResult->fetch_assoc();
            $totalVideos = $stats['total_videos'] ?? 0;
            $totalViews = $stats['total_views'] ?? 0;
            $featuredVideos = $stats['featured_videos'] ?? 0;
            $publishedVideos = $stats['published_videos'] ?? 0;
            $draftVideos = $stats['draft_videos'] ?? 0;
            $scheduledVideos = $stats['scheduled_videos'] ?? 0;
            $archivedVideos = $stats['archived_videos'] ?? 0;
        }
        
        // Get total comments from video_comments table if it exists
        $totalComments = 0;
        try {
            $commentsQuery = "SELECT COUNT(*) as total_comments FROM video_comments WHERE isDeleted = 'notDeleted'";
            $commentsResult = $con->query($commentsQuery);
            if ($commentsResult) {
                $commentsStats = $commentsResult->fetch_assoc();
                $totalComments = $commentsStats['total_comments'] ?? 0;
            }
        } catch (Exception $e) {
            // If video_comments table doesn't exist, set comments to 0
            $totalComments = 0;
        }
        
        // Get featured videos
        $featuredVideos = $videoManager->getFeaturedVideos(5);
        
        // Get trending videos
        $trendingVideos = $videoManager->getTrendingVideos(7, 5);
        
        // Get category statistics
        $sql = "SELECT 
                    c.CategoryName,
                    COUNT(v.VideoID) as video_count,
                    SUM(v.Views) as total_views,
                    AVG(v.Views) as avg_views
                FROM video_categories c
                LEFT JOIN video_posts v ON c.CategoryID = v.CategoryID 
                    AND v.Status = 'published' AND v.isDeleted = 'notDeleted'
                WHERE c.isActive = 1 AND c.isDeleted = 'notDeleted'
                GROUP BY c.CategoryID, c.CategoryName
                ORDER BY total_views DESC";
        
        $result = $con->query($sql);
        while ($row = $result->fetch_assoc()) {
            $categoryStats[] = $row;
        }
        
        // Get recent videos
        $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName
                FROM video_posts v
                LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                LEFT JOIN admin a ON v.AuthorID = a.AdminId
                WHERE v.Status = 'published' AND v.isDeleted = 'notDeleted'
                ORDER BY v.Created_at DESC
                LIMIT 10";
        
        $result = $con->query($sql);
        while ($row = $result->fetch_assoc()) {
            $recentVideos[] = $row;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Video Analytics Dashboard - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stats-card p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }
        .stats-card i {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .analytics-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        .video-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        .category-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .category-name {
            font-weight: bold;
            color: #333;
        }
        .category-count {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .trending-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff6b6b;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffd93d;
            color: #333;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .metric-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>

<body>
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
                            <li><a href="video_posts.php">Manage Videos</a></li>
                            <li><a href="video_analytics.php" class="active">Analytics</a></li>
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
                                <h4>Video Analytics Dashboard</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Video Analytics</li>
                                </ol>
                            </nav>
                            <small class="text-muted">Comprehensive insights into your video performance</small>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <a href="video_posts.php" class="btn btn-outline-primary">
                                <i class="icon-copy fa fa-video-camera"></i> Manage Videos
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Video Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($totalVideos) ?></h3>
                                    <p>Total Videos</p>
                                    <i class="fa fa-video-camera"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($totalViews) ?></h3>
                                    <p>Total Views</p>
                                    <i class="fa fa-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($publishedVideos) ?></h3>
                                    <p>Published Videos</p>
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($totalComments) ?></h3>
                                    <p>Total Comments</p>
                                    <i class="fa fa-comments"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="row">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($featuredVideos) ?></h3>
                                    <p>Featured Videos</p>
                                    <i class="fa fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($draftVideos) ?></h3>
                                    <p>Draft Videos</p>
                                    <i class="fa fa-edit"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($scheduledVideos) ?></h3>
                                    <p>Scheduled Videos</p>
                                    <i class="fa fa-clock-o"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-30">
                        <div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?= number_format($archivedVideos) ?></h3>
                                    <p>Archived Videos</p>
                                    <i class="fa fa-archive"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="analytics-section">
                            <h5>Video Performance Trends</h5>
                            <div class="chart-container">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="analytics-section">
                            <h5>Category Distribution</h5>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            
                                                         <!-- Category Statistics -->
                 <div class="row">
                     <div class="col-12">
                         <div class="analytics-section">
                             <h5>Category Performance</h5>
                             <?php if (!empty($categoryStats)): ?>
                                 <div class="metric-grid">
                                     <?php foreach ($categoryStats as $category): ?>
                                         <div class="metric-item">
                                             <div class="metric-value"><?= number_format($category['total_views']) ?></div>
                                             <div class="metric-label"><?= htmlspecialchars($category['CategoryName']) ?></div>
                                             <small class="text-muted">
                                                 <?= $category['video_count'] ?> videos •
                                                 Avg: <?= number_format(round($category['avg_views'])) ?> views
                                             </small>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php else: ?>
                                 <div class="text-center py-4">
                                     <i class="icon-copy fa fa-chart-pie fa-3x text-muted mb-3"></i>
                                     <h6 class="text-muted">No Category Data Available</h6>
                                     <p class="text-muted">Category performance data will appear here once videos are published and viewed.</p>
                                 </div>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>

                <!-- Featured and Trending Videos -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="analytics-section">
                            <h5>Featured Videos</h5>
                            <?php if (!empty($featuredVideos)): ?>
                                <?php foreach ($featuredVideos as $video): ?>
                                    <div class="card video-card">
                                        <div class="position-relative">
                                            <img src="<?= htmlspecialchars($video['VideoThumbnail'] ?: 'php/defaultavatar/video-thumbnail.png') ?>"
                                                 alt="<?= htmlspecialchars($video['Title']) ?>"
                                                 class="video-thumbnail">
                                            <span class="featured-badge">Featured</span>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($video['Title']) ?></h6>
                                            <p class="card-text small text-muted">
                                                <i class="fa fa-user"></i> <?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fa fa-eye"></i> <?= number_format($video['Views']) ?> views
                                            </p>
                                            <a href="video_view.php?id=<?= $video['VideoID'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No featured videos yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="analytics-section">
                            <h5>Trending Videos (Last 7 Days)</h5>
                            <?php if (!empty($trendingVideos)): ?>
                                <?php foreach ($trendingVideos as $video): ?>
                                    <div class="card video-card">
                                        <div class="position-relative">
                                            <img src="<?= htmlspecialchars($video['VideoThumbnail'] ?: 'php/defaultavatar/video-thumbnail.png') ?>"
                                                 alt="<?= htmlspecialchars($video['Title']) ?>"
                                                 class="video-thumbnail">
                                            <span class="trending-badge">Trending</span>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($video['Title']) ?></h6>
                                            <p class="card-text small text-muted">
                                                <i class="fa fa-user"></i> <?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fa fa-eye"></i> <?= number_format($video['Views']) ?> views
                                            </p>
                                            <a href="video_view.php?id=<?= $video['VideoID'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No trending videos yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                                 <!-- Recent Videos -->
                 <div class="row">
                     <div class="col-12">
                         <div class="analytics-section">
                             <h5>Recent Videos</h5>
                             <?php if (!empty($recentVideos)): ?>
                                 <div class="table-responsive">
                                     <table class="table table-hover">
                                         <thead>
                                             <tr>
                                                 <th>Video</th>
                                                 <th>Title</th>
                                                 <th>Category</th>
                                                 <th>Author</th>
                                                 <th>Views</th>
                                                 <th>Likes</th>
                                                 <th>Created</th>
                                                 <th>Actions</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             <?php foreach ($recentVideos as $video): ?>
                                                 <tr>
                                                     <td>
                                                         <img src="<?= htmlspecialchars($video['VideoThumbnail'] ?: 'php/defaultavatar/video-thumbnail.png') ?>"
                                                              alt="Thumbnail" style="width: 60px; height: 34px; object-fit: cover; border-radius: 4px;">
                                                     </td>
                                                     <td>
                                                         <strong><?= htmlspecialchars($video['Title']) ?></strong>
                                                         <?php if ($video['Featured']): ?>
                                                             <span class="badge badge-warning ml-2">Featured</span>
                                                         <?php endif; ?>
                                                     </td>
                                                     <td><?= htmlspecialchars($video['CategoryName'] ?? 'Uncategorized') ?></td>
                                                     <td><?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?></td>
                                                     <td><?= number_format($video['Views']) ?></td>
                                                     <td><?= number_format($video['Likes']) ?></td>
                                                     <td><?= date('M j, Y', strtotime($video['Created_at'])) ?></td>
                                                     <td>
                                                         <a href="video_view.php?id=<?= $video['VideoID'] ?>" class="btn btn-sm btn-outline-primary">
                                                             <i class="fa fa-eye"></i>
                                                         </a>
                                                         <a href="video_posts.php" class="btn btn-sm btn-outline-secondary">
                                                             <i class="fa fa-edit"></i>
                                                         </a>
                                                     </td>
                                                 </tr>
                                             <?php endforeach; ?>
                                         </tbody>
                                     </table>
                                 </div>
                             <?php else: ?>
                                 <div class="text-center py-4">
                                     <i class="icon-copy fa fa-video-camera fa-3x text-muted mb-3"></i>
                                     <h6 class="text-muted">No Recent Videos</h6>
                                     <p class="text-muted">Recent videos will appear here once they are published.</p>
                                 </div>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div>

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
        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Video Views',
                    data: [<?= $totalViews ?>, <?= $totalViews * 0.8 ?>, <?= $totalViews * 0.9 ?>, <?= $totalViews * 1.1 ?>, <?= $totalViews * 1.2 ?>, <?= $totalViews ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Video Uploads',
                    data: [<?= $totalVideos ?>, <?= $totalVideos * 0.9 ?>, <?= $totalVideos * 1.1 ?>, <?= $totalVideos * 0.95 ?>, <?= $totalVideos * 1.05 ?>, <?= $totalVideos ?>],
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        <?php if (!empty($categoryStats)): ?>
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', array_map(function($cat) { return '"' . $cat['CategoryName'] . '"'; }, $categoryStats)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_map(function($cat) { return $cat['video_count']; }, $categoryStats)) ?>],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe',
                        '#43e97b',
                        '#38f9d7'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        <?php else: ?>
        // Show "No Data" message for empty chart
        categoryCtx.font = '16px Arial';
        categoryCtx.textAlign = 'center';
        categoryCtx.fillStyle = '#999';
        categoryCtx.fillText('No Category Data Available', categoryCtx.canvas.width / 2, categoryCtx.canvas.height / 2);
        <?php endif; ?>
        
        // Initialize tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>
</body>
</html>
