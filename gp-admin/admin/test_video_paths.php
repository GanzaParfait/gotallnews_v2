<?php
/** Test script for video file paths and video player functionality */
require_once 'php/config.php';
require_once 'php/includes/VideoManager.php';

echo '<h2>Video File Path Test</h2>';

try {
    // Initialize VideoManager with correct paths
    $videoManager = new VideoManager($con, 'videos/', 'images/video_thumbnails/');
    echo '<p>✅ VideoManager initialized</p>';

    // Get a sample video to test
    $videos = $videoManager->getAllVideos(1, 3, []);
    if (!empty($videos)) {
        echo '<p>Found ' . count($videos) . ' videos to test</p>';

        foreach ($videos as $video) {
            echo '<hr>';
            echo '<h3>Testing Video: ' . htmlspecialchars($video['Title']) . '</h3>';
            echo '<p>Video ID: ' . $video['VideoID'] . '</p>';
            echo '<p>Video File: ' . htmlspecialchars($video['VideoFile']) . '</p>';
            echo '<p>Video Format: ' . $video['VideoFormat'] . '</p>';
            echo '<p>Video Thumbnail: ' . htmlspecialchars($video['VideoThumbnail']) . '</p>';

            if ($video['VideoFormat'] === 'embed') {
                echo '<p>This is an embedded video - no file path needed</p>';
                continue;
            }

            // Test video file path construction
            echo '<h4>Video File Path Test</h4>';
            $videoFile = $video['VideoFile'];

            echo "<p>Original path: $videoFile</p>";
            echo '<p>File exists (original): ' . (file_exists($videoFile) ? 'Yes' : 'No') . '</p>';

            // Test different path variations
            $paths = [
                'videos/' . basename($videoFile),
                'src/videos/' . basename($videoFile),
                'php/videos/' . basename($videoFile),
                'gp-admin/admin/videos/' . basename($videoFile),
                'gp-admin/admin/src/videos/' . basename($videoFile),
                'admin/videos/' . basename($videoFile),
                'admin/src/videos/' . basename($videoFile)
            ];

            $foundPath = null;
            foreach ($paths as $path) {
                $exists = file_exists($path);
                $size = $exists ? filesize($path) : 'N/A';
                echo "<p>Path: $path - Exists: " . ($exists ? 'Yes' : 'No') . " - Size: $size</p>";
                if ($exists && !$foundPath) {
                    $foundPath = $path;
                }
            }

            if ($foundPath) {
                echo "<p>✅ Found video file at: $foundPath</p>";

                // Test video player HTML
                echo '<h4>Video Player Test</h4>';

                // Determine video type
                $extension = strtolower(pathinfo($foundPath, PATHINFO_EXTENSION));
                $videoType = 'video/mp4';  // Default
                switch ($extension) {
                    case 'mp4':
                        $videoType = 'video/mp4';
                        break;
                    case 'webm':
                        $videoType = 'video/webm';
                        break;
                    case 'ogg':
                        $videoType = 'video/ogg';
                        break;
                    case 'avi':
                        $videoType = 'video/x-msvideo';
                        break;
                    case 'mov':
                        $videoType = 'video/quicktime';
                        break;
                }

                echo "<p>Detected video type: $videoType</p>";

                // Test video player rendering
                echo '<h5>Video Player Test:</h5>';
                echo "<video controls style='max-width: 500px; border: 1px solid #ccc;'>";
                echo "<source src='$foundPath' type='$videoType'>";
                echo 'Your browser does not support the video tag.';
                echo '</video>';
            } else {
                echo '<p>❌ Video file not found in any expected location</p>';
                echo '<p>This will cause the video player to show a black screen!</p>';
            }

            // Test thumbnail
            echo '<h4>Thumbnail Test</h4>';
            if (!empty($video['VideoThumbnail'])) {
                $thumbnailPath = $video['VideoThumbnail'];
                echo "<p>Thumbnail path: $thumbnailPath</p>";

                // Check thumbnail paths
                $thumbnailPaths = [
                    $thumbnailPath,
                    'images/video_thumbnails/' . basename($thumbnailPath),
                    'php/images/video_thumbnails/' . basename($thumbnailPath)
                ];

                $thumbnailFound = false;
                foreach ($thumbnailPaths as $path) {
                    if (file_exists($path)) {
                        echo "<p>✅ Thumbnail found at: $path</p>";
                        echo "<img src='$path' style='max-width: 200px; border: 1px solid #ccc;'>";
                        $thumbnailFound = true;
                        break;
                    }
                }

                if (!$thumbnailFound) {
                    echo '<p>❌ Thumbnail not found</p>';
                }
            } else {
                echo '<p>No thumbnail set</p>';
            }
        }
    } else {
        echo '<p>❌ No videos found in database</p>';
    }

    // Test directory structure
    echo '<h3>Directory Structure Test</h3>';
    $directories = [
        'videos/',
        'src/videos/',
        'php/videos/',
        'images/video_thumbnails/',
        'php/images/video_thumbnails/'
    ];

    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $fileCount = count(scandir($dir)) - 2;  // Subtract . and ..
            echo "<p>✅ $dir exists ($fileCount files)</p>";

            if ($fileCount > 0) {
                $files = scandir($dir);
                echo '<ul>';
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $dir . $file;
                        $fileSize = filesize($filePath);
                        echo "<li>$file ($fileSize bytes)</li>";
                    }
                }
                echo '</ul>';
            }
        } else {
            echo "<p>❌ $dir does not exist</p>";
        }
    }
} catch (Exception $e) {
    echo '<p>❌ Error: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<hr>';
echo "<p><a href='video_posts.php'>Back to Video Posts</a></p>";
?>
