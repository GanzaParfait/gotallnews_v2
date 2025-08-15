<?php
// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="left-side-bar">
    <div class="brand-logo">
        <a href="index.php">
            <img src="images/logo.png" width="200" alt="logo">
        </a>
        <div class="close-sidebar" data-toggle="left-sidebar-close">
            <i class="ion-close-round"></i>
        </div>
    </div>
    
    <div class="menu-block customscroll">
        <div class="sidebar-menu">
            <ul id="accordion-menu">
                <!-- Dashboard/Home -->
                <li class="<?= $current_page === 'index' ? 'active' : '' ?>">
                    <a href="index.php" class="dropdown-toggle no-arrow">
                        <span class="micon bi bi-house"></span>
                        <span class="mtext">Dashboard</span>
                    </a>
                </li>
                
                <!-- Content Management -->
                <li class="dropdown <?= in_array($current_page, ['new_article', 'view_article', 'up_article']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-newspaper-o" aria-hidden="true"></i></span>
                        <span class="mtext">Articles</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="new_article.php" class="<?= $current_page === 'new_article' ? 'active' : '' ?>">Create New</a></li>
                        <li><a href="view_article.php" class="<?= $current_page === 'view_article' ? 'active' : '' ?>">Manage Articles</a></li>
                        <?php
                        if ($current_page === 'up_article') {
                            echo '<li><a href="up_article.php" class="' . ($current_page === 'up_article' ? 'active' : '') . '">Update Articles</a></li>';
                        }
                        ?>
                    </ul>
                </li>
                
                <!-- Video Management -->
                <li class="dropdown <?= in_array($current_page, ['video_posts', 'video_shorts', 'video_analytics', 'video_shorts_reels', 'video_view']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-video-camera" aria-hidden="true"></i></span>
                        <span class="mtext">Videos</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="video_posts.php" class="<?= $current_page === 'video_posts' ? 'active' : '' ?>">Regular Videos</a></li>
                        <li><a href="video_shorts.php" class="<?= $current_page === 'video_shorts' ? 'active' : '' ?>">Short Videos</a></li>
                        <li><a href="video_shorts_reels.php" class="<?= $current_page === 'video_shorts_reels' ? 'active' : '' ?>">Reels View</a></li>
                        <li><a href="video_view.php" class="<?= $current_page === 'video_view' ? 'active' : '' ?>">Video Details</a></li>
                    </ul>
                </li>
                
                <!-- Creator Management -->
                <li class="dropdown <?= in_array($current_page, ['creator_profiles', 'creator_analytics', 'creator_profile_view']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-users" aria-hidden="true"></i></span>
                        <span class="mtext">Creators</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="creator_profiles.php" class="<?= $current_page === 'creator_profiles' ? 'active' : '' ?>">All Profiles</a></li>
                        <li><a href="creator_profile_view.php" class="<?= $current_page === 'creator_profile_view' ? 'active' : '' ?>">Profile Details</a></li>
                    </ul>
                </li>
                
                <!-- Category Management -->
                <li class="dropdown <?= in_array($current_page, ['new_category', 'view_category', 'up_category']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-object-ungroup" aria-hidden="true"></i></span>
                        <span class="mtext">Categories</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="new_category.php" class="<?= $current_page === 'new_category' ? 'active' : '' ?>">Create Category</a></li>
                        <li><a href="view_category.php" class="<?= $current_page === 'view_category' ? 'active' : '' ?>">Manage Categories</a></li>
                        <?php
                        if ($current_page === 'up_category') {
                            echo '<li><a href="up_category.php" class="' . ($current_page === 'up_category' ? 'active' : '') . '">Update Categories</a></li>';
                        }
                        ?>
                    </ul>
                </li>
                
                <!-- Communication -->
                <li class="<?= $current_page === 'view_received_message' ? 'active' : '' ?>">
                    <a href="view_received_message.php" class="dropdown-toggle no-arrow">
                        <span class="micon icon-copy fa fa-inbox"></span>
                        <span class="mtext">Messages</span>
                    </a>
                </li>
                
                <!-- Analytics & Reports -->
                <li class="dropdown <?= in_array($current_page, ['analytics', 'reports', 'insights']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-bar-chart" aria-hidden="true"></i></span>
                        <span class="mtext">Analytics</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="creator_analytics.php">Creator Analytics</a></li>
                        <li><a href="video_analytics.php">Video Analytics</a></li>
                        <li><a href="content_analytics.php">Content Analytics</a></li>
                        <li><a href="user_engagement.php">User Engagement</a></li>
                    </ul>
                </li>
                
                <!-- User Management -->
                <li class="dropdown <?= in_array($current_page, ['users', 'user_roles', 'permissions']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-user-secret" aria-hidden="true"></i></span>
                        <span class="mtext">Users</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="users.php">All Users</a></li>
                        <li><a href="user_roles.php">User Roles</a></li>
                        <li><a href="permissions.php">Permissions</a></li>
                        <li><a href="user_activity.php">User Activity</a></li>
                    </ul>
                </li>
                
                <!-- Content Moderation -->
                <li class="dropdown <?= in_array($current_page, ['moderation', 'reported_content', 'content_review']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-shield" aria-hidden="true"></i></span>
                        <span class="mtext">Moderation</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="reported_content.php">Reported Content</a></li>
                        <li><a href="content_review.php">Content Review</a></li>
                        <li><a href="moderation_queue.php">Moderation Queue</a></li>
                        <li><a href="moderation_logs.php">Moderation Logs</a></li>
                    </ul>
                </li>
                
                <!-- SEO & Marketing -->
                <li class="dropdown <?= in_array($current_page, ['seo', 'marketing', 'campaigns']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-bullhorn" aria-hidden="true"></i></span>
                        <span class="mtext">Marketing</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="seo_settings.php">SEO Settings</a></li>
                        <li><a href="marketing_campaigns.php">Campaigns</a></li>
                        <li><a href="social_media.php">Social Media</a></li>
                        <li><a href="email_marketing.php">Email Marketing</a></li>
                    </ul>
                </li>
                
                                            <!-- System & Tools -->
                            <li class="dropdown <?= in_array($current_page, ['system', 'tools', 'maintenance', 'performance_dashboard']) ? 'active' : '' ?>">
                                <a href="javascript:;" class="dropdown-toggle">
                                    <span class="micon"><i class="icon-copy fa fa-wrench" aria-hidden="true"></i></span>
                                    <span class="mtext">Tools</span>
                                </a>
                                <ul class="submenu">
                                    <li><a href="performance_dashboard.php" class="<?= $current_page === 'performance_dashboard' ? 'active' : '' ?>">Performance Dashboard</a></li>
                                    <li><a href="system_info.php">System Info</a></li>
                                    <li><a href="backup_restore.php">Backup & Restore</a></li>
                                    <li><a href="maintenance.php">Maintenance</a></li>
                                    <li><a href="logs.php">System Logs</a></li>
                                    <li><a href="debug_tools.php">Debug Tools</a></li>
                                </ul>
                            </li>
                
                <!-- Settings & Profile -->
                <li class="dropdown <?= in_array($current_page, ['profile', 'settings', 'preferences']) ? 'active' : '' ?>">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="micon"><i class="icon-copy fa fa-cogs" aria-hidden="true"></i></span>
                        <span class="mtext">Settings</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="profile.php" class="<?= $current_page === 'profile' ? 'active' : '' ?>">My Profile</a></li>
                        <li><a href="account_settings.php">Account Settings</a></li>
                        <li><a href="preferences.php">Preferences</a></li>
                        <li><a href="security.php">Security</a></li>
                        <li><a href="php/extras/logout.php">Log Out</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="mobile-menu-overlay"></div>
