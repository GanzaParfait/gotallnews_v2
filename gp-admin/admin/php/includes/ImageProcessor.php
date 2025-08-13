<?php
require_once 'image_config.php';

class ImageProcessor {
    private $uploadDir;
    private $quality;
    
    public function __construct($uploadDir = 'images/uploaded/', $quality = null) {
        $this->uploadDir = $uploadDir;
        $this->quality = $quality ?: IMAGE_QUALITY_MEDIUM;
        
        // Set memory and time limits for image processing
        ini_set('memory_limit', MEMORY_LIMIT);
        set_time_limit(MAX_EXECUTION_TIME);
    }
    
    /**
     * Process uploaded image: convert to WebP, resize, and compress
     * @param string $tmpName Temporary file path
     * @param string $fileName Original filename
     * @param string $time Timestamp for unique naming
     * @return array Array containing all generated image paths
     */
    public function processImage($tmpName, $fileName, $time) {
        $result = [];
        
        // Get image info
        $imageInfo = getimagesize($tmpName);
        if (!$imageInfo) {
            throw new Exception("Invalid image file");
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        $sourceImage = $this->createImageResource($tmpName, $mimeType);
        if (!$sourceImage) {
            throw new Exception("Failed to create image resource");
        }
        
        // Use configured sizes
        $sizes = IMAGE_SIZES;
        
        // Generate base filename without extension
        $baseFileName = $time . '_gpnews_' . pathinfo($fileName, PATHINFO_FILENAME);
        
        // Process each size
        foreach ($sizes as $size => $config) {
            $resizedImage = $this->resizeImage($sourceImage, $width, $height, $config['width'], $config['height']);
            
            // Generate filename for this size
            $sizeFileName = $baseFileName . '_' . $size . '.' . OUTPUT_FORMAT;
            $filePath = $this->uploadDir . $sizeFileName;
            
            // Save as WebP with size-specific quality
            if (imagewebp($resizedImage, $filePath, $config['quality'])) {
                $result[$size] = $sizeFileName;
                
                // Log success if logging is enabled
                if (LOG_ERRORS) {
                    error_log("Successfully processed image: $sizeFileName (Quality: {$config['quality']})");
                }
            }
            
            // Free memory
            imagedestroy($resizedImage);
        }
        
        // Free source image
        imagedestroy($sourceImage);
        
        return $result;
    }
    
    /**
     * Create image resource from uploaded file
     */
    private function createImageResource($tmpName, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($tmpName);
            case 'image/png':
                return imagecreatefrompng($tmpName);
            case 'image/gif':
                return imagecreatefromgif($tmpName);
            case 'image/webp':
                return imagecreatefromwebp($tmpName);
            default:
                return false;
        }
    }
    
    /**
     * Resize image maintaining aspect ratio
     */
    private function resizeImage($sourceImage, $sourceWidth, $sourceHeight, $targetWidth, $targetHeight) {
        // Calculate aspect ratios
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;
        
        // Calculate new dimensions maintaining aspect ratio
        if ($sourceRatio > $targetRatio) {
            $newWidth = $targetWidth;
            $newHeight = $targetWidth / $sourceRatio;
        } else {
            $newHeight = $targetHeight;
            $newWidth = $targetHeight * $sourceRatio;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        
        // Resize
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        return $newImage;
    }
    
    /**
     * Get optimal quality based on file size
     */
    public function getOptimalQuality($originalSize) {
        if ($originalSize < 500000) { // Less than 500KB
            return 85;
        } elseif ($originalSize < 2000000) { // Less than 2MB
            return 80;
        } else {
            return 75;
        }
    }
    
    /**
     * Clean up old image files
     */
    public function cleanupOldImages($imageArray) {
        foreach ($imageArray as $size => $filename) {
            $filePath = $this->uploadDir . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
?>
