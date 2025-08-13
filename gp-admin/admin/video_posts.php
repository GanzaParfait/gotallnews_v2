<?php
include 'php/header/top.php';
include 'php/includes/VideoManager.php';

// Initialize the video manager
$videoManager = null;
try {
    // Check if database connection is available
    if (!isset($con) || !$con) {
        throw new Exception("Database connection not available");
    }
    
    $videoManager = new VideoManager($con);
    $systemReady = true;
} catch (Exception $e) {
    $error_message = "Failed to initialize Video Manager: " . $e->getMessage();
    error_log("VideoManager initialization error: " . $e->getMessage());
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
                        
                        $videoId = $videoManager->createVideo($user_uniqueid, $videoData);
                        $success_message = 'Video post created successfully!';
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
        $error_message = "Error loading video data: " . $e->getMessage();
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
    <title>Video Posts Management - <?= $names; ?></title>
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
                            <li><a href="video_posts.php" class="active">Manage Videos</a></li>
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
                                <h4>Video Posts Management</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Video Posts</li>
                                </ol>
                            </nav>
                            <small class="text-muted">Manage all video content including creation, editing, and publishing</small>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#createVideoModal">
                                <i class="icon-copy fa fa-plus"></i> Create Video Post
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
                                    <i class="icon-copy fa fa-plus"></i> Create Video Post
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
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
                                            <tr>
                                                <td>
                                                    <div class="position-relative">
                                                        <img src="<?= htmlspecialchars($video['VideoThumbnail'] ?: 'php/defaultavatar/video-thumbnail.png') ?>"
                                                             alt="Thumbnail" class="video-thumbnail">
                                                        <?php if ($video['VideoDuration'] > 0): ?>
                                                            <span class="video-duration"><?= gmdate('i:s', $video['VideoDuration']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($video['Title']) ?></strong>
                                                    <?php if ($video['Featured']): ?>
                                                        <span class="badge badge-warning ml-2">Featured</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($video['Excerpt'] ?: 'No excerpt') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($video['CategoryName'] ?? 'Uncategorized') ?></td>
                                                <td><?= htmlspecialchars($video['FirstName'] . ' ' . $video['LastName']) ?></td>
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
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>Slug *</label>
                                    <input type="text" class="form-control" name="slug" required>
                                </div>
                                <div class="form-group">
                                    <label>Excerpt</label>
                                    <textarea class="form-control" name="excerpt" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="5"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="categoryID" class="form-control">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['CategoryID'] ?>"><?= htmlspecialchars($category['CategoryName']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div class="form-group" id="publishDateGroup" style="display: none;">
                                    <label>Publish Date</label>
                                    <input type="datetime-local" name="publishDate" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Tags</label>
                                    <input type="text" class="form-control" name="tags" placeholder="tag1, tag2, tag3">
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
                                <div class="form-group">
                                    <label>Video File</label>
                                    <input type="file" class="form-control-file" name="videoFile" accept="video/*">
                                </div>
                                <div class="form-group">
                                    <label>Or Embed Code/URL</label>
                                    <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste embed code or URL here..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Embed Source</label>
                                    <select name="embedSource" class="form-control">
                                        <option value="">Select Source</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="vimeo">Vimeo</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>SEO Settings</h6>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" class="form-control" name="metaTitle">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea class="form-control" name="metaDescription" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Keywords</label>
                                    <input type="text" class="form-control" name="metaKeywords">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_video" class="btn btn-primary">Create Video</button>
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
                        <!-- Content will be loaded dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_video" class="btn btn-primary">Update Video</button>
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
                    <p>Are you sure you want to delete this video post?</p>
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
                    <p>Are you sure you want to restore this video post?</p>
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
        // Show/hide publish date field based on status
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            const publishDateGroup = document.getElementById('publishDateGroup');
            if (this.value === 'scheduled') {
                publishDateGroup.style.display = 'block';
            } else {
                publishDateGroup.style.display = 'none';
            }
        });

        // View video function
        function viewVideo(videoId) {
            window.open('video_view.php?id=' + videoId, '_blank');
        }

        // Edit video function
        function editVideo(videoId) {
            fetch('get_video.php?id=' + videoId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const video = data.video;
                        document.getElementById('edit_video_id').value = videoId;
                        
                        document.getElementById('editVideoModalBody').innerHTML = `
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Title *</label>
                                        <input type="text" class="form-control" name="title" value="${video.Title || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Slug *</label>
                                        <input type="text" class="form-control" name="slug" value="${video.Slug || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Excerpt</label>
                                        <textarea class="form-control" name="excerpt" rows="3">${video.Excerpt || ''}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="description" rows="5">${video.Description || ''}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="categoryID" class="form-control">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['CategoryID'] ?>" ${video.CategoryID == <?= $category['CategoryID'] ?> ? 'selected' : ''}>
                                                    <?= htmlspecialchars($category['CategoryName']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" id="editVideoStatus">
                                            <option value="draft" ${video.Status === 'draft' ? 'selected' : ''}>Draft</option>
                                            <option value="published" ${video.Status === 'published' ? 'selected' : ''}>Published</option>
                                            <option value="scheduled" ${video.Status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                                            <option value="archived" ${video.Status === 'archived' ? 'selected' : ''}>Archived</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="editPublishDateGroup" style="display: ${video.Status === 'scheduled' ? 'block' : 'none'};">
                                        <label>Publish Date</label>
                                        <input type="datetime-local" name="publishDate" class="form-control" value="${video.PublishDate ? video.PublishDate.replace(' ', 'T') : ''}">
                                    </div>
                                    <div class="form-group">
                                        <label>Tags</label>
                                        <input type="text" class="form-control" name="tags" value="${video.Tags || ''}" placeholder="tag1, tag2, tag3">
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="featured" id="editFeatured" ${video.Featured ? 'checked' : ''}>
                                            <label class="custom-control-label" for="editFeatured">Featured Video</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="allowComments" id="editAllowComments" ${video.Featured ? 'checked' : ''}>
                                            <label class="custom-control-label" for="editAllowComments">Allow Comments</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Current Video</h6>
                                    <p><strong>Format:</strong> ${video.VideoFormat}</p>
                                    ${video.VideoFile ? `<p><strong>File:</strong> ${video.VideoFile}</p>` : ''}
                                    ${video.EmbedCode ? `<p><strong>Embed:</strong> ${video.EmbedCode.substring(0, 100)}...</p>` : ''}
                                </div>
                                <div class="col-md-6">
                                    <h6>Update Video</h6>
                                    <div class="form-group">
                                        <label>New Video File</label>
                                        <input type="file" class="form-control-file" name="videoFile" accept="video/*">
                                    </div>
                                    <div class="form-group">
                                        <label>Or New Embed Code</label>
                                        <textarea class="form-control" name="embedCode" rows="3" placeholder="Paste new embed code here...">${video.EmbedCode || ''}</textarea>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Add event listener for status change
                        document.getElementById('editVideoStatus').addEventListener('change', function() {
                            const publishDateGroup = document.getElementById('editPublishDateGroup');
                            if (this.value === 'scheduled') {
                                publishDateGroup.style.display = 'block';
                            } else {
                                publishDateGroup.style.display = 'none';
                            }
                        });
                        
                        $('#editVideoModal').modal('show');
                    } else {
                        alert('Error loading video: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading video');
                });
        }
        
        // Delete video function
        function deleteVideo(videoId) {
            document.getElementById('delete_video_id').value = videoId;
            $('#deleteVideoModal').modal('show');
        }
        
        // Restore video function
        function restoreVideo(videoId) {
            document.getElementById('restore_video_id').value = videoId;
            $('#restoreVideoModal').modal('show');
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Initialize tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Clear form when create modal is closed
        $('#createVideoModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
        
        // Clear form when edit modal is closed
        $('#editVideoModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
    </script>
</body>
</html>
