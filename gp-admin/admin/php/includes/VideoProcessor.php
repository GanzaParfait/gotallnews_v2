<?php

class VideoProcessor {
    private $uploadDir;
    private $ffmpegPath;
    private $quality;
    
    public function __construct($uploadDir = 'uploads/videos/', $quality = 80) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->quality = $quality;
        
        // Check if FFmpeg is available
        $this->ffmpegPath = $this->findFFmpeg();
        
        // Create directories if they don't exist
        $this->createDirectories();
    }
    
    /**
     * Find FFmpeg executable path
     */
    private function findFFmpeg() {
        // Common FFmpeg paths
        $possiblePaths = [
            'ffmpeg', // If in PATH
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'C:/ffmpeg/bin/ffmpeg.exe', // Windows
            'C:/xampp/ffmpeg/bin/ffmpeg.exe', // XAMPP Windows
            'C:/Program Files/ffmpeg/bin/ffmpeg.exe', // Program Files
            'C:/Program Files (x86)/ffmpeg/bin/ffmpeg.exe', // Program Files (x86)
            'C:/Users/' . get_current_user() . '/ffmpeg/bin/ffmpeg.exe', // User directory
            'C:/Users/' . get_current_user() . '/Downloads/ffmpeg/bin/ffmpeg.exe', // Downloads
            'C:/Users/' . get_current_user() . '/Desktop/ffmpeg/bin/ffmpeg.exe' // Desktop
        ];
        
        // Try to get FFmpeg from PATH first
        if ($this->isFFmpegAvailable('ffmpeg')) {
            return 'ffmpeg';
        }
        
        // Try specific paths
        foreach ($possiblePaths as $path) {
            if ($this->isFFmpegAvailable($path)) {
                return $path;
            }
        }
        
        // Try to find FFmpeg in PATH using where command (Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            exec('where ffmpeg 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                $ffmpegPath = trim($output[0]);
                if ($this->isFFmpegAvailable($ffmpegPath)) {
                    return $ffmpegPath;
                }
            }
        }
        
        throw new Exception('FFmpeg not found. Please install FFmpeg to enable video processing. Checked paths: ' . implode(', ', $possiblePaths));
    }
    
    /**
     * Check if FFmpeg is available at given path
     */
    private function isFFmpegAvailable($path) {
        $output = [];
        $returnCode = 0;
        
        // Use different commands for Windows vs Unix
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: use quotes and redirect stderr
            $command = "\"$path\" -version 2>&1";
        } else {
            // Unix: use single quotes
            $command = "'$path' -version 2>&1";
        }
        
        exec($command, $output, $returnCode);
        
        // Check if we got any output (even error output means the executable exists)
        if ($returnCode === 0 || !empty($output)) {
            // Verify it's actually FFmpeg by checking the output
            foreach ($output as $line) {
                if (strpos($line, 'ffmpeg version') !== false || strpos($line, 'FFmpeg') !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        $dirs = [
            $this->uploadDir,
            $this->uploadDir . 'original/',
            $this->uploadDir . 'compressed/',
            $this->uploadDir . 'thumbnails/'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create directory: $dir");
                }
            }
        }
    }
    
    /**
     * Process uploaded video: compress and generate multiple versions
     */
    public function processVideo($tmpPath, $originalName, $timestamp) {
        try {
            // Get video information
            $videoInfo = $this->getVideoInfo($tmpPath);
            
            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = $baseName . '_' . $timestamp;
            
            // Move original to original directory
            $originalPath = $this->uploadDir . 'original/' . $uniqueName . '.' . $extension;
            if (!move_uploaded_file($tmpPath, $originalPath)) {
                throw new Exception('Failed to move uploaded file');
            }
            
            // Generate compressed versions
            $compressedVersions = $this->generateCompressedVersions($originalPath, $uniqueName, $videoInfo);
            
            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($originalPath, $uniqueName);
            
            return [
                'original' => $originalPath,
                'compressed' => $compressedVersions,
                'thumbnail' => $thumbnailPath,
                'info' => $videoInfo
            ];
            
        } catch (Exception $e) {
            // Clean up on failure
            $this->cleanupOnFailure($originalPath ?? null);
            throw $e;
        }
    }
    
    /**
     * Get video information using FFmpeg
     */
    private function getVideoInfo($videoPath) {
        $output = [];
        $returnCode = 0;
        
        $command = "{$this->ffmpegPath} -i \"$videoPath\" 2>&1";
        exec($command, $output, $returnCode);
        
        $info = [
            'duration' => 0,
            'width' => 0,
            'height' => 0,
            'fps' => 0,
            'bitrate' => 0,
            'size' => filesize($videoPath)
        ];
        
        // Parse FFmpeg output for video information
        foreach ($output as $line) {
            if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2}\.\d{2})/', $line, $matches)) {
                $info['duration'] = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
            }
            
            if (preg_match('/(\d{3,4})x(\d{3,4})/', $line, $matches)) {
                $info['width'] = (int)$matches[1];
                $info['height'] = (int)$matches[2];
            }
            
            if (preg_match('/(\d+(?:\.\d+)?) fps/', $line, $matches)) {
                $info['fps'] = (float)$matches[1];
            }
            
            if (preg_match('/(\d+) kb\/s/', $line, $matches)) {
                $info['bitrate'] = (int)$matches[1];
            }
        }
        
        return $info;
    }
    
    /**
     * Generate compressed video versions
     */
    private function generateCompressedVersions($originalPath, $uniqueName, $videoInfo) {
        $versions = [];
        
        // High quality (for desktop)
        $versions['high'] = $this->compressVideo($originalPath, $uniqueName, 'high', [
            'width' => min(1920, $videoInfo['width']),
            'height' => min(1080, $videoInfo['height']),
            'bitrate' => '2000k',
            'crf' => 23
        ]);
        
        // Medium quality (for tablets)
        $versions['medium'] = $this->compressVideo($originalPath, $uniqueName, 'medium', [
            'width' => min(1280, $videoInfo['width']),
            'bitrate' => '1000k',
            'crf' => 25
        ]);
        
        // Low quality (for mobile)
        $versions['low'] = $this->compressVideo($originalPath, $uniqueName, 'low', [
            'width' => min(854, $videoInfo['width']),
            'height' => min(480, $videoInfo['height']),
            'bitrate' => '500k',
            'crf' => 28
        ]);
        
        return $versions;
    }
    
    /**
     * Compress video to specific quality settings
     */
    private function compressVideo($inputPath, $uniqueName, $quality, $settings) {
        $outputPath = $this->uploadDir . 'compressed/' . $uniqueName . '_' . $quality . '.mp4';
        
        $command = "{$this->ffmpegPath} -i \"$inputPath\" " .
                   "-vf scale={$settings['width']}:{$settings['height']} " .
                   "-c:v libx264 " .
                   "-preset medium " .
                   "-crf {$settings['crf']} " .
                   "-c:a aac " .
                   "-b:a 128k " .
                   "-movflags +faststart " .
                   "-y \"$outputPath\" 2>&1";
        
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Video compression failed for $quality quality: " . implode("\n", $output));
        }
        
        return $outputPath;
    }
    
    /**
     * Generate thumbnail from video
     */
    private function generateThumbnail($videoPath, $uniqueName) {
        $thumbnailPath = $this->uploadDir . 'thumbnails/' . $uniqueName . '_thumb.jpg';
        
        // Extract frame at 1 second mark
        $command = "{$this->ffmpegPath} -i \"$videoPath\" " .
                   "-ss 00:00:01 " .
                   "-vframes 1 " .
                   "-q:v 2 " .
                   "-y \"$thumbnailPath\" 2>&1";
        
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            // Fallback: try at 0 seconds
            $command = "{$this->ffmpegPath} -i \"$videoPath\" " .
                       "-ss 00:00:00 " .
                       "-vframes 1 " .
                       "-q:v 2 " .
                       "-y \"$thumbnailPath\" 2>&1";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Thumbnail generation failed: " . implode("\n", $output));
            }
        }
        
        return $thumbnailPath;
    }
    
    /**
     * Clean up files on processing failure
     */
    private function cleanupOnFailure($originalPath) {
        if ($originalPath && file_exists($originalPath)) {
            unlink($originalPath);
        }
    }
    
    /**
     * Clean up old processed files
     */
    public function cleanupOldFiles($filePaths) {
        foreach ($filePaths as $path) {
            if (is_array($path)) {
                $this->cleanupOldFiles($path);
            } elseif (file_exists($path)) {
                unlink($path);
            }
        }
    }
    
    /**
     * Get file size in human readable format
     */
    public function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get compression statistics
     */
    public function getCompressionStats($originalSize, $compressedSizes) {
        $totalCompressed = array_sum($compressedSizes);
        $savings = $originalSize - $totalCompressed;
        $percentage = ($savings / $originalSize) * 100;
        
        return [
            'original_size' => $this->formatFileSize($originalSize),
            'compressed_size' => $this->formatFileSize($totalCompressed),
            'savings' => $this->formatFileSize($savings),
            'percentage' => round($percentage, 1)
        ];
    }
}
