<?php
/**
 * Video Manager Class
 * Handles all video post operations including CRUD, scheduling, drafts, and media management
 */

class VideoManager {
    private $con;
    private $uploadDir;
    private $allowedVideoTypes;
    private $maxVideoSize;
    private $thumbnailDir;
    
    public function __construct($con, $uploadDir = 'videos/', $thumbnailDir = 'videos/thumbnails/') {
        $this->con = $con;
        $this->uploadDir = $uploadDir;
        $this->thumbnailDir = $thumbnailDir;
        $this->allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'];
        $this->maxVideoSize = 500 * 1024 * 1024; // 500MB
        
        // Create upload directories if they don't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0755, true);
        }
        
        // Check if required tables exist
        if (!$this->checkRequiredTables()) {
            throw new Exception("Required database tables for video management do not exist. Please run the installation script first.");
        }
    }
    
    /**
     * Check if required database tables exist
     */
    private function checkRequiredTables() {
        $requiredTables = [
            'video_posts',
            'video_categories',
            'video_tags',
            'video_tag_relationships'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $this->con->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Create a new video post
     */
    public function createVideo($authorId, $data) {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['slug'])) {
                throw new Exception("Title and slug are required");
            }
            
            // Check if slug already exists
            if ($this->slugExists($data['slug'])) {
                throw new Exception("Slug already exists");
            }
            
            // Process video file if uploaded
            $videoFile = '';
            $videoThumbnail = '';
            $videoDuration = 0;
            $videoSize = 0;
            $videoFormat = 'mp4';
            $videoResolution = '1920x1080';
            
            if (!empty($_FILES['videoFile']['name'])) {
                $videoInfo = $this->processVideoUpload($_FILES['videoFile']);
                // Ensure videoInfo is an array and has all required keys
                if (is_array($videoInfo) && isset($videoInfo['filepath'])) {
                    $videoFile = $videoInfo['filepath'];
                    $videoThumbnail = $videoInfo['thumbnail'] ?? '';
                    $videoDuration = $videoInfo['duration'] ?? 0;
                    $videoSize = $videoInfo['size'] ?? 0;
                    $videoFormat = $videoInfo['format'] ?? 'mp4';
                    $videoResolution = $videoInfo['resolution'] ?? '1920x1080';
                } else {
                    // Fallback values if processVideoUpload fails
                    $videoFile = '';
                    $videoThumbnail = '';
                    $videoDuration = 0;
                    $videoSize = 0;
                    $videoFormat = 'mp4';
                    $videoResolution = '1920x1080';
                    error_log("Video Upload Warning: processVideoUpload returned invalid data structure");
                }
            } elseif (!empty($data['embedCode'])) {
                // Handle embed videos
                $videoFile = '';
                $videoThumbnail = $data['videoThumbnail'] ?? '';
                $videoDuration = $data['videoDuration'] ?? 0;
                $videoSize = 0;
                $videoFormat = 'embed';
                $videoResolution = $data['videoResolution'] ?? '1920x1080';
                
                // Process embed code to extract video ID and source
                $embedData = $this->processEmbedCode($data['embedCode']);
                if ($embedData) {
                    $data['embedSource'] = $embedData['source'];
                    $data['embedVideoID'] = $embedData['videoId'];
                }
            }
            
            // Prepare publish date
            $publishDate = null;
            if ($data['status'] === 'scheduled' && !empty($data['publishDate'])) {
                $publishDate = $data['publishDate'];
            } elseif ($data['status'] === 'published') {
                $publishDate = date('Y-m-d H:i:s');
            }
            
            $sql = "INSERT INTO video_posts (
                Title, Slug, Excerpt, Description, VideoFile, VideoThumbnail,
                VideoDuration, VideoSize, VideoFormat, VideoResolution,
                EmbedCode, EmbedSource, EmbedVideoID, CategoryID, Tags,
                AuthorID, Status, PublishDate, Featured, AllowComments,
                MetaTitle, MetaDescription, MetaKeywords
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->con->prepare($sql);
            // Ensure all variables are properly defined and not null
            $title = $data['title'];
            $slug = $data['slug'];
            $excerpt = $data['excerpt'] ?? '';
            $description = $data['description'] ?? '';
            $embedCode = $data['embedCode'] ?? '';
            $embedSource = $data['embedSource'] ?? '';
            $embedVideoID = $data['embedVideoID'] ?? '';
            $categoryID = $data['categoryID'] ?? '';
            $tags = $data['tags'] ?? '';
            $status = $data['status'];
            $featured = $data['featured'] ?? 0;
            $allowComments = $data['allowComments'] ?? 1;
            $metaTitle = $data['metaTitle'] ?? $data['title'];
            $metaDescription = $data['metaDescription'] ?? $data['excerpt'] ?? '';
            $metaKeywords = $data['metaKeywords'] ?? $data['tags'] ?? '';
            
            // Debug logging to identify any issues
            error_log("Video Creation Debug - Title: $title, Slug: $slug, Status: $status, CategoryID: $categoryID");
            error_log("Video Creation Debug - VideoFile: $videoFile, Thumbnail: $videoThumbnail, Duration: $videoDuration, Size: $videoSize");
            
            // Final safety check - ensure all variables are proper types
            $videoDuration = (int)$videoDuration;
            $videoSize = (int)$videoSize;
            $featured = (int)$featured;
            $allowComments = (int)$allowComments;
            $categoryID = (string)$categoryID; // Convert null to empty string
            
            $stmt->bind_param("ssssssissssssssssssssss", 
                $title,
                $slug,
                $excerpt,
                $description,
                $videoFile,
                $videoThumbnail,
                $videoDuration,
                $videoSize,
                $videoFormat,
                $videoResolution,
                $embedCode,
                $embedSource,
                $embedVideoID,
                $categoryID,
                $tags,
                $authorId,
                $status,
                $publishDate,
                $featured,
                $allowComments,
                $metaTitle,
                $metaDescription,
                $metaKeywords
            );
            
            if ($stmt->execute()) {
                $videoId = $stmt->insert_id;
                
                // Handle tags if provided
                if (!empty($data['tags'])) {
                    $this->processTags($videoId, $data['tags']);
                }
                
                // Update published_at if status is published
                if ($data['status'] === 'published') {
                    $this->con->query("UPDATE video_posts SET Published_at = NOW() WHERE VideoID = $videoId");
                }
                
                return $videoId;
            } else {
                throw new Exception("Failed to create video post: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Video Creation Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update an existing video post
     */
    public function updateVideo($videoId, $data) {
        try {
            // Check if video exists
            $existingVideo = $this->getVideo($videoId);
            if (!$existingVideo) {
                throw new Exception("Video not found");
            }
            
            // Check if slug already exists (excluding current video)
            if (!empty($data['slug']) && $data['slug'] !== $existingVideo['Slug'] && $this->slugExists($data['slug'])) {
                throw new Exception("Slug already exists");
            }
            
            // Process video file if uploaded
            $videoFile = $existingVideo['VideoFile'];
            $videoThumbnail = $existingVideo['VideoThumbnail'];
            $videoDuration = $existingVideo['VideoDuration'];
            $videoSize = $existingVideo['VideoSize'];
            $videoFormat = $existingVideo['VideoFormat'];
            $videoResolution = $existingVideo['VideoResolution'];
            
            if (!empty($_FILES['videoFile']['name'])) {
                $videoInfo = $this->processVideoUpload($_FILES['videoFile']);
                $videoFile = $videoInfo['filepath'];
                $videoThumbnail = $videoInfo['thumbnail'];
                $videoDuration = $videoInfo['duration'];
                $videoSize = $videoInfo['size'];
                $videoFormat = $videoInfo['format'];
                $videoResolution = $videoInfo['resolution'];
                
                // Delete old video file if it exists
                if (!empty($existingVideo['VideoFile']) && file_exists($existingVideo['VideoFile'])) {
                    unlink($existingVideo['VideoFile']);
                }
            } elseif (!empty($data['embedCode'])) {
                // Handle embed videos
                $videoFile = '';
                $videoThumbnail = $data['videoThumbnail'] ?? $existingVideo['VideoThumbnail'];
                $videoDuration = $data['videoDuration'] ?? $existingVideo['VideoDuration'];
                $videoSize = 0;
                $videoFormat = 'embed';
                $videoResolution = $data['videoResolution'] ?? $existingVideo['VideoResolution'];
            }
            
            // Prepare publish date
            $publishDate = $existingVideo['PublishDate'];
            if ($data['status'] === 'scheduled' && !empty($data['publishDate'])) {
                $publishDate = $data['publishDate'];
            } elseif ($data['status'] === 'published' && $existingVideo['Status'] !== 'published') {
                $publishDate = date('Y-m-d H:i:s');
            }
            
            $sql = "UPDATE video_posts SET 
                Title = ?, Slug = ?, Excerpt = ?, Description = ?, VideoFile = ?, VideoThumbnail = ?,
                VideoDuration = ?, VideoSize = ?, VideoFormat = ?, VideoResolution = ?,
                EmbedCode = ?, EmbedSource = ?, EmbedVideoID = ?, CategoryID = ?, Tags = ?,
                Status = ?, PublishDate = ?, Featured = ?, AllowComments = ?,
                MetaTitle = ?, MetaDescription = ?, MetaKeywords = ?,
                Updated_at = CURRENT_TIMESTAMP
                WHERE VideoID = ?";
            
            // Ensure all variables are properly defined and not null
            $title = $data['title'];
            $slug = $data['slug'];
            $excerpt = $data['excerpt'] ?? '';
            $description = $data['description'] ?? '';
            $embedCode = $data['embedCode'] ?? '';
            $embedSource = $data['embedSource'] ?? '';
            $embedVideoID = $data['embedVideoID'] ?? '';
            $categoryID = $data['categoryID'] ?? '';
            $tags = $data['tags'] ?? '';
            $status = $data['status'];
            $featured = $data['featured'] ?? 0;
            $allowComments = $data['allowComments'] ?? 1;
            $metaTitle = $data['metaTitle'] ?? $data['title'];
            $metaDescription = $data['metaDescription'] ?? $data['excerpt'] ?? '';
            $metaKeywords = $data['metaKeywords'] ?? $data['tags'] ?? '';
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ssssssisssssssssssssssi", 
                $title,
                $slug,
                $excerpt,
                $description,
                $videoFile,
                $videoThumbnail,
                $videoDuration,
                $videoSize,
                $videoFormat,
                $videoResolution,
                $embedCode,
                $embedSource,
                $embedVideoID,
                $categoryID,
                $tags,
                $status,
                $publishDate,
                $featured,
                $allowComments,
                $metaTitle,
                $metaDescription,
                $metaKeywords,
                $videoId
            );
            
            if ($stmt->execute()) {
                // Handle tags if provided
                if (isset($data['tags'])) {
                    $this->processTags($videoId, $data['tags']);
                }
                
                // Update published_at if status changed to published
                if ($data['status'] === 'published' && $existingVideo['Status'] !== 'published') {
                    $this->con->query("UPDATE video_posts SET Published_at = NOW() WHERE VideoID = $videoId");
                }
                
                return true;
            } else {
                throw new Exception("Failed to update video post: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Video Update Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get video by ID
     */
    public function getVideo($videoId) {
        try {
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE v.VideoID = ? AND v.isDeleted = 'notDeleted'";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $video = $result->fetch_assoc();
                
                // Get tags
                $video['tags'] = $this->getVideoTags($videoId);
                
                // Get comments count
                $video['commentsCount'] = $this->getCommentsCount($videoId);
                
                return $video;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get Video Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all videos with pagination and filters
     */
    public function getAllVideos($page = 1, $limit = 20, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = ["v.isDeleted = 'notDeleted'"];
            $params = [];
            $paramTypes = "";
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereConditions[] = "v.Status = ?";
                $params[] = $filters['status'];
                $paramTypes .= "s";
            }
            
            if (!empty($filters['category'])) {
                $whereConditions[] = "v.CategoryID = ?";
                $params[] = $filters['category'];
                $paramTypes .= "i";
            }
            
            if (!empty($filters['author'])) {
                $whereConditions[] = "v.AuthorID = ?";
                $params[] = $filters['author'];
                $paramTypes .= "i";
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(v.Title LIKE ? OR v.Description LIKE ? OR v.Tags LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $paramTypes .= "sss";
            }
            
            if (!empty($filters['dateFrom'])) {
                $whereConditions[] = "v.Created_at >= ?";
                $params[] = $filters['dateFrom'];
                $paramTypes .= "s";
            }
            
            if (!empty($filters['dateTo'])) {
                $whereConditions[] = "v.Created_at <= ?";
                $params[] = $filters['dateTo'];
                $paramTypes .= "s";
            }
            
            $whereClause = implode(" AND ", $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM video_posts v WHERE $whereClause";
            $countStmt = $this->con->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($paramTypes, ...$params);
            }
            $countStmt->execute();
            $totalResult = $countStmt->get_result();
            $total = $totalResult->fetch_assoc()['total'];
            
            // Get videos
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE $whereClause 
                    ORDER BY v.Created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $paramTypes .= "ii";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param($paramTypes, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $row['tags'] = $this->getVideoTags($row['VideoID']);
                $row['commentsCount'] = $this->getCommentsCount($row['VideoID']);
                $videos[] = $row;
            }
            
            return [
                'videos' => $videos,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log("Get All Videos Error: " . $e->getMessage());
            return ['videos' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Delete video (soft delete)
     */
    public function deleteVideo($videoId) {
        try {
            $sql = "UPDATE video_posts SET isDeleted = 'deleted', Updated_at = CURRENT_TIMESTAMP WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Delete Video Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restore deleted video
     */
    public function restoreVideo($videoId) {
        try {
            $sql = "UPDATE video_posts SET isDeleted = 'notDeleted', Updated_at = CURRENT_TIMESTAMP WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Restore Video Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get video categories
     */
    public function getCategories() {
        try {
            $sql = "SELECT * FROM video_categories WHERE isActive = 1 AND isDeleted = 'notDeleted' ORDER BY SortOrder, CategoryName";
            $result = $this->con->query($sql);
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            return $categories;
        } catch (Exception $e) {
            error_log("Get Categories Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get video tags
     */
    public function getTags() {
        try {
            $sql = "SELECT * FROM video_tags WHERE isActive = 1 AND isDeleted = 'notDeleted' ORDER BY TagName";
            $result = $this->con->query($sql);
            
            $tags = [];
            while ($row = $result->fetch_assoc()) {
                $tags[] = $row;
            }
            
            return $tags;
        } catch (Exception $e) {
            error_log("Get Tags Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process embed code to extract video ID and source
     */
    private function processEmbedCode($embedCode) {
        try {
            // YouTube embed code processing
            if (strpos($embedCode, 'youtube.com') !== false || strpos($embedCode, 'youtu.be') !== false) {
                $videoId = '';
                
                // Extract video ID from various YouTube URL formats
                if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
                    $videoId = $matches[1];
                }
                
                if ($videoId) {
                    return [
                        'source' => 'youtube',
                        'videoId' => $videoId
                    ];
                }
            }
            
            // Vimeo embed code processing
            if (strpos($embedCode, 'vimeo.com') !== false) {
                if (preg_match('/vimeo\.com\/(\d+)/', $embedCode, $matches)) {
                    return [
                        'source' => 'vimeo',
                        'videoId' => $matches[1]
                    ];
                }
            }
            
            // Generic iframe embed processing
            if (strpos($embedCode, '<iframe') !== false) {
                // Extract src attribute
                if (preg_match('/src=["\']([^"\']+)["\']/', $embedCode, $matches)) {
                    $src = $matches[1];
                    
                    // Check if it's a YouTube or Vimeo iframe
                    if (strpos($src, 'youtube.com') !== false) {
                        if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $src, $matches)) {
                            return [
                                'source' => 'youtube',
                                'videoId' => $matches[1]
                            ];
                        }
                    } elseif (strpos($src, 'vimeo.com') !== false) {
                        if (preg_match('/video\/(\d+)/', $src, $matches)) {
                            return [
                                'source' => 'vimeo',
                                'videoId' => $matches[1]
                            ];
                        }
                    }
                    
                    // Generic iframe
                    return [
                        'source' => 'iframe',
                        'videoId' => md5($src)
                    ];
                }
            }
            
            // If no specific format detected, treat as generic embed
            return [
                'source' => 'generic',
                'videoId' => md5($embedCode)
            ];
            
        } catch (Exception $e) {
            error_log("Embed Code Processing Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process video file upload
     */
    private function processVideoUpload($file) {
        try {
            // Check file size
            if ($file['size'] > $this->maxVideoSize) {
                throw new Exception("File size must be less than " . ($this->maxVideoSize / (1024 * 1024)) . "MB");
            }
            
            // Check file type
            if (!in_array($file['type'], $this->allowedVideoTypes)) {
                throw new Exception("Invalid video file type. Allowed: " . implode(', ', $this->allowedVideoTypes));
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'video_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            // Generate thumbnail
            $thumbnail = $this->generateVideoThumbnail($filepath, $filename);
            
            // Get video information
            $videoInfo = $this->getVideoInfo($filepath);
            
            return [
                'filepath' => $filepath,
                'thumbnail' => $thumbnail,
                'duration' => $videoInfo['duration'],
                'size' => $file['size'],
                'format' => $extension,
                'resolution' => $videoInfo['resolution']
            ];
        } catch (Exception $e) {
            error_log("Video Upload Processing Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate video thumbnail
     */
    private function generateVideoThumbnail($videoPath, $filename) {
        try {
            // Use FFmpeg to generate thumbnail
            $thumbnailPath = $this->thumbnailDir . 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            
            $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -ss 00:00:01 -vframes 1 -q:v 2 " . escapeshellarg($thumbnailPath) . " 2>&1";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                return $thumbnailPath;
            }
            
            // Fallback: use default thumbnail
            return 'php/defaultavatar/video-thumbnail.png';
        } catch (Exception $e) {
            error_log("Thumbnail Generation Error: " . $e->getMessage());
            return 'php/defaultavatar/video-thumbnail.png';
        }
    }
    
    /**
     * Get video information using FFmpeg
     */
    private function getVideoInfo($videoPath) {
        try {
            $command = "ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($videoPath);
            $output = shell_exec($command);
            $info = json_decode($output, true);
            
            $duration = 0;
            $resolution = '720p';
            
            if ($info && isset($info['format']['duration'])) {
                $duration = (int)$info['format']['duration'];
            }
            
            if ($info && isset($info['streams'][0]['width']) && isset($info['streams'][0]['height'])) {
                $width = $info['streams'][0]['width'];
                $height = $info['streams'][0]['height'];
                
                if ($height >= 1080) {
                    $resolution = '1080p';
                } elseif ($height >= 720) {
                    $resolution = '720p';
                } elseif ($height >= 480) {
                    $resolution = '480p';
                } else {
                    $resolution = '360p';
                }
            }
            
            return [
                'duration' => $duration,
                'resolution' => $resolution
            ];
        } catch (Exception $e) {
            error_log("Video Info Error: " . $e->getMessage());
            return [
                'duration' => 0,
                'resolution' => '720p'
            ];
        }
    }
    
    /**
     * Process tags for a video
     */
    private function processTags($videoId, $tagsString) {
        try {
            // Remove existing tags
            $this->con->query("DELETE FROM video_tag_relationships WHERE VideoID = $videoId");
            
            if (empty($tagsString)) {
                return;
            }
            
            $tags = array_map('trim', explode(',', $tagsString));
            
            foreach ($tags as $tagName) {
                if (empty($tagName)) continue;
                
                // Get or create tag
                $tagId = $this->getOrCreateTag($tagName);
                
                // Create relationship
                $sql = "INSERT INTO video_tag_relationships (VideoID, TagID) VALUES (?, ?)";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("ii", $videoId, $tagId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Process Tags Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get or create tag
     */
    private function getOrCreateTag($tagName) {
        try {
            $tagSlug = $this->createSlug($tagName);
            
            // Check if tag exists
            $sql = "SELECT TagID FROM video_tags WHERE TagSlug = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("s", $tagSlug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $tag = $result->fetch_assoc();
                return $tag['TagID'];
            }
            
            // Create new tag
            $sql = "INSERT INTO video_tags (TagName, TagSlug) VALUES (?, ?)";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ss", $tagName, $tagSlug);
            $stmt->execute();
            
            return $stmt->insert_id;
        } catch (Exception $e) {
            error_log("Get Or Create Tag Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get video tags
     */
    private function getVideoTags($videoId) {
        try {
            $sql = "SELECT t.* FROM video_tags t 
                    JOIN video_tag_relationships vtr ON t.TagID = vtr.TagID 
                    WHERE vtr.VideoID = ? AND t.isActive = 1 AND t.isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tags = [];
            while ($row = $result->fetch_assoc()) {
                $tags[] = $row;
            }
            
            return $tags;
        } catch (Exception $e) {
            error_log("Get Video Tags Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get comments count
     */
    private function getCommentsCount($videoId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM video_comments WHERE VideoID = ? AND Status = 'approved' AND isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['count'];
        } catch (Exception $e) {
            error_log("Get Comments Count Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists($slug) {
        try {
            $sql = "SELECT VideoID FROM video_posts WHERE Slug = ? AND isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->num_rows > 0;
        } catch (Exception $e) {
            error_log("Slug Exists Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create slug from title
     */
    public function createSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Get scheduled videos
     */
    public function getScheduledVideos() {
        try {
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE v.Status = 'scheduled' AND v.PublishDate <= NOW() 
                    AND v.isDeleted = 'notDeleted'";
            
            $result = $this->con->query($sql);
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log("Get Scheduled Videos Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Publish scheduled videos
     */
    public function publishScheduledVideos() {
        try {
            $scheduledVideos = $this->getScheduledVideos();
            
            foreach ($scheduledVideos as $video) {
                $sql = "UPDATE video_posts SET Status = 'published', Published_at = NOW() WHERE VideoID = ?";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("i", $video['VideoID']);
                $stmt->execute();
            }
            
            return count($scheduledVideos);
        } catch (Exception $e) {
            error_log("Publish Scheduled Videos Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get video statistics
     */
    public function getVideoStatistics($videoId, $days = 30) {
        try {
            $sql = "SELECT * FROM video_statistics 
                    WHERE VideoID = ? AND ViewDate >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    ORDER BY ViewDate DESC";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ii", $videoId, $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get Video Statistics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Record video view
     */
    public function recordVideoView($videoId) {
        try {
            $today = date('Y-m-d');
            
            // Check if stats exist for today
            $sql = "SELECT ID FROM video_statistics WHERE VideoID = ? AND ViewDate = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("is", $videoId, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing stats
                $sql = "UPDATE video_statistics SET Views = Views + 1 WHERE VideoID = ? AND ViewDate = ?";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("is", $videoId, $today);
            } else {
                // Create new stats
                $sql = "INSERT INTO video_statistics (VideoID, ViewDate, Views) VALUES (?, ?, 1)";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("is", $videoId, $today);
            }
            
            $stmt->execute();
            
            // Update video views count
            $sql = "UPDATE video_posts SET Views = Views + 1 WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $videoId);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Record Video View Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get related videos
     */
    public function getRelatedVideos($videoId, $categoryId, $limit = 6) {
        try {
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE v.VideoID != ? AND v.Status = 'published' AND v.isDeleted = 'notDeleted'";
            
            $params = [$videoId];
            $paramTypes = "i";
            
            if ($categoryId) {
                $sql .= " AND v.CategoryID = ?";
                $params[] = $categoryId;
                $paramTypes .= "i";
            }
            
            $sql .= " ORDER BY v.Views DESC, v.Created_at DESC LIMIT ?";
            $params[] = $limit;
            $paramTypes .= "i";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param($paramTypes, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log("Get Related Videos Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get featured videos
     */
    public function getFeaturedVideos($limit = 10) {
        try {
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE v.Featured = 1 AND v.Status = 'published' AND v.isDeleted = 'notDeleted'
                    ORDER BY v.Views DESC, v.Created_at DESC LIMIT ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log("Get Featured Videos Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending videos
     */
    public function getTrendingVideos($days = 7, $limit = 10) {
        try {
            $sql = "SELECT v.*, c.CategoryName, a.FirstName, a.LastName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN admin a ON v.AuthorID = a.AdminId 
                    WHERE v.Status = 'published' AND v.isDeleted = 'notDeleted'
                    AND v.Created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    ORDER BY v.Views DESC, v.Likes DESC, v.Created_at DESC LIMIT ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ii", $days, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log("Get Trending Videos Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
