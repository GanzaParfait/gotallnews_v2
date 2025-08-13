<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="keywords"
        content="gotall news, global news, sports, entertainment, politics, technology, health, business, GotAll News, Got all news, Got all, Amakuru, Amakuru agezweho">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/headerIcon/favicon-32x32.png">
    <title>GotAll News | Content Links</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding: 10px 120px;
        }

        .count {
            font-size: 18px;
            margin-right: 4px;
        }
    </style>
</head>

<body>
    <h1>> GotAll News</h1>
    <br>
    <h2>BookMarks</h2>
    <p>What to explore over here, no maybe something different. Don't Hesitate!</p>
    <br><br>
    <p>Home - <a href="https://gotallnews.com/" target="_blank">GotAllNews</a></p>
    <br><br>


    <?php
    include "backend/php/config.php";
    $get_all_articles = mysqli_query($con, "SELECT * FROM `article`");

    $c = 1;
    // Loop through all articles and add them to the sitemap
    while ($row = mysqli_fetch_assoc($get_all_articles)) {
        $article_id = $row['ArticleID'];
        $article_link = $row['Article_link'];
        $url = "https://gotallnews.com/article/$article_id/$article_link";
        $lastmod = date('Y-m-d', strtotime($row['Last_updated']));

    ?>
        <p class="content"><span class="count"><?= $c++ . '.'; ?></span><a href="<?= $url; ?>" target="_blank"><?= $url; ?></a> - <b>GotAllNews</b></p>
        <br>
    <?php
    }
    ?>

    <br><br>
    <p><a href="https://gotallnews.com/" target="_blank">GotAllNews</a> © Copyright 2024 | All Rights Reserved.</p>

</body>

</html>