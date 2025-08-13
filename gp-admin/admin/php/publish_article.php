<?php
session_start();
include "config.php";

if (!isset($_SESSION['log_uni_id'])) {
    echo "<script>alert('You must login first.')</script>";
    echo "<script>window.location.href = '../../default/login.php'</script>";
}

$article_id = $_GET['article_id'];
$de_record = mysqli_query($con, "UPDATE `article` SET `Published` = 'published' WHERE `ArticleID` = '$article_id'");

if ($de_record) {
    echo "<script>alert('An article published successfully!')</script>";
    echo "<script>window.location.href = '../up_article.php?r_id=$article_id'</script>";
} else {
    echo "<script>alert('Sorry, something went wrong in publishing an article in the system!')</script>";
    echo "<script>window.location.href = '../up_article.php?r_id=$article_id'</script>";
}
