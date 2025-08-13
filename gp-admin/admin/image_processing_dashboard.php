<?php
/**
 * Image Processing Dashboard
 * Central hub for all image processing tools
 */

require_once 'php/config.php';
require_once 'php/includes/image_helper.php';

// Get system status
$gd_available = extension_loaded('gd');
$webp_supported = function_exists('imagewebp');
$upload_dir_writable = is_writable('images/uploaded/');

// Check database columns
$has_new_columns = false;
if ($con) {
    $check_query = mysqli_query($con, "SHOW COLUMNS FROM `article` LIKE 'image_large'");
    $has_new_columns = mysqli_num_rows($check_query) > 0;
}

// Get article count
$article_count = 0;
if ($con) {
    $count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM article WHERE Image IS NOT NULL AND Image != ''");
    if ($count_query) {
        $article_count = mysqli_fetch_assoc($count_query)['total'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Processing Dashboard - GotAllNews CMS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .status-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; }
        .status-card.success { border-left-color: #28a745; }
        .status-card.warning { border-left-color: #ffc107; }
        .status-card.error { border-left-color: #dc3545; }
        .status-card h3 { margin-top: 0; color: #333; }
        .status-icon { font-size: 24px; margin-bottom: 10px; }
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .tool-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; }
        .tool-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .tool-card h4 { margin-top: 0; color: #007bff; }
        .tool-card p { color: #666; margin-bottom: 20px; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; transition: background 0.2s; }
        .btn:hover { background: #0056b3; }
        .btn.success { background: #28a745; }
        .btn.warning { background: #ffc107; color: #333; }
        .btn.danger { background: #dc3545; }
        .btn:hover.success { background: #1e7e34; }
        .btn:hover.warning { background: #e0a800; }
        .btn:hover.danger { background: #c82333; }
        .stats { background: #e7f3ff; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .stats h3 { margin-top: 0; color: #007bff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 0.9em; }
        .back-link { text-align: center; margin-top: 30px; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Image Processing Dashboard</h1>
            <p>Central hub for managing and optimizing images in your GotAllNews CMS</p>
        </div>

        <!-- System Status -->
        <h2>📊 System Status</h2>
        <div class="status-grid">
            <div class="status-card <?= $gd_available ? 'success' : 'error' ?>">
                <div class="status-icon"><?= $gd_available ? '✅' : '❌' ?></div>
                <h3>GD Extension</h3>
                <p><?= $gd_available ? 'Available - Image processing supported' : 'Missing - Install GD extension' ?></p>
            </div>

            <div class="status-card <?= $webp_supported ? 'success' : 'warning' ?>">
                <div class="status-icon"><?= $webp_supported ? '✅' : '⚠️' ?></div>
                <h3>WebP Support</h3>
                <p><?= $webp_supported ? 'Available - WebP conversion supported' : 'Limited - WebP conversion not available' ?></p>
            </div>

            <div class="status-card <?= $upload_dir_writable ? 'success' : 'error' ?>">
                <div class="status-icon"><?= $upload_dir_writable ? '✅' : '❌' ?></div>
                <h3>Upload Directory</h3>
                <p><?= $upload_dir_writable ? 'Writable - Images can be uploaded' : 'Not writable - Check permissions' ?></p>
            </div>

            <div class="status-card <?= $has_new_columns ? 'success' : 'warning' ?>">
                <div class="status-icon"><?= $has_new_columns ? '✅' : '⚠️' ?></div>
                <h3>Database Structure</h3>
                <p><?= $has_new_columns ? 'Updated - Multiple image sizes supported' : 'Update required - Run database update' ?></p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <h3>📈 Current Statistics</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $article_count ?></div>
                    <div class="stat-label">Articles with Images</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4</div>
                    <div class="stat-label">Image Sizes Generated</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">70-90%</div>
                    <div class="stat-label">File Size Reduction</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">WebP</div>
                    <div class="stat-label">Output Format</div>
                </div>
            </div>
        </div>

        <!-- Tools -->
        <h2>🛠️ Available Tools</h2>
        <div class="tools-grid">
            <div class="tool-card">
                <h4>🗄️ Database Update</h4>
                <p>Add new columns for multiple image sizes to your database</p>
                <a href="update_database.php" class="btn <?= $has_new_columns ? 'success' : 'warning' ?>">
                    <?= $has_new_columns ? 'Already Updated' : 'Update Database' ?>
                </a>
            </div>

            <div class="tool-card">
                <h4>🧪 System Test</h4>
                <p>Test if the image processing system is working correctly</p>
                <a href="test_image_processing.php" class="btn">Run Tests</a>
            </div>

            <div class="tool-card">
                <h4>🔍 Image Retrieval</h4>
                <p>Test retrieving compressed images by Article ID</p>
                <a href="test_image_retrieval.php" class="btn">Test Retrieval</a>
            </div>

            <div class="tool-card">
                <h4>📚 Batch Processing</h4>
                <p>Convert all existing images to WebP format</p>
                <!-- <a href="batch_process_existing_images.php" class="btn <?= $article_count > 0 ? '' : 'danger' ?>">
                    <?= $article_count > 0 ? 'Process Images' : 'No Images Found' ?>
                </a> -->
                
                <button class="btn btn-primary" disabled>
                    Process Images
                </button>
            </div>

            <div class="tool-card">
                <h4>➕ New Article</h4>
                <p>Create a new article with automatic image processing</p>
                <a href="new_article.php" class="btn">Create Article</a>
            </div>

            <div class="tool-card">
                <h4>✏️ Edit Article</h4>
                <p>Update existing articles with new image processing</p>
                <a href="view_article.php" class="btn">Manage Articles</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2>⚡ Quick Actions</h2>
        <div style="text-align: center; margin: 20px 0;">
            <a href="update_database.php" class="btn <?= $has_new_columns ? 'success' : 'warning' ?>" style="margin: 5px;">
                <?= $has_new_columns ? '✅ Database Ready' : '⚠️ Update Database First' ?>
            </a>
            
            <?php if ($has_new_columns && $gd_available && $webp_supported): ?>
                <a href="test_image_processing.php" class="btn" style="margin: 5px;">🧪 Test System</a>
                <a href="new_article.php" class="btn" style="margin: 5px;">➕ Create Article</a>
                <?php if ($article_count > 0): ?>
                    <a href="batch_process_existing_images.php" class="btn" style="margin: 5px;">📚 Process Existing Images</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Documentation -->
        <h2>📚 Documentation</h2>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h4>📖 Available Guides</h4>
            <ul>
                <li><a href="README_IMAGE_PROCESSING.md" target="_blank">Complete Image Processing Guide</a></li>
                <li><a href="IMAGE_FILE_STRUCTURE.md" target="_blank">Image File Structure & Storage</a></li>
                <li><a href="database_update.sql" target="_blank">Database Update SQL Script</a></li>
            </ul>
            
            <h4>🔧 Configuration</h4>
            <p>Edit <code>php/includes/image_config.php</code> to customize image sizes, quality, and other settings.</p>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Main Dashboard</a>
        </div>
    </div>
</body>
</html>
