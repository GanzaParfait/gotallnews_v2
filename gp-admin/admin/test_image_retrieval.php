<?php
/**
 * Test Image Retrieval by Article ID
 * This file allows you to enter an ArticleID and see all compressed images
 */

require_once 'php/config.php';
require_once 'php/includes/image_helper.php';

// Check if form is submitted
if (isset($_POST['article_id'])) {
    $article_id = (int)$_POST['article_id'];
    
    // Get article data
    $query = mysqli_query($con, "SELECT * FROM article WHERE ArticleID = $article_id");
    
    if (mysqli_num_rows($query) > 0) {
        $article = mysqli_fetch_assoc($query);
        $show_results = true;
    } else {
        $error = "Article ID $article_id not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Image Retrieval - GotAllNews CMS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"] { width: 200px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .image-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; }
        .image-card img { max-width: 100%; height: auto; border-radius: 4px; }
        .image-info { margin-top: 10px; }
        .image-info h4 { margin: 5px 0; color: #333; }
        .image-info p { margin: 5px 0; color: #666; font-size: 14px; }
        .file-size { color: #007bff; font-weight: bold; }
        .dimensions { color: #28a745; }
        .quality { color: #ffc107; }
        .back-link { margin-top: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Test Image Retrieval by Article ID</h1>
        <p>Enter an Article ID to see all compressed images for that article.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="article_id">Article ID:</label>
                <input type="number" id="article_id" name="article_id" value="<?= isset($_POST['article_id']) ? htmlspecialchars($_POST['article_id']) : '' ?>" required>
            </div>
            <button type="submit">🔍 Retrieve Images</button>
        </form>

        <?php if (isset($error)): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($show_results) && $show_results): ?>
            <div class="success">
                ✅ Found Article: <strong><?= htmlspecialchars($article['Title']) ?></strong>
            </div>

            <h2>📊 Article Information</h2>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <p><strong>Article ID:</strong> <?= $article['ArticleID'] ?></p>
                <p><strong>Title:</strong> <?= htmlspecialchars($article['Title']) ?></p>
                <p><strong>Category ID:</strong> <?= $article['CategoryID'] ?></p>
                <p><strong>Published:</strong> <?= $article['Published'] ?></p>
                <p><strong>Date:</strong> <?= $article['Date'] ?></p>
            </div>

            <h2>🖼️ Image Analysis</h2>
            
            <?php
            // Check if new image columns exist
            $has_new_columns = isset($article['image_large']) || isset($article['image_medium']) || isset($article['image_small']) || isset($article['image_thumbnail']);
            
            if (!$has_new_columns): ?>
                <div class="error">
                    ⚠️ <strong>Database Update Required!</strong><br>
                    The new image size columns are not present in the database yet.<br>
                    Please run the database update script first.
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 20px 0;">
                    <h4>📋 Database Update Required</h4>
                    <p>Run this SQL command in your database:</p>
                    <code style="background: #f8f9fa; padding: 10px; display: block; border-radius: 4px; margin: 10px 0;">
                        ALTER TABLE `article` <br>
                        ADD COLUMN `image_large` VARCHAR(255) NULL AFTER `Image`,<br>
                        ADD COLUMN `image_medium` VARCHAR(255) NULL AFTER `image_large`,<br>
                        ADD COLUMN `image_small` VARCHAR(255) NULL AFTER `image_medium`,<br>
                        ADD COLUMN `image_thumbnail` VARCHAR(255) NULL AFTER `image_small`;
                    </code>
                </div>
            <?php else: ?>
                <div class="image-grid">
                    <?php
                    $image_sizes = [
                        'large' => ['name' => 'Large', 'width' => 1200, 'height' => 800, 'quality' => 80],
                        'medium' => ['name' => 'Medium', 'width' => 800, 'height' => 600, 'quality' => 75],
                        'small' => ['name' => 'Small', 'width' => 400, 'height' => 300, 'quality' => 70],
                        'thumbnail' => ['name' => 'Thumbnail', 'width' => 150, 'height' => 150, 'quality' => 65]
                    ];

                    foreach ($image_sizes as $size => $config):
                        $image_field = 'image_' . $size;
                        $image_filename = $article[$image_field] ?? $article['Image'];
                        $image_path = 'images/uploaded/' . $image_filename;
                        $file_exists = file_exists($image_path);
                        $file_size = $file_exists ? filesize($image_path) : 0;
                        $file_size_formatted = $file_exists ? formatFileSize($file_size) : 'File not found';
                    ?>
                        <div class="image-card">
                            <?php if ($file_exists): ?>
                                <img src="<?= $image_path ?>" alt="<?= $config['name'] ?> size image" 
                                     style="max-width: 100%; height: auto;">
                            <?php else: ?>
                                <div style="background: #f8f9fa; height: 200px; display: flex; align-items: center; justify-content: center; border: 2px dashed #ddd;">
                                    <span style="color: #999;">Image not found</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="image-info">
                                <h4><?= $config['name'] ?> Size</h4>
                                <p class="dimensions">📐 <?= $config['width'] ?> × <?= $config['height'] ?> px</p>
                                <p class="quality">🎯 Quality: <?= $config['quality'] ?>%</p>
                                <p class="file-size">💾 Size: <?= $file_size_formatted ?></p>
                                <p><strong>File:</strong> <?= htmlspecialchars($image_filename) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3>📈 Performance Summary</h3>
                <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;">
                    <?php
                    $original_size = file_exists('images/uploaded/' . $article['Image']) ? filesize('images/uploaded/' . $article['Image']) : 0;
                    $compressed_sizes = [];
                    
                    foreach ($image_sizes as $size => $config) {
                        $image_field = 'image_' . $size;
                        if (isset($article[$image_field]) && !empty($article[$image_field])) {
                            $compressed_path = 'images/uploaded/' . $article[$image_field];
                            if (file_exists($compressed_path)) {
                                $compressed_sizes[$size] = filesize($compressed_path);
                            }
                        }
                    }
                    
                    if ($original_size > 0 && !empty($compressed_sizes)):
                        $total_compressed = array_sum($compressed_sizes);
                        $savings = $original_size - $total_compressed;
                        $savings_percent = round(($savings / $original_size) * 100, 1);
                    ?>
                        <p><strong>Original Image:</strong> <?= formatFileSize($original_size) ?></p>
                        <p><strong>Total Compressed:</strong> <?= formatFileSize($total_compressed) ?></p>
                        <p><strong>Space Saved:</strong> <span style="color: #28a745; font-weight: bold;"><?= formatFileSize($savings) ?> (<?= $savings_percent ?>%)</span></p>
                    <?php else: ?>
                        <p>⚠️ Cannot calculate savings - some files may be missing</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a> | 
            <a href="test_image_processing.php">Test Image Processing</a> | 
            <a href="batch_process_existing_images.php">Batch Process Images</a>
        </div>
    </div>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
