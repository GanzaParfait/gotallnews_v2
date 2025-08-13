<?php
session_start();
include "../backend/php/config.php";
$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];


// Check if a user is already have an account to comment on the article

if (isset($_SESSION['user'])) {
    $get_user = mysqli_query($con, "SELECT * FROM `users` WHERE `UserId` = {$_SESSION['user']}");
    $get_user_row = mysqli_fetch_assoc($get_user);
    $user_id = $get_user_row['UserId'];


    if (!mysqli_num_rows($get_user) > 0) {
        session_destroy();
        session_unset();
    } else {
        $cookie_name = 'user_access';
        $cookie_value = $user_id;
        setcookie($cookie_name, $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true); // 30 days
    }
} else {
    $user_id = 0;
}


if (isset($_COOKIE['user_access'])) {
    $_SESSION['user'] = $_COOKIE['user_access'];
    $id = $_COOKIE['user_access'];

    $check = mysqli_query($con, "SELECT * FROM `users` WHERE `UserId` = '$id'");

    if (!mysqli_num_rows($check) > 0) {
        setcookie("user_access", "", time() - 3600, "/"); // Clear the cookie
    }
}

?>

<!-- 
    /*--------------------- Loader ------------------*\
    -->


<div class="loader">
    <div class="spinner"></div>
</div>

<script>
    // JavaScript to control the loader
    window.addEventListener('DOMContentLoaded', function() {
        // Hide the loader once the content is fully loaded
        const loader = document.querySelector('.loader');
        if (loader) {
            loader.classList.add('hidden');
        }
    });
</script>

<!--
    /*--------------------- Loader ------------------*\
    -->


<div class="su-header-content">
    <div class="inner-su-content">
        <div class="left-content">
            <div class="nflex-content">
                <a href="mailto:news@gotallnews.com" class="nflex-content"><span class="nbold"><i
                            class="fa-solid fa-inbox"></i></span>
                    news@gotallnews.com</a>

                <a href="contact" class="nflex-content mdnone">| <span class="nbold"><i
                            class="fa-solid fa-phone"></i></span>Contact us
                </a>
            </div>
        </div>
        <div class="right-content">

            <div class="follow-icons">
                <a href="https://instagram.com" aria-label="Follow us on instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="https://facebook.com" aria-label="Follow us on facebook">
                    <i class="fa-brands fa-facebook"></i>
                </a>
                <a href="https://twitter.com" aria-label="Follow us on twitter">
                    <i class="fa-brands fa-twitter"></i>
                </a>
            </div>

        </div>
    </div>
</div>


<div class="stay-top-hide-show">

    <div class="inner-header-cont">
        <div class="top-header">
            <a href="./" class="logo">
                <img src="assets/images/logo.png" width="100" height="40" alt="logo">
            </a>
            <div class="banner">
                <div class="banner1">
                    <img src="assets/images/peiryshop_s.webp" alt="ad" class="img-wrapper" loading="lazy">
                </div>
                <div class="banner2">
                    <img src="assets/images/airline.webp" alt="ad" class="img-wrapper" loading="lazy">
                </div>
            </div>

            <div class="bars-icon">
                <i class="fa-solid fa-bars-staggered"></i>
            </div>

        </div>
        <nav class="navbar">
            <ul>

                <li>
                    <span class="subIcon">Category <i class="fa-solid fa-caret-down"></i></span>
                </li>

                <li class="mdnone">
                    <span>|</span>
                </li>


                <li class="other-links">
                    <ul>
                        <li>
                            <a href="contact">Contact</a>
                        </li>

                        <li>
                            <a href="latest-news">LatestNews</a>
                        </li>

                        <?php

                        $get_categories = mysqli_query($con, "SELECT * FROM `category`");

                        while ($row = mysqli_fetch_assoc($get_categories)) {
                            $category = strtolower($row['Category']);

                            $fileName = '' . strtolower($category) . ".php"; // Convert to lowercase and create file name

                            // Define the content you want to write in each file
                            $content = '<?php
                                include "../backend/php/config.php";
                                $category_name = basename($_SERVER["REQUEST_URI"]);
                                $get_category_id = mysqli_query($con, "SELECT * FROM `category` WHERE `Category` = \'$category_name\'");
                                $get_category_id_row = mysqli_fetch_assoc($get_category_id);
                                $gp_category = intval($get_category_id_row["CategoryID"]);
                                include "../fill_category.php";
                            ?>';

                            // Create or overwrite the PHP file
                            $file = fopen($fileName, "w");

                            if ($file) {
                                // Write the content to the file
                                fwrite($file, $content);

                                // Close the file
                                fclose($file);

                                // echo "File '$fileName' created successfully.<br>";
                            } else {
                                // echo "Failed to create file for $category.<br>";
                            }

                        ?>
                            <li>
                                <a href="category/<?= $category; ?>/" class="<?= ($row['CategoryID'] == $gp_category) ? 'active' : ''; ?>"><?= $category; ?></a>
                            </li>
                        <?php
                        }

                        ?>
                    </ul>
                </li>

                <li>
                    <a href="">Home</a>
                </li>

                <li>
                    <a href="latest-news">LatestNews</a>
                </li>

                <?php

                $set_active = $_GET['gp_category'];

                $get_categories2 = mysqli_query($con, "SELECT * FROM `category`");

                while ($row2 = mysqli_fetch_assoc($get_categories2)) {
                    $category2 = strtolower($row2['Category']);
                ?>
                    <li>
                        <a href="category/<?= $category2; ?>/" class="<?= ($row2['CategoryID'] == $gp_category) ? 'active' : ''; ?>"><?= $category2; ?></a>
                    </li>
                <?php
                }

                ?>
            </ul>
        </nav>
    </div>

</div>



<div class="popup-wrapper">
    <div class="popup" id="popup">
        <div class="img-or-icon">
            <i class="fa-regular fa-newspaper"></i>
        </div>
        <div class="assign-icon"><i class="fa-regular fa-newspaper"></i></div>
        <div class="assign-icon-user"><i class="fa-regular fa-user"></i></div>
        <div class="assign-icon-inbox"><i class="fa-solid fa-inbox"></i></div>
        <div class="popup-body">
            <h2 class="popup-title">Welcome, C!</h2>
            <p class="popup-text">Get in touch in order to place your service and products on GAll News or Our Website. It's just contact us. <a href="mailto:news@gotallnews.com" class="link">news@gotallnews.com</a> <a href="contact" class="link">Contact us</a></p>
            <a href="#" class="popup-btn">Continue</a>
            <a href="#canceled" class="close-popup">Cancel</a>
        </div>
    </div>
</div>