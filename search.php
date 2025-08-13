<?php

include_once "backend/php/includes/header.php";


?>


<body>

    <?php include_once "backend/php/includes/header_nav.php"; ?>

    <?php


    $search_text = trim(mysqli_real_escape_string($con, $_GET['query']));


    ?>

    <section class="trending-to-container ruler-meter n-bottom-50" style="background-color: #fff;">


        <div class="ncontainer restrictNav">

            <div class="search-container" style="margin-top: 190px;padding: 0 10px;margin-block-end: 15px;">
                <div class="search-title">
                    <p>Results for "<?= ucfirst($search_text); ?>"</p>
                </div>

                <form action="search.php" method="get" onsubmit="return redirectToCleanUrl()">
                    <div class="search-form">
                        <div class="search-input">
                            <input type="search" name="query" <?php

                                                                if (isset($_GET['query'])) {
                                                                ?>
                                value="<?= $search_text; ?>"
                                <?php
                                                                }

                                ?> placeholder="Search any keyword...">
                            <i class="fa-solid fa-search searchIcon"></i>
                        </div>
                        <button type="submit" aria-label="Search" name="search"><i class="fa-solid fa-caret-right"></i></button>
                    </div>
                </form>

            </div>



            <div class="div-container">


                <div class="right-div-container">

                    <?php


                    $records_per_page = 8;

                    // Get the current page or set it to 1
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($page - 1) * $records_per_page;




                    $query = mysqli_query($con, "SELECT * FROM `article`
                        INNER JOIN category ON category.CategoryID = article.CategoryID
                        INNER JOIN admin ON admin.AdminId = article.AdminId
                        WHERE article.Published = 'published' AND article.Title LIKE '%$search_text%'
                        OR article.Article_link LIKE '%$search_text%'
                        OR category.Category LIKE '%$search_text%'
                        ORDER BY article.ArticleID DESC LIMIT $records_per_page OFFSET $offset");



                    // Get the total number of records
                    $total_query = "SELECT COUNT(*) AS total FROM article
                    INNER JOIN category ON category.CategoryID = article.CategoryID
                    WHERE article.Title LIKE '%$search_text%'
                    OR article.Article_link LIKE '%$search_text%'
                    OR category.Category LIKE '%$search_text%'";
                    $total_result = mysqli_query($con, $total_query);
                    $total_row = mysqli_fetch_assoc($total_result);
                    $total_records = $total_row['total'];


                    // Calculate total pages
                    $total_pages = ceil($total_records / $records_per_page);


                    // Range of pages to display around the current page
                    $range = 2;

                    if (mysqli_num_rows($query) > 0) {
                        while ($row = mysqli_fetch_assoc($query)) {
                            $title = $row['Title'];
                            $content = strip_tags($row['Content']);

                            $trim_title_lenght = (strlen($title) > 120) ? substr($title, 0, 120) . '...' : $title;
                            $trim_content_lenght = (strlen($content) > 250) ? substr($content, 0, 250) . '...' : $content;
                    ?>

                            <div class="list-div-right">
                                <div class="div-img-right">
                                    <img src="gp-admin/admin/images/uploaded/<?= $row['Image']; ?>" alt="<?= $row['Title'] ?>" class="img-wrapper">
                                </div>
                                <div class="div-content-left">
                                    <span class="categorydiv"><?= $row['Category']; ?></span>
                                    <a href="article/<?= $row['ArticleID']; ?>/<?= urlencode($row['Article_link']); ?>" class="titlediv"><?= $trim_title_lenght; ?></a>

                                    <div class="author text-muted">
                                        <p class="article-date"><i class="authoricon fa-solid fa-clock"></i> <?= date('d M Y H:m:i', strtotime($row['Created_at'])); ?></p>
                                        <a href="<?= $row['ChatLink']; ?>" class="author-name"><i class="authoricon fa-solid fa-user"></i><?= $row['FirstName'] . ' ' . $row['LastName'] ?></a>
                                    </div>

                                    <p class="contentdivright"><?= $trim_content_lenght; ?></p>
                                </div>
                            </div>

                    <?php
                        }
                    } else {
                        echo "No result against '<u><b>$search_text</b></u>'";
                    }

                    ?>
                    
                    
                    
            <div class="pagination-container">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li><a href="<?= $_SERVER['PHP_SELF']; ?>?query=<?= $search_text ?>&page=1" class="rounded">«</a></li>
                        <li><a href="<?= $_SERVER['PHP_SELF']; ?>?query=<?= $search_text ?>&page=<?php echo $page - 1; ?>">‹</a></li>
                    <?php else: ?>
                        <li><a class="disabled rounded">«</a></li>
                        <li><a class="disabled">‹</a></li>
                    <?php endif; ?>

                    <?php
                    if ($page > $range + 1) {
                        echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?query=' . $search_text . '&page=1">1</a></li>';
                        echo '<li><a class="dots disabled">...</a></li>';
                    }

                    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
                        echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?query=' . $search_text . '&page=' . $i . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a></li>';
                    }

                    if ($page < $total_pages - $range) {
                        echo '<li><a class="dots disabled">...</a></li>';
                        echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?query=' . $search_text . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <li><a href="<?= $_SERVER['PHP_SELF']; ?>?query=<?= $search_text ?>&page=<?php echo $page + 1; ?>">›</a></li>
                        <li><a href="<?= $_SERVER['PHP_SELF']; ?>?query=<?= $search_text ?>&page=<?php echo $total_pages; ?>" class="rounded">»</a></li>
                    <?php else: ?>
                        <li><a class="disabled">›</a></li>
                        <li><a class="disabled rounded">»</a></li>
                    <?php endif; ?>
                </ul>
            </div>


                </div>

                <div class="left-div-container">

                    <div class="first-list-cont">
                        <header class="left-header">
                            <span>Sports</span>
                        </header>

                        <div class="list-articles-content">
                            <?php

                            $query_category_article1 = mysqli_query($con, "SELECT * FROM `article`
                                    INNER JOIN category ON category.CategoryID = article.CategoryID
                                    INNER JOIN admin ON admin.AdminId = article.AdminId
                                    WHERE article.Published = 'published' AND category.Category = 'sports' OR category.Category = 'sport'
                                    ORDER BY article.ArticleID DESC LIMIT 8");


                            if (mysqli_num_rows($query_category_article1) > 0) {
                                while ($row_category_article1 = mysqli_fetch_assoc($query_category_article1)) {
                                    $title = $row_category_article1['Title'];

                                    $trim_title_lenght = (strlen($title) > 60) ? substr($title, 0, 60) . '...' : $title;
                            ?>
                                    <a href="article/<?= $row_category_article1['ArticleID']; ?>/<?= urlencode($row_category_article1['Article_link']); ?>" class="list-div-left">
                                        <div class="div-img-left">
                                            <img src="gp-admin/admin/images/uploaded/<?= $row_category_article1['Image']; ?>" alt="<?= $row_category_article1['Title'] ?>" class="img-wrapper" loading="lazy">
                                        </div>
                                        <div class="div-content-left">
                                            <span class="categorydiv"><?= $row_category_article1['Category']; ?></span>
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
                            <span>Technology</span>
                        </header>

                        <div class="list-articles-content">
                            <?php

                            $query_category_article2 = mysqli_query($con, "SELECT * FROM `article`
                                    INNER JOIN category ON category.CategoryID = article.CategoryID
                                    INNER JOIN admin ON admin.AdminId = article.AdminId
                                    WHERE article.Published = 'published' AND category.Category = 'technology' OR category.Category = 'technologies'
                                    ORDER BY article.ArticleID DESC LIMIT 8");


                            if (mysqli_num_rows($query_category_article2) > 0) {
                                while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                                    $title = $row_category_article2['Title'];

                                    $trim_title_lenght = (strlen($title) > 60) ? substr($title, 0, 60) . '...' : $title;
                            ?>
                                    <a href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>" class="list-div-left">
                                        <div class="div-img-left">
                                            <img src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>" alt="<?= $row_category_article2['Title'] ?>" class="img-wrapper" loading="lazy">
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
                                    <a href="category/<?= strtolower($category_row['Category']); ?>.php?gp_category=<?= $category_row['CategoryID']; ?>" class="txtLink"><?= $category_row['Category']; ?></a>
                            <?php
                                }
                            } else {
                                echo "No categories available";
                            }

                            ?>
                        </div>

                    </div>
                    
                    <!-- Advertisement -->

                </div>

            </div>

        </div>


    </section>

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
                                <img src="gp-admin/admin/images/uploaded/<?= $row_left_article['Image']; ?>" alt="<?= $row_left_article['Title'] ?>" loading="lazy">
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
</body>

</html>