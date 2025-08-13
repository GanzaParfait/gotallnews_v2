<?php

include "backend/php/config.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="keywords"
        content="news, global news, sports, entertainment, politics, technology, health, business, GotAll News, Got all news, Got all, Amakuru, Amakuru agezweho">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Document header icon -->

    <link rel="icon" href="assets/images/headerIcon/favicon-32x32.png">



    <!-- 

        ======================== %&% ========================
            Include styles from assets repository
        ======================== %&% ========================

    -->
    <base href="./">

    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="assets/css/contact.css?v=1.2">


    <!-- 

        ======================== %&% ========================
            Fontawesome icons
        ======================== %&% ========================

    -->

<link rel="stylesheet" href="assets/extensions/fontawesome/css/all.css" media="print" onload="this.media='all'">
<noscript>
    <link rel="stylesheet" href="assets/extensions/fontawesome/css/all.css">
</noscript>
     
     <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-8NDWQ8D5MZ"></script>
<!--<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2767521503352326"-->
<!--     crossorigin="anonymous"></script>-->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-8NDWQ8D5MZ');
</script>


    <!-- 

        ======================== %&% ========================
            Google fonts includes down here
        ======================== %&% ========================

    -->

    <!-- <link rel="stylesheet" href="Google_fonts_link_url"> -->

    <?php

    if (isset($_GET['gp'])) {
        $gp_id = $_GET['gp'];
        $article = mysqli_query($con, "SELECT * FROM `article` WHERE `ArticleID` = '$gp_id'");
        $article_row_header = mysqli_fetch_assoc($article);
        $header_title = $article_row_header['Title'];

    ?>
        <title><?= $header_title; ?> | Got All News | Got new updates for you</title>
        <meta name="description" content="<?= $header_title; ?>">
    <?php
    } else if (isset($_GET['query'])) {
        $search_text = mysqli_real_escape_string($con, $_GET['query']);

        $query_count_results = mysqli_query($con, "SELECT * FROM `article`
        INNER JOIN category ON category.CategoryID = article.CategoryID
        INNER JOIN admin ON admin.AdminId = article.AdminId
        WHERE article.Published = 'published' AND article.Title LIKE '%$search_text%'
        OR article.Article_link LIKE '%$search_text%'
        OR category.Category LIKE '%$search_text%'
        ORDER BY article.ArticleID DESC");

        $total_result_found = mysqli_num_rows($query_count_results);

    ?>
        <title>Search for "<?= $_GET['query']; ?>" | Total found records(<?= $total_result_found; ?>) | Got All News | Got new updates for you</title>
    <?php
    } else {
    ?>
        <title>GotAll News | Got new updates for you</title>
        <meta name="description" content="GotAll News provides the latest news updates from around the world, including politics,
        sports, technology, business, health, and entertainment. Stay updated with the latest news on Got All News.">
    <?php
    }

    ?>
    

    <script>
        let isScriptLoaded=!1;const loadOneSignal=()=>{if(!isScriptLoaded){isScriptLoaded=!0;const script=document.createElement('script');script.src='https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';script.onload=()=>{window.OneSignalDeferred.push(async function(OneSignal){await OneSignal.init({appId:"ea51f03e-f510-4e2f-b5f9-e5a6121ded93",})})};document.head.appendChild(script)}};const debounce=(func,delay)=>{let timeoutId;return function(...args){clearTimeout(timeoutId);timeoutId=setTimeout(()=>{func.apply(this,args)},delay)}};const debouncedLoadOneSignal=debounce(loadOneSignal,200);window.addEventListener('scroll',debouncedLoadOneSignal)
    </script>
</head>