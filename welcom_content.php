<div class="search-container ruler-meter restrictNav">
    <div class="search-title">
        <p>Breaking News</p><span class="text-muted">What to know nowadays, outside there is Trending Insights.</span>
    </div>

    <form action="search.php" method="get" onsubmit="return redirectToCleanUrl()">
        <div class="search-form">
            <div class="search-input">
                <input type="search" name="query" placeholder="Search any keyword...">
                <i class="fa-solid fa-search searchIcon"></i>
            </div>
            <button type="submit" aria-label="Search"><i class="fa-solid fa-caret-right"></i></button>
        </div>
    </form>

</div>

<section class="top-trending-news ruler-meter">

    <div class="trending-container">
        <div class="first-left-content">

            <?php


            $query_left_article = mysqli_query($con, "SELECT * FROM `article`
                        INNER JOIN category ON category.CategoryID = article.CategoryID
                        INNER JOIN view_logs ON view_logs.ArticleID = article.ArticleID
                        INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
                        ORDER BY article.Last_updated DESC LIMIT 8");


            if (mysqli_num_rows($query_left_article) > 0) {
                while ($row_left_article = mysqli_fetch_assoc($query_left_article)) {
                    $title = $row_left_article['Title'];

                    $trim_title_lenght = (strlen($title) > 30) ? substr($title, 0, 30) . '...' : $title;
            ?>
                    <a href="article/<?= $row_left_article['ArticleID']; ?>/<?= urlencode($row_left_article['Article_link']); ?>" class="list-content">
                        <div class="img-cont skeleton">
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
                            <div class="new-update-badge">New</div>
                        </div>
                        <div class="imgCategory">
                            <p class="categorydiv"><?= $row_left_article['Category']; ?></p>
                            <span><?= $trim_title_lenght; ?></span>
                        </div>
                    </a>
            <?php
                }
            } else {
                echo "No new updates available";
            }

            ?>

        </div>

        <div class="middle-content">
            <div class="inner-middle-content">
                <!-- Slideshow container -->
                <div class="slideshow-container">

                    <?php
                    $x = 1;
                    $query_middle_article = mysqli_query($con, "SELECT * FROM `article`
            INNER JOIN category ON category.CategoryID = article.CategoryID
            INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
            ORDER BY article.ArticleID DESC LIMIT 5");

                    while ($query_middle_article_row = mysqli_fetch_assoc($query_middle_article)) {
                    ?>
                        <div class="mySlides fade">
                            <div class="numbertext"><?= $x++; ?> / 5</div>

                            <div class="slide-img-container">
                                <img
                                    src="gp-admin/admin/images/uploaded/<?= $query_middle_article_row['Image']; ?>"
                                    srcset="gp-admin/admin/images/uploaded/<?= $query_middle_article_row['Image']; ?> 1200w, 
                                gp-admin/admin/images/uploaded/<?= $query_middle_article_row['Image']; ?> 800w, 
                                gp-admin/admin/images/uploaded/<?= $query_middle_article_row['Image']; ?> 400w"
                                    sizes="(max-width: 600px) 400px, 
                               (max-width: 1000px) 800px, 
                               1200px"
                                    alt="<?= $query_middle_article_row['Title']; ?>"
                                    class="img-wrapper"
                                    loading="lazy">
                                <div class="dis-category">
                                    <?= $query_middle_article_row['Category']; ?>
                                </div>

                                <a href="article/<?= $query_middle_article_row['ArticleID']; ?>/<?= urlencode($query_middle_article_row['Article_link']); ?>" class="text"><?= $query_middle_article_row['Title']; ?></a>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <!-- Next and previous buttons -->
                    <span class="prev" onclick="plusSlides(-1)">&#10094;</span>
                    <span class="next" onclick="plusSlides(1)">&#10095;</span>
                </div>
                <br>

                <!-- The dots/circles -->
                <div style="text-align:center">

                    <?php
                    $x = 1;
                    $query_middle_article = mysqli_query($con, "SELECT * FROM `article`
            INNER JOIN category ON category.CategoryID = article.CategoryID
            INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
            ORDER BY article.ArticleID DESC LIMIT 5");
                    while ($query_middle_article_row = mysqli_fetch_assoc($query_middle_article)) {
                    ?>
                        <span class="dot" onclick="currentSlide(<?= $x++; ?>)"></span>
                    <?php
                    }
                    ?>
                </div>
            </div>


            <div class="u-container">
                <?php

                $c = 1;
                $query_left_article = mysqli_query($con, "SELECT * FROM `article`
                                INNER JOIN category ON category.CategoryID = article.CategoryID
                                INNER JOIN view_logs ON view_logs.ArticleID = article.ArticleID
                                INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
                                ORDER BY Trending_score ASC LIMIT 10");

                $toTal = mysqli_num_rows($query_left_article);

                ?>
                <h2 class="u-title">Most Read(<?= $toTal; ?>)</h2>

                <div class="u-inner-container">

                    <?php
                    if (mysqli_num_rows($query_left_article) > 0) {
                        while ($row_left_article = mysqli_fetch_assoc($query_left_article)) {
                            $title = $row_left_article['Title'];

                            $trim_title_lenght = (strlen($title) > 230) ? substr($title, 0, 230) . '...' : $title;
                    ?>
                            <a href="article/<?= $row_left_article['ArticleID']; ?>/<?= urlencode($row_left_article['Article_link']); ?>" class="u-text"><span class="number"><?= $c++; ?>. </span>
                                <span class="u-inner-text"><?= $trim_title_lenght; ?></span></a>
                    <?php
                        }
                    } else {
                        echo "No new updates available";
                    }

                    ?>
                </div>
            </div>
        </div>

        <div class="last-right-content first-left-content">
            <?php
            $query_right_article = mysqli_query($con, "SELECT * FROM `article`
                INNER JOIN category ON category.CategoryID = article.CategoryID
                INNER JOIN admin ON admin.AdminId = article.AdminId WHERE article.Published = 'published'
                ORDER BY article.Trending_score DESC LIMIT 8");
            
            if (mysqli_num_rows($query_right_article) > 0) {
                $article_count = 0; // Track article position
                
                while ($row_right_article = mysqli_fetch_assoc($query_right_article)) {
                    $title = $row_right_article['Title'];
                    $article_content = $row_right_article['Content'];
            
                    // Step 1: Calculate word count
                    $word_count = str_word_count(strip_tags($article_content));
            
                    // Step 2: Determine reading time (average 200 words per minute)
                    $reading_speed = 300; // Adjust speed as needed
                    $reading_time = ceil($word_count / $reading_speed); // Round up to the nearest minute
            
                    $trim_title_lenght = (strlen($title) > 80) ? substr($title, 0, 80) . '...' : $title;
            
                    // Output the article
                    ?>
                    <a href="article/<?= $row_right_article['ArticleID']; ?>/<?= urlencode($row_right_article['Article_link']); ?>" class="list-content">
                        <div class="imgCategory">
                            <p class="categorydiv"><?= $row_right_article['Category']; ?></p>
                            <span><?= $trim_title_lenght; ?></span>
                            <div class="date-posted nflex-content text-muted">
                                <p><?= $reading_time; ?> min read</p>
                                <p class="inner-dot"></p>
                                <p><?= date('F d, Y', strtotime($row_right_article['Last_updated'])); ?></p>
                            </div>
                        </div>
                        <div class="img-cont skeleton">
                            <img
                                src="gp-admin/admin/images/uploaded/<?= $row_right_article['Image']; ?>"
                                srcset="gp-admin/admin/images/uploaded/<?= $row_right_article['Image']; ?> 1000w, 
                                    gp-admin/admin/images/uploaded/<?= $row_right_article['Image']; ?> 600w, 
                                    gp-admin/admin/images/uploaded/<?= $row_right_article['Image']; ?> 300w"
                                sizes="(max-width: 600px) 300px, 
                                    (max-width: 1200px) 600px, 
                                    1000px"
                                alt="<?= $row_right_article['Title'] ?>"
                                loading="lazy">
                            <div class="new-update-badge">New</div>
                        </div>
                    </a>
                    
                    <?php
                    $article_count++; // Increment the count after displaying each article
            
                    // Insert ads after every 2 articles, up to 3 ads
                    if ($article_count % 2 == 0 && $article_count <= 6) {
                        ?>
                        <?php
                    }
                }
            } else {
                echo "No new updates available";
            }
            ?>


        </div>
    </div>

</section>