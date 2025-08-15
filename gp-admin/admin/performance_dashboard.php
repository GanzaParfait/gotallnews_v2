<?php
include 'php/header/top.php';
include 'php/includes/PerformanceConfig.php';

// Get system performance metrics
$systemMetrics = getSystemMetrics();
$userMetrics = getUserMetrics();
$videoMetrics = getVideoMetrics();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard - Video Shorts</title>
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
    
    <!-- Chart.js for performance graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .performance-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .metric-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #4e73df;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4e73df;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .metric-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-good {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-critical {
            background: #f8d7da;
            color: #721c24;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        
        .performance-alerts {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        }
        
        .alert-icon.warning {
            background: #f39c12;
        }
        
        .alert-icon.critical {
            background: #e74c3c;
        }
        
        .alert-icon.info {
            background: #3498db;
        }
    </style>
</head>
<body>
    <div class="whole-content-container">
        <?php include 'php/includes/header.php'; ?>
        <?php include 'php/includes/sidebar.php'; ?>
        
        <div class="main-container">
            <div class="pd-ltr-20 xs-pd-20-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="weight-600 font-24">Performance Dashboard</h1>
                    <button class="btn btn-primary" onclick="refreshMetrics()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <!-- Performance Alerts -->
                <?php if (!empty($systemMetrics['alerts'])): ?>
                    <div class="performance-alerts">
                        <h5><i class="fas fa-exclamation-triangle"></i> Performance Alerts</h5>
                        <?php foreach ($systemMetrics['alerts'] as $alert): ?>
                            <div class="alert-item">
                                <div class="alert-icon <?= $alert['level'] ?>">
                                    <?= $alert['level'] === 'critical' ? '!' : ($alert['level'] === 'warning' ? '!' : 'i') ?>
                                </div>
                                <span><?= htmlspecialchars($alert['message']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- System Metrics -->
                <div class="performance-card">
                    <h3 class="weight-600 font-18 mb-3">System Performance</h3>
                    <div class="metric-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?= $systemMetrics['cpu_usage'] ?>%</div>
                            <div class="metric-label">CPU Usage</div>
                            <div class="metric-status <?= getStatusClass($systemMetrics['cpu_usage'], 70, 90) ?>">
                                <?= getStatusText($systemMetrics['cpu_usage'], 70, 90) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= formatBytes($systemMetrics['memory_usage']) ?></div>
                            <div class="metric-label">Memory Usage</div>
                            <div class="metric-status <?= getStatusClass($systemMetrics['memory_percentage'], 80, 95) ?>">
                                <?= getStatusText($systemMetrics['memory_percentage'], 80, 95) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $systemMetrics['disk_usage'] ?>%</div>
                            <div class="metric-label">Disk Usage</div>
                            <div class="metric-status <?= getStatusClass($systemMetrics['disk_usage'], 80, 95) ?>">
                                <?= getStatusText($systemMetrics['disk_usage'], 80, 95) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $systemMetrics['active_connections'] ?></div>
                            <div class="metric-label">Active Connections</div>
                            <div class="metric-status <?= getStatusClass($systemMetrics['active_connections'], 100, 200) ?>">
                                <?= getStatusText($systemMetrics['active_connections'], 100, 200) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Metrics -->
                <div class="performance-card">
                    <h3 class="weight-600 font-18 mb-3">User Performance</h3>
                    <div class="metric-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?= $userMetrics['concurrent_users'] ?></div>
                            <div class="metric-label">Concurrent Users</div>
                            <div class="metric-status <?= getStatusClass($userMetrics['concurrent_users'], 1000, 5000) ?>">
                                <?= getStatusText($userMetrics['concurrent_users'], 1000, 5000) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $userMetrics['avg_response_time'] ?>ms</div>
                            <div class="metric-label">Avg Response Time</div>
                            <div class="metric-status <?= getStatusClass($userMetrics['avg_response_time'], 200, 500, true) ?>">
                                <?= getStatusText($userMetrics['avg_response_time'], 200, 500, true) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $userMetrics['requests_per_second'] ?></div>
                            <div class="metric-label">Requests/Second</div>
                            <div class="metric-status <?= getStatusClass($userMetrics['requests_per_second'], 100, 200) ?>">
                                <?= getStatusText($userMetrics['requests_per_second'], 100, 200) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $userMetrics['error_rate'] ?>%</div>
                            <div class="metric-label">Error Rate</div>
                            <div class="metric-status <?= getStatusClass($userMetrics['error_rate'], 1, 5, true) ?>">
                                <?= getStatusText($userMetrics['error_rate'], 1, 5, true) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Video Performance -->
                <div class="performance-card">
                    <h3 class="weight-600 font-18 mb-3">Video Performance</h3>
                    <div class="metric-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?= $videoMetrics['videos_served'] ?></div>
                            <div class="metric-label">Videos Served Today</div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $videoMetrics['avg_load_time'] ?>s</div>
                            <div class="metric-label">Avg Video Load Time</div>
                            <div class="metric-status <?= getStatusClass($videoMetrics['avg_load_time'], 2, 5, true) ?>">
                                <?= getStatusText($videoMetrics['avg_load_time'], 2, 5, true) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $videoMetrics['cache_hit_rate'] ?>%</div>
                            <div class="metric-label">Cache Hit Rate</div>
                            <div class="metric-status <?= getStatusClass($videoMetrics['cache_hit_rate'], 60, 80, true) ?>">
                                <?= getStatusText($videoMetrics['cache_hit_rate'], 60, 80, true) ?>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-value"><?= $videoMetrics['bandwidth_usage'] ?></div>
                            <div class="metric-label">Bandwidth Usage</div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="performance-card">
                            <h3 class="weight-600 font-18 mb-3">Response Time Trend</h3>
                            <div class="chart-container">
                                <canvas id="responseTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="performance-card">
                            <h3 class="weight-600 font-18 mb-3">User Load Trend</h3>
                            <div class="chart-container">
                                <canvas id="userLoadChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Recommendations -->
                <div class="performance-card">
                    <h3 class="weight-600 font-18 mb-3">Performance Recommendations</h3>
                    <div id="recommendationsList">
                        <!-- Recommendations will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    
    <script>
        // Initialize charts
        let responseTimeChart, userLoadChart;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadRecommendations();
            startAutoRefresh();
        });
        
        function initializeCharts() {
            // Response Time Chart
            const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
            responseTimeChart = new Chart(responseTimeCtx, {
                type: 'line',
                data: {
                    labels: generateTimeLabels(24),
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: generateRandomData(24, 100, 300),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Milliseconds'
                            }
                        }
                    }
                }
            });
            
            // User Load Chart
            const userLoadCtx = document.getElementById('userLoadChart').getContext('2d');
            userLoadChart = new Chart(userLoadCtx, {
                type: 'line',
                data: {
                    labels: generateTimeLabels(24),
                    datasets: [{
                        label: 'Concurrent Users',
                        data: generateRandomData(24, 1000, 5000),
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Users'
                            }
                        }
                    }
                }
            });
        }
        
        function generateTimeLabels(hours) {
            const labels = [];
            const now = new Date();
            for (let i = hours - 1; i >= 0; i--) {
                const time = new Date(now.getTime() - (i * 60 * 60 * 1000));
                labels.push(time.getHours() + ':00');
            }
            return labels;
        }
        
        function generateRandomData(count, min, max) {
            const data = [];
            for (let i = 0; i < count; i++) {
                data.push(Math.floor(Math.random() * (max - min + 1)) + min);
            }
            return data;
        }
        
        function refreshMetrics() {
            // Simulate refreshing metrics
            location.reload();
        }
        
        function loadRecommendations() {
            const recommendations = [
                'Consider implementing video compression for better bandwidth usage',
                'Monitor memory usage and implement cleanup for long sessions',
                'Implement connection pooling for database optimization',
                'Consider CDN for video content delivery',
                'Monitor cache hit rates and adjust cache sizes accordingly'
            ];
            
            const recommendationsList = document.getElementById('recommendationsList');
            recommendationsList.innerHTML = recommendations.map(rec => 
                `<div class="alert-item">
                    <div class="alert-icon info">i</div>
                    <span>${rec}</span>
                </div>`
            ).join('');
        }
        
        function startAutoRefresh() {
            // Refresh charts every 5 minutes
            setInterval(() => {
                updateCharts();
            }, 300000);
        }
        
        function updateCharts() {
            // Update chart data with new values
            if (responseTimeChart && userLoadChart) {
                responseTimeChart.data.datasets[0].data = generateRandomData(24, 100, 300);
                responseTimeChart.update();
                
                userLoadChart.data.datasets[0].data = generateRandomData(24, 1000, 5000);
                userLoadChart.update();
            }
        }
    </script>
</body>
</html>

<?php
// Helper functions
function getSystemMetrics() {
    // In a real implementation, these would come from system monitoring
    return [
        'cpu_usage' => rand(20, 85),
        'memory_usage' => rand(2, 8) * 1024 * 1024 * 1024, // 2-8 GB
        'memory_percentage' => rand(60, 90),
        'disk_usage' => rand(70, 95),
        'active_connections' => rand(50, 300),
        'alerts' => []
    ];
}

function getUserMetrics() {
    return [
        'concurrent_users' => rand(500, 8000),
        'avg_response_time' => rand(150, 600),
        'requests_per_second' => rand(80, 250),
        'error_rate' => rand(0, 3)
    ];
}

function getVideoMetrics() {
    return [
        'videos_served' => rand(1000, 50000),
        'avg_load_time' => rand(1, 4),
        'cache_hit_rate' => rand(50, 95),
        'bandwidth_usage' => formatBytes(rand(100, 1000) * 1024 * 1024 * 1024) // 100-1000 GB
    ];
}

function getStatusClass($value, $warningThreshold, $criticalThreshold, $lowerIsBetter = false) {
    if ($lowerIsBetter) {
        if ($value <= $warningThreshold) return 'status-good';
        if ($value <= $criticalThreshold) return 'status-warning';
        return 'status-critical';
    } else {
        if ($value <= $warningThreshold) return 'status-good';
        if ($value <= $criticalThreshold) return 'status-warning';
        return 'status-critical';
    }
}

function getStatusText($value, $warningThreshold, $criticalThreshold, $lowerIsBetter = false) {
    if ($lowerIsBetter) {
        if ($value <= $warningThreshold) return 'Good';
        if ($value <= $criticalThreshold) return 'Warning';
        return 'Critical';
    } else {
        if ($value <= $warningThreshold) return 'Good';
        if ($value <= $criticalThreshold) return 'Warning';
        return 'Critical';
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
