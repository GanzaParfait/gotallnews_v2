<?php
/**
 * Image Processing Configuration
 * Modify these settings to adjust image quality, sizes, and compression
 */

// Image quality settings (0-100, higher = better quality but larger file size)
define('IMAGE_QUALITY_LARGE', 80);      // Quality for large images
define('IMAGE_QUALITY_MEDIUM', 75);     // Quality for medium images  
define('IMAGE_QUALITY_SMALL', 70);      // Quality for small images
define('IMAGE_QUALITY_THUMBNAIL', 65);  // Quality for thumbnails

// Image dimensions for different sizes
define('IMAGE_SIZES', [
    'large' => [
        'width' => 1200,
        'height' => 800,
        'quality' => IMAGE_QUALITY_LARGE,
        'description' => 'Featured articles, hero sections'
    ],
    'medium' => [
        'width' => 800,
        'height' => 600,
        'quality' => IMAGE_QUALITY_MEDIUM,
        'description' => 'Article headers, content images'
    ],
    'small' => [
        'width' => 400,
        'height' => 300,
        'quality' => IMAGE_QUALITY_SMALL,
        'description' => 'Sidebar, related articles'
    ],
    'thumbnail' => [
        'width' => 150,
        'height' => 150,
        'quality' => IMAGE_QUALITY_THUMBNAIL,
        'description' => 'Thumbnails, lists, grids'
    ]
]);

// File size optimization thresholds (in bytes)
define('FILE_SIZE_THRESHOLDS', [
    'small' => 50000,      // 50KB - for thumbnails
    'medium' => 150000,    // 150KB - for small images
    'large' => 300000,     // 300KB - for medium images
    'xlarge' => 800000     // 800KB - for large images
]);

// Allowed file extensions
define('ALLOWED_EXTENSIONS', ['png', 'jpeg', 'jpg', 'gif', 'webp']);

// Output format (currently only WebP is supported)
define('OUTPUT_FORMAT', 'webp');

// Enable/disable features
define('ENABLE_WEBP_CONVERSION', true);
define('ENABLE_MULTIPLE_SIZES', true);
define('ENABLE_COMPRESSION', true);
define('ENABLE_ASPECT_RATIO_PRESERVATION', true);

// Error handling
define('THROW_ON_ERROR', true);
define('LOG_ERRORS', true);

// Performance settings
define('MAX_EXECUTION_TIME', 30);  // Maximum time to process an image (seconds)
define('MEMORY_LIMIT', '256M');    // Memory limit for image processing

// Cleanup settings
define('CLEANUP_TEMP_FILES', true);
define('CLEANUP_ON_FAILURE', true);
?>
