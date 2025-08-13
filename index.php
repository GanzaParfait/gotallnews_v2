<?php

include_once "backend/php/includes/header.php";

?>

<body>

    <?php include_once "backend/php/includes/header_nav.php"; ?>



    <div class="ncontainer nspace-top">
        <?php

        if (isset($_GET['page'])) {
            $validate_page = $_GET['page'];

            if ($validate_page == 1 || $validate_page < 2) {
                include "welcom_content.php";
            }
        } else {
            include "welcom_content.php";
        }

        ?>

    </div>

    <section class="improvement-container ruler-meter" <?= (isset($_GET['page']) && $_GET['page'] != 1) ? 'restrictNav_two' : '' ?>>

        <div class="ncontainer">

            <div class="big-post-card-header">
                <div class="first-big-post-card-header">
                    <p class="its-title">Most Topics</p>
                    <p class="its-sub-title">The latest news updates from around the world, including politics, sports, technology, business, health, and entertainment.</p>
                </div>
                <div class="last-big-post-card-header">
                    <a href="latest-news" class="nbtn nbtn-radius"><i class="fa-solid fa-tasks"></i> More Topics</a>
                </div>
            </div>

            <div class="gpp-card">
                <div class="post-card-container">
                    <div class="post-card">
                        <a href="category/technology" class="header-card">
                            Technology >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published' AND category.Category = 'technology' OR category.Category = 'technologies'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "No new updates available";
                        }

                        ?>
                    </div>

                    <div class="post-card">
                        <a href="category/health" class="header-card">
                            Health >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published' AND category.Category = 'health' OR category.Category = 'healthy'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "No new updates available";
                        }

                        ?>
                    </div>

                    <div class="post-card">
                        <a href="category/entertainment" class="header-card">
                            Entertainment >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published' AND category.Category = 'entertainment' OR category.Category = 'entertainmenties'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "No new updates available";
                        }

                        ?>
                    </div>

                    <div class="post-card">
                        <a href="category/sports" class="header-card">
                            Sports >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published' AND category.Category = 'sports' OR category.Category = 'sport'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "No new updates available";
                        }

                        ?>
                    </div>

                    <div class="post-card">
                        <a href="category/politics" class="header-card">
                            Politics >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published' AND category.Category = 'politics' OR category.Category = 'politic'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "No new updates available";
                        }

                        ?>
                    </div>

                    <div class="post-card">
                        <a href="latest-news" class="header-card">
                            Latest News >
                        </a>

                        <?php
                        $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId
                                        WHERE article.Published = 'published'
                                        ORDER BY article.ArticleID DESC LIMIT 5");


                        if (mysqli_num_rows($query_category_article2) > 0) {
                            while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                $title = $row_category_article2['Title'];
                                $article_content = $row_category_article2['Content'];

                                $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;


                                // Step 1: Calculate word count
                                $word_count = str_word_count(strip_tags($article_content));

                                // Step 2: Determine reading time (average 200 words per minute)
                                $reading_speed = 300; // You can adjust this if you want a different speed
                                $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
                        ?>
                                <div class="flex-post-card">
                                    <div class="first-post-card">
                                        <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="sub-post-article"><?= $trim_title_lenght; ?></a>

                                        <div class="date-posted nflex-content text-muted">
                                            <p><?= $reading_time; ?> min read</p>
                                            <p class="inner-dot"></p>
                                            <a href="<?= $row_category_article2['ChatLink']; ?>" class="author-name">By <?= $row_category_article2['FirstName'] . ' ' . $row_category_article2['LastName'] ?></a>
                                        </div>
                                    </div>
                                    <div class="last-post-card skeleton">
                                        <img
                                            src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 800w, 
                                                        gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                                                    (max-width: 1000px) 800px, 
                                                    1200px"
                                            alt="<?= $row_category_article2['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
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
            </div>


            <div class="sub-comment-section-container">
                <div class="comment-section-home">
                    <header class="home-comment-header">Comments</header>

                    <div class="flex-comm-newsletter">
                        <div class="comm-container">
                            <div class="sub-comm-container">
                                <?php
                                $query = mysqli_query($con, "SELECT * FROM `comment`
                                                INNER JOIN users ON users.UserId = comment.UserId
                                                INNER JOIN article ON comment.ArticleId = article.ArticleId
                                                ORDER BY `comment`.`Id` DESC LIMIT 4");



                                if (mysqli_num_rows($query) > 0) {
                                    while ($row = mysqli_fetch_assoc($query)) {
                                        $comment_id_track = $row['Id'];
                                        $title = $row['Title'];
                                        $trim_title_lenght = (strlen($title) > 75) ? substr($title, 0, 75) . '...' : $title;

                                        $comment = $row['Comment'];
                                        $trim_comment_lenght = (strlen($comment) > 45) ? substr($comment, 0, 45) . '...' : $comment;


                                        $calculate_total_replies = mysqli_query($con, "SELECT * FROM `comment_reply` WHERE `Id` = '$comment_id_track'");
                                        $total_replies = mysqli_num_rows($calculate_total_replies);

                                        if ($total_replies > 1) {
                                            $keyW = 'Replies';
                                        } else {
                                            $keyW = 'Reply';
                                        }
                                ?>
                                        <div class="comm-home-card">
                                            <div class="comm-img">
                                                <img src="<?= $row['PictureUrl']; ?>" alt="<?= $row['Title']; ?>">
                                            </div>
                                            <div class="comm-content">
                                                <p class="comm-user-name nflex-content" style="align-items: center;"><?= $row['FullName']; ?> - <span class="text-muted" style="font-size: 12px;"><?= $total_replies; ?> <?= $keyW; ?></span></p>
                                                <div class="article-msg">
                                                    <a href="article/<?= $row['ArticleID']; ?>/<?= urlencode($row['Article_link']); ?>" class="comm-artile"><?= $trim_title_lenght; ?></a>
                                                    <p class="comm-msg"><?= $trim_comment_lenght; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                    }
                                } else {
                                    $output .= "<span class='text-muted'>Let's talk about it, Be the first one 👋!</span>";
                                }

                                ?>
                            </div>
                            <div class="latest-content">

                                <?php
                                $query_left_article = mysqli_query($con, "SELECT * FROM `article`
                                        INNER JOIN category ON category.CategoryID = article.CategoryID
                                        INNER JOIN view_logs ON view_logs.ArticleID = article.ArticleID
                                        INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
                                        ORDER BY article.Last_updated DESC LIMIT 1");
                                $row_left_article = mysqli_fetch_assoc($query_left_article);
                                $description = strip_tags($row_left_article['Content']);
                                $trim_description_lenght = (strlen($description) > 120) ? substr($description, 0, 120) . '...' : $description;

                                $title = $row_left_article['Title'];

                                $trim_title_lenght = (strlen($title) > 75) ? substr($title, 0, 75) . '...' : $title;
                                ?>

                                <div class="first-latest">
                                    <div class="categorydiv"><?= $row_left_article['Category']; ?></div>
                                    <a href="article/<?= $row_left_article['ArticleID']; ?>/<?= urlencode($row_left_article['Article_link']); ?>" class="title-latest underline-hover"><?= $trim_title_lenght; ?></a>
                                    <p class="description-latest"><?= $trim_description_lenght; ?></p>
                                </div>
                                <div class="last-latest">
                                    <img
                                        src="gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?>"
                                        srcset="gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 1000w, 
                                                gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 600w, 
                                                gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?> 300w"
                                        sizes="(max-width: 600px) 300px, 
                                            (max-width: 1200px) 600px, 
                                            1000px"
                                        alt="<?= $row_left_article['Title'] ?>">
                                </div>
                            </div>
                        </div>
                        <div class="newsletter-container">
                            <p class="newsletter-title">Signup for GotAllNews</p>
                            <h1 class="newsletter-title2">Weekly Newsletter</h1>
                            <p class="newsletter-slogan">Breaking News, Latest News, News alert from around the world!</p>

                            <div id="mc_embed_signup">
                                <form
                                    action="https://gotallnews.us8.list-manage.com/subscribe/post?u=6f20e37e9f36bab938d4594c5&amp;id=3383711b48&amp;f_id=004ac3e1f0"
                                    method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate"
                                    target="_blank">
                                    <div id="mc_embed_signup_scroll" class="form-flex">
                                        <div class="mc-field-group n-footer-field">
                                            <input type="email" style="color: #444;" name="EMAIL" class="required email"
                                                id="mce-EMAIL" placeholder="Email..." required="" value="" aria-label="Email Address">
                                        </div>
                                        <div id="mce-responses" class="clear foot">
                                            <div class="response" id="mce-error-response" style="display: none;"></div>
                                            <div class="response" id="mce-success-response" style="display: none;"></div>
                                        </div>
                                        <div aria-hidden="true" style="position: absolute; left: -5000px;">
                                            /* real people should not fill this in and expect good things - do not remove this or risk form
                                            bot signups */
                                            <input type="text" name="b_6f20e37e9f36bab938d4594c5_3383711b48" tabindex="-1" value="" aria-label="Email Address">
                                        </div>
                                        <div class="optionalParent">
                                            <div class="clear foot">
                                                <button type="submit" aria-label="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button nbtn nbtn-radius">Subscribe</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <a href="privacy-policy" class="nflex-content">By Sign up, you agree to <p class="underline-hover nblue">Our Privacy Policy</p></a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </section>


    <div class="ncontainer ruler-meter viewed-list-container">
        <h1 class="viewed-title">Most Viewed</h1>

        <div class="viewed-flex-content">

            <div class="u-container">
                <?php

                $c = 1;

                $query_left_article = mysqli_query($con, "SELECT * FROM `article`
            INNER JOIN view_logs ON article.ArticleID = view_logs.ArticleID
            WHERE view_logs.Last_viewed_At >= NOW() - INTERVAL 7 DAY
            LIMIT 5");

                $toTal = mysqli_num_rows($query_left_article);

                ?>
                <h2 class="u-title u-title-viewed">Last 7 Days(<?= $toTal; ?> Articles)</h2>

                <div class="u-inner-container">

                    <?php
                    if (mysqli_num_rows($query_left_article) > 0) {
                        while ($row_tre = mysqli_fetch_assoc($query_left_article)) {
                            $title = $row_tre['Title'];

                            $trim_title_lenght = (strlen($title) > 230) ? substr($title, 0, 230) . '...' : $title;
                    ?>
                            <a href="article/<?= $row_tre['ArticleID']; ?>/<?= urlencode($row_tre['Article_link']); ?>" class="u-text u-text-viewed"><span class="number"><?= $c++; ?>. </span>
                                <span class="u-inner-text"><?= $trim_title_lenght; ?><i class="nflex-content"><span>Trending: <?= $row_tre['Trending_score']; ?></span><span>Views: <?= $row_tre['Views']; ?></span></i></span></a>
                    <?php
                        }
                    } else {
                        echo "No updates available";
                    }

                    ?>
                </div>
            </div>

            <div class="u-container">
                <?php

                $c = 1;
                $query_left_article = mysqli_query($con, "SELECT * FROM `article`
                                INNER JOIN view_logs ON article.ArticleID = view_logs.ArticleID
                                INNER JOIN category ON article.CategoryID = category.CategoryID
                                WHERE category.Category = 'health' AND view_logs.Last_viewed_At >= NOW() - INTERVAL 1 MONTH
                                LIMIT 5");

                $toTal = mysqli_num_rows($query_left_article);

                ?>
                <a href="category/health/">
                    <h2 class="u-title u-title-viewed">Last 1 Month(<?= $toTal; ?> Health Articles)</h2>
                </a>

                <div class="u-inner-container">

                    <?php
                    if (mysqli_num_rows($query_left_article) > 0) {
                        while ($row_tre = mysqli_fetch_assoc($query_left_article)) {
                            $title = $row_tre['Title'];

                            $trim_title_lenght = (strlen($title) > 230) ? substr($title, 0, 230) . '...' : $title;
                    ?>
                            <a href="article/<?= $row_tre['ArticleID']; ?>/<?= urlencode($row_tre['Article_link']); ?>" class="u-text u-text-viewed"><span class="number"><?= $c++; ?>. </span>
                                <span class="u-inner-text"><?= $trim_title_lenght; ?><i class="nflex-content"><span>Trending: <?= $row_tre['Trending_score']; ?></span><span>Views: <?= $row_tre['Views']; ?></span></i></span></a>
                    <?php
                        }
                    } else {
                        echo "No updates available";
                    }

                    ?>
                </div>
            </div>
        </div>

    </div>
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
    <?php
    include_once "backend/php/includes/footer.php";
    ?>


    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->


    <script src="assets/js/script.js" defer></script>
    <script>
        var slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n)
        }

        function currentSlide(n) {
            showSlides(slideIndex = n)
        }

        function autoShowSlides() {
            plusSlides(1)
        }
        setInterval(autoShowSlides, 3000);

        function showSlides(n) {
            var i;
            var slides = document.getElementsByClassName("mySlides");
            var dots = document.getElementsByClassName("dot");
            if (n > slides.length) {
                slideIndex = 1
            }
            if (n < 1) {
                slideIndex = slides.length
            }
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none"
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "")
            }
            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active"
        }
    </script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "url": "https://gotallnews.com",
            "logo": "https://gotallnews.com/assets/images/logo.png",
            "name": "Got All News",
            "sameAs": [
                "https://www.facebook.com/GotAllNews",
                "https://twitter.com/GotAllNews_x",
                "https://www.instagram.com/GotAllNews"
            ]
        }
    </script>
</body>

</html>