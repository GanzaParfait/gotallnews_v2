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

// Get profile ID from URL
$profileId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$profileId) {
    header('Location: creator_profiles.php');
    exit;
}

// Get creator profile data
$profile = null;
$articles = [];
$statistics = [];
$socialLinks = [];
$achievements = [];

if ($systemReady) {
    try {
        $profile = $creatorManager->getProfile($profileId);
        
        if (!$profile) {
            header('Location: creator_profiles.php');
            exit;
        }
        
        // Get creator articles
        $articlesData = $creatorManager->getCreatorArticles($profileId, 1, 10);
        $articles = $articlesData['articles'];
        
        // Get creator statistics
        $statistics = $creatorManager->getCreatorStatistics($profileId, 30);
        
        // Get social links
        $socialLinks = $profile['socialLinks'] ?? [];
        
        // Get achievements
        $achievements = $profile['achievements'] ?? [];
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($profile['DisplayName'] ?? 'Creator Profile') ?> - Creator Profile View - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <!-- Additional CSS for better styling -->
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.15);
            margin-left: 2rem; /* Add left padding */
        }
        .profile-cover {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.35rem;
            margin-bottom: 1rem;
        }
        .stats-card {
            background: white;
            border-radius: 0.35rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4e73df;
        }
        .stats-label {
            color: #858796;
            font-size: 0.875rem;
        }
        .verified-badge {
            background: #1cc88a;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        .featured-badge {
            background: #f6c23e;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: #f8f9fc;
            color: #5a5c69;
            border-radius: 50%;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
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
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .achievement-icon {
            color: #f39c12;
            margin-right: 0.5rem;
        }
        .article-item {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .article-item:hover {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .expertise-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .back-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .back-button:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        /* Add padding bottom to achievements section */
        .achievements-section {
            padding-bottom: 2rem;
        }
        /* Ensure main container has proper structure */
        .main-container {
            min-height: 100vh;
        }
        /* Fix sidebar display */
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
        }
        .no-data-message {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 1rem;
        }
    </style>
</head>

<body>
	<div class="whole-content-container">
		<?php
		include "php/includes/header.php";
		?>

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
								<span class="micon"><i class="icon-copy fa fa-newspaper-o" aria-hidden="true"></i></span><span
									class="mtext">Article</span>
							</a>
							<ul class="submenu">
								<li><a href="new_article.php">New</a></li>
								<li><a href="view_article.php">Manage</a></li>
							</ul>
						</li>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle active">
								<span class="micon"><i class="icon-copy fa fa-users" aria-hidden="true"></i></span><span
									class="mtext">Creators</span>
							</a>
							<ul class="submenu">
								<li><a href="creator_profiles.php">Profiles</a></li>
								<li><a href="creator_profile_view.php" class="active">View Profile</a></li>
							</ul>
						</li>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="micon"><i class="icon-copy fa fa-object-ungroup" aria-hidden="true"></i></span><span
									class="mtext">Category</span>
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
								<span class="micon"><i class="icon-copy fa fa-cogs" aria-hidden="true"></i></span><span
									class="mtext">Settings</span>
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
				<?php if (!$systemReady): ?>
					<div class="alert alert-danger">
						<h4>System Not Ready</h4>
						<p>The Creator Profiles system is not properly configured. Please run the installation script first.</p>
						<p><strong>Error:</strong> <?= htmlspecialchars($systemError) ?></p>
						<a href="install_creator_profiles.php" class="btn btn-primary">Install Creator Profiles</a>
					</div>
				<?php else: ?>
					<!-- Back Button -->
					<a href="creator_profiles.php" class="back-button">
						<i class="icon-copy fa fa-arrow-left"></i> Back to Creator Profiles
					</a>
					
					<!-- Messages -->
					<?php if (isset($success_message)): ?>
						<div class="alert alert-success alert-dismissible fade show" role="alert">
							<?= htmlspecialchars($success_message) ?>
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
					<?php endif; ?>
					
					<?php if (isset($error_message)): ?>
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<?= htmlspecialchars($error_message) ?>
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
					<?php endif; ?>
					
					<?php if ($profile): ?>
						<!-- Debug Section (temporary - remove in production) -->
						<?php if (isset($_GET['debug'])): ?>
							<div class="alert alert-info">
								<h6>Debug Information:</h6>
								<p><strong>Profile ID:</strong> <?= $profileId ?></p>
								<p><strong>Admin ID:</strong> <?= htmlspecialchars($profile['AdminId'] ?? 'N/A') ?></p>
								<p><strong>Profile Photo:</strong> <?= htmlspecialchars($profile['ProfilePhoto'] ?? 'N/A') ?></p>
								<p><strong>Articles Count:</strong> <?= count($articles) ?></p>
								<p><strong>Statistics Count:</strong> <?= count($statistics) ?></p>
								<p><strong>Social Links Count:</strong> <?= count($socialLinks) ?></p>
								<p><strong>Achievements Count:</strong> <?= count($achievements) ?></p>
								<p><strong>Categories Count:</strong> <?= count($profile['categories'] ?? []) ?></p>
								
								<h6>Articles Debug:</h6>
								<pre><?= htmlspecialchars(print_r($articles, true)) ?></pre>
							</div>
						<?php endif; ?>
						
						<!-- Profile Header -->
						<div class="profile-header">
							<div class="row align-items-center">
								<div class="col-md-3 text-center">
									<?php 
									$photoSrc = 'images/defaultavatar/avatar.png';
									$photoExists = false;
									
									if (!empty($profile['ProfilePhoto'])) {
										// Check if it's a full URL or just a filename
										if (filter_var($profile['ProfilePhoto'], FILTER_VALIDATE_URL)) {
											$photoSrc = $profile['ProfilePhoto'];
											$photoExists = true;
										} elseif (strpos($profile['ProfilePhoto'], '/') === 0 || strpos($profile['ProfilePhoto'], 'http') === 0) {
											$photoSrc = $profile['ProfilePhoto'];
											$photoExists = true;
										} else {
											// Check if it's already a full path
											if (file_exists($profile['ProfilePhoto'])) {
												$photoSrc = $profile['ProfilePhoto'];
												$photoExists = true;
											} else {
												// Try the images/creators/ directory
												$fullPath = 'images/creators/' . $profile['ProfilePhoto'];
												if (file_exists($fullPath)) {
													$photoSrc = $fullPath;
													$photoExists = true;
												} else {
													// Try with current directory
													$currentPath = './images/creators/' . $profile['ProfilePhoto'];
													if (file_exists($currentPath)) {
														$photoSrc = $currentPath;
														$photoExists = true;
													}
												}
											}
										}
									}
									?>
									
									<?php if ($photoExists): ?>
										<img src="<?= htmlspecialchars($photoSrc) ?>" 
											 alt="Profile Photo" 
											 class="profile-avatar"
											 onerror="this.src='images/defaultavatar/avatar.png';">
									<?php else: ?>
										<div class="profile-avatar" style="background: #e3e6f0; display: flex; align-items: center; justify-content: center;">
											<i class="icon-copy fa fa-user" style="font-size: 3rem; color: #858796;"></i>
										</div>
									<?php endif; ?>
								</div>
								<div class="col-md-9">
									<h1 class="mb-2">
										<?= htmlspecialchars($profile['DisplayName'] ?? 'Unknown Creator') ?>
										<?php if ($profile['IsVerified'] ?? false): ?>
											<span class="verified-badge">✔ Verified</span>
										<?php endif; ?>
										<?php if ($profile['IsFeatured'] ?? false): ?>
											<span class="featured-badge">★ Featured</span>
										<?php endif; ?>
									</h1>
									<p class="mb-2">
										<strong>Username:</strong> @<?= htmlspecialchars($profile['Username'] ?? 'unknown') ?>
									</p>
									<?php if (!empty($profile['Bio'])): ?>
										<p class="mb-0"><?= htmlspecialchars($profile['Bio']) ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>
						
						<!-- Profile Content -->
						<div class="row">
							<!-- Left Column -->
							<div class="col-lg-8">
								<!-- About Section -->
								<div class="stats-card">
									<h4><i class="icon-copy fa fa-info-circle"></i> About</h4>
									<div class="row">
										<?php if (!empty($profile['Location'])): ?>
											<div class="col-md-6 mb-3">
												<strong>Location:</strong> <?= htmlspecialchars($profile['Location']) ?>
											</div>
										<?php endif; ?>
										<?php if (!empty($profile['Expertise'])): ?>
											<div class="col-md-6 mb-3">
												<strong>Expertise:</strong> <?= htmlspecialchars($profile['Expertise']) ?>
											</div>
										<?php endif; ?>
										<?php if (isset($profile['YearsExperience'])): ?>
											<div class="col-md-6 mb-3">
												<strong>Years of Experience:</strong> <?= htmlspecialchars($profile['YearsExperience']) ?> years
											</div>
										<?php endif; ?>
										<?php if (!empty($profile['Website'])): ?>
											<div class="col-md-6 mb-3">
												<strong>Website:</strong> 
												<a href="<?= htmlspecialchars($profile['Website']) ?>" target="_blank" rel="noopener">
													<?= htmlspecialchars($profile['Website']) ?>
												</a>
											</div>
										<?php endif; ?>
									</div>
									
									<?php if (!empty($profile['categories'])): ?>
										<div class="mt-3">
											<strong>Categories:</strong><br>
											<?php foreach ($profile['categories'] as $category): ?>
												<span class="expertise-tag"><?= htmlspecialchars($category['CategoryName']) ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
									
									<!-- Show expertise as a tag if no categories -->
									<?php if (empty($profile['categories']) && !empty($profile['Expertise'])): ?>
										<div class="mt-3">
											<strong>Expertise:</strong><br>
											<span class="expertise-tag"><?= htmlspecialchars($profile['Expertise']) ?></span>
										</div>
									<?php endif; ?>
								</div>
								
								<!-- Articles Section -->
								<div class="stats-card">
									<h4><i class="icon-copy fa fa-newspaper-o"></i> Recent Articles</h4>
									<?php if (!empty($articles)): ?>
										<?php foreach ($articles as $article): ?>
											<div class="article-item">
												<h6>
													<a href="view_article.php?id=<?= $article['ArticleID'] ?>" target="_blank">
														<?= htmlspecialchars($article['Title']) ?>
													</a>
												</h6>
												<p class="text-muted mb-2">
													<small>
														<i class="icon-copy fa fa-calendar"></i> 
														<?= !empty($article['PublishDate']) && $article['PublishDate'] !== '0000-00-00' ? date('M j, Y', strtotime($article['PublishDate'])) : 'Date not set' ?>
														<i class="icon-copy fa fa-eye ml-2"></i> 
														<?= number_format($article['Views'] ?? 0) ?> views
													</small>
												</p>
												<?php if (!empty($article['Excerpt'])): ?>
													<p class="mb-0"><?= htmlspecialchars($article['Excerpt']) ?></p>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									<?php else: ?>
										<p class="text-muted">No articles published yet.</p>
									<?php endif; ?>
								</div>
							</div>
							
							<!-- Right Column -->
							<div class="col-lg-4">
								<!-- Statistics -->
								<div class="stats-card">
									<h4><i class="icon-copy fa fa-chart-bar"></i> Statistics</h4>
									<div class="row text-center">
										<div class="col-6 mb-3">
											<div class="stats-number"><?= count($articles) ?></div>
											<div class="stats-label">Articles</div>
										</div>
										<div class="col-6 mb-3">
											<div class="stats-number"><?= $profile['recentStats']['totalViews'] ?? 0 ?></div>
											<div class="stats-label">Total Views</div>
										</div>
										<div class="col-6 mb-3">
											<div class="stats-number"><?= $profile['recentStats']['totalLikes'] ?? 0 ?></div>
											<div class="stats-label">Total Likes</div>
										</div>
										<div class="col-6 mb-3">
											<div class="stats-number"><?= $profile['recentStats']['totalComments'] ?? 0 ?></div>
											<div class="stats-label">Comments</div>
										</div>
									</div>
								</div>
								
								<!-- Social Links -->
								<div class="stats-card">
									<h4><i class="icon-copy fa fa-share-alt"></i> Social Links</h4>
									<?php if (!empty($socialLinks)): ?>
										<?php foreach ($socialLinks as $link): ?>
											<a href="<?= htmlspecialchars($link['URL']) ?>" 
											   target="_blank" 
											   rel="noopener" 
											   class="social-link"
											   title="<?= htmlspecialchars($link['DisplayText'] ?? $link['Platform']) ?>">
												<i class="icon-copy fa fa-<?= strtolower($link['Platform']) ?>"></i>
											</a>
										<?php endforeach; ?>
									<?php else: ?>
										<p class="no-data-message">No social links added yet.</p>
									<?php endif; ?>
								</div>
								
								<!-- Achievements -->
								<div class="stats-card achievements-section">
									<h4><i class="icon-copy fa fa-trophy"></i> Achievements</h4>
									<?php if (!empty($achievements)): ?>
										<?php foreach ($achievements as $achievement): ?>
											<div class="achievement-item">
												<i class="achievement-icon fa fa-<?= $achievement['Icon'] ?? 'star' ?>"></i>
												<strong><?= htmlspecialchars($achievement['Title']) ?></strong>
												<p class="mb-0"><?= htmlspecialchars($achievement['Description']) ?></p>
												<small class="text-muted">
													Earned on <?= !empty($achievement['EarnedDate']) && $achievement['EarnedDate'] !== '0000-00-00' ? date('M j, Y', strtotime($achievement['EarnedDate'])) : 'Date not set' ?>
												</small>
											</div>
										<?php endforeach; ?>
									<?php else: ?>
										<p class="no-data-message">No achievements earned yet.</p>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php else: ?>
						<div class="alert alert-warning">
							<h4>Profile Not Found</h4>
							<p>The requested creator profile could not be found.</p>
							<a href="creator_profiles.php" class="btn btn-primary">Back to Creator Profiles</a>
						</div>
					<?php endif; ?>
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
    <script src="src/scripts/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
</body>
</html>
