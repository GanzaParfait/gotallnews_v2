<?php
/**
 * Batch Process Existing Images
 * This script processes all existing images in the database and generates WebP versions
 * Run this after setting up the new image processing system
 */

require_once 'php/includes/ImageProcessor.php';

// Configuration
$batchSize = 10; // Process 10 images at a time
$delay = 1; // Delay between batches in seconds

echo "<h2>Batch Processing Existing Images</h2>";

// Check if we can connect to database
if (!isset($con)) {
    include 'php/config.php';
}

if (!$con) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Get total count of articles with images
$countQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM article WHERE Image IS NOT NULL AND Image != ''");
$totalCount = mysqli_fetch_assoc($countQuery)['total'];

echo "<p>Total articles with images: <strong>$totalCount</strong></p>";

if ($totalCount == 0) {
    echo "<p>No images to process.</p>";
    exit;
}

// Get articles in batches
$offset = 0;
$processed = 0;
$errors = 0;

while ($offset < $totalCount) {
    $query = mysqli_query($con, "SELECT ArticleID, Image FROM article WHERE Image IS NOT NULL AND Image != '' LIMIT $batchSize OFFSET $offset");
    
    echo "<h3>Processing batch " . (($offset / $batchSize) + 1) . "</h3>";
    
    while ($article = mysqli_fetch_assoc($query)) {
        $articleId = $article['ArticleID'];
        $imageName = $article['Image'];
        $imagePath = 'images/uploaded/' . $imageName;
        
        echo "<p>Processing Article ID: $articleId - Image: $imageName</p>";
        
        // Check if image file exists
        if (!file_exists($imagePath)) {
            echo "<p style='color: orange;'>⚠️ Image file not found: $imagePath</p>";
            $errors++;
            continue;
        }
        
        // Check if WebP versions already exist
        $baseName = pathinfo($imageName, PATHINFO_FILENAME);
        $webpLarge = $baseName . '_large.webp';
        $webpMedium = $baseName . '_medium.webp';
        $webpSmall = $baseName . '_small.webp';
        $webpThumbnail = $baseName . '_thumbnail.webp';
        
        if (file_exists('images/uploaded/' . $webpLarge) && 
            file_exists('images/uploaded/' . $webpMedium) && 
            file_exists('images/uploaded/' . $webpSmall) && 
            file_exists('images/uploaded/' . $webpThumbnail)) {
            echo "<p style='color: blue;'>ℹ️ WebP versions already exist for: $imageName</p>";
            $processed++;
            continue;
        }
        
        try {
            // Initialize image processor
            $imageProcessor = new ImageProcessor('images/uploaded/', 80);
            
            // Process the image
            $processedImages = $imageProcessor->processImage($imagePath, $imageName, time());
            
            if ($processedImages && count($processedImages) > 0) {
                // Update database with new image paths
                $updateQuery = "UPDATE article SET 
                    image_large = '" . ($processedImages['large'] ?? '') . "',
                    image_medium = '" . ($processedImages['medium'] ?? '') . "',
                    image_small = '" . ($processedImages['small'] ?? '') . "',
                    image_thumbnail = '" . ($processedImages['thumbnail'] ?? '') . "'
                    WHERE ArticleID = $articleId";
                
                if (mysqli_query($con, $updateQuery)) {
                    echo "<p style='color: green;'>✅ Successfully processed: $imageName</p>";
                    $processed++;
                } else {
                    echo "<p style='color: red;'>❌ Database update failed: " . mysqli_error($con) . "</p>";
                    $errors++;
                    // Clean up processed images
                    $imageProcessor->cleanupOldImages($processedImages);
                }
            } else {
                echo "<p style='color: red;'>❌ Image processing failed for: $imageName</p>";
                $errors++;
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error processing $imageName: " . $e->getMessage() . "</p>";
            $errors++;
        }
    }
    
    $offset += $batchSize;
    
    // Progress update
    $progress = min(100, ($offset / $totalCount) * 100);
    echo "<p><strong>Progress: " . number_format($progress, 1) . "%</strong></p>";
    
    // Delay between batches to prevent server overload
    if ($offset < $totalCount) {
        echo "<p>Waiting $delay second before next batch...</p>";
        sleep($delay);
    }
}

// Final summary
echo "<h2>Processing Complete</h2>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>Total articles: $totalCount</li>";
echo "<li>Successfully processed: <span style='color: green;'>$processed</span></li>";
echo "<li>Errors: <span style='color: red;'>$errors</span></li>";
echo "</ul>";

if ($errors > 0) {
    echo "<p style='color: orange;'>⚠️ Some images failed to process. Check the error messages above.</p>";
} else {
    echo "<p style='color: green;'>🎉 All images processed successfully!</p>";
}

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test the website to ensure images are loading correctly</li>";
echo "<li>Check page load times - they should be significantly improved</li>";
echo "<li>Monitor server performance and adjust settings if needed</li>";
echo "</ol>";
?>
