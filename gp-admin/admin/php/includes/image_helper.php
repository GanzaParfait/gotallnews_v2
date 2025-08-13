<?php
/**
 * Image Helper Functions for displaying optimized images
 */

/**
 * Get the appropriate image size based on context
 * @param array $article Article data from database
 * @param string $context Context where image will be used (large, medium, small, thumbnail)
 * @param string $fallback Fallback image if the requested size doesn't exist
 * @return string Image filename
 */
function getImageBySize($article, $context = 'medium', $fallback = null) {
    $imageField = 'image_' . $context;
    
    // Check if the specific size exists
    if (isset($article[$imageField]) && !empty($article[$imageField])) {
        return $article[$imageField];
    }
    
    // Fallback to main image if specific size doesn't exist
    if (isset($article['Image']) && !empty($article['Image'])) {
        return $article['Image'];
    }
    
    // Return default fallback image
    return $fallback ?: 'default-article-image.webp';
}

/**
 * Generate responsive image HTML with srcset for different sizes
 * @param array $article Article data from database
 * @param string $alt Alt text for the image
 * @param string $class CSS classes
 * @param string $fallback Fallback image
 * @return string HTML img tag with srcset
 */
function getResponsiveImage($article, $alt = '', $class = '', $fallback = null) {
    $basePath = 'images/uploaded/';
    
    // Get different image sizes
    $large = getImageBySize($article, 'large', $fallback);
    $medium = getImageBySize($article, 'medium', $fallback);
    $small = getImageBySize($article, 'small', $fallback);
    
    // Build srcset
    $srcset = '';
    if ($large && $large !== $fallback) {
        $srcset .= $basePath . $large . ' 1200w, ';
    }
    if ($medium && $medium !== $fallback) {
        $srcset .= $basePath . $medium . ' 800w, ';
    }
    if ($small && $small !== $fallback) {
        $srcset .= $basePath . $small . ' 400w';
    }
    
    // Remove trailing comma and space
    $srcset = rtrim($srcset, ', ');
    
    // Get default image
    $defaultImage = getImageBySize($article, 'medium', $fallback);
    
    $html = '<img src="' . $basePath . $defaultImage . '"';
    
    if (!empty($srcset)) {
        $html .= ' srcset="' . $srcset . '"';
    }
    
    if (!empty($alt)) {
        $html .= ' alt="' . htmlspecialchars($alt) . '"';
    }
    
    if (!empty($class)) {
        $html .= ' class="' . htmlspecialchars($class) . '"';
    }
    
    $html .= ' loading="lazy" decoding="async">';
    
    return $html;
}

/**
 * Get image dimensions for a specific size
 * @param string $size Size identifier (large, medium, small, thumbnail)
 * @return array Array with width and height
 */
function getImageDimensions($size) {
    $dimensions = [
        'large' => ['width' => 1200, 'height' => 800],
        'medium' => ['width' => 800, 'height' => 600],
        'small' => ['width' => 400, 'height' => 300],
        'thumbnail' => ['width' => 150, 'height' => 150]
    ];
    
    return isset($dimensions[$size]) ? $dimensions[$size] : $dimensions['medium'];
}

/**
 * Check if WebP is supported by the browser
 * @return bool True if WebP is supported
 */
function isWebPSupported() {
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }
    return false;
}

/**
 * Get the best image format for the current browser
 * @param array $article Article data
 * @param string $size Image size
 * @return string Image filename with appropriate extension
 */
function getBestImageFormat($article, $size = 'medium') {
    $imageField = 'image_' . $size;
    
    // If WebP is supported and available, use it
    if (isWebPSupported() && isset($article[$imageField]) && !empty($article[$imageField])) {
        return $article[$imageField];
    }
    
    // Fallback to original image format
    if (isset($article['Image']) && !empty($article['Image'])) {
        return $article['Image'];
    }
    
    return 'default-article-image.jpg';
}
?>
