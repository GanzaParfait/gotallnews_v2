<?php

include "config.php";

// echo "Comment";


$article_id = mysqli_real_escape_string($con, $_POST['article_id']);
$user_id = mysqli_real_escape_string($con, $_POST['user_id']);
$comment = mysqli_real_escape_string($con, $_POST['comment']);


$query = mysqli_query($con, "INSERT INTO `comment`(`UserId` ,`ArticleID`, `Comment`) VALUES ('$user_id' ,'$article_id','$comment')");


if ($query) {
    echo "Your comment posted.";
} else {
    echo "Sorry, comment not posted. Try again.";
}
