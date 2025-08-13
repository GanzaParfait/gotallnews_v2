<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <!-- Document header icon -->

    <link rel="icon" href="images/headerIcon/favicon-32x32.png">



    <!-- 

        ======================== %&% ========================
            Include styles from assets repository
        ======================== %&% ========================

    -->

    <link rel="stylesheet" href="css/login.css">


    <!-- 

        ======================== %&% ========================
            Fontawesome icons
        ======================== %&% ========================

    -->

    <link rel="stylesheet" href="extensions/fontawesome/css/all.css">

    <!-- 

        ======================== %&% ========================
            Google fonts includes down here
        ======================== %&% ========================

    -->

    <!-- <link rel="stylesheet" href="Google_fonts_link_url"> -->

    <title>GP News CMS | Got new updates for you</title>
</head>

<body>

    <div class="ncontainer alignContentCenter">

        <div class="error-msg nnotification-center-small active" style="display: none;"></div>




        <div class="nform-container">
            <div class="nform-left-img">
                <img src="images/undraw_Halloween_re_2kq1.png" alt="presentaiton_img">
            </div>
            <div class="nform-content">
                <form action="#" autocomplete="off" class="loginform">

                    <header>Welcome back!</header>

                    <div class="loginWith">
                        <div>
                            <div class="imgIcon">
                                <img src="images/img_google.jpg" alt="img_google">
                            </div>
                            <div class="imgTxt">
                                Login with google
                            </div>
                        </div>
                    </div>


                    <div class="or-form-container">
                        <span>-</span>
                        <span>OR</span>
                        <span>-</span>
                    </div>

                    <!-- <div class="error-msg">
                        <p class="text-center text-danger" style="font-weight: 500;"></p>
                    </div> -->

                    <div class="form-fields">
                        <div class="nfield">
                            <input type="text" name="phone" value="<?php if (isset($_COOKIE['PhoneNumber'])) {
                                                                        echo $_COOKIE['PhoneNumber'];
                                                                    } ?>" placeholder="Email..." />
                        </div>

                        <div class="nfield">
                            <input type="password" name="password" id="password" placeholder="Password">
                            <i class="fa-solid fa-eye icon"></i>
                        </div>


                        <input type="checkbox" style="display: none;" name="rememberme" checked />
                    </div>

                    <button type="submit" class="nbtn n-full-width loginbtn">Continue</button>

                    <div class="form-bottom-content">
                        <span>
                            GP News dashboard access.
                        </span>
                        <span>
                            <a href="https://gotallnews.com">Back to the website</a>
                        </span>
                    </div>

                </form>
            </div>
        </div>



    </div>











    <!-- 

        ======================== %&% ========================
            JS Properties and related files includes
        ======================== %&% ========================

    -->


    <script src="extensions/Jquery/jquery.js"></script>
    <script src="js/password-hide-show.js"></script>
    <!-- Define backend js(Ajax) -->
    <script src="js/login.js"></script>
    <!-- End -->
</body>

</html>