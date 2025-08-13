<section class="section_full footerBg">
    <footer class="footer ncontainer">


        <div class="footer-contents">


            <div class="footer-column">
                <div class="footer-header">
                    <p>About Us</p>
                </div>
                <p class="footer-desc">GP News provides the latest news updates from around the world, including politics,
                    sports, technology, business, health, and entertainment.
                </p>
                <p class="footer-desc" style="display: flex;gap:10px;">Call us: <a href="tel:+250798442649">+250 798 442 649</a></p>


                <div class="footer-icons">

                    <a href="https://facebook.com" aria-label="Follow us on facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>

                    <a href="https://twitter.com" aria-label="Follow us on twitter">
                        <i class="fa-brands fa-twitter"></i>
                    </a>

                    <a href="https://youtube.com" aria-label="Follow us on youtube">
                        <i class="fa-brands fa-youtube"></i>
                    </a>

                    <a href="https://instagram.com" aria-label="Follow us on instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>

                </div>
            </div>

            <div class="footer-column">

                <div class="footer-header">
                    <p>Quick Links</p>
                </div>

                <div class="footer-quick-links">

                    <div>
                        <ul>
                            <li>
                                <a href="./"><i class="fa-solid fa-caret-right"></i> Home</a>
                            </li>

                            <li>
                                <a href="contact"><i class="fa-solid fa-caret-right"></i> Contact</a>
                            </li>


                            <li>
                                <a href="#done" class="Acnbtn"><i class="fa-solid fa-caret-right"></i> Advertise</a>
                            </li>

                        </ul>
                    </div>
                    <div>
                        <ul>
                            <?php

                            $query_categories = mysqli_query($con, "SELECT * FROM `category` LIMIT 6");

                            if (mysqli_num_rows($query_categories) > 0) {
                                while ($category_row = mysqli_fetch_assoc($query_categories)) {
                            ?>
                                    <li>
                                        <a href="category/<?= strtolower($category_row['Category']); ?>/"><i class="fa-solid fa-caret-right"></i> <?= $category_row['Category']; ?></a>
                                    </li>
                            <?php
                                }
                            } else {
                                echo "";
                            }

                            ?>
                        </ul>
                    </div>

                </div>

            </div>

            <div class="footer-column">

                <div class="footer-header">
                    <p>Top Stories</p>
                </div>

                <p class="footer-desc">We are always there for you.</p>

                <div class="top-story-img">


                    <?php

                    $query_category_article2 = mysqli_query($con, "SELECT * FROM `article` ORDER BY article.Trending_score DESC LIMIT 8");


                    if (mysqli_num_rows($query_category_article2) > 0) {
                        while ($row_category_article2 = mysqli_fetch_assoc($query_category_article2)) {
                    ?>
                            <a title="<?= $row_category_article2['Title']; ?>" href="article/<?= $row_category_article2['ArticleID']; ?>/<?= urlencode($row_category_article2['Article_link']); ?>">
                                <img
                                    src="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>"
                                    srcset="gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?> 1200w, 
                                    gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>-medium 800w, 
                                    gp-admin/admin/images/uploaded/<?= $row_category_article2['Image']; ?>-small 400w"
                                    sizes="(max-width: 600px) 400px, 
                                    (max-width: 1000px) 800px, 
                                    1200px"
                                    alt="<?= $row_category_article2['Title'] ?>"
                                    loading="lazy">
                            </a>
                    <?php
                        }
                    } else {
                        echo "";
                    }

                    ?>

                </div>

            </div>

            <div class="footer-column">

                <div class="footer-header">
                    <p>Newsletter</p>
                </div>

                <p class="footer-desc">Subscribe to our newsletter to get more updates.</p>


                <div class="footer-form">

                    <div id="mc_embed_signup">
                        <form
                            action="https://gotallnews.us8.list-manage.com/subscribe/post?u=6f20e37e9f36bab938d4594c5&amp;id=3383711b48&amp;f_id=004ac3e1f0"
                            method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate"
                            target="_blank">
                            <div id="mc_embed_signup_scroll" class="form-flex">
                                <div class="mc-field-group n-footer-field">
                                    <input type="email" style="color: #ccc;" name="EMAIL" class="required email"
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
                                        <button type="submit" aria-label="Subscribe" name="subscribe" id="mc-embedded-subscribe" style="height: 40px;" class="button footer-form-btn"><i
                                                class="fa-solid fa-arrow-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


        </div>


    </footer>

    <div class="footer-bottom">
        <div class="footer-bottom-first">
            © Copyright 2024 | All Rights Reserved.
        </div>
        <div class="footer-bottom-second">
            <a href="terms">Terms of use</a>
            <a href="privacy-policy">Privacy Policy</a>
            <a href="contact">Contact</a>
        </div>
    </div>
</section>

<button id="scrollTop" title="Go to top"><i class="fa-solid fa-arrow-up"></i></button>