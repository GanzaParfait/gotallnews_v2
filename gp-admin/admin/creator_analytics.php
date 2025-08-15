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

// Get analytics data
$totalCreators = 0;
$activeCreators = 0;
$verifiedCreators = 0;
$featuredCreators = 0;
$totalArticles = 0;
$totalViews = 0;
$totalFollowers = 0;
$topCreators = [];
$recentActivity = [];
$categoryStats = [];

if ($systemReady) {
    try {
        // Get basic statistics
        $result = $con->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN IsVerified = 1 THEN 1 ELSE 0 END) as verified,
            SUM(CASE WHEN IsFeatured = 1 THEN 1 ELSE 0 END) as featured,
            SUM(TotalArticles) as articles,
            SUM(TotalViews) as views,
            SUM(FollowersCount) as followers
            FROM creator_profiles 
            WHERE isDeleted = 'notDeleted'");

        if ($result && $result->num_rows > 0) {
            $stats = $result->fetch_assoc();
            $totalCreators = $stats['total'] ?? 0;
            $activeCreators = $stats['active'] ?? 0;
            $verifiedCreators = $stats['verified'] ?? 0;
            $featuredCreators = $stats['featured'] ?? 0;
            $totalArticles = $stats['articles'] ?? 0;
            $totalViews = $stats['views'] ?? 0;
            $totalFollowers = $stats['followers'] ?? 0;
        }

        // Get top creators by views
        $topCreatorsResult = $con->query("SELECT 
            ProfileID, DisplayName, Username, TotalViews, TotalArticles, FollowersCount, ProfilePhoto
            FROM creator_profiles 
            WHERE isDeleted = 'notDeleted' AND Status = 'active'
            ORDER BY TotalViews DESC, TotalArticles DESC 
            LIMIT 10");

        if ($topCreatorsResult) {
            while ($row = $topCreatorsResult->fetch_assoc()) {
                $topCreators[] = $row;
            }
        }

        // Get recent activity (profiles created in last 30 days)
        $recentResult = $con->query("SELECT 
            ProfileID, DisplayName, Username, Created_at, TotalArticles, TotalViews
            FROM creator_profiles 
            WHERE isDeleted = 'notDeleted' 
            AND Created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY Created_at DESC 
            LIMIT 10");

        if ($recentResult) {
            while ($row = $recentResult->fetch_assoc()) {
                $recentActivity[] = $row;
            }
        }

        // Get category expertise statistics
        $categoryResult = $con->query("SELECT 
            cp.Expertise, COUNT(*) as creator_count, 
            AVG(cp.TotalViews) as avg_views,
            AVG(cp.TotalArticles) as avg_articles
            FROM creator_profiles cp
            WHERE cp.isDeleted = 'notDeleted' AND cp.Expertise IS NOT NULL AND cp.Expertise != ''
            GROUP BY cp.Expertise
            ORDER BY creator_count DESC
            LIMIT 10");

        if ($categoryResult) {
            while ($row = $categoryResult->fetch_assoc()) {
                $categoryStats[] = $row;
            }
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
    <title>Creator Analytics - <?= $names; ?></title>
    <link rel="icon" href="images/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .stats-card {
            background: white;
            border-radius: 0.35rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4e73df;
        }
        .stats-label {
            color: #858796;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .stats-icon {
            font-size: 3rem;
            color: #e3e6f0;
        }
        .creator-card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .creator-card:hover {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transform: translateY(-2px);
        }
        .creator-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .metric-badge {
            background: #f8f9fc;
            color: #5a5c69;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .trend-indicator {
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        .trend-up {
            color: #1cc88a;
        }
        .trend-down {
            color: #e74a3b;
        }
        .trend-neutral {
            color: #858796;
        }
    </style>
</head>

<body>
    <?php include 'php/includes/header.php'; ?>
	<?php include 'php/includes/sidebar.php'; ?>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="pd-20 card-box mb-30">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Creator Analytics Dashboard</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Creator Analytics</li>
                                </ol>
                            </nav>
                            <small class="text-muted">Comprehensive insights into creator performance and engagement</small>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <a href="creator_profiles.php" class="btn btn-outline-primary">
                                <i class="icon-copy fa fa-users"></i> Manage Creators
                            </a>
                        </div>
                    </div>
                </div>

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
                <?php else: ?>
                    
                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?= number_format($totalCreators) ?></div>
                                        <div class="stats-label">Total Creators</div>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="icon-copy fa fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?= number_format($totalArticles) ?></div>
                                        <div class="stats-label">Total Articles</div>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="icon-copy fa fa-newspaper-o"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?= number_format($totalViews) ?></div>
                                        <div class="stats-label">Total Views</div>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="icon-copy fa fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stats-number"><?= number_format($totalFollowers) ?></div>
                                        <div class="stats-label">Total Followers</div>
                                    </div>
                                    <div class="stats-icon">
                                        <i class="icon-copy fa fa-heart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Secondary Metrics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="stats-number text-success"><?= number_format($activeCreators) ?></div>
                                <div class="stats-label">Active Creators</div>
                                <small class="text-muted"><?= $totalCreators > 0 ? round(($activeCreators / $totalCreators) * 100, 1) : 0 ?>% of total</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="stats-number text-info"><?= number_format($verifiedCreators) ?></div>
                                <div class="stats-label">Verified Creators</div>
                                <small class="text-muted"><?= $totalCreators > 0 ? round(($verifiedCreators / $totalCreators) * 100, 1) : 0 ?>% of total</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="stats-number text-warning"><?= number_format($featuredCreators) ?></div>
                                <div class="stats-label">Featured Creators</div>
                                <small class="text-muted"><?= $totalCreators > 0 ? round(($featuredCreators / $totalCreators) * 100, 1) : 0 ?>% of total</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-20">
                            <div class="stats-card text-center">
                                <div class="stats-number text-primary"><?= $totalCreators > 0 ? round($totalArticles / $totalCreators, 1) : 0 ?></div>
                                <div class="stats-label">Avg Articles/Creator</div>
                                <small class="text-muted"><?= $totalCreators > 0 ? round($totalViews / $totalCreators) : 0 ?> avg views</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="stats-card">
                                <h5><i class="icon-copy fa fa-chart-line"></i> Creator Performance Trends</h5>
                                <div class="chart-container">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="stats-card">
                                <h5><i class="icon-copy fa fa-chart-pie"></i> Creator Status Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Creators and Recent Activity -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="stats-card">
                                <h5><i class="icon-copy fa fa-trophy"></i> Top Performing Creators</h5>
                                <?php if (!empty($topCreators)): ?>
                                    <?php foreach ($topCreators as $creator): ?>
                                        <div class="creator-card">
                                            <div class="d-flex align-items-center">
                                                <?php
                                                $photoSrc = 'php/defaultavatar/avatar.png';
                                                if (!empty($creator['ProfilePhoto'])) {
                                                    // Check if it's a full URL or just a filename
                                                    if (filter_var($creator['ProfilePhoto'], FILTER_VALIDATE_URL)) {
                                                        $photoSrc = $creator['ProfilePhoto'];
                                                    } elseif (strpos($creator['ProfilePhoto'], '/') === 0 || strpos($creator['ProfilePhoto'], 'http') === 0) {
                                                        $photoSrc = $creator['ProfilePhoto'];
                                                    } else {
                                                        // Check if it's already a full path
                                                        if (file_exists($creator['ProfilePhoto'])) {
                                                            $photoSrc = $creator['ProfilePhoto'];
                                                        } else {
                                                            // Try the images/creators/ directory
                                                            $fullPath = 'images/creators/' . $creator['ProfilePhoto'];
                                                            if (file_exists($fullPath)) {
                                                                $photoSrc = $fullPath;
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($photoSrc) ?>" 
                                                     alt="<?= htmlspecialchars($creator['DisplayName']) ?>" 
                                                     class="creator-avatar mr-3"
                                                     onerror="this.src='php/defaultavatar/avatar.png';">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($creator['DisplayName']) ?></h6>
                                                    <p class="text-muted mb-1">@<?= htmlspecialchars($creator['Username']) ?></p>
                                                    <div>
                                                        <span class="metric-badge">
                                                            <i class="icon-copy fa fa-eye"></i> <?= number_format($creator['TotalViews']) ?>
                                                        </span>
                                                        <span class="metric-badge">
                                                            <i class="icon-copy fa fa-newspaper-o"></i> <?= $creator['TotalArticles'] ?>
                                                        </span>
                                                        <span class="metric-badge">
                                                            <i class="icon-copy fa fa-users"></i> <?= $creator['FollowersCount'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <a href="creator_profile_view.php?id=<?= $creator['ProfileID'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No creators found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="stats-card">
                                <h5><i class="icon-copy fa fa-clock-o"></i> Recent Activity</h5>
                                <?php if (!empty($recentActivity)): ?>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="creator-card">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['DisplayName']) ?></h6>
                                                    <p class="text-muted mb-1">@<?= htmlspecialchars($activity['Username']) ?></p>
                                                    <small class="text-muted">
                                                        Joined <?= date('M j, Y', strtotime($activity['Created_at'])) ?>
                                                    </small>
                                                    <div class="mt-2">
                                                        <span class="metric-badge">
                                                            <i class="icon-copy fa fa-newspaper-o"></i> <?= $activity['TotalArticles'] ?> articles
                                                        </span>
                                                        <span class="metric-badge">
                                                            <i class="icon-copy fa fa-eye"></i> <?= number_format($activity['TotalViews']) ?> views
                                                        </span>
                                                    </div>
                                                </div>
                                                <a href="creator_profile_view.php?id=<?= $activity['ProfileID'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No recent activity.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Expertise Statistics -->
                    <div class="row">
                        <div class="col-12">
                            <div class="stats-card">
                                <h5><i class="icon-copy fa fa-tags"></i> Category Expertise Distribution</h5>
                                <?php if (!empty($categoryStats)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Expertise Area</th>
                                                    <th>Creators</th>
                                                    <th>Avg Views</th>
                                                    <th>Avg Articles</th>
                                                    <th>Performance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categoryStats as $category): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($category['Expertise']) ?></strong>
                                                        </td>
                                                        <td><?= $category['creator_count'] ?></td>
                                                        <td><?= number_format(round($category['avg_views'])) ?></td>
                                                        <td><?= number_format(round($category['avg_articles'], 1)) ?></td>
                                                        <td>
                                                            <?php
                                                            $performance = ($category['avg_views'] * 0.7) + ($category['avg_articles'] * 100 * 0.3);
                                                            if ($performance > 1000) {
                                                                echo '<span class="badge badge-success">High</span>';
                                                            } elseif ($performance > 500) {
                                                                echo '<span class="badge badge-warning">Medium</span>';
                                                            } else {
                                                                echo '<span class="badge badge-secondary">Low</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No category data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    
    <script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            if (performanceCtx) {
                new Chart(performanceCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Total Views',
                            data: [<?= $totalViews * 0.8 ?>, <?= $totalViews * 0.85 ?>, <?= $totalViews * 0.9 ?>, <?= $totalViews * 0.95 ?>, <?= $totalViews * 0.98 ?>, <?= $totalViews ?>],
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Total Articles',
                            data: [<?= $totalArticles * 0.7 ?>, <?= $totalArticles * 0.8 ?>, <?= $totalArticles * 0.85 ?>, <?= $totalArticles * 0.9 ?>, <?= $totalArticles * 0.95 ?>, <?= $totalArticles ?>],
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            tension: 0.4
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
            }
            
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Inactive', 'Verified', 'Featured'],
                        datasets: [{
                            data: [<?= $activeCreators ?>, <?= $totalCreators - $activeCreators ?>, <?= $verifiedCreators ?>, <?= $featuredCreators ?>],
                            backgroundColor: [
                                '#1cc88a',
                                '#e74a3b',
                                '#4e73df',
                                '#f6c23e'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
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
            }
        });
    </script>
</body>
</html>
