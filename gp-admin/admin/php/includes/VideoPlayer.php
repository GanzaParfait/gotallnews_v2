<?php
/**
 * Video Player Class
 * Handles video display, embedding, and player functionality
 */

class VideoPlayer {
    private $video;
    private $autoplay = false;
    private $controls = true;
    private $loop = false;
    private $muted = false;
    private $width = '100%';
    private $height = 'auto';
    private $responsive = true;
    
    public function __construct($video, $options = []) {
        $this->video = $video;
        
        // Set options
        if (isset($options['autoplay'])) $this->autoplay = $options['autoplay'];
        if (isset($options['controls'])) $this->controls = $options['controls'];
        if (isset($options['loop'])) $this->loop = $options['loop'];
        if (isset($options['muted'])) $this->muted = $options['muted'];
        if (isset($options['width'])) $this->width = $options['width'];
        if (isset($options['height'])) $this->height = $options['height'];
        if (isset($options['responsive'])) $this->responsive = $options['responsive'];
    }
    
    /**
     * Render the video player
     */
    public function render() {
        if ($this->video['VideoFormat'] === 'embed') {
            return $this->renderEmbedPlayer();
        } else {
            return $this->renderHTML5Player();
        }
    }
    
    /**
     * Render HTML5 video player
     */
    private function renderHTML5Player() {
        $autoplay = $this->autoplay ? 'autoplay' : '';
        $controls = $this->controls ? 'controls' : '';
        $loop = $this->loop ? 'loop' : '';
        $muted = $this->muted ? 'muted' : '';
        
        $style = "width: {$this->width}; height: {$this->height};";
        if ($this->responsive) {
            $style .= " max-width: 100%; height: auto;";
        }
        
        $html = "<div class='video-player-container'>";
        $html .= "<video class='video-player' style='{$style}' {$autoplay} {$controls} {$loop} {$muted}>";
        
        // Add video source
        $html .= "<source src='" . htmlspecialchars($this->video['VideoFile']) . "' type='video/" . htmlspecialchars($this->video['VideoFormat']) . "'>";
        
        // Fallback message
        $html .= "Your browser does not support the video tag.";
        $html .= "</video>";
        
        // Add custom controls if needed
        if (!$this->controls) {
            $html .= $this->renderCustomControls();
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Render embedded video player
     */
    private function renderEmbedPlayer() {
        $embedCode = $this->video['EmbedCode'];
        
        // Process YouTube URLs
        if ($this->video['EmbedSource'] === 'youtube') {
            $embedCode = $this->processYouTubeEmbed($embedCode);
        }
        
        // Process Vimeo URLs
        if ($this->video['EmbedSource'] === 'vimeo') {
            $embedCode = $this->processVimeoEmbed($embedCode);
        }
        
        $style = "width: {$this->width}; height: {$this->height};";
        if ($this->responsive) {
            $style .= " max-width: 100%;";
        }
        
        $html = "<div class='video-player-container' style='{$style}'>";
        $html .= "<div class='embed-responsive embed-responsive-16by9'>";
        $html .= $embedCode;
        $html .= "</div>";
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Process YouTube URLs to embed format
     */
    private function processYouTubeEmbed($url) {
        // Extract video ID from various YouTube URL formats
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $videoId = $matches[1];
                $autoplay = $this->autoplay ? '1' : '0';
                $controls = $this->controls ? '1' : '0';
                $loop = $this->loop ? '1' : '0';
                $muted = $this->muted ? '1' : '0';
                
                return "<iframe src='https://www.youtube.com/embed/{$videoId}?autoplay={$autoplay}&controls={$controls}&loop={$loop}&mute={$muted}' 
                        frameborder='0' allowfullscreen style='width: 100%; height: 100%;'></iframe>";
            }
        }
        
        // If no pattern matches, return original embed code
        return $embedCode;
    }
    
    /**
     * Process Vimeo URLs to embed format
     */
    private function processVimeoEmbed($url) {
        // Extract video ID from Vimeo URL
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $videoId = $matches[1];
            $autoplay = $this->autoplay ? '1' : '0';
            $controls = $this->controls ? '1' : '0';
            $loop = $this->loop ? '1' : '0';
            $muted = $this->muted ? '1' : '0';
            
            return "<iframe src='https://player.vimeo.com/video/{$videoId}?autoplay={$autoplay}&controls={$controls}&loop={$loop}&muted={$muted}' 
                    frameborder='0' allowfullscreen style='width: 100%; height: 100%;'></iframe>";
        }
        
        // If no pattern matches, return original embed code
        return $embedCode;
    }
    
    /**
     * Render custom video controls
     */
    private function renderCustomControls() {
        $html = "<div class='custom-video-controls'>";
        $html .= "<button class='btn btn-sm btn-primary play-pause-btn' onclick='togglePlayPause(this)'>";
        $html .= "<i class='fa fa-play'></i>";
        $html .= "</button>";
        $html .= "<input type='range' class='video-progress' min='0' max='100' value='0' onchange='seekVideo(this.value)'>";
        $html .= "<span class='video-time'>00:00 / 00:00</span>";
        $html .= "<button class='btn btn-sm btn-secondary mute-btn' onclick='toggleMute(this)'>";
        $html .= "<i class='fa fa-volume-up'></i>";
        $html .= "</button>";
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Get video thumbnail
     */
    public function getThumbnail() {
        if (!empty($this->video['VideoThumbnail'])) {
            return $this->video['VideoThumbnail'];
        }
        
        // Generate thumbnail for embedded videos
        if ($this->video['VideoFormat'] === 'embed') {
            return $this->generateEmbedThumbnail();
        }
        
        return 'php/defaultavatar/video-thumbnail.png';
    }
    
    /**
     * Generate thumbnail for embedded videos
     */
    private function generateEmbedThumbnail() {
        if ($this->video['EmbedSource'] === 'youtube') {
            // Extract YouTube video ID and generate thumbnail URL
            if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $this->video['EmbedCode'], $matches)) {
                $videoId = $matches[1];
                return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
            }
        }
        
        if ($this->video['EmbedSource'] === 'vimeo') {
            // For Vimeo, we would need to use their API to get thumbnails
            // For now, return default
            return 'php/defaultavatar/video-thumbnail.png';
        }
        
        return 'php/defaultavatar/video-thumbnail.png';
    }
    
    /**
     * Get video duration in formatted string
     */
    public function getFormattedDuration() {
        $duration = $this->video['VideoDuration'];
        
        if ($duration <= 0) {
            return 'Unknown';
        }
        
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }
    
    /**
     * Get video file size in formatted string
     */
    public function getFormattedFileSize() {
        $size = $this->video['VideoSize'];
        
        if ($size <= 0) {
            return 'Unknown';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Render video info card
     */
    public function renderInfoCard() {
        $html = "<div class='video-info-card'>";
        $html .= "<h4>" . htmlspecialchars($this->video['Title']) . "</h4>";
        
        if (!empty($this->video['Excerpt'])) {
            $html .= "<p class='video-excerpt'>" . htmlspecialchars($this->video['Excerpt']) . "</p>";
        }
        
        $html .= "<div class='video-meta'>";
        $html .= "<span class='video-duration'><i class='fa fa-clock-o'></i> " . $this->getFormattedDuration() . "</span>";
        
        if ($this->video['VideoFormat'] !== 'embed') {
            $html .= "<span class='video-size'><i class='fa fa-file-video-o'></i> " . $this->getFormattedFileSize() . "</span>";
        }
        
        $html .= "<span class='video-resolution'><i class='fa fa-expand'></i> " . htmlspecialchars($this->video['VideoResolution']) . "</span>";
        $html .= "<span class='video-views'><i class='fa fa-eye'></i> " . number_format($this->video['Views']) . " views</span>";
        $html .= "</div>";
        
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Render video with info
     */
    public function renderWithInfo() {
        $html = "<div class='video-with-info'>";
        $html .= $this->render();
        $html .= $this->renderInfoCard();
        $html .= "</div>";
        
        return $html;
    }
}
?>
