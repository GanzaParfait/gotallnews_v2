<?php
/**
 * Test script for image processing functionality
 * Run this to test if the image processing system is working correctly
 */

// Include required files
require_once 'php/includes/ImageProcessor.php';

echo "<h2>Image Processing Test</h2>";

// Check if GD extension is available
if (!extension_loaded('gd')) {
    echo "<p style='color: red;'>❌ GD extension is not available. Please install it.</p>";
    exit;
}

echo "<p style='color: green;'>✅ GD extension is available</p>";

// Check if WebP support is available
if (!function_exists('imagewebp')) {
    echo "<p style='color: red;'>❌ WebP support is not available in GD extension</p>";
    exit;
}

echo "<p style='color: green;'>✅ WebP support is available</p>";

// Check upload directory
$uploadDir = 'images/uploaded/';
if (!is_dir($uploadDir)) {
    echo "<p style='color: red;'>❌ Upload directory does not exist: $uploadDir</p>";
    exit;
}

if (!is_writable($uploadDir)) {
    echo "<p style='color: red;'>❌ Upload directory is not writable: $uploadDir</p>";
    exit;
}

echo "<p style='color: green;'>✅ Upload directory is accessible and writable</p>";

// Test ImageProcessor class
try {
    $processor = new ImageProcessor($uploadDir);
    echo "<p style='color: green;'>✅ ImageProcessor class instantiated successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to instantiate ImageProcessor: " . $e->getMessage() . "</p>";
    exit;
}

// Display configuration
echo "<h3>Current Configuration:</h3>";
echo "<ul>";
foreach (IMAGE_SIZES as $size => $config) {
    echo "<li><strong>$size</strong>: {$config['width']}x{$config['height']} (Quality: {$config['quality']})</li>";
}
echo "</ul>";

echo "<h3>System Information:</h3>";
echo "<ul>";
echo "<li>Memory Limit: " . ini_get('memory_limit') . "</li>";
echo "<li>Max Execution Time: " . ini_get('max_execution_time') . " seconds</li>";
echo "<li>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>Post Max Size: " . ini_get('post_max_size') . "</li>";
echo "</ul>";

echo "<p style='color: green;'>✅ All tests passed! Image processing system is ready to use.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Run the database update script: <code>php database_update.sql</code></li>";
echo "<li>Upload an article with an image to test the system</li>";
echo "<li>Check the uploaded images folder for multiple WebP versions</li>";
echo "</ol>";
?>
