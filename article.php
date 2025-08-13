<?php
include_once "backend/php/includes/header.php";
$article_id = $_GET['gp'];

$check_if_article_id_exists = mysqli_query($con, "SELECT * FROM `view_logs` WHERE `ArticleID` = '$article_id'");

if (mysqli_num_rows($check_if_article_id_exists) > 0) {
    // Increment article view count If already exists in view_logs table
    $update_views_query = mysqli_query($con, "UPDATE `view_logs` SET `Views` = Views + 1, Last_viewed_at = NOW() WHERE `ArticleID` = '$article_id'");
} else {
    // echo "<script>alert('Not exists!')</script>";
    $add_article_id = mysqli_query($con, "INSERT INTO `view_logs`(`ArticleID`, `Views`)
    VALUES ('$article_id',1)");
}

// Engagement Score

$get_score = mysqli_query($con, "SELECT * FROM `settings`");

while ($get_score_row = mysqli_fetch_assoc($get_score)) {
    if ($get_score_row['Name'] === 'viewWeight') {
        $viewWeight = $get_score_row['Value'];
    }

    if ($get_score_row['Name'] === 'commentWeight') {
        $commentWeight = $get_score_row['Value'];
    }

    if ($get_score_row['Name'] === 'shareWeight') {
        $shareWeight = $get_score_row['Value'];
    }
}


$calculate_total_views = mysqli_query($con, "SELECT * FROM `view_logs` WHERE `ArticleID` = '$article_id'");
$views_row = mysqli_fetch_assoc($calculate_total_views);

if (mysqli_num_rows($calculate_total_views) > 0) {
    $views = $views_row['Views'];
} else {
    $views = 0;
}

$calculate_total_comments = mysqli_query($con, "SELECT * FROM `comment` WHERE `ArticleID` = '$article_id'");
$comments = mysqli_num_rows($calculate_total_comments);

$calculate_total_shares = mysqli_query($con, "SELECT * FROM `share_logs` WHERE `ArticleID` = '$article_id'");
$shares_row = mysqli_fetch_assoc($calculate_total_shares);

if (mysqli_num_rows($calculate_total_shares) > 0) {
    $shares = $shares_row['Times'];
} else {
    $shares = 0;
}

$engagement_score = $views * $viewWeight
    +
    $comments * $commentWeight
    +
    $shares * $shareWeight;



$ar = mysqli_query($con, "SELECT * FROM `article` WHERE `ArticleID` = '$article_id'");
$ar_row = mysqli_fetch_assoc($ar);

$timeSincePublished = time() - strtotime($ar_row['Created_at']); // Time in seconds
$decayFactor = pow(0.5, $timeSincePublished / (3600 * 24));  // Half-life decay over 24 hours

$trendingScore = $ar_row['Engagement_score'] * $decayFactor;


$report_engagement_score = mysqli_query($con, "UPDATE `article` SET `Engagement_score` = '$engagement_score',
`Trending_score` = '$trendingScore'
WHERE `ArticleID` = '$article_id'");

// echo "<script>alert('$trendingScore!')</script>";


// Structured JSON Format - Schema Markup - Fetch the necessary article data

$query_article_json = mysqli_query($con, "SELECT * FROM `article`
INNER JOIN `admin` ON `admin`.`AdminId` = `article`.`AdminId`
WHERE `article`.`ArticleID` = '$article_id'");

$article_json = mysqli_fetch_assoc($query_article_json);
$author_name = $article_json['FirstName'] . ' ' . $article_json['LastName'];

// Set variables for structured data
$title = htmlspecialchars($article_json['Title'], ENT_QUOTES);
$description = htmlspecialchars(strip_tags($article_json['Content']), ENT_QUOTES);
$author = htmlspecialchars($author_name, ENT_QUOTES);
$publish_date = date('c', strtotime($article_json['Created_at']));
$modified_date = date('c', strtotime($article_json['Last_updated']));
$image_url = "gp-admin/admin/images/uploaded/" . $article_json['Image'];

?>

<body>


    <?php include_once "backend/php/includes/header_nav.php"; ?>

    <div class="ncontainer">

        <section class="article-details ruler-meter restrictNav">


            <?php
            $get_article_desc = mysqli_query($con, "SELECT * FROM `article`
                INNER JOIN category ON category.CategoryID = article.CategoryID
                INNER JOIN admin ON admin.AdminId = article.AdminId
                WHERE `article`.`ArticleID` = '$article_id'");

            $shared = mysqli_query($con, "SELECT * FROM `share_logs` WHERE `ArticleID` = '$article_id'");
            $shared_row = mysqli_fetch_assoc($shared);

            if (mysqli_num_rows($shared) > 0) {
                $how_many_times = $shared_row['Times'];
            } else {
                $how_many_times = 0;
            }

            $article_desc_row = mysqli_fetch_assoc($get_article_desc);

            // Timestamp day's count
            $lastUpdatedAt = $article_desc_row['Last_updated'];

            // Convert the last_updated_at to a DateTime object
            $lastUpdatedDate = new DateTime($lastUpdatedAt);
            $now = new DateTime(); // Current date and time

            // Calculate the difference between now and last_updated_at
            $diff = $now->diff($lastUpdatedDate);

            // Calculate the total difference in seconds
            $diffInSeconds = $now->getTimestamp() - $lastUpdatedDate->getTimestamp();

            // Format the difference in a human-readable way
            if ($diffInSeconds < 60) {
                // Less than 1 minute ago
                $last_updated = 'Just now';
            } elseif ($diffInSeconds < 3600) {
                // Less than 1 hour ago
                $minutes = floor($diffInSeconds / 60);
                $last_updated = ($minutes == 1) ? '1 minute ago' : "$minutes minutes ago";
            } elseif ($diffInSeconds < 86400) {
                // Less than 1 day ago
                $hours = floor($diffInSeconds / 3600);
                $last_updated = ($hours == 1) ? '1 hour ago' : "$hours hours ago";
            } elseif ($diff->days == 1) {
                // 1 day ago
                $last_updated = 'Yesterday';
            } elseif ($diff->days < 7) {
                // Within this week
                $last_updated = $lastUpdatedDate->format('l'); // Returns the day of the week (e.g., Monday)
            } elseif ($diff->days < 14) {
                // More than a week ago but less than 2 weeks
                $last_updated = '1 week ago';
            } elseif ($diff->days < 21) {
                // More than 2 weeks ago
                $last_updated = '2 weeks ago';
            } elseif ($diff->days < 28) {
                // More than 3 weeks ago
                $last_updated = '3 weeks ago';
            } elseif ($diff->days < 365) {
                // More than a month ago but within the same year
                $last_updated = $lastUpdatedDate->format('F d'); // Returns the month and day (e.g., October 01)
            } else {
                // More than a year ago
                $last_updated = $lastUpdatedDate->format('Y-m-d'); // Full date (e.g., 2023-09-15)
            }
            ?>

            <div class="left-article-container">
                <h2 class="article-title fast-header">
                    <?= $article_desc_row['Title']; ?>
                </h2>
                <p style="margin-bottom: 10px;"><i><?= $article_desc_row['Category']; ?></i> > <span class="text-muted"><?= $article_desc_row['Title']; ?></span></p>

                <div class="article-img">
                    <img
                        src="gp-admin/admin/images/uploaded/<?= $article_desc_row['Image']; ?>"
                        srcset="gp-admin/admin/images/uploaded/<?= $article_desc_row['Image']; ?> 1200w, 
                    gp-admin/admin/images/uploaded/<?= $article_desc_row['Image']; ?> 800w, 
                    gp-admin/admin/images/uploaded/<?= $article_desc_row['Image']; ?> 400w"
                        sizes="(max-width: 600px) 400px, 
                   (max-width: 1000px) 800px, 
                   1200px"
                        alt="<?= $article_desc_row['Title'] ?>"
                        class="img-wrapper"
                        loading="lazy">
                </div>

                <div class="article-content n-bottom-50">

                    <div class="font-size-control" title="Font size">
                        <select id="fontSize" aria-label="Zoom">
                            <option value="small">S</option>
                            <option value="medium" selected>M</option>
                            <option value="large">L</option>
                            <option value="x-large">EL</option>
                        </select>
                    </div>

                    <div class="article-description">
                        <p class="content-text">
                            <?php
                                // Retrieve the full content
                                $content = $article_desc_row['Content'];
                                
                                // Split content by paragraphs
                                $paragraphs = explode('</p>', $content);
                                $adCode = '';
                                
                                // Initialize variable to store modified content
                                $modifiedContent = '';
                                foreach ($paragraphs as $index => $paragraph) {
                                    // Append the paragraph and close </p> tag
                                    $modifiedContent .= $paragraph . '</p>';
                                
                                    // Insert ad after the 2nd paragraph only
                                    if ($index == 1) { // Index 1 means after the 2nd paragraph
                                        $modifiedContent .= $adCode;
                                        break; // Exit the loop after inserting the ad
                                    }
                                }
                                
                                // Append remaining content after the ad
                                for ($i = $index + 1; $i < count($paragraphs); $i++) {
                                    $modifiedContent .= $paragraphs[$i] . '</p>';
                                }
                            ?>
                            <?= $modifiedContent; ?>
                        </p>
                    </div>

                </div>


                <div class="leave-msg-container">

                    <section class="author-section">
                        <p class="nflex-content">By <a target="_blank" href="<?= $article_desc_row['ChatLink']; ?>" class="text-muted"><?= $article_desc_row['FirstName'] . ' ' . $article_desc_row['LastName'] ?></a></p>
                        <p class="nflex-content">Last updated on <span class="text-muted"><?= $last_updated; ?></span></p>
                        <div class="copy-link-container">
                            <button class="copy-btn" onclick="copyLink()" title="Copy: <?= $article_desc_row['Title']; ?>">
                                <span onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Copied')"><i class="fa-solid fa-copy"></i> Copy Link</span>
                            </button>
                            <span id="copy-notification" class="copy-notification">Link Copied!</span>
                        </div>
                    </section>


                    <div class="share-cont">
                        <h4>Share: <span style="font-size: 12px;" class="text-muted">(<?= $how_many_times; ?> <?= ($how_many_times > 1) ? 'Shares' : 'Share' ?>)</span></h4>

                        <div class="share-web-icons">
                            <a onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Facebook')" title="Facebook: <?= $article_desc_row['Title']; ?>" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>">
                                <img src="assets/images/icons/facebook.png" alt="facebok_icon">
                            </a>

                            <a onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Linkeldin')" title="Linkeldin: <?= $article_desc_row['Title']; ?>" target="_blank" href="https://www.linkedin.com/sharing/share-offsite/?url=https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>">
                                <img src="assets/images/icons/linkeldin.png" alt="linkeldin_icon">
                            </a>

                            <a onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Telegram')" title="Telegram: <?= $article_desc_row['Title']; ?>" target="_blank" href="https://t.me/share/url?url=https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>">
                                <img src="assets/images/icons/telegram.png" alt="telegram_icon">
                            </a>

                            <a onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Whatsapp')" title="Whatsapp: <?= $article_desc_row['Title']; ?>" target="_blank" href="https://wa.me/?text=https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>">
                                <img src="assets/images/icons/whatsapp.png" alt="whatsapp_icon">
                            </a>

                            <a onclick="shareArticle(<?= $article_desc_row['ArticleID']; ?>, 'Twitter')" title="Twitter: <?= $article_desc_row['Title']; ?>" target="_blank" href="https://twitter.com/intent/tweet?url=https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>">
                                <img src="assets/images/icons/twitter.png" alt="twitter_icon">
                            </a>

                            <!-- Link to copy (hidden) -->
                            <input type="text" aria-label="An article link" value="https://www.gotallnews.com/article/<?= $article_desc_row['ArticleID']; ?>/<?= urlencode($article_desc_row['Article_link']); ?>" id="article-link" style="position:absolute;left:-9999px;">
                        </div>



                    </div>

                    <?php


                    $query_comment = mysqli_query($con, "SELECT * FROM `comment`
                    WHERE `comment`.`ArticleID` = '$article_id' ORDER BY `comment`.`Id` DESC");

                    $total_comments = mysqli_num_rows($query_comment);


                    ?>


                    <div class="show-comment">

                        <div class="comment-title">
                            <h4>Comments(<?= $total_comments; ?>)</h4>
                        </div>

                        <div class="show-comment-container"></div>
                        <div class="show-comment-reply-backend"></div>
                    </div>

                    <section class="fast-header n-top-50" style="padding-bottom: 20px;">
                        <p>Leave a comment</p>
                    </section>

                    <form action="#" id="comment-form">
                        <input type="hidden" name="article_id" value="<?= $article_id; ?>">
                        <input type="hidden" name="user_id" value="<?= $user_id; ?>">
                        <div class="field">
                            <textarea name="comment" <?= (!isset($_SESSION['user'])) ? 'disabled' : ''; ?> placeholder="Share your comment..." id="comment_field" required></textarea>
                        </div>

                        <div class="button-area">
                            <span style="padding-top: 10px;display: none;">Posting your comment...</span>

                            <?php

                            if (!isset($_SESSION['user'])) {
                            ?>
                                <a href="signin" class="GoogleSignIn">
                                    <div class="imgIcon">
                                        <img src="assets/images/img_google.jpg" alt="img_google">
                                    </div>
                                    <div class="imgTxt">
                                        Signin with google
                                    </div>
                                </a>
                            <?php
                            } else {
                            ?>
                                <button class="nbtn n-full-width n-top-20">Send</button>
                            <?php
                            }

                            ?>
                        </div>

                    </form>

                </div>

            </div>
            <div class="right-article-container">

                <div class="n-bottom-20">
                    <form action="search.php" method="get" onsubmit="return redirectToCleanUrl()">
                        <div class="search-form">
                            <div class="search-input">
                                <input type="search" name="query" placeholder="Search any keyword...">
                                <i class="fa-solid fa-search searchIcon"></i>
                            </div>
                            <button type="submit" name="search" aria-label="Search"><i class="fa-solid fa-caret-right"></i></button>
                        </div>
                    </form>
                </div>

                <?php
                $get_category = mysqli_query($con, "SELECT * FROM `article` WHERE ArticleID = '$article_id'");
                $get_category_row = mysqli_fetch_assoc($get_category);
                $articleCategoryId = $get_category_row['CategoryID'];

                $related_topics = mysqli_query($con, "SELECT * FROM `article`
                WHERE `CategoryID` = '$articleCategoryId' AND `ArticleID` != '$article_id'
                LIMIT 5");

                if (mysqli_num_rows($related_topics) > 0) {
                ?>
                    <div class="trending-articles">
                        <h3 class="trending-title">Related Contents</h3>

                        <div class="trending-content">
                            <?php
                            while ($row_related = mysqli_fetch_assoc($related_topics)) {
                            ?>
                                <a href="article/<?= $row_related['ArticleID']; ?>/<?= urlencode($row_related['Article_link']); ?>"><?= $row_related['Title']; ?></a>
                            <?php
                            }
                            ?>
                        </div>

                    </div>
                <?php
                }

                ?>

                <div class="first-list-cont">
                    <header class="left-header">
                        <span>Category</span>
                    </header>

                    <div class="list-articles-content autoheight">


                        <?php

                        $query_categories = mysqli_query($con, "SELECT * FROM `category`");

                        if (mysqli_num_rows($query_categories) > 0) {
                            while ($category_row = mysqli_fetch_assoc($query_categories)) {
                        ?>
                                <a href="category/<?= strtolower($category_row['Category']); ?>/" class="txtLink"><?= $category_row['Category']; ?></a>
                        <?php
                            }
                        } else {
                            echo "No categories available";
                        }

                        ?>
                    </div>

                </div>
            </div>


        </section>

    </div>

    <input type="hidden" value="<?= $article_id; ?>" id="article_id">

    <section class="top-footer-section">
        <div class="ncontainer">
            <div class="n-bottom-50">
                <header>Trending now</header><span class="text-muted">What to know nowadays, outside there is Trending Insights.</span>
            </div>
            
            <div class="flex-content-cont">

                <?php
                $query_limit_four = mysqli_query($con, "SELECT * FROM `article`
                INNER JOIN category ON category.CategoryID = article.CategoryID
                INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
                ORDER BY article.Trending_score DESC LIMIT 4");

                if (mysqli_num_rows($query_limit_four) > 0) {
                    while ($row_left_article = mysqli_fetch_assoc($query_limit_four)) {
                        $title = $row_left_article['Title'];

                        $trim_title_lenght = (strlen($title) > 75) ? substr($title, 0, 75) . '...' : $title;
                ?>
                        <div class="content-cont">
                            <div class="img">
                                <img
                                    src="gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?>"
                                    srcset="gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 1000w, 
                            gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 600w, 
                            gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 300w"
                                    sizes="(max-width: 600px) 300px, 
                           (max-width: 1200px) 600px, 
                           1000px"
                                    alt="<?= $row_left_article['Title'] ?>"
                                    loading="lazy">
                                <div class="sub-text">
                                    <div class="span-txt"><?= $row_left_article['Category']; ?></div>
                                    <a href="article/<?= $row_left_article['ArticleID']; ?>/<?= urlencode($row_left_article['Article_link']); ?>" class="title"><?= $trim_title_lenght; ?></a>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo "No new updates available";
                }
                ?>
            </div>
        </div>
    </section>

    <?php include_once "backend/php/includes/footer.php"; ?>


    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->

    <script src="assets/js/script.js" defer></script>
    <script src="assets/extensions/Jquery/jquery.js" defer></script>
    <script src="backend/javascript/comment.js" defer></script>
    <script src="backend/javascript/reply_comment.js" defer></script>
    <script src="backend/javascript/share_logs.js" defer></script>

    <script>
        document.getElementById('fontSize').addEventListener('change', function() {
            const selectedSize = this.value;
            const article = document.querySelector('.article-description');
            switch (selectedSize) {
                case 'small':
                    article.style.fontSize = '14px';
                    break;
                case 'medium':
                    article.style.fontSize = '16px';
                    break;
                case 'large':
                    article.style.fontSize = '18px';
                    break;
                case 'x-large':
                    article.style.fontSize = '20px';
                    break
            }
            localStorage.setItem('fontSizePreference', selectedSize)
        });
        window.addEventListener('load', function() {
            const savedSize = localStorage.getItem('fontSizePreference');
            if (savedSize) {
                document.getElementById('fontSize').value = savedSize;
                const article = document.querySelector('.article-description');
                switch (savedSize) {
                    case 'small':
                        article.style.fontSize = '14px';
                        break;
                    case 'medium':
                        article.style.fontSize = '16px';
                        break;
                    case 'large':
                        article.style.fontSize = '18px';
                        break;
                    case 'x-large':
                        article.style.fontSize = '20px';
                        break
                }
            }
        })
    </script>


    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "<?= $title; ?>",
            "image": [
                "<?= $image_url; ?>"
            ],
            "datePublished": "<?= $publish_date; ?>",
            "dateModified": "<?= $modified_date; ?>",
            "author": {
                "@type": "Person",
                "name": "<?= $author; ?>"
            },
            "publisher": {
                "@type": "Organization",
                "name": "Got All News",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://gotallnews.com/assets/images/logo.png"
                }
            },
            "description": "<?= $description; ?>"
        }
    </script>
</body>

</html>