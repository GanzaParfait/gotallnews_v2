<?php include_once "backend/php/includes/header.php"; ?>

<body>
    
    <?php include_once "backend/php/includes/header_nav.php"; ?>

    <div class="ncontainer">

        <?php

        if (isset($_GET['msg'])) {
        ?>
            <div class="nnotification-center-small active"><?= $_GET['msg']; ?></div>
        <?php
        }

        ?>

        <!-- Contact us page -->

        <section class="ruler-meter n-top-50 n-bottom-100">

            <div class="contact_section restrictNav">

                <div class="contact-page">

                    <div class="contact-header-contents">
                        <h4 class="title">Contact Us</h4>
                        <p class="text">Be intouch with us for anything, as our beloved reader would prefer to say.</p>
                    </div>

                    <div class="contact-boxes">
                        <div class="box">
                            <div class="f-box inner-box">
                                <p><span class="contact-icon"><i class="fa-solid fa-location"></i></span> KN 301 Kigali,
                                    RW
                                </p>
                            </div>
                        </div>
                        <div class="box" data-aos="fade-up">
                            <div class="s-box inner-box">
                                <p><span class="contact-icon"><i class="fa-regular fa-message"></i></span>
                                    <a href="mailto:news@gotallnews.com">news@gotallnews.com</a>
                                </p>
                            </div>
                        </div>
                        <div class="box">
                            <div class="t-box inner-box">
                                <p><span class="contact-icon"><i class="fa-solid fa-phone"></i></span> <a
                                        href="tel:+250798442649">+250 798 442 649</a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-form-contents">

                        <div class="iframe-container" data-aos="fade-right">
                            <!-- <img src="assets/images/map.png" alt="map"> -->
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3987.515968028863!2d30.05699047523008!3d-1.9465602367047588!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19dca4240db7b3f5%3A0x5256fd511623ef15!2sMakuza%20Peace%20Plaza!5e0!3m2!1sen!2srw!4v1726300077907!5m2!1sen!2srw"
                                width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>

                        <div class="form-container">
                            <p class="text">Here we go again, Be intouch with us for anything, as our beloved amd professional writer
                                would
                                prefer to say. You're welcome, please! <span class="mailUs_contact">Mail Us on <a
                                        href="mailto:news@gotallnews.com">news@gotallnews.com</a></span>
                            </p>

                            <form action="backend/php/contact.php" method="post">

                                <input type="hidden" name="access_key" value="91c588eb-a8ea-418d-a41d-21c09d7aabad">
                                <div class="flex-field">

                                    <?php if (isset($_SESSION['user'])) {
                                    ?>
                                        <input type="hidden" name="user_id" value="<?= $_SESSION['user']; ?>">
                                    <?php
                                    } else {
                                    ?>
                                        <input type="hidden" name="user_id" value="0">
                                    <?php
                                    }
                                    ?>

                                    <div class="field">
                                        <input type="text" name="name" <?php if (isset($_SESSION['user'])) {
                                                                        ?>
                                            value="<?= $get_user_row['FullName']; ?>"
                                            <?php
                                                                        } ?> id="name" required>
                                        <div class="labelline">Your Name</div>
                                    </div>

                                    <div class="field">
                                        <input type="text" name="email" <?php if (isset($_SESSION['user'])) {
                                                                        ?>
                                            value="<?= $get_user_row['Email']; ?>"
                                            <?php
                                                                        } ?> id="email" required>
                                        <div class="labelline">Your Email address</div>
                                    </div>
                                </div>

                                <div class="field subject-field">
                                    <input type="text" name="subject" id="subject" required>
                                    <div class="labelline">Subject</div>
                                </div>

                                <textarea name="message" id="message" placeholder="Message" required></textarea>



                                <div class="buttons">
                                    <!-- <span class="btn_contact_msg">Sending a message...</span> -->
                                    <button type="submit" name="submit">Send</button>
                                </div>
                                <input type="checkbox" name="botcheck" class="hidden" style="display: none;">

                            </form>
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
    <?php include_once "backend/php/includes/footer.php"; ?>


    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->


    <script src="assets/js/script.js" defer></script>
</body>

</html>