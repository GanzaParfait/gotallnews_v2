<?php include_once "backend/php/includes/header.php"; ?>

<body>

    <?php include_once "backend/php/includes/header_nav.php"; ?>


    <div class="ncontainer nspace-top">
        <section class="weekly-articles ruler-meter  n-bottom-100">
            <div class="display-flex restrictNav" style="padding-top: 10px;">

                <div class="privacy-container n-bottom-50">
                    <h1 class="main-title">Terms of Use</h1>

                    <h3 class="sub-main-title">Terms of Use for GotAll News</h3>
                    

                    <p class="date-pub"><b>Effective Date:</b> Oct 01, 2024</p>


                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">1.</span> Acceptance of Terms</h2>
                        <p class="text">By accessing and using GotAll News, you agree to comply with these Terms of Use and all applicable
                            laws and regulations. If you do not agree with any part of these terms, please do not use our website.</p>
                    </section>



                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">2.</span> Content</h2>
                        <p class="text">All content on GotAll News, including articles, images, graphics, and other materials, is provided
                            for informational purposes only. We strive to ensure the accuracy and completeness of the content but do not
                            guarantee it. You acknowledge that reliance on any information provided by GotAll News is at your own risk.</p>
                    </section>


                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">3.</span> User Responsibilities</h2>
                        <p class="text">As a user of GotAll News, you agree to:</p>

                        <ul>
                            <li>
                                <p class="sub-section-text">Provide accurate, current, and complete information when subscribing or commenting
                                    on our site.</p>
                            </li>

                            <li>
                                <p class="sub-section-text">Not post or share any content that is unlawful, abusive, defamatory, obscene,
                                    or otherwise objectionable.</p>
                            </li>

                            <li>
                                <p class="sub-section-text">Not engage in any activity that could damage, disable, or impair the functionality of
                                    our website or interfere with any other party's use of the website.</p>
                            </li>

                            <li>
                                <p class="sub-section-text">Comply with all applicable laws and regulations in your use of our site.</p>
                            </li>
                        </ul>
                    </section>



                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">4.</span> Intellectual Property Rights</h2>
                        <p class="text">All content and materials on GotAll News, including text, graphics, logos, and images,
                            are the property of GotAll News or its licensors and are protected by copyright, trademark, and other
                            intellectual property laws. You may not reproduce, distribute, or modify any content from our website
                            without our prior written consent.</p>
                    </section>




                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">5.</span> Third-Party Links</h2>
                        <p class="text">Our website may contain links to third-party websites for your convenience. We do not control
                            these websites and are not responsible for their content or practices. We encourage you to review the terms
                            and privacy policies of any third-party sites you visit.</p>
                    </section>




                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">6.</span> Disclaimer of Warranties</h2>
                        <p class="text">GotAll News is provided on an "as is" and "as available" basis. We make no warranties,
                            express or implied, regarding the website's operation, content, or services. To the fullest extent
                            permitted by law, we disclaim all warranties, including but not limited to implied warranties of
                            merchantability and fitness for a particular purpose.</p>
                    </section>





                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">7.</span> Limitation of Liability</h2>
                        <p class="text">In no event shall GotAll News or its affiliates be liable for any direct, indirect, incidental, special,
                            consequential, or punitive damages arising from your access to or use of our website. This includes, but is not
                            limited to, damages for loss of profits, goodwill, or data.</p>
                    </section>





                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">8.</span> Changes to Terms</h2>
                        <p class="text">We reserve the right to modify these Terms of Use at any time. Changes will be effective
                            immediately upon posting the updated terms on our website. Your continued use of GotAll News after any
                            changes signifies your acceptance of the new terms.</p>
                    </section>





                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">9.</span> Governing Law</h2>
                        <p class="text">These Terms of Use shall be governed by and construed in accordance with the laws of Rwanda,
                            and any disputes relating to these terms will be subject to the exclusive jurisdiction of the courts
                            located in Kigali, Rwanda.</p>
                    </section>



                    <section class="section-privacy">
                        <h2 class="section-header"><span class="number">10.</span> Contact Us</h2>
                        <p class="text">For any questions regarding these Terms of Use, please contact us at: <br> <span class="email textContact"><a href="mailto:news@gotallnews.com">news@gotallnews.com</a></span>
                            <span class="textContact"><a href="contact.php">Contact us</a></span>
                        <p>
                    </section>

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

                    <div class="third-ad tACenter">
                        <img src="assets/images/phone.webp" alt="phone_cover" class="img-wrapper">
                        <span class="ad-text right-align">Phone cases, Any version.</span>
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

    <?php include_once "backend/php/includes/footer.php"; ?>


    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->


    <script src="assets/js/script.js" defer></script>
</body>

</html>