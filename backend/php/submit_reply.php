<?php
session_start();
include "config.php";

if (isset($_POST['parentId']) && isset($_POST['reply'])) {
    $parentId = $_POST['parentId'];
    $reply = mysqli_real_escape_string($con, $_POST['reply']);
    $articleId = $_POST['article_id'];
    $user_id = $_POST['user_id']; // Assuming the user is logged in

    // Insert the reply into the database
    $query = "INSERT INTO `comment_reply`(`UserId` ,`Id`, `Reply`, `Replied_at`) VALUES ('$user_id' ,'$parentId','$reply',NOW())";

    if (mysqli_query($con, $query)) {
        // echo "Reply submitted successfully!";
        echo "sent";
    } else {
        echo "Error: " . mysqli_error($con);
    }
} else {
    echo "Invalid input.";
}
