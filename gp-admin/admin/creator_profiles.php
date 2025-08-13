<?php
include 'php/header/top.php';
include 'php/includes/CreatorProfileManager.php';

// Initialize the creator profile manager
try {
    $creatorManager = new CreatorProfileManager($con);
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
                    if (isset($_POST['create_profile'])) {
                        $profileData = [
                            'username' => $_POST['username'],
                            'displayName' => $_POST['displayName'],
                            'bio' => $_POST['bio'] ?? null,
                            'website' => $_POST['website'] ?? null,
                            'location' => $_POST['location'] ?? null,
                            'expertise' => $_POST['expertise'] ?? null,
                            'yearsExperience' => !empty($_POST['yearsExperience']) ? (int) $_POST['yearsExperience'] : 0,
                            'isVerified' => isset($_POST['isVerified']) ? 1 : 0,
                            'isFeatured' => isset($_POST['isFeatured']) ? 1 : 0
                        ];

                        // Handle profile photo upload
                        if (!empty($_FILES['profilePhoto']['name'])) {
                            // Process uploaded image
                            $profileData['profilePhoto'] = processProfilePhoto($_FILES['profilePhoto']);
                        } elseif (!empty($_POST['profilePhotoUrl'])) {
                            // Use provided URL
                            $profileData['profilePhoto'] = $_POST['profilePhotoUrl'];
                        }

                        $profileId = $creatorManager->createProfile($user_uniqueid, $profileData);

                        // Handle social links
                        if ($profileId && isset($_POST['social_platforms']) && isset($_POST['social_urls'])) {
                            $socialPlatforms = $_POST['social_platforms'];
                            $socialUrls = $_POST['social_urls'];
                            $socialDisplayTexts = $_POST['social_display_texts'] ?? [];

                            for ($i = 0; $i < count($socialPlatforms); $i++) {
                                if (!empty($socialPlatforms[$i]) && !empty($socialUrls[$i])) {
                                    $socialData = [
                                        'platform' => $socialPlatforms[$i],
                                        'url' => $socialUrls[$i],
                                        'displayText' => $socialDisplayTexts[$i] ?? $socialPlatforms[$i],
                                        'icon' => $socialPlatforms[$i],
                                        'orderIndex' => $i + 1
                                    ];
                                    $creatorManager->addSocialLink($profileId, $socialData);
                                }
                            }
                        }

                        $success_message = 'Creator profile created successfully!';
                    }
                    break;

                case 'update':
                    if (isset($_POST['update_profile'])) {
                        $profileId = $_POST['profile_id'];
                        $profileData = [
                            'username' => $_POST['username'],
                            'displayName' => $_POST['displayName'],
                            'bio' => $_POST['bio'] ?? null,
                            'website' => $_POST['website'] ?? null,
                            'location' => $_POST['location'] ?? null,
                            'expertise' => $_POST['expertise'] ?? null,
                            'yearsExperience' => !empty($_POST['yearsExperience']) ? (int) $_POST['yearsExperience'] : 0,
                            'isVerified' => isset($_POST['isVerified']) ? 1 : 0,
                            'isFeatured' => isset($_POST['isFeatured']) ? 1 : 0
                        ];

                        // Handle profile photo upload
                        if (!empty($_FILES['profilePhoto']['name'])) {
                            // Process uploaded image
                            $profileData['profilePhoto'] = processProfilePhoto($_FILES['profilePhoto']);
                        } elseif (!empty($_POST['profilePhotoUrl'])) {
                            // Use provided URL
                            $profileData['profilePhoto'] = $_POST['profilePhotoUrl'];
                        }

                        $creatorManager->updateProfile($profileId, $profileData);

                        // Handle social links - first remove existing ones, then add new ones
                        if (isset($_POST['social_platforms']) && isset($_POST['social_urls'])) {
                            // Get existing social links and remove them
                            $existingLinks = $creatorManager->getSocialLinks($profileId);
                            foreach ($existingLinks as $link) {
                                $creatorManager->deleteSocialLink($link['LinkID']);
                            }

                            // Add new social links
                            $socialPlatforms = $_POST['social_platforms'];
                            $socialUrls = $_POST['social_urls'];
                            $socialDisplayTexts = $_POST['social_display_texts'] ?? [];

                            for ($i = 0; $i < count($socialPlatforms); $i++) {
                                if (!empty($socialPlatforms[$i]) && !empty($socialUrls[$i])) {
                                    $socialData = [
                                        'platform' => $socialPlatforms[$i],
                                        'url' => $socialUrls[$i],
                                        'displayText' => $socialDisplayTexts[$i] ?? $socialPlatforms[$i],
                                        'icon' => $socialPlatforms[$i],
                                        'orderIndex' => $i + 1
                                    ];
                                    $creatorManager->addSocialLink($profileId, $socialData);
                                }
                            }
                        }

                        $success_message = 'Creator profile updated successfully!';
                    }
                    break;

                case 'delete':
                    if (isset($_POST['delete_profile'])) {
                        $profileId = $_POST['profile_id'];
                        $creatorManager->deleteProfile($profileId);
                        $success_message = 'Creator profile deleted successfully!';
                    }
                    break;

                case 'restore':
                    if (isset($_POST['restore_profile'])) {
                        $profileId = $_POST['profile_id'];
                        $creatorManager->restoreProfile($profileId);
                        $success_message = 'Creator profile restored successfully!';
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
        'verified' => $_GET['verified'] ?? '',
        'featured' => $_GET['featured'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];

    // Get creator profiles with pagination
    $profilesData = $creatorManager->getAllProfiles($page, 20, $filters);
    $profiles = $profilesData['profiles'];
    $totalPages = $profilesData['pages'];
    $currentPage = $profilesData['current_page'];

    // Get trending creators for sidebar
    $trendingCreators = $creatorManager->getTrendingCreators(5);
} else {
    // System not ready, set default values
    $profiles = [];
    $totalPages = 0;
    $currentPage = 1;
    $trendingCreators = [];
}

/**
 * Process profile photo upload with compression and WebP conversion
 */
function processProfilePhoto($file)
{
    try {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is not available');
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB');
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and GIF files are allowed');
        }

        // Create upload directory if it doesn't exist
        $uploadDir = 'images/creators/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'creator_' . time() . '_' . uniqid() . '.webp';
        $filepath = $uploadDir . $filename;

        // Load image
        $image = null;
        switch ($file['type']) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file['tmp_name']);
                break;
        }

        if (!$image) {
            throw new Exception('Failed to load image');
        }

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate new dimensions (max 400x400 for profile photos)
        $maxSize = 400;
        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = ($height / $width) * $maxSize;
        } else {
            $newHeight = $maxSize;
            $newWidth = ($width / $height) * $maxSize;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($file['type'] === 'image/png' || $file['type'] === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save as WebP with compression
        $quality = 80;  // Good quality with compression
        if (!imagewebp($newImage, $filepath, $quality)) {
            throw new Exception('Failed to save WebP image');
        }

        // Clean up
        imagedestroy($image);
        imagedestroy($newImage);

        return $filepath;
    } catch (Exception $e) {
        error_log('Profile Photo Processing Error: ' . $e->getMessage());
        return null;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Creator Profiles Management - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <style>
        .creator-card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            transition: all 0.3s ease;
        }
        .creator-card:hover {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transform: translateY(-2px);
        }
        .creator-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .creator-cover {
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.35rem 0.35rem 0 0;
        }
        .stats-badge {
            background: #f8f9fc;
            color: #5a5c69;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }
        .verified-badge {
            background: #1cc88a;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .featured-badge {
            background: #f6c23e;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .social-link {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            background: #f8f9fc;
            color: #5a5c69;
            border-radius: 50%;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            background: #4e73df;
            color: white;
            transform: scale(1.1);
        }
        .achievement-item {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.25rem;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .achievement-icon {
            color: #f39c12;
            margin-right: 0.5rem;
        }
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
        .trending-creator {
            padding: 0.75rem;
            border-bottom: 1px solid #e3e6f0;
        }
        .trending-creator:last-child {
            border-bottom: none;
        }
        .trending-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .social-links-section {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .social-link-row {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0.25rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .social-link-row:hover {
            border-color: #4e73df;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .remove-social-link {
            transition: all 0.2s ease;
        }
        .remove-social-link:hover {
            transform: scale(1.1);
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
                            <li><a href="creator_profiles.php" class="active">Profiles</a></li>
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
            <div class="pd-20 card-box mb-30">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Creator Profiles Management</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Creator Profiles</li>
                                </ol>
                            </nav>
                            <small class="text-muted">Manage content creators, their profiles, and achievements</small>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#createProfileModal">
                                <i class="icon-copy fa fa-plus"></i> New Creator Profile
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

                <!-- System Status Message -->
                <?php if (!$systemReady): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5><i class="icon-copy fa fa-exclamation-triangle"></i> System Not Ready</h5>
                        <p class="mb-2"><?= htmlspecialchars($systemError) ?></p>
                        <p class="mb-0">
                            <strong>To fix this:</strong> 
                            <a href="install_creator_profiles.php" class="alert-link">Run the installation script</a> 
                            to set up the required database tables.
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
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search creators..." 
                                   value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="verified">
                                <option value="">All Verification</option>
                                <option value="1" <?= $filters['verified'] === '1' ? 'selected' : '' ?>>Verified</option>
                                <option value="0" <?= $filters['verified'] === '0' ? 'selected' : '' ?>>Not Verified</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="featured">
                                <option value="">All Featured</option>
                                <option value="1" <?= $filters['featured'] === '1' ? 'selected' : '' ?>>Featured</option>
                                <option value="0" <?= $filters['featured'] === '0' ? 'selected' : '' ?>>Not Featured</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                            <a href="creator_profiles.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                    
                    <!-- Debug Info (remove in production) -->
                    <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['verified']) || !empty($filters['featured'])): ?>
                    <div class="mt-2 p-2 bg-light border rounded">
                        <small class="text-muted">
                            <strong>Active Filters:</strong><br>
                            Search: <?= !empty($filters['search']) ? htmlspecialchars($filters['search']) : 'None' ?><br>
                            Status: <?= !empty($filters['status']) ? htmlspecialchars($filters['status']) : 'All' ?><br>
                            Verified: <?= !empty($filters['verified']) ? ($filters['verified'] == '1' ? 'Yes' : 'No') : 'All' ?><br>
                            Featured: <?= !empty($filters['featured']) ? ($filters['featured'] == '1' ? 'Yes' : 'No') : 'All' ?><br>
                            Total Results: <?= $profilesData['total'] ?? 0 ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Creator Profiles Grid -->
                <?php if ($systemReady): ?>
                <div class="row">
                    <?php if (empty($profiles)): ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-info">
                                <i class="icon-copy fa fa-info-circle"></i> No creator profiles found matching your criteria.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($profiles as $profile): ?>
                            <div class="col-lg-6 col-md-12 mb-4">
                                <div class="creator-card">
                                    <div class="creator-cover"></div>
                                    <div class="card-body text-center">
                                        <div class="mb-3" style="margin-top: -60px;">
                                            <?php
                                            $photoSrc = 'php/defaultavatar/avatar.png';
                                            if (!empty($profile['ProfilePhoto'])) {
                                                // Check if it's a full URL or just a filename
                                                if (filter_var($profile['ProfilePhoto'], FILTER_VALIDATE_URL)) {
                                                    $photoSrc = $profile['ProfilePhoto'];
                                                } elseif (strpos($profile['ProfilePhoto'], '/') === 0 || strpos($profile['ProfilePhoto'], 'http') === 0) {
                                                    $photoSrc = $profile['ProfilePhoto'];
                                                } else {
                                                    // Check if it's already a full path
                                                    if (file_exists($profile['ProfilePhoto'])) {
                                                        $photoSrc = $profile['ProfilePhoto'];
                                                    } else {
                                                        $photoSrc = 'images/creators/' . $profile['ProfilePhoto'];
                                                    }
                                                }
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($photoSrc) ?>" 
                                                 alt="<?= htmlspecialchars($profile['DisplayName']) ?>" 
                                                 class="creator-avatar border border-white" style="border-width: 4px !important;"
                                                 onerror="this.src='php/defaultavatar/avatar.png';">
                                        </div>
                                        
                                        <h5 class="card-title mb-1">
                                            <?= htmlspecialchars($profile['DisplayName']) ?>
                                            <?php if ($profile['IsVerified']): ?>
                                                <span class="verified-badge ml-2">
                                                    <i class="icon-copy fa fa-check-circle"></i> Verified
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($profile['IsFeatured']): ?>
                                                <span class="featured-badge ml-2">
                                                    <i class="icon-copy fa fa-star"></i> Featured
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <p class="text-muted mb-2">@<?= htmlspecialchars($profile['Username']) ?></p>
                                        
                                        <?php if (!empty($profile['Bio'])): ?>
                                            <p class="card-text text-muted mb-3"><?= htmlspecialchars(substr($profile['Bio'], 0, 100)) ?><?= strlen($profile['Bio']) > 100 ? '...' : '' ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- Stats -->
                                        <div class="mb-3">
                                            <span class="stats-badge">
                                                <i class="icon-copy fa fa-newspaper-o"></i> <?= $profile['TotalArticles'] ?> Articles
                                            </span>
                                            <span class="stats-badge">
                                                <i class="icon-copy fa fa-eye"></i> <?= number_format($profile['TotalViews']) ?> Views
                                            </span>
                                            <span class="stats-badge">
                                                <i class="icon-copy fa fa-users"></i> <?= $profile['FollowersCount'] ?> Followers
                                            </span>
                                        </div>
                                        
                                        <!-- Location & Expertise -->
                                        <?php if (!empty($profile['Location'])): ?>
                                            <p class="text-muted mb-2">
                                                <i class="icon-copy fa fa-map-marker"></i> <?= htmlspecialchars($profile['Location']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($profile['Expertise'])): ?>
                                            <p class="text-muted mb-3">
                                                <i class="icon-copy fa fa-lightbulb-o"></i> <?= htmlspecialchars($profile['Expertise']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- Action Buttons -->
                                        <div class="btn-group" role="group">
                                            <a href="creator_profile_view.php?id=<?= $profile['ProfileID'] ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="icon-copy fa fa-eye"></i> View
                                            </a>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="editProfile(<?= $profile['ProfileID'] ?>)">
                                                <i class="icon-copy fa fa-edit"></i> Edit
                                            </button>
                                            <?php if ($profile['isDeleted'] === 'notDeleted'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteProfile(<?= $profile['ProfileID'] ?>, '<?= htmlspecialchars($profile['DisplayName']) ?>')">
                                                    <i class="icon-copy fa fa-trash"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="restoreProfile(<?= $profile['ProfileID'] ?>)">
                                                    <i class="icon-copy fa fa-undo"></i> Restore
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($systemReady && $totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <nav aria-label="Creator profiles pagination">
                            <ul class="pagination">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Trending Creators Sidebar -->
            <?php if ($systemReady): ?>
            <div class="pd-20 card-box mb-30">
                <h5 class="mb-3">
                    <i class="icon-copy fa fa-fire"></i> Trending Creators
                </h5>
                <?php if (empty($trendingCreators)): ?>
                    <p class="text-muted">No trending creators at the moment.</p>
                <?php else: ?>
                    <?php foreach ($trendingCreators as $creator): ?>
                        <div class="trending-creator">
                            <div class="d-flex align-items-center">
                                <img src="<?= !empty($creator['ProfilePhoto']) ? 'images/creators/' . $creator['ProfilePhoto'] : 'php/defaultavatar/avatar.png' ?>" 
                                     alt="<?= htmlspecialchars($creator['DisplayName']) ?>" 
                                     class="trending-avatar mr-3"
                                     onerror="this.src='php/defaultavatar/avatar.png';">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($creator['DisplayName']) ?></h6>
                                    <small class="text-muted">
                                        <?= $creator['RecentArticles'] ?> recent articles
                                    </small>
                                </div>
                                <a href="creator_profile_view.php?id=<?= $creator['ProfileID'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Profile Modal -->
    <?php if ($systemReady): ?>
    <div class="modal fade" id="createProfileModal" tabindex="-1" role="dialog" aria-labelledby="createProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createProfileModalLabel">Create New Creator Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" class="form-control" name="username" placeholder="Enter unique username" required>
                                    <small class="text-muted">Unique username for the creator</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Display Name *</label>
                                    <input type="text" class="form-control" name="displayName" placeholder="Enter display name" required>
                                    <small class="text-muted">Public display name</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Bio *</label>
                            <textarea class="form-control" name="bio" rows="3" placeholder="Tell us about the creator..." required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Website</label>
                                    <input type="url" class="form-control" name="website" placeholder="https://example.com">
                                    <small class="text-muted">Optional website URL</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input type="text" class="form-control" name="location" placeholder="City, Country" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expertise *</label>
                                    <input type="text" class="form-control" name="expertise" placeholder="e.g., Technology, Politics, Sports" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Years of Experience</label>
                                    <input type="number" class="form-control" name="yearsExperience" min="0" max="50" placeholder="Enter years of experience" value="0">
                                    <small class="text-muted">Leave empty for 0 years experience</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="isVerified" id="isVerified">
                                    <label class="form-check-label" for="isVerified">Verified Creator</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="isFeatured" id="isFeatured">
                                    <label class="form-check-label" for="isFeatured">Featured Creator</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Photo Upload Section -->
                        <div class="form-group">
                            <!-- <label>Profile Photo</label> -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Upload Image</label>
                                        <input type="file" class="form-control" name="profilePhoto" accept="image/*">
                                        <small class="text-muted">Upload JPG, PNG, or GIF (max 5MB). Will be automatically compressed and converted to WebP.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Or Use Image URL</label>
                                        <input type="url" class="form-control" name="profilePhotoUrl" placeholder="https://example.com/image.jpg">
                                        <small class="text-muted">Provide a direct link to an image</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Links Section -->
                        <div class="form-group">
                            <label>Social Media Links</label>
                            <div id="social-links-container">
                                <div class="social-link-row row mb-2">
                                    <div class="col-md-3">
                                        <select class="form-control" name="social_platforms[]">
                                            <option value="facebook">Facebook</option>
                                            <option value="twitter">Twitter</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="youtube">YouTube</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="website">Website</option>
                                            <option value="blog">Blog</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="url" class="form-control" name="social_urls[]" placeholder="https://example.com/profile" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="social_display_texts[]" placeholder="Display Text">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-social-link" style="display: none;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-social-link">
                                <i class="fa fa-plus"></i> Add Social Link
                            </button>
                            <small class="text-muted">Add social media profiles and links for the creator</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_profile" class="btn btn-primary">Create Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Creator Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="profile_id" id="edit_profile_id">
                    <div class="modal-body">
                        <!-- Same form fields as create modal -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" class="form-control" name="username" id="edit_username" placeholder="Enter unique username">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Display Name *</label>
                                    <input type="text" class="form-control" name="displayName" id="edit_displayName" placeholder="Enter display name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Bio *</label>
                            <textarea class="form-control" name="bio" id="edit_bio" rows="3" placeholder="Tell us about the creator..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Website</label>
                                    <input type="url" class="form-control" name="website" id="edit_website" placeholder="https://example.com">
                                    <small class="text-muted">Optional website URL</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input type="text" class="form-control" name="location" id="edit_location" placeholder="City, Country">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expertise *</label>
                                    <input type="text" class="form-control" name="expertise" id="edit_expertise" placeholder="e.g., Technology, Politics, Sports">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Years of Experience</label>
                                    <input type="number" class="form-control" name="yearsExperience" id="edit_yearsExperience" min="0" max="50" placeholder="Enter years of experience" value="0">
                                    <small class="text-muted">Leave empty for 0 years experience</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="isVerified" id="edit_isVerified">
                                    <label class="form-check-label" for="edit_isVerified">Verified Creator</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="isFeatured" id="edit_isFeatured">
                                    <label class="form-check-label" for="edit_isFeatured">Featured Creator</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Photo Upload Section -->
                        <div class="form-group">
                            <!-- <label>Profile Photo</label> -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Upload New Image</label>
                                        <input type="file" class="form-control" name="profilePhoto" accept="image/*">
                                        <small class="text-muted">Upload JPG, PNG, or GIF (max 5MB). Will be automatically compressed and converted to WebP.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Or Use Image URL</label>
                                        <input type="url" class="form-control" name="profilePhotoUrl" id="edit_profilePhotoUrl" placeholder="https://example.com/image.jpg">
                                        <small class="text-muted">Provide a direct link to an image</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Current Profile Photo</label>
                                <div id="current_profile_photo" class="text-center">
                                    <img id="current_photo_preview" src="php/defaultavatar/avatar.png" alt="Current Profile Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #e3e6f0;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Links Section -->
                        <div class="form-group">
                            <label>Social Media Links</label>
                            <div id="edit-social-links-container">
                                <div class="social-link-row row mb-2">
                                    <div class="col-md-3">
                                        <select class="form-control" name="social_platforms[]">
                                            <option value="facebook">Facebook</option>
                                            <option value="twitter">Twitter</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="youtube">YouTube</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="website">Website</option>
                                            <option value="blog">Blog</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="url" class="form-control" name="social_urls[]" placeholder="https://example.com/profile" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="social_display_texts[]" placeholder="Display Text">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-social-link" style="display: none;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="edit-add-social-link">
                                <i class="fa fa-plus"></i> Add Social Link
                            </button>
                            <small class="text-muted">Add social media profiles and links for the creator</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProfileModal" tabindex="-1" role="dialog" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProfileModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the creator profile "<span id="delete_profile_name"></span>"?</p>
                    <p class="text-warning"><small>This action will soft delete the profile and can be restored later.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="profile_id" id="delete_profile_id">
                        <button type="submit" name="delete_profile" class="btn btn-danger">Delete Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreProfileModal" tabindex="-1" role="dialog" aria-labelledby="restoreProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreProfileModalLabel">Confirm Restore</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore this creator profile?</p>
                    <p class="text-info"><small>This will reactivate the profile and make it visible again.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="profile_id" id="restore_profile_id">
                        <button type="submit" name="restore_profile" class="btn btn-success">Restore Profile</button>
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
        // Edit profile function
        function editProfile(profileId) {
            // Fetch profile data via AJAX
            fetch(`get_creator_profile.php?id=${profileId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const profile = data.profile;
                        
                        // Populate form fields
                        $('#edit_profile_id').val(profile.ProfileID);
                        $('#edit_username').val(profile.Username);
                        $('#edit_displayName').val(profile.DisplayName);
                        $('#edit_bio').val(profile.Bio);
                        $('#edit_website').val(profile.Website);
                        $('#edit_location').val(profile.Location);
                        $('#edit_expertise').val(profile.Expertise);
                        $('#edit_yearsExperience').val(profile.YearsExperience);
                        $('#edit_isVerified').prop('checked', profile.IsVerified == 1);
                        $('#edit_isFeatured').prop('checked', profile.IsFeatured == 1);
                        
                        // Populate profile photo fields
                        if (profile.ProfilePhoto) {
                            $('#current_photo_preview').attr('src', profile.ProfilePhoto);
                            $('#edit_profilePhotoUrl').val(profile.ProfilePhoto);
                        } else {
                            $('#current_photo_preview').attr('src', 'php/defaultavatar/avatar.png');
                            $('#edit_profilePhotoUrl').val('');
                        }
                        
                        // Populate social links
                        populateSocialLinks(profile.socialLinks || []);
                        
                        // Show the modal
                        $('#editProfileModal').modal('show');
                    } else {
                        alert('Error loading profile data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading profile data. Please try again.');
                });
        }
        
        // Function to populate social links in edit modal
        function populateSocialLinks(socialLinks) {
            const container = $('#edit-social-links-container');
            container.empty();
            
            if (socialLinks.length === 0) {
                // Add one empty row if no social links
                addSocialLinkRow('#edit-social-links-container');
            } else {
                // Add rows for each existing social link
                socialLinks.forEach((link, index) => {
                    const newRow = $(`
                        <div class="social-link-row row mb-2">
                            <div class="col-md-3">
                                <select class="form-control" name="social_platforms[]">
                                    <option value="facebook" ${link.Platform === 'facebook' ? 'selected' : ''}>Facebook</option>
                                    <option value="twitter" ${link.Platform === 'twitter' ? 'selected' : ''}>Twitter</option>
                                    <option value="instagram" ${link.Platform === 'instagram' ? 'selected' : ''}>Instagram</option>
                                    <option value="linkedin" ${link.Platform === 'linkedin' ? 'selected' : ''}>LinkedIn</option>
                                    <option value="youtube" ${link.Platform === 'youtube' ? 'selected' : ''}>YouTube</option>
                                    <option value="tiktok" ${link.Platform === 'tiktok' ? 'selected' : ''}>TikTok</option>
                                    <option value="website" ${link.Platform === 'website' ? 'selected' : ''}>Website</option>
                                    <option value="blog" ${link.Platform === 'blog' ? 'selected' : ''}>Blog</option>
                                    <option value="other" ${link.Platform === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="url" class="form-control" name="social_urls[]" placeholder="https://example.com/profile" value="${link.URL || ''}" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control" name="social_display_texts[]" placeholder="Display Text" value="${link.DisplayText || ''}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-social-link" ${index === 0 ? 'style="display: none;"' : ''}>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `);
                    
                    container.append(newRow);
                });
            }
        }
        
        // Delete profile function
        function deleteProfile(profileId, profileName) {
            $('#delete_profile_id').val(profileId);
            $('#delete_profile_name').text(profileName);
            $('#deleteProfileModal').modal('show');
        }
        
        // Restore profile function
        function restoreProfile(profileId) {
            $('#restore_profile_id').val(profileId);
            $('#restoreProfileModal').modal('show');
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
        $('#createProfileModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
        
        // Clear form when edit modal is closed
        $('#editProfileModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
        
        // Social Links Management
        function addSocialLinkRow(containerId) {
            const container = $(containerId);
            const newRow = $(`
                <div class="social-link-row row mb-2">
                    <div class="col-md-3">
                        <select class="form-control" name="social_platforms[]">
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="instagram">Instagram</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="youtube">YouTube</option>
                            <option value="tiktok">TikTok</option>
                            <option value="website">Website</option>
                            <option value="blog">Blog</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="url" class="form-control" name="social_urls[]" placeholder="https://example.com/profile" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="social_display_texts[]" placeholder="Display Text">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-social-link">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            
            container.append(newRow);
            
            // Show remove buttons for all rows except the first one
            container.find('.remove-social-link').show();
            container.find('.social-link-row:first .remove-social-link').hide();
        }
        
        function removeSocialLinkRow(button) {
            const container = $(button).closest('#social-links-container, #edit-social-links-container');
            $(button).closest('.social-link-row').remove();
            
            // Hide remove button for the first row if only one remains
            if (container.find('.social-link-row').length === 1) {
                container.find('.remove-social-link').hide();
            }
        }
        
        // Add social link event handlers
        $('#add-social-link').on('click', function() {
            addSocialLinkRow('#social-links-container');
        });
        
        $('#edit-add-social-link').on('click', function() {
            addSocialLinkRow('#edit-social-links-container');
        });
        
        // Remove social link event handlers (using event delegation)
        $(document).on('click', '.remove-social-link', function() {
            removeSocialLinkRow(this);
        });
        
        // Handle form submission for social links
        $('form').on('submit', function(e) {
            const form = $(this);
            const socialPlatforms = form.find('select[name="social_platforms[]"]').map(function() {
                return $(this).val();
            }).get();
            const socialUrls = form.find('input[name="social_urls[]"]').map(function() {
                return $(this).val();
            }).get();
            const socialDisplayTexts = form.find('input[name="social_display_texts[]"]').map(function() {
                return $(this).val();
            }).get();
            
            // Validate that all social links have both platform and URL
            for (let i = 0; i < socialPlatforms.length; i++) {
                if (socialPlatforms[i] && !socialUrls[i]) {
                    e.preventDefault();
                    alert('Please provide a URL for all social media platforms.');
                    return false;
                }
                if (!socialPlatforms[i] && socialUrls[i]) {
                    e.preventDefault();
                    alert('Please select a platform for all social media URLs.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
