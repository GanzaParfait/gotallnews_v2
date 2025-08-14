<?php
/**
 * Creator Profile Manager Class
 * Handles all creator profile operations including CRUD, statistics, social media, and achievements
 */

class CreatorProfileManager {
    private $con;
    private $uploadDir;
    private $allowedImageTypes;
    private $maxImageSize;
    
    public function __construct($con, $uploadDir = 'images/creators/') {
        $this->con = $con;
        $this->uploadDir = $uploadDir;
        $this->allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp'];
        $this->maxImageSize = 5 * 1024 * 1024; // 5MB
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Check if required tables exist
        error_log("CreatorProfileManager: Checking required tables...");
        if (!$this->checkRequiredTables()) {
            error_log("CreatorProfileManager: Required tables check failed");
            throw new Exception("Required database tables for creator profiles do not exist. Please run the installation script first.");
        }
        error_log("CreatorProfileManager: All required tables exist, initialization successful");
    }
    
    /**
     * Check if required database tables exist
     */
    private function checkRequiredTables() {
        $requiredTables = [
            'creator_profiles',
            'creator_social_links',
            'creator_followers',
            'creator_statistics',
            'creator_achievements',
            'creator_categories'
        ];
        
        error_log("CreatorProfileManager: Checking tables: " . implode(', ', $requiredTables));
        
        foreach ($requiredTables as $table) {
            $result = $this->con->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                error_log("CreatorProfileManager: Table '$table' does not exist");
                return false;
            } else {
                error_log("CreatorProfileManager: Table '$table' exists");
            }
        }
        
        error_log("CreatorProfileManager: All required tables exist");
        return true;
    }
    
    /**
     * Create a new creator profile
     */
    public function createProfile($adminId, $data) {
        try {
            // Validate required fields - only username and displayName are required
            if (empty($data['username']) || empty($data['displayName'])) {
                throw new Exception("Username and Display Name are required");
            }
            
            // Check if username already exists
            if ($this->usernameExists($data['username'])) {
                throw new Exception("Username already exists");
            }
            
            // Check if admin already has a profile - but allow if they want to create a new one
            // This check is removed to allow admins to create profiles
            
            // Generate unique username if not provided
            if (empty($data['username'])) {
                $data['username'] = $this->generateUniqueUsername($data['displayName']);
            }
            
            $sql = "INSERT INTO creator_profiles (
                AdminId, Username, DisplayName, Bio, ProfilePhoto, CoverPhoto,
                Website, Location, Expertise, YearsExperience, IsVerified, IsFeatured
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->con->prepare($sql);
            
            // Prepare values to avoid null reference issues
            $bio = $data['bio'] ?? '';
            $profilePhoto = $data['profilePhoto'] ?? '';
            $coverPhoto = $data['coverPhoto'] ?? '';
            $website = $data['website'] ?? '';
            $location = $data['location'] ?? '';
            $expertise = $data['expertise'] ?? '';
            $yearsExperience = isset($data['yearsExperience']) && !empty($data['yearsExperience']) ? (int)$data['yearsExperience'] : 0;
            $isVerified = $data['isVerified'] ?? 0;
            $isFeatured = $data['isFeatured'] ?? 0;
            
            $stmt->bind_param("issssssssiii", 
                $adminId,
                $data['username'],
                $data['displayName'],
                $bio,
                $profilePhoto,
                $coverPhoto,
                $website,
                $location,
                $expertise,
                $yearsExperience,
                $isVerified,
                $isFeatured
            );
            
            if ($stmt->execute()) {
                $profileId = $stmt->insert_id;
                
                // Create default social links
                $this->createDefaultSocialLinks($profileId);
                
                // Create default achievements
                $this->createDefaultAchievements($profileId);
                
                // Create default statistics
                $this->createDefaultStatistics($profileId);
                
                // Update admin table with profile reference
                $this->updateAdminProfileReference($adminId, $profileId);
                
                return $profileId;
            } else {
                throw new Exception("Failed to create creator profile: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Creator Profile Creation Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update an existing creator profile
     */
    public function updateProfile($profileId, $data) {
        try {
            // Check if profile exists
            if (!$this->profileExists($profileId)) {
                throw new Exception("Creator profile not found");
            }
            
            // Validate required fields - only username and displayName are required
            if (empty($data['username']) || empty($data['displayName'])) {
                throw new Exception("Username and Display Name are required");
            }
            
            // Check username uniqueness if changing
            if (isset($data['username']) && $data['username'] !== $this->getProfileUsername($profileId)) {
                if ($this->usernameExists($data['username'])) {
                    throw new Exception("Username already exists");
                }
            }
            
            $sql = "UPDATE creator_profiles SET 
                Username = ?, DisplayName = ?, Bio = ?, ProfilePhoto = ?, CoverPhoto = ?,
                Website = ?, Location = ?, Expertise = ?, YearsExperience = ?,
                IsVerified = ?, IsFeatured = ?, Updated_at = CURRENT_TIMESTAMP
                WHERE ProfileID = ?";
            
            $stmt = $this->con->prepare($sql);
            
            // Prepare values to avoid null reference issues
            $bio = $data['bio'] ?? '';
            $profilePhoto = $data['profilePhoto'] ?? '';
            $coverPhoto = $data['coverPhoto'] ?? '';
            $website = $data['website'] ?? '';
            $location = $data['location'] ?? '';
            $expertise = $data['expertise'] ?? '';
            $yearsExperience = isset($data['yearsExperience']) && !empty($data['yearsExperience']) ? (int)$data['yearsExperience'] : 0;
            $isVerified = $data['isVerified'] ?? 0;
            $isFeatured = $data['isFeatured'] ?? 0;
            
            $stmt->bind_param("ssssssssiiii", 
                $data['username'],
                $data['displayName'],
                $bio,
                $profilePhoto,
                $coverPhoto,
                $website,
                $location,
                $expertise,
                $yearsExperience,
                $isVerified,
                $isFeatured,
                $profileId
            );
            
            if ($stmt->execute()) {
                return true;
            } else {
                throw new Exception("Failed to update creator profile: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Creator Profile Update Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get creator profile by ID
     */
    public function getProfile($profileId) {
        try {
            error_log("CreatorProfileManager: Getting profile for ID: " . $profileId);
            
            // Get the main profile data from creator_profiles table
            // Include both active and inactive profiles, but exclude deleted ones
            $sql = "SELECT * FROM creator_profiles WHERE ProfileID = ? AND isDeleted = 'notDeleted'";
            error_log("CreatorProfileManager: SQL: " . $sql);
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            error_log("CreatorProfileManager: Query executed, rows found: " . $result->num_rows);
            
            if ($result->num_rows > 0) {
                $profile = $result->fetch_assoc();
                error_log("CreatorProfileManager: Profile data retrieved: " . json_encode($profile));
                
                // Get admin information if needed
                if (!empty($profile['AdminId'])) {
                    $adminSql = "SELECT FirstName, LastName, Email, PhoneNumber, Gender FROM admin WHERE AdminId = ?";
                    $adminStmt = $this->con->prepare($adminSql);
                    $adminStmt->bind_param("i", $profile['AdminId']);
                    $adminStmt->execute();
                    $adminResult = $adminStmt->get_result();
                    
                    if ($adminResult->num_rows > 0) {
                        $adminData = $adminResult->fetch_assoc();
                        $profile['FirstName'] = $adminData['FirstName'];
                        $profile['LastName'] = $adminData['LastName'];
                        $profile['Email'] = $adminData['Email'];
                        $profile['PhoneNumber'] = $adminData['PhoneNumber'];
                        $profile['Gender'] = $adminData['Gender'];
                        error_log("CreatorProfileManager: Admin data added: " . json_encode($adminData));
                    }
                }
                
                // Get social links
                $profile['socialLinks'] = $this->getSocialLinks($profileId);
                error_log("CreatorProfileManager: Social links count: " . count($profile['socialLinks']));
                
                // Get achievements
                $profile['achievements'] = $this->getAchievements($profileId);
                error_log("CreatorProfileManager: Achievements count: " . count($profile['achievements']));
                
                // Get category expertise
                $profile['categories'] = $this->getCategoryExpertise($profileId);
                error_log("CreatorProfileManager: Categories count: " . count($profile['categories']));
                
                // Get recent statistics
                $profile['recentStats'] = $this->getRecentStatistics($profileId);
                error_log("CreatorProfileManager: Recent stats retrieved: " . json_encode($profile['recentStats']));
                
                error_log("CreatorProfileManager: Final profile data keys: " . implode(', ', array_keys($profile)));
                return $profile;
            }
            
            error_log("CreatorProfileManager: No profile found for ProfileID: " . $profileId);
            return null;
        } catch (Exception $e) {
            error_log("CreatorProfileManager: Get Creator Profile Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get creator profile by username
     */
    public function getProfileByUsername($username) {
        try {
            $sql = "SELECT ProfileID FROM creator_profiles 
                    WHERE Username = ? AND isDeleted = 'notDeleted'";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $this->getProfile($row['ProfileID']);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get Profile By Username Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get profile by AdminId
     */
    public function getProfileByAdminId($adminId) {
        try {
            $sql = "SELECT cp.*, 
                           a.FirstName, a.LastName, a.Email, a.PhoneNumber, a.Gender,
                           (SELECT COUNT(*) FROM creator_followers cf WHERE cf.FollowingID = cp.ProfileID AND cf.isDeleted = 'notDeleted') as FollowersCount,
                           (SELECT COUNT(*) FROM creator_followers cf WHERE cf.FollowerID = cp.ProfileID AND cf.isDeleted = 'notDeleted') as FollowingCount,
                           (SELECT COUNT(*) FROM article art WHERE art.AdminId = cp.AdminId AND art.Published = 'published' AND art.isDeleted = 'notDeleted') as TotalArticles,
                           (SELECT SUM(art.Views) FROM article art WHERE art.AdminId = cp.AdminId AND art.Published = 'published' AND art.isDeleted = 'notDeleted') as TotalViews
                    FROM creator_profiles cp
                    LEFT JOIN admin a ON cp.AdminId = a.AdminId
                    WHERE cp.AdminId = ? AND cp.isDeleted = 'notDeleted'";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $profile = $result->fetch_assoc();
                
                // Get social links
                $profile['socialLinks'] = $this->getSocialLinks($profile['ProfileID']);
                
                // Get achievements
                $profile['achievements'] = $this->getAchievements($profile['ProfileID']);
                
                // Get categories
                $profile['categories'] = $this->getCategories($profile['ProfileID']);
                
                // Get recent statistics
                $profile['recentStats'] = $this->getRecentStatistics($profile['ProfileID']);
                
                return $profile;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get Profile By AdminId Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get profile by ProfileID
     */
    public function getProfileByProfileId($profileId) {
        try {
            $sql = "SELECT * FROM creator_profiles WHERE ProfileID = ? AND isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param('i', $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Error getting profile by ProfileID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get profile by AdminId (for backward compatibility)
     */
    
    /**
     * Get all creator profiles with pagination
     */
    public function getAllProfiles($page = 1, $limit = 20, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE cp.isDeleted = 'notDeleted'";
            $params = [];
            $types = "";
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereClause .= " AND cp.Status = ?";
                $params[] = $filters['status'];
                $types .= "s";
            }
            
            if (!empty($filters['verified'])) {
                $whereClause .= " AND cp.IsVerified = ?";
                $params[] = $filters['verified'];
                $types .= "i";
            }
            
            if (!empty($filters['featured'])) {
                $whereClause .= " AND cp.IsFeatured = ?";
                $params[] = $filters['featured'];
                $types .= "i";
            }
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (cp.DisplayName LIKE ? OR cp.Username LIKE ? OR cp.Bio LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            // Get profiles directly from creator_profiles table
            $sql = "SELECT * FROM creator_profiles cp $whereClause
                    ORDER BY cp.IsFeatured DESC, cp.TotalViews DESC, cp.Created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            // Log the query for debugging
            error_log("Creator Profiles Query: " . $sql);
            error_log("Creator Profiles Params: " . json_encode($params));
            error_log("Creator Profiles Types: " . $types);
            
            $stmt = $this->con->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->con->error);
            }
            
            // Always bind parameters since we always have LIMIT and OFFSET
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $profiles = [];
            while ($row = $result->fetch_assoc()) {
                $profiles[] = $row;
            }
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM creator_profiles cp $whereClause";
            $countStmt = $this->con->prepare($countSql);
            
            if (!$countStmt) {
                throw new Exception("Failed to prepare count statement: " . $this->con->error);
            }
            
            // For count query, we don't need LIMIT and OFFSET parameters
            if (count($params) > 2) {
                // We have filters, bind them (excluding the last 2 which are LIMIT and OFFSET)
                $filterTypes = substr($types, 0, -2);
                $filterParams = array_slice($params, 0, -2);
                if (!empty($filterTypes)) {
                    $countStmt->bind_param($filterTypes, ...$filterParams);
                }
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            error_log("Creator Profiles Found: " . count($profiles) . " out of " . $total);
            
            return [
                'profiles' => $profiles,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log("Get All Creator Profiles Error: " . $e->getMessage());
            return ['profiles' => [], 'total' => 0, 'pages' => 0, 'current_page' => $page];
        }
    }
    
    /**
     * Soft delete a creator profile
     */
    public function deleteProfile($profileId) {
        try {
            $sql = "UPDATE creator_profiles SET 
                    isDeleted = 'deleted', 
                    Status = 'inactive',
                    Updated_at = CURRENT_TIMESTAMP 
                    WHERE ProfileID = ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            
            if ($stmt->execute()) {
                // Also deactivate social links
                $this->deactivateSocialLinks($profileId);
                return true;
            } else {
                throw new Exception("Failed to delete creator profile: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Delete Creator Profile Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restore a deleted creator profile
     */
    public function restoreProfile($profileId) {
        try {
            $sql = "UPDATE creator_profiles SET 
                    isDeleted = 'notDeleted', 
                    Status = 'active',
                    Updated_at = CURRENT_TIMESTAMP 
                    WHERE ProfileID = ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            
            if ($stmt->execute()) {
                // Reactivate social links
                $this->reactivateSocialLinks($profileId);
                return true;
            } else {
                throw new Exception("Failed to restore creator profile: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Restore Creator Profile Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add social media link
     */
    public function addSocialLink($profileId, $data) {
        try {
            $sql = "INSERT INTO creator_social_links (
                ProfileID, Platform, URL, DisplayText, Icon, OrderIndex
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->con->prepare($sql);
            
            // Prepare values to avoid null reference issues
            $displayText = $data['displayText'] ?? '';
            $icon = $data['icon'] ?? '';
            $orderIndex = $data['orderIndex'] ?? 0;
            
            $stmt->bind_param("issssi", 
                $profileId,
                $data['platform'],
                $data['url'],
                $displayText,
                $icon,
                $orderIndex
            );
            
            if ($stmt->execute()) {
                return $stmt->insert_id;
            } else {
                throw new Exception("Failed to add social link: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Add Social Link Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update social media link
     */
    public function updateSocialLink($linkId, $data) {
        try {
            $sql = "UPDATE creator_social_links SET 
                    Platform = ?, URL = ?, DisplayText = ?, Icon = ?, 
                    OrderIndex = ?, IsActive = ?, Updated_at = CURRENT_TIMESTAMP
                    WHERE LinkID = ?";
            
            $stmt = $this->con->prepare($sql);
            
            // Prepare values to avoid null reference issues
            $displayText = $data['displayText'] ?? '';
            $icon = $data['icon'] ?? '';
            $orderIndex = $data['orderIndex'] ?? 0;
            $isActive = $data['isActive'] ?? 1;
            
            $stmt->bind_param("ssssiii", 
                $data['platform'],
                $data['url'],
                $displayText,
                $icon,
                $orderIndex,
                $isActive,
                $linkId
            );
            
            if ($stmt->execute()) {
                return true;
            } else {
                throw new Exception("Failed to update social link: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Update Social Link Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete social media link
     */
    public function deleteSocialLink($linkId) {
        try {
            $sql = "DELETE FROM creator_social_links WHERE LinkID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $linkId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Delete Social Link Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Follow a creator
     */
    public function followCreator($followerId, $creatorProfileId) {
        try {
            // Check if already following
            if ($this->isFollowing($followerId, $creatorProfileId)) {
                throw new Exception("Already following this creator");
            }
            
            $sql = "INSERT INTO creator_followers (FollowerID, FollowingID) VALUES (?, ?)";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ii", $followerId, $creatorProfileId);
            
            if ($stmt->execute()) {
                return true;
            } else {
                throw new Exception("Failed to follow creator: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log("Follow Creator Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Unfollow a creator
     */
    public function unfollowCreator($followerId, $creatorProfileId) {
        try {
            $sql = "UPDATE creator_followers SET Status = 'blocked' 
                    WHERE FollowerID = ? AND FollowingID = ?";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ii", $followerId, $creatorProfileId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Unfollow Creator Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get creator articles with pagination
     */
    public function getCreatorArticles($profileId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // First try to get articles by ProfileID (new method)
            $sql = "SELECT DISTINCT a.ArticleID, a.Title, a.Article_link, a.Image, a.Content, 
                           a.Published, a.Date as PublishDate, a.Views, a.Engagement_score,
                           a.AdminId, c.Category
                    FROM article a
                    LEFT JOIN category c ON a.CategoryID = c.CategoryID
                    WHERE a.ProfileID = ? AND a.Published = 'published'
                    ORDER BY a.Date DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("iii", $profileId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $articles = [];
            while ($row = $result->fetch_assoc()) {
                $articles[] = [
                    'ArticleID' => $row['ArticleID'],
                    'Title' => $row['Title'] ?? 'Untitled Article',
                    'Article_link' => $row['Article_link'] ?? '#',
                    'Image' => $row['Image'] ?? '',
                    'Content' => $row['Content'] ?? '',
                    'Excerpt' => $this->generateExcerpt($row['Content'] ?? ''),
                    'PublishDate' => $row['PublishDate'] ?? date('Y-m-d'),
                    'Views' => $row['Views'] ?? 0,
                    'Engagement_score' => $row['Engagement_score'] ?? 0,
                    'Category' => $row['Category'] ?? 'Uncategorized'
                ];
            }
            
            // If no articles found by ProfileID, fall back to AdminId method
            if (empty($articles)) {
                $adminId = $this->getAdminIdFromProfile($profileId);
                if ($adminId) {
                    $sql = "SELECT DISTINCT a.ArticleID, a.Title, a.Article_link, a.Image, a.Content, 
                                   a.Published, a.Date as PublishDate, a.Views, a.Engagement_score,
                                   a.AdminId, c.Category
                            FROM article a
                            LEFT JOIN category c ON a.CategoryID = c.CategoryID
                            WHERE a.AdminId = ? AND a.Published = 'published'
                            ORDER BY a.Date DESC
                            LIMIT ? OFFSET ?";
                    
                    $stmt = $this->con->prepare($sql);
                    $stmt->bind_param("iii", $adminId, $limit, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $articles[] = [
                            'ArticleID' => $row['ArticleID'],
                            'Title' => $row['Title'] ?? 'Untitled Article',
                            'Article_link' => $row['Article_link'] ?? '#',
                            'Image' => $row['Image'] ?? '',
                            'Content' => $row['Content'] ?? '',
                            'Excerpt' => $this->generateExcerpt($row['Content'] ?? ''),
                            'PublishDate' => $row['PublishDate'] ?? date('Y-m-d'),
                            'Views' => $row['Views'] ?? 0,
                            'Engagement_score' => $row['Engagement_score'] ?? 0,
                            'Category' => $row['Category'] ?? 'Uncategorized'
                        ];
                    }
                }
            }
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(DISTINCT a.ArticleID) as total FROM article a
                         WHERE (a.ProfileID = ? OR a.AdminId = (SELECT AdminId FROM creator_profiles WHERE ProfileID = ?)) 
                         AND a.Published = 'published'";
            $countStmt = $this->con->prepare($countSql);
            $countStmt->bind_param("ii", $profileId, $profileId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            return [
                'articles' => $articles,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log("Get Creator Articles Error: " . $e->getMessage());
            return ['articles' => [], 'total' => 0, 'pages' => 0, 'current_page' => $page];
        }
    }
    
    /**
     * Get AdminId from ProfileID
     */
    private function getAdminIdFromProfile($profileId) {
        try {
            $sql = "SELECT AdminId FROM creator_profiles WHERE ProfileID = ? AND isDeleted = 'notDeleted'";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc()['AdminId'];
            }
            return null;
        } catch (Exception $e) {
            error_log("Get AdminId from Profile Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get creator statistics
     */
    public function getCreatorStatistics($profileId, $days = 30) {
        try {
            $sql = "SELECT Date, ArticlesPublished, TotalViews, TotalLikes, 
                           TotalComments, TotalShares, NewFollowers, EngagementRate
                    FROM creator_statistics
                    WHERE ProfileID = ? AND Date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    ORDER BY Date DESC";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("ii", $profileId, $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get Creator Statistics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto($file, $profileId) {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new Exception("No file uploaded");
            }
            
            // Validate file type
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            if (!in_array($extension, $this->allowedImageTypes)) {
                throw new Exception("Invalid file type. Allowed: " . implode(', ', $this->allowedImageTypes));
            }
            
            // Validate file size
            if ($file['size'] > $this->maxImageSize) {
                throw new Exception("File too large. Maximum size: " . ($this->maxImageSize / 1024 / 1024) . "MB");
            }
            
            // Generate unique filename
            $filename = time() . '_creator_' . $profileId . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update profile with new photo
                $this->updateProfilePhoto($profileId, $filename);
                return $filename;
            } else {
                throw new Exception("Failed to save uploaded file");
            }
        } catch (Exception $e) {
            error_log("Upload Profile Photo Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get trending creators
     */
    public function getTrendingCreators($limit = 10) {
        try {
            $sql = "SELECT cp.*,
                           COUNT(a2.ArticleID) as RecentArticles,
                           SUM(COALESCE(a2.Engagement_score, 0)) as RecentEngagement
                    FROM creator_profiles cp
                    LEFT JOIN article a2 ON cp.AdminId = a2.AdminId 
                        AND a2.Published = 'published' 
                        AND a2.Date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    WHERE cp.isDeleted = 'notDeleted' AND cp.Status = 'active'
                    GROUP BY cp.ProfileID, cp.DisplayName, cp.Username, cp.TotalViews, cp.FollowersCount
                    ORDER BY RecentEngagement DESC, cp.FollowersCount DESC
                    LIMIT ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $creators = [];
            while ($row = $result->fetch_assoc()) {
                $creators[] = $row;
            }
            
            return $creators;
        } catch (Exception $e) {
            error_log("Get Trending Creators Error: " . $e->getMessage());
            return [];
        }
    }
    
    // Private helper methods
    
    private function usernameExists($username) {
        $sql = "SELECT ProfileID FROM creator_profiles WHERE Username = ? AND isDeleted = 'notDeleted'";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    private function adminHasProfile($adminId) {
        $sql = "SELECT ProfileID FROM creator_profiles WHERE AdminId = ? AND isDeleted = 'notDeleted'";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    private function profileExists($profileId) {
        $sql = "SELECT ProfileID FROM creator_profiles WHERE ProfileID = ? AND isDeleted = 'notDeleted'";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    private function generateUniqueUsername($displayName) {
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $displayName));
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    private function getProfileUsername($profileId) {
        $sql = "SELECT Username FROM creator_profiles WHERE ProfileID = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc()['Username'] : null;
    }
    
    /**
     * Create default social links for a new profile
     */
    private function createDefaultSocialLinks($profileId) {
        try {
            $defaultLinks = [
                ['Platform' => 'twitter', 'URL' => 'https://twitter.com', 'DisplayText' => 'Twitter', 'Icon' => 'twitter', 'OrderIndex' => 1],
                ['Platform' => 'linkedin', 'URL' => 'https://linkedin.com', 'DisplayText' => 'LinkedIn', 'Icon' => 'linkedin', 'OrderIndex' => 2],
                ['Platform' => 'website', 'URL' => 'https://example.com', 'DisplayText' => 'Website', 'Icon' => 'globe', 'OrderIndex' => 3]
            ];
            
            foreach ($defaultLinks as $link) {
                $sql = "INSERT INTO creator_social_links (ProfileID, Platform, URL, DisplayText, Icon, OrderIndex, IsActive) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("issssi", 
                    $profileId, 
                    $link['Platform'], 
                    $link['URL'], 
                    $link['DisplayText'], 
                    $link['Icon'], 
                    $link['OrderIndex']
                );
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Create Default Social Links Error: " . $e->getMessage());
        }
    }
    
    /**
     * Create default achievements for a new profile
     */
    private function createDefaultAchievements($profileId) {
        try {
            $defaultAchievements = [
                ['AchievementType' => 'first_article', 'Title' => 'First Article Published', 'Description' => 'Successfully published your first article', 'Icon' => 'fas fa-star', 'AchievedDate' => date('Y-m-d')],
                ['AchievementType' => 'community_contributor', 'Title' => 'Profile Created', 'Description' => 'Your creator profile has been successfully created', 'Icon' => 'fas fa-user', 'AchievedDate' => date('Y-m-d')]
            ];
            
            foreach ($defaultAchievements as $achievement) {
                $sql = "INSERT INTO creator_achievements (ProfileID, AchievementType, Title, Description, Icon, AchievedDate, IsActive) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)";
                $stmt = $this->con->prepare($sql);
                $stmt->bind_param("isssss", 
                    $profileId, 
                    $achievement['AchievementType'],
                    $achievement['Title'], 
                    $achievement['Description'], 
                    $achievement['Icon'], 
                    $achievement['AchievedDate']
                );
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Create Default Achievements Error: " . $e->getMessage());
        }
    }
    
    /**
     * Create default statistics for a new profile
     */
    private function createDefaultStatistics($profileId) {
        try {
            $sql = "INSERT INTO creator_statistics (ProfileID, Date, ArticlesPublished, TotalViews, TotalLikes, TotalComments, TotalShares, NewFollowers, EngagementRate) 
                    VALUES (?, CURDATE(), 0, 0, 0, 0, 0, 0, 0.0)";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Create Default Statistics Error: " . $e->getMessage());
        }
    }
    
    private function addAchievement($profileId, $achievement) {
        $sql = "INSERT INTO creator_achievements (ProfileID, AchievementType, Title, Description, Icon) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("issss", $profileId, $achievement['type'], $achievement['title'], 
                         $achievement['description'], $achievement['icon']);
        $stmt->execute();
    }
    
    private function updateAdminProfileReference($adminId, $profileId) {
        // This method can be used to add a reference to the admin table if needed
        // For now, we'll just log it
        error_log("Admin $adminId now has creator profile $profileId");
    }
    
    /**
     * Get social links for a profile
     */
    public function getSocialLinks($profileId) {
        try {
            $sql = "SELECT * FROM creator_social_links WHERE ProfileID = ? AND IsActive = 1 ORDER BY OrderIndex";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $links = [];
            while ($row = $result->fetch_assoc()) {
                // Ensure all required fields have default values
                $links[] = [
                    'LinkID' => $row['LinkID'] ?? 0,
                    'Platform' => $row['Platform'] ?? 'website',
                    'URL' => $row['URL'] ?? '#',
                    'DisplayText' => $row['DisplayText'] ?? $row['Platform'] ?? 'Link',
                    'Icon' => $row['Icon'] ?? 'link',
                    'OrderIndex' => $row['OrderIndex'] ?? 0,
                    'IsActive' => $row['IsActive'] ?? 1
                ];
            }
            return $links;
        } catch (Exception $e) {
            error_log("Get Social Links Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getAchievements($profileId) {
        try {
            $sql = "SELECT * FROM creator_achievements WHERE ProfileID = ? AND IsActive = 1 ORDER BY AchievedDate DESC";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $achievements = [];
            while ($row = $result->fetch_assoc()) {
                // Ensure all required fields have default values
                $achievements[] = [
                    'AchievementID' => $row['AchievementID'] ?? 0,
                    'AchievementType' => $row['AchievementType'] ?? 'first_article',
                    'Title' => $row['Title'] ?? 'Achievement',
                    'Description' => $row['Description'] ?? 'No description available',
                    'Icon' => $row['Icon'] ?? 'star',
                    'EarnedDate' => $row['AchievedDate'] ?? date('Y-m-d'),
                    'IsActive' => $row['IsActive'] ?? 1
                ];
            }
            return $achievements;
        } catch (Exception $e) {
            error_log("Get Achievements Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getCategoryExpertise($profileId) {
        try {
            $sql = "SELECT cc.*, c.Category FROM creator_categories cc
                    JOIN category c ON cc.CategoryID = c.CategoryID
                    WHERE cc.ProfileID = ? ORDER BY cc.IsPrimary DESC, cc.ExpertiseLevel DESC";
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                // Ensure all required fields have default values
                $categories[] = [
                    'CategoryID' => $row['CategoryID'] ?? 0,
                    'CategoryName' => $row['Category'] ?? 'Unknown Category',
                    'ExpertiseLevel' => $row['ExpertiseLevel'] ?? 'beginner',
                    'IsPrimary' => $row['IsPrimary'] ?? 0,
                    'AddedDate' => $row['AddedDate'] ?? date('Y-m-d')
                ];
            }
            return $categories;
        } catch (Exception $e) {
            error_log("Get Category Expertise Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentStatistics($profileId) {
        try {
            // Get aggregated statistics for the profile
            $sql = "SELECT 
                        SUM(TotalViews) as totalViews,
                        SUM(TotalLikes) as totalLikes,
                        SUM(TotalComments) as totalComments,
                        SUM(TotalShares) as totalShares,
                        SUM(NewFollowers) as totalFollowers,
                        AVG(EngagementRate) as avgEngagement
                    FROM creator_statistics 
                    WHERE ProfileID = ?";
            
            $stmt = $this->con->prepare($sql);
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stats = $result->fetch_assoc();
                return [
                    'totalViews' => $stats['totalViews'] ?? 0,
                    'totalLikes' => $stats['totalLikes'] ?? 0,
                    'totalComments' => $stats['totalComments'] ?? 0,
                    'totalShares' => $stats['totalShares'] ?? 0,
                    'totalFollowers' => $stats['totalFollowers'] ?? 0,
                    'avgEngagement' => $stats['avgEngagement'] ?? 0
                ];
            }
            
            return [
                'totalViews' => 0,
                'totalLikes' => 0,
                'totalComments' => 0,
                'totalShares' => 0,
                'totalFollowers' => 0,
                'avgEngagement' => 0
            ];
        } catch (Exception $e) {
            error_log("Get Recent Statistics Error: " . $e->getMessage());
            return [
                'totalViews' => 0,
                'totalLikes' => 0,
                'totalComments' => 0,
                'totalShares' => 0,
                'totalFollowers' => 0,
                'avgEngagement' => 0
            ];
        }
    }
    
    private function isFollowing($followerId, $creatorProfileId) {
        $sql = "SELECT FollowID FROM creator_followers 
                WHERE FollowerID = ? AND FollowingID = ? AND Status = 'active'";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("ii", $followerId, $creatorProfileId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    private function deactivateSocialLinks($profileId) {
        $sql = "UPDATE creator_social_links SET IsActive = 0 WHERE ProfileID = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
    }
    
    private function reactivateSocialLinks($profileId) {
        $sql = "UPDATE creator_social_links SET IsActive = 1 WHERE ProfileID = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
    }
    
    private function updateProfilePhoto($profileId, $filename) {
        $sql = "UPDATE creator_profiles SET ProfilePhoto = ? WHERE ProfileID = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("si", $filename, $profileId);
        $stmt->execute();
    }

    private function generateExcerpt($content, $length = 150) {
        if (strlen($content) <= $length) {
            return $content;
        }
        $excerpt = substr($content, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        return $excerpt . '...';
    }
}
?>
