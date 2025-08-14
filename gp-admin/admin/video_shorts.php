<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

// Initialize the video manager
try {
    $videoManager = new VideoManager($con);
    $systemReady = true;
} catch (Exception $e) {
    $systemReady = false;
    $systemError = $e->getMessage();
}

// Only proceed with operations if the system is ready
if ($systemReady) {
    // Handle form submissions
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    if (isset($_POST['create_short'])) {
                        // Prepare short video data
                        $shortData = [
                            'title' => $_POST['title'],
                            'slug' => $_POST['slug'],
                            'description' => $_POST['description'] ?? '',
                            'categoryID' => $_POST['categoryID'] ?? null,
                            'tags' => $_POST['tags'] ?? '',
                            'status' => $_POST['status'],
                            'publishDate' => $_POST['publishDate'] ?? null,
                            'featured' => isset($_POST['featured']) ? 1 : 0,
                            'allowComments' => isset($_POST['allowComments']) ? 1 : 0,
                            'metaTitle' => $_POST['metaTitle'] ?? $_POST['title'],
                            'metaDescription' => $_POST['metaDescription'] ?? $_POST['description'] ?? '',
                            'metaKeywords' => $_POST['metaKeywords'] ?? $_POST['tags'] ?? '',
                            'videoFormat' => 'short',  // Mark as short video
                            'videoResolution' => '1080x1920'  // Vertical aspect ratio
                        ];
                        
                        // Handle video file upload
                        if (!empty($_FILES['videoFile']['name'])) {
                            $shortData['videoFile'] = $_FILES['videoFile'];
                            error_log('Short Video Creation - Video file uploaded: ' . $_FILES['videoFile']['name']);
                        }
                        
                        // Handle thumbnail upload
                        if (!empty($_FILES['videoThumbnail']['name'])) {
                            $shortData['videoThumbnail'] = $_FILES['videoThumbnail'];
                            error_log('Short Video Creation - Thumbnail uploaded: ' . $_FILES['videoThumbnail']['name']);
                        }
                        
                        // Get author ID from form
                        $authorId = $_POST['authorId'] ?? $user_uniqueid;
                        
                        if (empty($authorId)) {
                            throw new Exception('Author ID is required');
                        }
                        
                        // Create short video
                        $videoId = $videoManager->createVideo($authorId, $shortData);
                        
                        if ($videoId) {
                            $success_message = "Short video created successfully! Video ID: $videoId";
                        } else {
                            throw new Exception('Failed to create short video');
                        }
                    }
                    break;
                    
                case 'delete':
                    if (isset($_POST['delete_short'])) {
                        $videoId = $_POST['video_id'];
                        $videoManager->deleteVideo($videoId);
                        $success_message = 'Short video deleted successfully!';
                    }
                    break;
                    
                case 'restore':
                    if (isset($_POST['restore_short'])) {
                        $videoId = $_POST['video_id'];
                        $videoManager->restoreVideo($videoId);
                        $success_message = 'Short video restored successfully!';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log('Short Video Error: ' . $e->getMessage());
        }
    }

    // Get current page and filters
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $filters = [
        'status' => $_GET['status'] ?? '',
        'featured' => $_GET['featured'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];

    // Get short videos with pagination (filter by videoType = 'short')
    $filters['videoType'] = 'short';
    $videosData = $videoManager->getAllVideos($page, 20, $filters);
    $videos = $videosData['videos'];
    $totalPages = $videosData['pages'];
    $currentPage = $videosData['current_page'];

    // Get video categories for the form
    $categories = $videoManager->getCategories();
} else {
    // System not ready, set default values
    $videos = [];
    $totalPages = 0;
    $currentPage = 1;
    $categories = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Video Shorts Management - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <style>
        .shorts-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .short-video-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .short-video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .short-video-player {
            position: relative;
            width: 100%;
            height: 400px;
            background: #000;
            overflow: hidden;
        }
        
        .short-video-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        
        .short-video-thumbnail:hover {
            opacity: 0.9;
        }
        
        .short-video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .short-video-card:hover .short-video-overlay {
            opacity: 1;
        }
        
        .play-button-large {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .play-button-large:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,1);
        }
        
        .play-button-large i {
            font-size: 32px;
            color: #333;
            margin-left: 4px;
        }
        
        .short-video-info {
            padding: 20px;
        }
        
        .short-video-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .short-video-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meta-item i {
            color: #4e73df;
        }
        
        .short-video-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f6c23e 0%, #f39c12 100%);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
            color: white;
        }
        
        .btn-restore {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .shorts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .create-short-btn {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .create-short-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .create-short-btn i {
            margin-right: 10px;
        }
        
        .filters-section {
            background: #f8f9fc;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e3e6f0;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #d1d3e2;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-filter {
            background: #4e73df;
            color: white;
        }
        
        .btn-clear {
            background: #6c757d;
            color: white;
        }
        
        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
         
         .view-toggle .btn {
             margin-right: 5px;
         }
         
         .view-toggle .btn.active {
             background-color: #4e73df;
             color: white;
             border-color: #4e73df;
         }
         
         .table-thumbnail {
             width: 60px;
             height: 40px;
             object-fit: cover;
             border-radius: 4px;
         }
         
         .pagination-wrapper {
             margin-top: 2rem;
         }
         
         .pagination-info {
             font-size: 0.9rem;
         }
        
        .no-shorts {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-shorts i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .no-shorts h4 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        @media (max-width: 768px) {
            .shorts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .short-video-player {
                height: 300px;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-actions {
                justify-content: center;
            }
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
                            <span class="micon"><i class="icon-copy fa fa-video-camera" aria-hidden="true"></i></span>
                            <span class="mtext">Videos</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="video_posts.php">Posts</a></li>
                            <li><a href="video_shorts.php" class="active">Shorts</a></li>
                            <li><a href="video_analytics.php">Analytics</a></li>
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
            <div class="shorts-container">
                <!-- Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-2">Video Shorts Management</h2>
                        <p class="text-muted mb-0">View and manage short-form videos (TikTok/Instagram Reels style)</p>
                    </div>
                                         <button class="create-short-btn" onclick="openCreateShortModal()">
                        <i class="icon-copy fa fa-plus"></i> Create Short
                    </button>
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

                <!-- Filters Section -->
                <div class="filters-section">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                         <div class="view-toggle">
                             <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleView('cards')" id="cardsViewBtn">
                                 <i class="fa fa-th-large"></i> Cards
                             </button>
                             <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleView('table')" id="tableViewBtn">
                                 <i class="fa fa-table"></i> Table
                             </button>
                         </div>
                         <div class="pagination-info">
                             <small class="text-muted">
                                 Showing <?= (($currentPage - 1) * 20) + 1 ?> to <?= min($currentPage * 20, $videosData['total'] ?? 0) ?> 
                                 of <?= $videosData['total'] ?? 0 ?> shorts
                             </small>
                         </div>
                     </div>
                     
                    <form method="GET" class="filter-row">
                        <div class="filter-group">
                            <label>Search Shorts</label>
                            <input type="text" name="search" placeholder="Search by title, description..." 
                                   value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="scheduled" <?= $filters['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="archived" <?= $filters['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Featured</label>
                            <select name="featured">
                                <option value="">All</option>
                                <option value="1" <?= $filters['featured'] === '1' ? 'selected' : '' ?>>Featured</option>
                                <option value="0" <?= $filters['featured'] === '0' ? 'selected' : '' ?>>Not Featured</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="filter-btn btn-filter">Filter</button>
                            <a href="video_shorts.php" class="filter-btn btn-clear">Clear</a>
                        </div>
                    </form>
                </div>

                <!-- Shorts Grid -->
                 <div id="cardsView">
                <?php if (empty($videos)): ?>
                    <div class="no-shorts">
                        <i class="icon-copy fa fa-video-camera"></i>
                        <h4>No Short Videos Found</h4>
                             <p>No short videos have been created yet.</p>
                             <button class="create-short-btn" onclick="openCreateShortModal()">
                            <i class="icon-copy fa fa-plus"></i> Create Your First Short
                        </button>
                    </div>
                <?php else: ?>
                    <div class="shorts-grid">
                        <?php foreach ($videos as $video): ?>
                            <div class="short-video-card">
                                <div class="short-video-player">
                                    <?php 
                                    $thumbnailSrc = 'images/default-video-thumbnail.jpg';
                                    if (!empty($video['VideoThumbnail'])) {
                                        if (filter_var($video['VideoThumbnail'], FILTER_VALIDATE_URL)) {
                                            $thumbnailSrc = $video['VideoThumbnail'];
                                        } elseif (file_exists($video['VideoThumbnail'])) {
                                            $thumbnailSrc = $video['VideoThumbnail'];
                                        } else {
                                            $thumbnailSrc = $video['VideoThumbnail'];
                                        }
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($thumbnailSrc) ?>" 
                                         alt="Short video thumbnail" 
                                         class="short-video-thumbnail"
                                         onerror="this.src='images/default-video-thumbnail.jpg';">
                                    
                                    <div class="short-video-overlay">
                                        <div class="play-button-large" onclick="playShortVideo(<?= $video['VideoID'] ?>)">
                                            <i class="icon-copy fa fa-play"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="short-video-info">
                                    <h5 class="short-video-title"><?= htmlspecialchars($video['Title']) ?></h5>
                                    
                                    <div class="short-video-meta">
                                        <div class="meta-item">
                                            <i class="icon-copy fa fa-calendar"></i>
                                            <?= date('M j, Y', strtotime($video['Created_at'])) ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="icon-copy fa fa-eye"></i>
                                            <?= number_format($video['Views'] ?? 0) ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="icon-copy fa fa-comment"></i>
                                            <?= number_format($video['Comments'] ?? 0) ?>
                                        </div>
                                        <?php if ($video['Featured']): ?>
                                            <div class="meta-item">
                                                <i class="icon-copy fa fa-star" style="color: #f6c23e;"></i>
                                                Featured
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="short-video-actions">
                                         <a href="video_view.php?id=<?= $video['VideoID'] ?>" class="action-btn btn-view" target="_blank">
                                            <i class="icon-copy fa fa-play"></i> View
                                        </a>
                                         <button type="button" class="action-btn btn-edit" onclick="editShort(<?= $video['VideoID'] ?>, <?= htmlspecialchars(json_encode($video)) ?>)">
                                            <i class="icon-copy fa fa-edit"></i> Edit
                                        </button>
                                        <?php if ($video['isDeleted'] === 'notDeleted'): ?>
                                            <button type="button" class="action-btn btn-delete" onclick="deleteShort(<?= $video['VideoID'] ?>, '<?= htmlspecialchars($video['Title']) ?>')">
                                                <i class="icon-copy fa fa-trash"></i> Delete
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="action-btn btn-restore" onclick="restoreShort(<?= $video['VideoID'] ?>)">
                                                <i class="icon-copy fa fa-undo"></i> Restore
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                 
                 <!-- Table View (Hidden by default) -->
                 <div id="tableView" style="display: none;">
                     <?php if (empty($videos)): ?>
                         <div class="no-shorts">
                             <i class="icon-copy fa fa-video-camera"></i>
                             <h4>No Short Videos Found</h4>
                             <p>No short videos have been created yet.</p>
                             <button class="create-short-btn" onclick="openCreateShortModal()">
                                 <i class="icon-copy fa fa-plus"></i> Create Your First Short
                             </button>
            </div>
                     <?php else: ?>
                         <div class="table-responsive">
                             <table class="table table-striped table-hover">
                                 <thead class="thead-dark">
                                     <tr>
                                         <th>Thumbnail</th>
                                         <th>Title</th>
                                         <th>Category</th>
                                         <th>Status</th>
                                         <th>Featured</th>
                                         <th>Views</th>
                                         <th>Created</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach ($videos as $video): ?>
                                         <?php
        $thumbnailSrc = 'images/default-video-thumbnail.jpg';
        if (!empty($video['VideoThumbnail'])) {
            if (filter_var($video['VideoThumbnail'], FILTER_VALIDATE_URL)) {
                $thumbnailSrc = $video['VideoThumbnail'];
            } elseif (file_exists($video['VideoThumbnail'])) {
                $thumbnailSrc = $video['VideoThumbnail'];
            } else {
                $thumbnailSrc = $video['VideoThumbnail'];
            }
        }
        ?>
                                         <tr>
                                             <td>
                                                 <img src="<?= htmlspecialchars($thumbnailSrc) ?>" 
                                                      alt="Thumbnail" 
                                                      class="table-thumbnail"
                                                      onerror="this.src='images/default-video-thumbnail.jpg';">
                                             </td>
                                             <td>
                                                 <strong><?= htmlspecialchars($video['Title']) ?></strong>
                                                 <br><small class="text-muted"><?= htmlspecialchars($video['Slug']) ?></small>
                                             </td>
                                             <td><?= htmlspecialchars($video['CategoryName'] ?? 'Uncategorized') ?></td>
                                             <td>
                                                 <span class="badge badge-<?= $video['Status'] === 'published' ? 'success' : ($video['Status'] === 'draft' ? 'warning' : 'info') ?>">
                                                     <?= ucfirst($video['Status']) ?>
                                                 </span>
                                             </td>
                                             <td>
                                                 <?php if ($video['Featured']): ?>
                                                     <span class="badge badge-warning"><i class="fa fa-star"></i> Featured</span>
                                                 <?php else: ?>
                                                     <span class="text-muted">-</span>
                                                 <?php endif; ?>
                                             </td>
                                             <td><?= number_format($video['Views'] ?? 0) ?></td>
                                             <td><?= date('M j, Y', strtotime($video['Created_at'])) ?></td>
                                             <td>
                                                 <div class="btn-group btn-group-sm" role="group">
                                                     <a href="video_view.php?id=<?= $video['VideoID'] ?>" class="btn btn-primary btn-sm" target="_blank" title="View">
                                                         <i class="fa fa-play"></i>
                                                     </a>
                                                     <button type="button" class="btn btn-warning btn-sm" onclick="editShort(<?= $video['VideoID'] ?>, <?= htmlspecialchars(json_encode($video)) ?>)" title="Edit">
                                                         <i class="fa fa-edit"></i>
                                                     </button>
                                                     <?php if ($video['isDeleted'] === 'notDeleted'): ?>
                                                         <button type="button" class="btn btn-danger btn-sm" onclick="deleteShort(<?= $video['VideoID'] ?>, '<?= htmlspecialchars($video['Title']) ?>')" title="Delete">
                                                             <i class="fa fa-trash"></i>
                                                         </button>
                                                     <?php else: ?>
                                                         <button type="button" class="btn btn-success btn-sm" onclick="restoreShort(<?= $video['VideoID'] ?>)" title="Restore">
                                                             <i class="fa fa-undo"></i>
                                                         </button>
                                                     <?php endif; ?>
                                                 </div>
                                             </td>
                                         </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                     <?php endif; ?>
                 </div>
                 
                 <!-- Pagination -->
                 <?php if ($totalPages > 1): ?>
                     <div class="pagination-wrapper">
                         <nav aria-label="Shorts pagination">
                             <ul class="pagination justify-content-center">
                                 <?php if ($currentPage > 1): ?>
                                     <li class="page-item">
                                         <a class="page-link" href="?page=<?= $currentPage - 1 ?>&status=<?= $filters['status'] ?>&featured=<?= $filters['featured'] ?>&search=<?= urlencode($filters['search']) ?>">
                                             <i class="fa fa-chevron-left"></i> Previous
                                         </a>
                                     </li>
                                 <?php endif; ?>
                                 
                                 <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                     <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                         <a class="page-link" href="?page=<?= $i ?>&status=<?= $filters['status'] ?>&featured=<?= $filters['featured'] ?>&search=<?= urlencode($filters['search']) ?>">
                                             <?= $i ?>
                                         </a>
                                     </li>
                                 <?php endfor; ?>
                                 
                                 <?php if ($currentPage < $totalPages): ?>
                                     <li class="page-item">
                                         <a class="page-link" href="?page=<?= $currentPage + 1 ?>&status=<?= $filters['status'] ?>&featured=<?= $filters['featured'] ?>&search=<?= urlencode($filters['search']) ?>">
                                             Next <i class="fa fa-chevron-right"></i>
                                         </a>
                                     </li>
                                 <?php endif; ?>
                             </ul>
                         </nav>
                     </div>
                 <?php endif; ?>
             </div>
         </div>
     </div>



    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteShortModal" tabindex="-1" role="dialog" aria-labelledby="deleteShortModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteShortModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the short video "<span id="delete_short_title"></span>"?</p>
                    <p class="text-warning"><small>This action will soft delete the video and can be restored later.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="video_id" id="delete_short_id">
                        <button type="submit" name="delete_short" class="btn btn-danger">Delete Short</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreShortModal" tabindex="-1" role="dialog" aria-labelledby="restoreShortModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreShortModalLabel">Confirm Restore</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore this short video?</p>
                    <p class="text-info"><small>This will reactivate the video and make it visible again.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="video_id" id="restore_short_id">
                        <button type="submit" name="restore_short" class="btn btn-success">Restore Short</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Short Modal -->
    <div class="modal fade" id="createShortModal" tabindex="-1" role="dialog" aria-labelledby="createShortModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createShortModalLabel">Create New Short Video</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                 <form id="createShortForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                     <input type="hidden" name="profileId" value="<?= $user_profileid ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                     <label>Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" placeholder="Enter short video title..." required>
                                </div>
                                <div class="form-group">
                                     <label>Slug <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="slug" placeholder="short-video-slug" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="3" placeholder="Describe your short video..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Tags</label>
                                    <input type="text" class="form-control" name="tags" placeholder="Enter tags separated by commas">
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
                                     <select name="videoType" class="form-control" required onchange="toggleShortVideoFields(this.value)">
                                         <option value="">Select Type</option>
                                         <option value="video">Regular Video</option>
                                         <option value="short" selected>Short Video (TikTok/Reels)</option>
                                     </select>
                                 </div>
                                 <div class="form-group">
                                     <label>Status <span class="text-danger">*</span></label>
                                                                      <select name="status" class="form-control" required onchange="togglePublishDate(this.value)">
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="scheduled">Scheduled</option>
                                    </select>
                                </div>
                                 <div class="form-group" id="publishDateGroup" style="display: none;">
                                     <label>Publish Date <span class="text-danger">*</span></label>
                                     <input type="datetime-local" class="form-control" name="publishDate" required>
                                     <small class="text-muted">Select when this short should be published</small>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="featured" id="featured">
                                        <label class="custom-control-label" for="featured">Featured Short</label>
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
                                <div class="form-group">
                                     <label>Short Video File <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control-file" name="videoFile" accept="video/*" required>
                                     <small class="text-muted">Upload MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong></small>
                                 </div>
                                 <div class="form-group" style="display: none;">
                                     <label>Embed Code/URL</label>
                                     <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste YouTube, Vimeo, or other embed code here..."></textarea>
                                     <small class="text-muted">For external video links (YouTube, Vimeo, etc.)</small>
                                </div>
                                <div class="form-group">
                                    <label>Video Thumbnail</label>
                                    <input type="file" class="form-control-file" name="videoThumbnail" accept="image/*">
                                    <small class="text-muted">Upload JPG, PNG, or GIF (max 2MB). Will be automatically compressed.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>SEO Settings</h6>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" class="form-control" name="metaTitle" placeholder="SEO title for search engines">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea class="form-control" name="metaDescription" rows="3" placeholder="SEO description for search engines"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Keywords</label>
                                    <input type="text" class="form-control" name="metaKeywords" placeholder="SEO keywords separated by commas">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                         <button type="submit" class="btn btn-primary">Create Short</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     <!-- Edit Short Modal -->
     <div class="modal fade" id="editShortModal" tabindex="-1" role="dialog" aria-labelledby="editShortModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                     <h5 class="modal-title" id="editShortModalLabel">Edit Short Video</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                 <form id="editShortForm" enctype="multipart/form-data">
                     <input type="hidden" name="action" value="update">
                     <input type="hidden" name="video_id" id="editVideoId">
                     <input type="hidden" name="profileId" value="<?= $user_profileid ?>">
                <div class="modal-body">
                         <div class="row">
                             <div class="col-md-8">
                                 <div class="form-group">
                                     <label>Title <span class="text-danger">*</span></label>
                                     <input type="text" class="form-control" name="title" id="editTitle" placeholder="Enter short video title..." required>
                </div>
                                 <div class="form-group">
                                     <label>Slug <span class="text-danger">*</span></label>
                                     <input type="text" class="form-control" name="slug" id="editSlug" placeholder="short-video-slug" required>
                </div>
                                 <div class="form-group">
                                     <label>Description</label>
                                     <textarea class="form-control" name="description" id="editDescription" rows="3" placeholder="Describe your short video..."></textarea>
                                 </div>
                                 <div class="form-group">
                                     <label>Tags</label>
                                     <input type="text" class="form-control" name="tags" id="editTags" placeholder="Enter tags separated by commas">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="form-group">
                                     <label>Category</label>
                                     <select name="categoryID" id="editCategoryID" class="form-control">
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
                                     <select name="videoType" id="editVideoType" class="form-control" required onchange="toggleEditShortVideoFields(this.value)">
                                         <option value="">Select Type</option>
                                         <option value="video">Regular Video</option>
                                         <option value="short" selected>Short Video (TikTok/Reels)</option>
                                     </select>
                                 </div>
                                 <div class="form-group">
                                     <label>Status <span class="text-danger">*</span></label>
                                                                      <select name="status" id="editStatus" class="form-control" required onchange="toggleEditPublishDate(this.value)">
                                     <option value="draft">Draft</option>
                                     <option value="published">Published</option>
                                     <option value="scheduled">Scheduled</option>
                                 </select>
                                 </div>
                                 <div class="form-group" id="editPublishDateGroup" style="display: none;">
                                     <label>Publish Date <span class="text-danger">*</span></label>
                                     <input type="datetime-local" class="form-control" name="publishDate" id="editPublishDate" required>
                                     <small class="text-muted">Select when this short should be published</small>
                                 </div>
                                 <div class="form-group">
                                     <div class="custom-control custom-checkbox">
                                         <input type="checkbox" class="custom-control-input" name="featured" id="editFeatured">
                                         <label class="custom-control-label" for="editFeatured">Featured Short</label>
                                     </div>
                                 </div>
                                 <div class="form-group">
                                     <div class="custom-control custom-checkbox">
                                         <input type="checkbox" class="custom-control-input" name="allowComments" id="editAllowComments">
                                         <label class="custom-control-label" for="editAllowComments">Allow Comments</label>
                                     </div>
            </div>
        </div>
    </div>

                         <hr>
                         
                         <div class="row">
                             <div class="col-md-6">
                                 <h6>Video Content</h6>
                                 <div class="form-group">
                                     <label>Short Video File</label>
                                     <input type="file" class="form-control-file" name="videoFile" accept="video/*">
                                     <small class="text-muted">Upload new MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong></small>
                </div>
                                 <div class="form-group" style="display: none;">
                                     <label>Embed Code/URL</label>
                                     <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste YouTube, Vimeo, or other embed code here..."></textarea>
                                     <small class="text-muted">For external video links (YouTube, Vimeo, etc.)</small>
                                 </div>
                                 <div class="form-group">
                                     <label>Video Thumbnail</label>
                                     <input type="file" class="form-control-file" name="videoThumbnail" accept="image/*">
                                     <small class="text-muted">Upload new JPG, PNG, or GIF (max 2MB). Will be automatically compressed.</small>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <h6>SEO Settings</h6>
                                 <div class="form-group">
                                     <label>Meta Title</label>
                                     <input type="text" class="form-control" name="metaTitle" id="editMetaTitle" placeholder="SEO title for search engines">
                                 </div>
                                 <div class="form-group">
                                     <label>Meta Description</label>
                                     <textarea class="form-control" name="metaDescription" id="editMetaDescription" rows="3" placeholder="SEO description for search engines"></textarea>
                                 </div>
                                 <div class="form-group">
                                     <label>Meta Keywords</label>
                                     <input type="text" class="form-control" name="metaKeywords" id="editMetaKeywords" placeholder="SEO keywords separated by commas">
                                 </div>
                             </div>
                         </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                         <button type="submit" class="btn btn-primary">Update Short</button>
                     </div>
                    </form>
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
                            // Function to open create short modal
         function openCreateShortModal() {
             $('#createShortModal').modal('show');
             // Initialize publish date field visibility
             togglePublishDate('draft');
         }
          
          // Function to toggle between card and table view
          function toggleView(viewType) {
              const cardsView = document.getElementById('cardsView');
              const tableView = document.getElementById('tableView');
              const cardsBtn = document.getElementById('cardsViewBtn');
              const tableBtn = document.getElementById('tableViewBtn');
              
              if (viewType === 'cards') {
                  cardsView.style.display = 'block';
                  tableView.style.display = 'none';
                  cardsBtn.classList.add('active');
                  tableBtn.classList.remove('active');
                  localStorage.setItem('shortsViewType', 'cards');
              } else {
                  cardsView.style.display = 'none';
                  tableView.style.display = 'block';
                  tableBtn.classList.add('active');
                  cardsBtn.classList.remove('active');
                  localStorage.setItem('shortsViewType', 'table');
              }
          }
          
          // Set initial view based on localStorage
          document.addEventListener('DOMContentLoaded', function() {
              const savedView = localStorage.getItem('shortsViewType') || 'cards';
              toggleView(savedView);
          });
         
         // Function to toggle fields based on video type in create modal
         function toggleShortVideoFields(videoType) {
             const videoFileGroup = document.querySelector('#createShortModal .form-group:has(input[name="videoFile"])');
             const embedCodeGroup = document.querySelector('#createShortModal .form-group:has(textarea[name="embedCode"])');
             
             if (videoType === 'short') {
                 // For shorts, show video file upload only
                 if (videoFileGroup) videoFileGroup.style.display = 'block';
                 if (embedCodeGroup) embedCodeGroup.style.display = 'none';
                 
                 // Update labels for shorts
                 const videoFileLabel = document.querySelector('#createShortModal label:has(+ input[name="videoFile"])');
                 if (videoFileLabel) videoFileLabel.innerHTML = 'Short Video File <span class="text-danger">*</span>';
                 
                 const videoFileHelp = document.querySelector('#createShortModal input[name="videoFile"] + small');
                 if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong>';
             } else if (videoType === 'video') {
                 // For regular videos, show both options
                 if (videoFileGroup) videoFileGroup.style.display = 'block';
                 if (embedCodeGroup) embedCodeGroup.style.display = 'block';
                 
                 // Update labels for regular videos
                 const videoFileLabel = document.querySelector('#createShortModal label:has(+ input[name="videoFile"])');
                 if (videoFileLabel) videoFileLabel.innerHTML = 'Video File <span class="text-danger">*</span>';
                 
                 const videoFileHelp = document.querySelector('#createShortModal input[name="videoFile"] + small');
                 if (videoFileHelp) videoFileHelp.textContent = 'Upload MP4, MOV, or AVI file (max 100MB)';
             }
         }
         
         // Function to toggle fields based on video type in edit modal
         function toggleEditShortVideoFields(videoType) {
             const videoFileGroup = document.querySelector('#editShortModal .form-group:has(input[name="videoFile"])');
             const embedCodeGroup = document.querySelector('#editShortModal .form-group:has(textarea[name="embedCode"])');
             
             if (videoType === 'short') {
                 // For shorts, show video file upload only
                 if (videoFileGroup) videoFileGroup.style.display = 'block';
                 if (embedCodeGroup) embedCodeGroup.style.display = 'none';
                 
                 // Update labels for shorts
                 const videoFileLabel = document.querySelector('#editShortModal label:has(+ input[name="videoFile"])');
                 if (videoFileLabel) videoFileLabel.innerHTML = 'Short Video File';
                 
                 const videoFileHelp = document.querySelector('#editShortModal input[name="videoFile"] + small');
                 if (videoFileHelp) videoFileHelp.textContent = 'Upload new MP4, MOV, or AVI file (max 100MB). <strong>REQUIRED: 1080x1920 (9:16) aspect ratio for shorts.</strong>';
             } else if (videoType === 'video') {
                 // For regular videos, show both options
                 if (videoFileGroup) videoFileGroup.style.display = 'block';
                 if (embedCodeGroup) embedCodeGroup.style.display = 'block';
                 
                 // Update labels for regular videos
                 const videoFileLabel = document.querySelector('#editShortModal label:has(+ input[name="videoFile"])');
                 if (videoFileLabel) videoFileLabel.innerHTML = 'Video File';
                 
                 const videoFileHelp = document.querySelector('#editShortModal input[name="videoFile"] + small');
                 if (videoFileHelp) videoFileHelp.textContent = 'Upload new MP4, MOV, or AVI file (max 100MB)';
             }
         }
         
         // Toggle publish date field based on status
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
         
        // Auto-generate slug from title
         document.querySelector('#createShortModal input[name="title"]').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
             document.querySelector('#createShortModal input[name="slug"]').value = slug;
        });
        
        // Play short video function
        function playShortVideo(videoId) {
            // Redirect to video view page
            window.location.href = `video_view.php?id=${videoId}`;
        }
        
                 // Edit short function - opens modal with video data
         function editShort(videoId, videoData) {
             // Populate the edit form with video data
             document.getElementById('editVideoId').value = videoId;
             document.getElementById('editTitle').value = videoData.Title || '';
             document.getElementById('editSlug').value = videoData.Slug || '';
             document.getElementById('editDescription').value = videoData.Description || '';
             document.getElementById('editTags').value = videoData.Tags || '';
             document.getElementById('editCategoryID').value = videoData.CategoryID || '';
             document.getElementById('editVideoType').value = videoData.videoType || 'short';
             document.getElementById('editStatus').value = videoData.Status || 'draft';
             document.getElementById('editPublishDate').value = videoData.PublishDate ? videoData.PublishDate.replace(' ', 'T') : '';
             document.getElementById('editFeatured').checked = videoData.Featured == 1;
             
             // Toggle publish date field visibility based on status
             toggleEditPublishDate(videoData.Status || 'draft');
             document.getElementById('editAllowComments').checked = videoData.AllowComments == 1;
             document.getElementById('editMetaTitle').value = videoData.MetaTitle || '';
             document.getElementById('editMetaDescription').value = videoData.MetaDescription || '';
             document.getElementById('editMetaKeywords').value = videoData.MetaKeywords || '';
             
             // Show the edit modal
             $('#editShortModal').modal('show');
        }
        
        // Delete short function
        function deleteShort(videoId, videoTitle) {
            document.getElementById('delete_short_id').value = videoId;
            document.getElementById('delete_short_title').textContent = videoTitle;
            $('#deleteShortModal').modal('show');
        }
        
        // Restore short function
        function restoreShort(videoId) {
            document.getElementById('restore_short_id').value = videoId;
            $('#restoreShortModal').modal('show');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
         // Handle create short form submission
         document.getElementById('createShortForm').addEventListener('submit', function(e) {
             e.preventDefault();
             
             const formData = new FormData(this);
             
             // Show loading state
             const submitBtn = this.querySelector('button[type="submit"]');
             const originalText = submitBtn.textContent;
             submitBtn.disabled = true;
             submitBtn.textContent = 'Creating...';
             
             fetch('ajax_create_video.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     // Show success message
                     showAlert('success', data.message);
                     
                     // Close modal and reset form
                     $('#createShortModal').modal('hide');
                     this.reset();
                     
                     // Reload page to show new short
                     setTimeout(() => {
                         location.reload();
                     }, 1500);
                 } else {
                     showAlert('error', data.error || 'Failed to create short video');
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 showAlert('error', 'An error occurred while creating the short video');
             })
             .finally(() => {
                 // Reset button state
                 submitBtn.disabled = false;
                 submitBtn.textContent = originalText;
             });
         });
         
         // Handle edit short form submission
         document.getElementById('editShortForm').addEventListener('submit', function(e) {
             e.preventDefault();
             
             const formData = new FormData(this);
             
             // Show loading state
             const submitBtn = this.querySelector('button[type="submit"]');
             const originalText = submitBtn.textContent;
             submitBtn.disabled = true;
             submitBtn.textContent = 'Updating...';
             
             fetch('ajax_update_video.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     // Show success message
                     showAlert('success', data.message);
                     
                     // Close modal
                     $('#editShortModal').modal('hide');
                     
                     // Reload page to show updated data
                     setTimeout(() => {
                         location.reload();
                     }, 1500);
                 } else {
                     showAlert('error', data.error || 'Failed to update short video');
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 showAlert('error', 'An error occurred while updating the short video');
             })
             .finally(() => {
                 // Reset button state
                 submitBtn.disabled = false;
                 submitBtn.textContent = originalText;
             });
         });
         
         // Helper function to show alerts
         function showAlert(type, message) {
             const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
             const alertHtml = `
                 <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                     ${message}
                     <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
             `;
             
             // Insert alert at the top of the page
             const container = document.querySelector('.shorts-container');
             container.insertAdjacentHTML('afterbegin', alertHtml);
             
             // Auto-hide after 5 seconds
             setTimeout(() => {
                 const alert = container.querySelector('.alert');
                 if (alert) {
                     alert.remove();
                 }
             }, 5000);
         }
    </script>
</body>
</html>
