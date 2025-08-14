<?php

/**
 * Video Manager Class
 * Handles all video post operations including CRUD, scheduling, drafts, and media management
 */
class VideoManager
{
    private $con;
    private $uploadDir;
    private $allowedVideoTypes;
    private $maxVideoSize;
    private $thumbnailDir;

    public function __construct($con, $uploadDir = 'videos/', $thumbnailDir = 'videos/thumbnails/')
    {
        $this->con = $con;
        $this->uploadDir = $uploadDir;
        $this->thumbnailDir = $thumbnailDir;
        $this->allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'];
        $this->maxVideoSize = 500 * 1024 * 1024;  // 500MB

        // Create upload directories if they don't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                error_log('Failed to create video upload directory: ' . $this->uploadDir);
            }
        }
        if (!is_dir($this->thumbnailDir)) {
            if (!mkdir($this->thumbnailDir, 0755, true)) {
                error_log('Failed to create video thumbnail directory: ' . $this->thumbnailDir);
            }
        }

        // Check if required tables exist
        if (!$this->checkRequiredTables()) {
            throw new Exception('Required database tables for video management do not exist. Please run the installation script first.');
        }
    }

    /**
     * Check if required database tables exist
     */
    private function checkRequiredTables()
    {
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
    public function createVideo($profileId, $data)
    {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['slug'])) {
                throw new Exception('Title and slug are required');
            }
            
            // Validate profile ID
            if (empty($profileId)) {
                throw new Exception('Profile ID is required');
            }
            
            // Check if slug already exists
            if ($this->slugExists($data['slug'])) {
                throw new Exception('Slug already exists');
            }
            
            // Process video file if uploaded
            $videoFile = '';
            $videoThumbnail = '';
            $videoDuration = 0;
            $videoSize = 0;
            $videoFormat = 'mp4';
            $videoResolution = '1920x1080';
            
            if (!empty($_FILES['videoFile']['name'])) {
                try {
                $videoInfo = $this->processVideoUpload($_FILES['videoFile']);
                if ($videoInfo && is_array($videoInfo)) {
                        $videoFile = $videoInfo['filepath'] ?? '';
                        $videoThumbnail = $videoInfo['thumbnail'] ?? '';
                        $videoDuration = $videoInfo['duration'] ?? 0;
                        $videoSize = $videoInfo['size'] ?? 0;
                    $videoFormat = 'mp4';
                    $videoResolution = '1920x1080';
                        
                        error_log('Video upload successful: ' . $videoFile);
                } else {
                    error_log('Video Upload Warning: processVideoUpload returned invalid data structure');
                        throw new Exception('Video upload failed - invalid data structure');
                    }
                } catch (Exception $e) {
                    error_log('Video upload error: ' . $e->getMessage());
                    throw new Exception('Video upload failed: ' . $e->getMessage());
                }
            } elseif (!empty($data['embedCode'])) {
                $videoFormat = 'embed';
                $videoResolution = $data['videoResolution'] ?? '1920x1080';
                
                // Process embed code to extract source and video ID
                if (!empty($data['embedCode'])) {
                    $embedInfo = $this->processEmbedCode($data['embedCode']);
                    if ($embedInfo) {
                        $data['embedSource'] = $embedInfo['source'];
                        $data['embedVideoID'] = $embedInfo['videoId'];
                    }
                }
            }
            
            // Process thumbnail upload if provided
            if (!empty($_FILES['videoThumbnail']['name'])) {
                try {
                    $thumbnailInfo = $this->processThumbnailUpload($_FILES['videoThumbnail']);
                    if (is_array($thumbnailInfo) && isset($thumbnailInfo['filepath'])) {
                        $videoThumbnail = $thumbnailInfo['filepath'];
                        error_log('Thumbnail uploaded successfully: ' . $videoThumbnail);
                    } else {
                        error_log('Thumbnail upload returned invalid data structure: ' . print_r($thumbnailInfo, true));
                        $videoThumbnail = 'images/default-video-thumbnail.jpg';
                    }
                } catch (Exception $e) {
                    error_log('Thumbnail upload error: ' . $e->getMessage());
                    $videoThumbnail = 'images/default-video-thumbnail.jpg';
                }
            } elseif (empty($videoThumbnail)) {
                // Only set default if no thumbnail was set by video upload
                $videoThumbnail = 'images/default-video-thumbnail.jpg';
            }
            
            // Prepare publish date
            $publishDate = date('Y-m-d H:i:s');
            if ($data['status'] === 'scheduled' && !empty($data['publishDate'])) {
                $publishDate = $data['publishDate'];
            } elseif ($data['status'] === 'published') {
                $publishDate = date('Y-m-d H:i:s');
            }
            
            $sql = 'INSERT INTO video_posts (
                Title, Slug, Excerpt, Description, VideoFile, VideoThumbnail,
                VideoDuration, VideoSize, VideoFormat, VideoResolution,
                EmbedCode, EmbedSource, EmbedVideoID, CategoryID, Tags, ProfileID,
                Status, PublishDate, Featured, AllowComments,
                MetaTitle, MetaDescription, MetaKeywords, videoType, Created_at, Updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';
            
            $stmt = $this->con->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $this->con->error);
            }
            
            // Ensure all variables are properly defined and not null
            $title = $data['title'];
            $slug = $data['slug'];
            $excerpt = $data['excerpt'] ?? '';
            $description = $data['description'] ?? '';
            $tags = $data['tags'] ?? '';
            $categoryID = !empty($data['categoryID']) ? $data['categoryID'] : null;  // Allow null for category
            $status = $data['status'];
            $featured = $data['featured'] ?? 0;
            $allowComments = $data['allowComments'] ?? 1;
            $metaTitle = $data['metaTitle'] ?? $data['title'] ?? '';
            $metaDescription = $data['metaDescription'] ?? $data['excerpt'] ?? '';
            $metaKeywords = $data['metaKeywords'] ?? $data['tags'] ?? '';
            $embedCode = $data['embedCode'] ?? '';
            $embedSource = $data['embedSource'] ?? '';
            $embedVideoID = $data['embedVideoID'] ?? '';
            $videoType = $data['videoType'] ?? 'video';
            
            // Debug logging to identify any issues
            error_log("Video Creation Debug - Title: $title, Slug: $slug, Status: $status, CategoryID: " . ($categoryID ?? 'NULL') . ", ProfileID: $profileId");
            error_log("Video Creation Debug - VideoFile: $videoFile, Thumbnail: $videoThumbnail, Duration: $videoDuration, Size: $videoSize, VideoType: $videoType");
            
            // Final safety check - ensure all variables are proper types and not null
            $videoDuration = (int) ($videoDuration ?? 0);
            $videoSize = (int) ($videoSize ?? 0);
            $featured = (int) ($featured ?? 0);
            $allowComments = (int) ($allowComments ?? 1);
            
            // Ensure video file path is not empty for non-embed videos
            if ($videoFormat !== 'embed' && empty($videoFile)) {
                throw new Exception('Video file is required for non-embed videos');
            }
            
            // Ensure thumbnail path is set
            if (empty($videoThumbnail)) {
                $videoThumbnail = 'images/default-video-thumbnail.jpg';
            }
            
            $stmt->bind_param('ssssssisssssssssssssssss',
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
                $profileId,
                $status,
                $publishDate,
                $featured,
                $allowComments,
                $metaTitle,
                $metaDescription,
                $metaKeywords,
                $videoType);
            
            if ($stmt->execute()) {
                $videoId = $this->con->insert_id;
                
                // Handle tags if provided
                if (isset($data['tags'])) {
                    $this->processTags($videoId, $data['tags']);
                }
                
                // Update published_at if status is published
                if ($data['status'] === 'published') {
                    $this->con->query("UPDATE video_posts SET Published_at = NOW() WHERE VideoID = $videoId");
                }
                
                error_log("Video Creation Success: Created video ID $videoId for profile $profileId");
                return $videoId;
            } else {
                throw new Exception('Failed to create video post: ' . $stmt->error);
            }
        } catch (Exception $e) {
            error_log('Video Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing video post
     */
    public function updateVideo($videoId, $data)
    {
        try {
            // Check if video exists
            $existingVideo = $this->getVideo($videoId);
            if (!$existingVideo) {
                throw new Exception('Video not found');
            }

            // Check if slug already exists (excluding current video)
            if (!empty($data['slug']) && $data['slug'] !== $existingVideo['Slug'] && $this->slugExists($data['slug'])) {
                throw new Exception('Slug already exists');
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

            // Handle thumbnail upload if provided
            if (!empty($_FILES['videoThumbnail']['name'])) {
                try {
                    $thumbnailInfo = $this->processThumbnailUpload($_FILES['videoThumbnail']);
                    $videoThumbnail = $thumbnailInfo['filepath'];
                    
                    // Delete old thumbnail if it exists and is different from default
                    if (!empty($existingVideo['VideoThumbnail']) && 
                        $existingVideo['VideoThumbnail'] !== 'images/default-video-thumbnail.jpg' &&
                        file_exists($existingVideo['VideoThumbnail'])) {
                        unlink($existingVideo['VideoThumbnail']);
                    }
                } catch (Exception $e) {
                    error_log('Thumbnail upload error: ' . $e->getMessage());
                    // Keep existing thumbnail if upload fails
                    $videoThumbnail = $existingVideo['VideoThumbnail'];
                }
            } else {
                // No new thumbnail uploaded - preserve existing thumbnail
                $videoThumbnail = $existingVideo['VideoThumbnail'];
                error_log("No new thumbnail uploaded - preserving existing: " . $videoThumbnail);
            }

            // Prepare publish date
            $publishDate = $existingVideo['PublishDate'];
            if ($data['status'] === 'scheduled' && !empty($data['publishDate'])) {
                $publishDate = $data['publishDate'];
            } elseif ($data['status'] === 'published' && $existingVideo['Status'] !== 'published') {
                $publishDate = date('Y-m-d H:i:s');
            }

            $sql = 'UPDATE video_posts SET 
                Title = ?, Slug = ?, Excerpt = ?, Description = ?, VideoFile = ?, VideoThumbnail = ?,
                VideoDuration = ?, VideoSize = ?, VideoFormat = ?, VideoResolution = ?,
                EmbedCode = ?, EmbedSource = ?, EmbedVideoID = ?, CategoryID = ?, Tags = ?,
                Status = ?, PublishDate = ?, Featured = ?, AllowComments = ?,
                MetaTitle = ?, MetaDescription = ?, MetaKeywords = ?, videoType = ?,
                Updated_at = CURRENT_TIMESTAMP
                WHERE VideoID = ?';

            // Ensure all variables are properly defined and not null
            $title = $data['title'];
            $slug = $data['slug'];
            $excerpt = $data['excerpt'] ?? '';
            $description = $data['description'] ?? '';
            $embedCode = $data['embedCode'] ?? '';
            $embedSource = $data['embedSource'] ?? '';
            $embedVideoID = $data['embedVideoID'] ?? '';
            $categoryID = !empty($data['categoryID']) ? $data['categoryID'] : null;  // Allow null for category
            $tags = $data['tags'] ?? '';
            $status = $data['status'];
            $featured = $data['featured'] ?? 0;
            $allowComments = $data['allowComments'] ?? 1;
            $metaTitle = $data['metaTitle'] ?? $data['title'];
            $metaDescription = $data['metaDescription'] ?? $data['excerpt'] ?? '';
            $metaKeywords = $data['metaKeywords'] ?? $data['tags'] ?? '';
            $videoType = $data['videoType'] ?? $existingVideo['videoType'] ?? 'video';

            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('ssssssissssssssssssssssi',
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
                $videoType,
                $videoId);

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
                throw new Exception('Failed to update video post: ' . $stmt->error);
            }
        } catch (Exception $e) {
            error_log('Video Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get video by ID
     */
    public function getVideo($videoId)
    {
        try {
            $sql = "SELECT v.*, c.CategoryName, cp.Username, cp.DisplayName, cp.ProfileID
                    FROM video_posts v
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID 
                    WHERE v.VideoID = ? AND v.isDeleted = 'notDeleted'";

            $stmt = $this->con->prepare($sql);
            if (!$stmt) {
                error_log('Get Video Error: Failed to prepare statement - ' . $this->con->error);
                return null;
            }

            $stmt->bind_param('i', $videoId);
            if (!$stmt->execute()) {
                error_log('Get Video Error: Failed to execute statement - ' . $stmt->error);
                return null;
            }

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $video = $result->fetch_assoc();

                // Get tags
                $video['tags'] = $this->getVideoTags($videoId);

                // Get comments count
                $video['commentsCount'] = $this->getCommentsCount($videoId);

                error_log("Get Video Success: Found video ID $videoId - Title: " . $video['Title']);
                return $video;
            } else {
                error_log("Get Video Error: No video found with ID $videoId");
                return null;
            }
        } catch (Exception $e) {
            error_log('Get Video Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all videos with optional filters and pagination
     */
    public function getAllVideos($page = 1, $limit = 20, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = [];
            $params = [];
            $types = '';

            // Build WHERE conditions based on filters
            if (!empty($filters['search'])) {
                $whereConditions[] = '(v.Title LIKE ? OR v.Description LIKE ? OR v.Tags LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= 'sss';
            }

            if (!empty($filters['status'])) {
                $whereConditions[] = 'v.Status = ?';
                $params[] = $filters['status'];
                $types .= 's';
            }

            if (isset($filters['featured']) && $filters['featured'] !== '') {
                $whereConditions[] = 'v.Featured = ?';
                $params[] = $filters['featured'];
                $types .= 'i';
            }

            if (!empty($filters['categoryID'])) {
                $whereConditions[] = 'v.CategoryID = ?';
                $params[] = $filters['categoryID'];
                $types .= 'i';
            }

            if (!empty($filters['videoType'])) {
                $whereConditions[] = 'v.videoType = ?';
                $params[] = $filters['videoType'];
                $types .= 's';
            }

            // Always filter out deleted videos
            $whereConditions[] = "v.isDeleted = 'notDeleted'";

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Count total videos
            $countSql = "SELECT COUNT(*) as total FROM video_posts v $whereClause";
            $countStmt = $this->con->prepare($countSql);

            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }

            $countStmt->execute();
            $totalResult = $countStmt->get_result()->fetch_assoc();
            $total = $totalResult['total'];

            // Get videos with pagination
            $sql = "SELECT v.*, 
                           c.CategoryName,
                           cp.Username as AuthorName,
                           cp.DisplayName as AuthorDisplayName,
                           (SELECT COUNT(*) FROM video_comments vc WHERE vc.VideoID = v.VideoID AND vc.isDeleted = 'notDeleted') as Comments
                    FROM video_posts v
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID
                    $whereClause
                    ORDER BY v.Created_at DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->con->prepare($sql);

            // Add limit and offset to params
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }

            $totalPages = ceil($total / $limit);

            return [
                'videos' => $videos,
                'total' => $total,
                'pages' => $totalPages,
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log('Error getting all videos: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete video (soft delete)
     */
    public function deleteVideo($videoId)
    {
        try {
            $sql = "UPDATE video_posts SET isDeleted = 'deleted', Updated_at = CURRENT_TIMESTAMP WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $videoId);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Delete Video Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore deleted video
     */
    public function restoreVideo($videoId)
    {
        try {
            $sql = "UPDATE video_posts SET isDeleted = 'notDeleted', Updated_at = CURRENT_TIMESTAMP WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $videoId);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Restore Video Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all video categories
     */
    public function getCategories()
    {
        try {
            // Check if video_categories table exists
            $tableExists = $this->con->query("SHOW TABLES LIKE 'video_categories'");
            if ($tableExists->num_rows === 0) {
                error_log('Video Categories table does not exist');
                return [];
            }

            $sql = "SELECT c.* FROM video_categories c WHERE c.isActive = 1 AND c.isDeleted = 'notDeleted' ORDER BY c.SortOrder, c.CategoryName";
            $result = $this->con->query($sql);

            if ($result) {
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                return $categories;
            }

            return [];
        } catch (Exception $e) {
            error_log('Get Categories Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all video tags
     */
    public function getTags()
    {
        try {
            // Check if video_tags table exists
            $tableExists = $this->con->query("SHOW TABLES LIKE 'video_tags'");
            if ($tableExists->num_rows === 0) {
                error_log('Video Tags table does not exist');
                return [];
            }

            $sql = "SELECT t.* FROM video_tags t WHERE t.Status = 'active' AND t.isDeleted = 'notDeleted' ORDER BY t.TagName";
            $result = $this->con->query($sql);

            if ($result) {
                $tags = [];
                while ($row = $result->fetch_assoc()) {
                    $tags[] = $row;
                }
                return $tags;
            }

            return [];
        } catch (Exception $e) {
            error_log('Get Tags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Process embed code to extract video ID and source
     */
    private function processEmbedCode($embedCode)
    {
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
            error_log('Embed Code Processing Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process video file upload
     */
    public function processVideoUpload($file)
    {
        try {
            // Check file size
            if ($file['size'] > $this->maxVideoSize) {
                throw new Exception('File size must be less than ' . ($this->maxVideoSize / (1024 * 1024)) . 'MB');
            }

            // Check file type
            if (!in_array($file['type'], $this->allowedVideoTypes)) {
                throw new Exception('Invalid video file type. Allowed: ' . implode(', ', $this->allowedVideoTypes));
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'video_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Compress video to optimize file size while maintaining quality
            $compressedFilepath = $this->compressVideo($filepath, $filename);

            // Use compressed file if compression was successful
            if ($compressedFilepath && $compressedFilepath !== $filepath) {
                // Delete original uncompressed file
                unlink($filepath);
                $filepath = $compressedFilepath;
            }

            // Generate thumbnail
            $thumbnail = $this->generateVideoThumbnail($filepath, $filename);

            // Get video information
            $videoInfo = $this->getVideoInfo($filepath);

            return [
                'filepath' => $filepath,
                'thumbnail' => $thumbnail,
                'duration' => $videoInfo['duration'],
                'size' => filesize($filepath),  // Use actual file size after compression
                'format' => $extension,
                'resolution' => $videoInfo['resolution']
            ];
        } catch (Exception $e) {
            error_log('Video Upload Processing Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Compress video to optimize file size while maintaining quality
     * Uses FFmpeg with intelligent compression settings
     */
    private function compressVideo($inputPath, $filename)
    {
        try {
            // Check if FFmpeg is available
            exec('ffmpeg -version', $output, $returnCode);
            if ($returnCode !== 0) {
                error_log('FFmpeg not available, skipping video compression');
                return $inputPath;  // Return original file if FFmpeg not available
            }

            // Get original file size
            $originalSize = filesize($inputPath);
            $maxTargetSize = 100 * 1024 * 1024;  // 100MB target

            // If file is already small enough, skip compression
            if ($originalSize <= $maxTargetSize) {
                error_log("Video file size ($originalSize bytes) is already optimal, skipping compression");
                return $inputPath;
            }

            // Generate compressed filename
            $compressedFilename = 'compressed_' . $filename;
            $compressedPath = $this->uploadDir . $compressedFilename;

            // Determine compression settings based on file size
            $compressionSettings = $this->getCompressionSettings($originalSize, $maxTargetSize);

            // Build FFmpeg command for compression
            $command = 'ffmpeg -i ' . escapeshellarg($inputPath) . ' ' . $compressionSettings . ' ' . escapeshellarg($compressedPath) . ' 2>&1';

            error_log('Executing video compression command: ' . $command);

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($compressedPath)) {
                $compressedSize = filesize($compressedPath);
                $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 2);

                error_log("Video compression successful: $originalSize -> $compressedSize bytes ($compressionRatio% reduction)");

                // Only use compressed file if it's significantly smaller
                if ($compressedSize < $originalSize * 0.9) {  // At least 10% reduction
                    return $compressedPath;
                } else {
                    // Delete compressed file if compression wasn't effective
                    unlink($compressedPath);
                    error_log('Compression not effective enough, keeping original file');
                    return $inputPath;
                }
            } else {
                error_log('Video compression failed: ' . implode("\n", $output));
                return $inputPath;  // Return original file if compression fails
            }
        } catch (Exception $e) {
            error_log('Video Compression Error: ' . $e->getMessage());
            return $inputPath;  // Return original file if compression fails
        }
    }

    /**
     * Get optimal compression settings based on file size and target
     */
    private function getCompressionSettings($originalSize, $targetSize)
    {
        $sizeRatio = $originalSize / $targetSize;

        if ($sizeRatio > 5) {
            // Very large file - aggressive compression
            return '-c:v libx264 -preset slower -crf 28 -c:a aac -b:a 128k -movflags +faststart';
        } elseif ($sizeRatio > 3) {
            // Large file - moderate compression
            return '-c:v libx264 -preset medium -crf 25 -c:a aac -b:a 160k -movflags +faststart';
        } elseif ($sizeRatio > 2) {
            // Medium-large file - light compression
            return '-c:v libx264 -preset fast -crf 23 -c:a aac -b:a 192k -movflags +faststart';
        } else {
            // Small file - minimal compression to maintain quality
            return '-c:v libx264 -preset veryfast -crf 20 -c:a aac -b:a 256k -movflags +faststart';
        }
    }

    /**
     * Generate video thumbnail
     */
    private function generateVideoThumbnail($videoPath, $filename)
    {
        try {
            // Use FFmpeg to generate thumbnail
            $thumbnailPath = $this->thumbnailDir . 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

            $command = 'ffmpeg -i ' . escapeshellarg($videoPath) . ' -ss 00:00:01 -vframes 1 -q:v 2 ' . escapeshellarg($thumbnailPath) . ' 2>&1';

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                return $thumbnailPath;
            }

            // Fallback: use default thumbnail
            return 'php/defaultavatar/video-thumbnail.png';
        } catch (Exception $e) {
            error_log('Thumbnail Generation Error: ' . $e->getMessage());
            return 'php/defaultavatar/video-thumbnail.png';
        }
    }

    /**
     * Get video information using FFmpeg
     */
    public function getVideoInfo($videoPath)
    {
        try {
            $command = 'ffprobe -v quiet -print_format json -show_format -show_streams ' . escapeshellarg($videoPath);
            $output = shell_exec($command);
            $info = json_decode($output, true);

            $duration = 0;
            $resolution = '720p';

            if ($info && isset($info['format']['duration'])) {
                $duration = (int) $info['format']['duration'];
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
            error_log('Video Info Error: ' . $e->getMessage());
            return [
                'duration' => 0,
                'resolution' => '720p'
            ];
        }
    }

    /**
     * Process tags for a video
     */
    private function processTags($videoId, $tagsString)
    {
        try {
            // Remove existing tags
            $this->con->query("DELETE FROM video_tag_relationships WHERE VideoID = $videoId");

            if (empty($tagsString)) {
                return;
            }

            $tags = array_map('trim', explode(',', $tagsString));

            foreach ($tags as $tagName) {
                if (empty($tagName))
                    continue;

                // Get or create tag
                $tagId = $this->getOrCreateTag($tagName);

                // Create relationship
                $sql = 'INSERT INTO video_tag_relationships (VideoID, TagID) VALUES (?, ?)';
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param('ii', $videoId, $tagId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log('Process Tags Error: ' . $e->getMessage());
        }
    }

    /**
     * Get or create tag
     */
    private function getOrCreateTag($tagName)
    {
        try {
            $tagSlug = $this->createSlug($tagName);

            // Check if tag exists
            $sql = 'SELECT TagID FROM video_tags WHERE TagSlug = ?';
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('s', $tagSlug);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $tag = $result->fetch_assoc();
                return $tag['TagID'];
            }

            // Create new tag
            $sql = 'INSERT INTO video_tags (TagName, TagSlug) VALUES (?, ?)';
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('ss', $tagName, $tagSlug);
            $stmt->execute();

            return $stmt->insert_id;
        } catch (Exception $e) {
            error_log('Get Or Create Tag Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get video tags
     */
    private function getVideoTags($videoId)
    {
        try {
            $sql = "SELECT t.* FROM video_tags t 
                    JOIN video_tag_relationships vtr ON t.TagID = vtr.TagID 
                    WHERE vtr.VideoID = ? AND t.isActive = 1 AND t.isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tags = [];
            while ($row = $result->fetch_assoc()) {
                $tags[] = $row;
            }
            
            return $tags;
        } catch (Exception $e) {
            error_log('Get Video Tags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comments count
     */
    private function getCommentsCount($videoId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM video_comments vc WHERE vc.VideoID = ? AND vc.Status = 'approved' AND vc.isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['count'];
        } catch (Exception $e) {
            error_log('Get Comments Count Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug) {
        try {
            $sql = "SELECT v.VideoID FROM video_posts v WHERE v.Slug = ? AND v.isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->num_rows > 0;
        } catch (Exception $e) {
            error_log('Slug Exists Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create slug from title
     */
    public function createSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Get scheduled videos
     */
    public function getScheduledVideos()
    {
        try {
            $sql = "SELECT v.*, c.CategoryName, cp.Username, cp.DisplayName 
                    FROM video_posts v
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID
                    WHERE v.Status = 'scheduled' AND v.PublishDate <= NOW() 
                    AND v.isDeleted = 'notDeleted'";
            
            $result = $this->con->query($sql);
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log('Get Scheduled Videos Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Publish scheduled videos
     */
    public function publishScheduledVideos()
    {
        try {
            $scheduledVideos = $this->getScheduledVideos();

            foreach ($scheduledVideos as $video) {
                $sql = "UPDATE video_posts SET Status = 'published', Published_at = NOW() WHERE VideoID = ?";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param('i', $video['VideoID']);
                $stmt->execute();
            }

            return count($scheduledVideos);
        } catch (Exception $e) {
            error_log('Publish Scheduled Videos Error: ' . $e->getMessage());
            return 0;
        }
    }

         /**
      * Get video statistics by type
      */
     public function getVideoStatsByType($videoType)
     {
         try {
             $sql = "SELECT 
                 COUNT(*) as total_videos,
                 SUM(Views) as total_views,
                 SUM(CASE WHEN Featured = 1 THEN 1 ELSE 0 END) as featured_videos,
                 SUM(CASE WHEN Status = 'published' THEN 1 ELSE 0 END) as published_videos,
                 SUM(CASE WHEN Status = 'draft' THEN 1 ELSE 0 END) as draft_videos,
                 SUM(CASE WHEN Status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_videos,
                 SUM(CASE WHEN Status = 'archived' THEN 1 ELSE 0 END) as archived_videos
                 FROM video_posts v
                 WHERE v.isDeleted = 'notDeleted' AND v.videoType = ?";
             
             $stmt = $this->con->prepare($sql);
             $stmt->bind_param('s', $videoType);
             $stmt->execute();
             $result = $stmt->get_result();
             $stats = $result->fetch_assoc();
 
             // Get total comments for this video type
             $commentsSql = "SELECT COUNT(*) as total_comments FROM video_comments vc 
                           JOIN video_posts v ON vc.VideoID = v.VideoID 
                           WHERE vc.isDeleted = 'notDeleted' AND v.videoType = ?";
             $commentsStmt = $this->con->prepare($commentsSql);
             $commentsStmt->bind_param('s', $videoType);
             $commentsStmt->execute();
             $commentsResult = $commentsStmt->get_result();
             $commentsStats = $commentsResult->fetch_assoc();
             
             $stats['total_comments'] = $commentsStats['total_comments'] ?? 0;
 
             return $stats;
         } catch (Exception $e) {
             error_log('Error getting video stats by type: ' . $e->getMessage());
             return [
                 'total_videos' => 0,
                 'total_views' => 0,
                 'featured_videos' => 0,
                 'published_videos' => 0,
                 'draft_videos' => 0,
                 'scheduled_videos' => 0,
                 'archived_videos' => 0,
                 'total_comments' => 0
             ];
        }
    }

    /**
     * Get video statistics
     */
    public function getVideoStats()
    {
        try {
            $sql = "SELECT 
                COUNT(*) as total_videos,
                SUM(Views) as total_views,
                SUM(CASE WHEN Featured = 1 THEN 1 ELSE 0 END) as featured_videos,
                SUM(CASE WHEN Status = 'published' THEN 1 ELSE 0 END) as published_videos,
                SUM(CASE WHEN Status = 'draft' THEN 1 ELSE 0 END) as draft_videos,
                SUM(CASE WHEN Status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_videos,
                SUM(CASE WHEN Status = 'archived' THEN 1 ELSE 0 END) as archived_videos
                FROM video_posts v
                WHERE v.isDeleted = 'notDeleted'";

            $stmt = $this->con->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();

            // Get total comments
            $commentsSql = "SELECT COUNT(*) as total_comments FROM video_comments vc WHERE vc.isDeleted = 'notDeleted'";
            $commentsStmt = $this->con->prepare($commentsSql);
            $commentsStmt->execute();
            $commentsResult = $commentsStmt->get_result();
            $commentsStats = $commentsResult->fetch_assoc();
            
            $stats['total_comments'] = $commentsStats['total_comments'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            error_log('Error getting video stats: ' . $e->getMessage());
            return [
                'total_videos' => 0,
                'total_views' => 0,
                'featured_videos' => 0,
                'published_videos' => 0,
                'draft_videos' => 0,
                'scheduled_videos' => 0,
                'archived_videos' => 0,
                'total_comments' => 0
            ];
        }
    }

    /**
     * Get featured videos
     */
     public function getFeaturedVideos($limit = 5, $videoType = null)
    {
        try {
            $sql = "SELECT v.*, c.CategoryName, cp.Username, cp.DisplayName
                    FROM video_posts v
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID
                     WHERE v.Featured = 1 AND v.Status = 'published' AND v.isDeleted = 'notDeleted'";
             
             $params = [$limit];
             $types = 'i';
             
             if ($videoType && $videoType !== 'all') {
                 $sql .= " AND v.videoType = ?";
                 $params[] = $videoType;
                 $types = 'si';
             }
             
             $sql .= " ORDER BY v.Created_at DESC LIMIT ?";
            
            $stmt = $this->con->prepare($sql);
             $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log('Error getting featured videos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending videos
     */
     public function getTrendingVideos($days = 7, $limit = 5, $videoType = null)
    {
        try {
            $sql = "SELECT v.*, c.CategoryName, cp.Username, cp.DisplayName
                    FROM video_posts v
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID
                    WHERE v.Status = 'published' AND v.isDeleted = 'notDeleted'
                     AND v.Created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             
             $params = [$days, $limit];
             $types = 'ii';
             
             if ($videoType && $videoType !== 'all') {
                 $sql .= " AND v.videoType = ?";
                 $params[] = $videoType;
                 $types = 'iis';
             }
             
             $sql .= " ORDER BY v.Views DESC, v.Created_at DESC LIMIT ?";
            
            $stmt = $this->con->prepare($sql);
             $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $videos = [];
            while ($row = $result->fetch_assoc()) {
                $videos[] = $row;
            }
            
            return $videos;
        } catch (Exception $e) {
            error_log('Error getting trending videos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Process thumbnail upload with robust image processing
     * Similar to the ImageProcessor class used for articles
     */
    public function processThumbnailUpload($file)
    {
        try {
            error_log('=== THUMBNAIL UPLOAD START ===');
            error_log('File info: ' . print_r($file, true));
            
            // Check if GD extension is available
            if (!extension_loaded('gd')) {
                throw new Exception('GD extension is not available');
            }
            error_log('✅ GD extension check passed');

            // Check GD capabilities
            $gdInfo = gd_info();
            error_log('GD Info: ' . print_r($gdInfo, true));
            
            // Check if WebP is supported
            $webpSupported = isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
            if (!$webpSupported) {
                error_log('WebP not supported by GD, will use original format');
            }
            error_log('✅ GD capabilities check passed');

            // Check file size (max 2MB for thumbnails)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Thumbnail file size must be less than 2MB');
            }
            error_log('✅ File size check passed: ' . $file['size'] . ' bytes');

            // Check file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Only JPG, PNG, and GIF files are allowed for thumbnails');
            }
            error_log('✅ File type check passed: ' . $file['type']);

            // Create upload directory if it doesn't exist
            $uploadDir = 'images/video_thumbnails/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('Failed to create thumbnail upload directory');
                }
            }
            error_log('✅ Upload directory check passed: ' . $uploadDir);

            // Get image info
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                throw new Exception('Invalid image file');
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            error_log("✅ Image info retrieved: {$width}x{$height} ({$mimeType})");

            // Create image resource
            $sourceImage = $this->createImageResource($file['tmp_name'], $mimeType);
            if (!$sourceImage) {
                throw new Exception('Failed to create image resource');
            }
            error_log('✅ Image resource created successfully');

            // Generate unique filename
            $time = time();
            $baseFileName = 'thumb_' . $time . '_' . uniqid();
            error_log('✅ Filename generated: ' . $baseFileName);
            
            // Process thumbnail with optimal dimensions
            $targetWidth = 800;
            $targetHeight = 600;
            
            // Calculate aspect ratio preserving dimensions
            $sourceRatio = $width / $height;
            $targetRatio = $targetWidth / $targetHeight;
            
            if ($sourceRatio > $targetRatio) {
                $newWidth = $targetWidth;
                $newHeight = $targetWidth / $sourceRatio;
            } else {
                $newHeight = $targetHeight;
                $newWidth = $targetHeight * $sourceRatio;
            }

            error_log("✅ Dimensions calculated: {$newWidth}x{$newHeight}");

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            if (!$resizedImage) {
                throw new Exception('Failed to create resized image');
            }
            error_log('✅ Resized image created');

            // Preserve transparency for PNG/GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
                error_log('✅ Transparency preserved');
            }

            // Resize image
            $resizeResult = imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            if (!$resizeResult) {
                throw new Exception('Failed to resize image');
            }
            error_log('✅ Image resized successfully');

            // Try to save as WebP first, fallback to original format if not supported
            $saved = false;
            $finalFilename = '';
            $finalFilepath = '';

            if ($webpSupported) {
                $webpFilename = $baseFileName . '.webp';
                $webpFilepath = $uploadDir . $webpFilename;
                
                $quality = 85;  // Good quality for thumbnails
                error_log('Attempting to save as WebP: ' . $webpFilepath);
                if (imagewebp($resizedImage, $webpFilepath, $quality)) {
                    $finalFilename = $webpFilename;
                    $finalFilepath = $webpFilepath;
                    $saved = true;
                    error_log('✅ WebP thumbnail saved successfully: ' . $webpFilepath);
                } else {
                    error_log('❌ Failed to save WebP, will try original format');
                }
            }

            // Fallback to original format if WebP failed or not supported
            if (!$saved) {
                error_log('Attempting to save in original format: ' . $mimeType);
                switch ($mimeType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $jpegFilename = $baseFileName . '.jpg';
                        $jpegFilepath = $uploadDir . $jpegFilename;
                        if (imagejpeg($resizedImage, $jpegFilepath, 85)) {
                            $finalFilename = $jpegFilename;
                            $finalFilepath = $jpegFilepath;
                            $saved = true;
                            error_log('✅ JPEG thumbnail saved successfully: ' . $jpegFilepath);
            } else {
                            error_log('❌ Failed to save JPEG');
                        }
                        break;
                    case 'image/png':
                        $pngFilename = $baseFileName . '.png';
                        $pngFilepath = $uploadDir . $pngFilename;
                        if (imagepng($resizedImage, $pngFilepath, 6)) {
                            $finalFilename = $pngFilename;
                            $finalFilepath = $pngFilepath;
                            $saved = true;
                            error_log('✅ PNG thumbnail saved successfully: ' . $pngFilepath);
                        } else {
                            error_log('❌ Failed to save PNG');
                        }
                        break;
                    case 'image/gif':
                        $gifFilename = $baseFileName . '.gif';
                        $gifFilepath = $uploadDir . $gifFilename;
                        if (imagegif($resizedImage, $gifFilepath)) {
                            $finalFilename = $gifFilename;
                            $finalFilepath = $gifFilepath;
                            $saved = true;
                            error_log('✅ GIF thumbnail saved successfully: ' . $gifFilepath);
                        } else {
                            error_log('❌ Failed to save GIF');
                        }
                        break;
                }
            }

            if (!$saved) {
                throw new Exception('Failed to save thumbnail in any format');
            }

            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            error_log('✅ Memory cleaned up');

            // Verify file was created
            if (!file_exists($finalFilepath)) {
                throw new Exception('Thumbnail file was not created on disk');
            }

            $fileSize = filesize($finalFilepath);
            error_log('✅ Thumbnail uploaded successfully: ' . $finalFilepath . ' (Size: ' . $fileSize . ' bytes)');
            error_log('=== THUMBNAIL UPLOAD SUCCESS ===');
            
            return [
                'filepath' => $finalFilepath,
                'filename' => $finalFilename
            ];
        } catch (Exception $e) {
            error_log('=== THUMBNAIL UPLOAD ERROR ===');
            error_log('Thumbnail Processing Error: ' . $e->getMessage());
            error_log('Thumbnail Processing Error Stack: ' . $e->getTraceAsString());
            error_log('=== END ERROR ===');
            return null;
        }
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
     * Record video view
     */
    public function recordVideoView($videoId) {
        try {
            $sql = "UPDATE video_posts SET Views = Views + 1 WHERE VideoID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $videoId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Record Video View Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get related videos
     */
    public function getRelatedVideos($videoId, $categoryId, $limit = 6) {
        try {
            $sql = "SELECT v.*, c.CategoryName, cp.Username, cp.DisplayName 
                    FROM video_posts v 
                    LEFT JOIN video_categories c ON v.CategoryID = c.CategoryID 
                    LEFT JOIN creator_profiles cp ON v.ProfileID = cp.ProfileID 
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
            error_log('Get Related Videos Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all video categories
     */
    public function getAllCategories() {
        try {
            $sql = "SELECT c.CategoryID, c.CategoryName, c.Description FROM video_categories c WHERE c.isDeleted = 'notDeleted' ORDER BY c.CategoryName";
            $stmt = $this->con->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            return $categories;
        } catch (Exception $e) {
            error_log('Error getting video categories: ' . $e->getMessage());
            return [];
        }
    }
}
?>
