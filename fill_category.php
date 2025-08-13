<?php include_once "backend/php/includes/header_category.php"; ?>

<body>
    <?php include_once "backend/php/includes/header_nav_category.php"; ?>


    <div class="ncontainer nspace-top">
        <section class="weekly-articles ruler-meter  n-bottom-100">

            <h2 class="restrictNav" style="padding-top: 20px;">Weekly Articles</h2>


            <div class="display-flex">

                <div class="n-bottom-50">
                    <div class="weekly-trend">

                        <?php

                        $records_per_page = 15;

                        // Get the current page or set it to 1
                        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $offset = ($page - 1) * $records_per_page;



                        $get_article_data = mysqli_query($con, "SELECT * FROM `article`
                                    INNER JOIN category ON category.CategoryID = article.CategoryID
                                    INNER JOIN admin ON admin.AdminId = article.AdminId
                                    WHERE `article`.`CategoryID` = '$gp_category' AND `Published` = 'published' ORDER BY `article`.`Last_updated` DESC LIMIT $records_per_page OFFSET $offset");


                        // Get the total number of records
                        $total_query = "SELECT COUNT(*) AS total FROM article
                    INNER JOIN category ON category.CategoryID = article.CategoryID
                    WHERE `article`.`CategoryID` = '$gp_category'";
                        $total_result = mysqli_query($con, $total_query);
                        $total_row = mysqli_fetch_assoc($total_result);
                        $total_records = $total_row['total'];


                        // Calculate total pages
                        $total_pages = ceil($total_records / $records_per_page);


                        // Range of pages to display around the current page
                        $range = 2;

                        if (mysqli_num_rows($get_article_data) > 0) {
                            while ($row_article = mysqli_fetch_assoc($get_article_data)) {
                        ?>
                                <a href="article/<?= $row_article['ArticleID']; ?>/<?= urlencode($row_article['Article_link']); ?>" class="card">
                                    <div class="card-img"><img
                                            src="gp-admin/admin/images/uploaded/<?= $row_article['Image']; ?>"
                                            srcset="gp-admin/admin/images/uploaded/<?= $row_article['Image']; ?> 1200w, 
                    gp-admin/admin/images/uploaded/<?= $row_article['Image']; ?> 800w, 
                    gp-admin/admin/images/uploaded/<?= $row_article['Image']; ?> 400w"
                                            sizes="(max-width: 600px) 400px, 
                   (max-width: 1000px) 800px, 
                   1200px"
                                            alt="<?= $row_article['Title'] ?>"
                                            class="img-wrapper"
                                            loading="lazy">
                                        <span class="category-badge"><?= $row_article['Category'] ?></span>
                                    </div>
                                    <div class="card-body">
                                        <p class="date"><?= date('d M Y', strtotime($row_article['Last_updated'])); ?></p>
                                        <p class="title"><?= $row_article['Title']; ?></p>
                                        <p class="nflex-content n-top-20 articleAuthor">
                                            <span><i class="fa-solid fa-clock"></i><?= date('d M Y', strtotime($row_article['Created_at'])); ?></span>
                                            <span><i class="fa-solid fa-user"></i><?= $row_article['FirstName'] . ' ' . $row_article['LastName'] ?></span>
                                        </p>
                                    </div>
                                </a>
                        <?php
                            }
                        } else {
                            echo "<span class='text-muted'>No articles found, please try other category.</span>";
                        }

                        ?>

                    </div>

                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li><a href="<?= $_SERVER['PHP_SELF']; ?>?gp_category=<?= $gp_category; ?>&page=1" class="rounded">«</a></li>
                                <li><a href="<?= $_SERVER['PHP_SELF']; ?>?gp_category=<?= $gp_category; ?>&page=<?php echo $page - 1; ?>">‹</a></li>
                            <?php else: ?>
                                <li><a class="disabled rounded">«</a></li>
                                <li><a class="disabled">‹</a></li>
                            <?php endif; ?>

                            <?php
                            if ($page > $range + 1) {
                                echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?gp_category=' . $gp_category . '&page=1">1</a></li>';
                                echo '<li><a class="dots disabled">...</a></li>';
                            }

                            for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
                                echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?gp_category=' . $gp_category . '&page=' . $i . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a></li>';
                            }

                            if ($page < $total_pages - $range) {
                                echo '<li><a class="dots disabled">...</a></li>';
                                echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?gp_category=' . $gp_category . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li><a href="<?= $_SERVER['PHP_SELF']; ?>?gp_category=<?= $gp_category; ?>&page=<?php echo $page + 1; ?>">›</a></li>
                                <li><a href="<?= $_SERVER['PHP_SELF']; ?>?gp_category=<?= $gp_category; ?>&page=<?php echo $total_pages; ?>" class="rounded">»</a></li>
                            <?php else: ?>
                                <li><a class="disabled">›</a></li>
                                <li><a class="disabled rounded">»</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="ads-content">
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

                    $gp_category = $_GET['gp_category'];
                    $related_topics = mysqli_query($con, "SELECT * FROM `article`
                    WHERE `CategoryID` = '$gp_category'
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

                    <div class="trending-articles">
                        <h3 class="trending-title">Trending News</h3>

                        <div class="trending-content">
                            <?php
                            $trending_article = mysqli_query($con, "SELECT * FROM `article`
                            INNER JOIN view_logs ON view_logs.ArticleID = article.ArticleID
                            ORDER BY article.Trending_score DESC LIMIT 5");
                            while ($row_tre = mysqli_fetch_assoc($trending_article)) {
                            ?>
                                <a href="article/<?= $row_tre['ArticleID']; ?>/<?= urlencode($row_tre['Article_link']); ?>"><?= $row_tre['Title']; ?> <i class="text-muted nflex-content"><span>Trending: <?= $row_tre['Trending_score']; ?></span><span>Views: <?= $row_tre['Views']; ?></span></i></a>
                            <?php
                            }
                            ?>
                        </div>

                    </div>

                    <div class="first-list-cont">

                        <header class="left-header">
                            <span>Articles</span>
                        </header>

                        <div class="list-articles-content">
                            <?php

                            $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                    INNER JOIN category ON category.CategoryID = article.CategoryID
                                    INNER JOIN admin ON admin.AdminId = article.AdminId
                                    WHERE article.Published = 'published'
                                    ORDER BY article.ArticleID DESC LIMIT 8");


                            if (mysqli_num_rows($query_category_article2) > 0) {
                                while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                    $title = $row_category_article2['Title'];

                                    $trim_title_lenght = (strlen($title) > 60) ? substr($title, 0, 60) . '...' : $title;
                            ?>
                                    <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="list-div-left">
                                        <div class="div-img-left"><img
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
                                        <div class="div-content-left">
                                            <span class="categorydiv"><?= $row_category_article2['Category']; ?></span>
                                            <p class="titlediv"><?= $trim_title_lenght; ?></p>
                                        </div>
                                    </a>
                            <?php
                                }
                            } else {
                                echo "No new updates available";
                            }

                            ?>
                        </div>
                    </div>


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

            </div>
        </section>


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

    <?php include_once "backend/php/includes/footer_category.php"; ?>


    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->


    <script src="assets/js/script.js" defer></script>
</body>

</html>