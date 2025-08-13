<?php
session_start();
include "config.php";

if (!isset($_SESSION['user'])) {
    $user_id = 0;
} else {
    $get_user = mysqli_query($con, "SELECT * FROM `users` WHERE `UserId` = {$_SESSION['user']}");
    $get_user_row = mysqli_fetch_assoc($get_user);
    $user_id = $get_user_row['UserId'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $CommentID = $input['CommentID']; // Get comment ID
    $action = $input['action']; // Either 'like' or 'dislike'

    // Check if the user has already liked or disliked this comment
    $check = mysqli_query($con, "SELECT * FROM `comment_like` WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");

    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $current_status = $row['Like_status'];

        if ($current_status == $action) {
            // If the user clicks the same action again, undo the like or dislike
            $update = mysqli_query($con, "UPDATE `comment_like` SET `Like_status` = 'none' WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
            // Decrement the respective count
            if ($action === 'like') {
                mysqli_query($con, "UPDATE `comment_like` SET `Like_count` = `Like_count` - 1 WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
            } else {
                mysqli_query($con, "UPDATE `comment_like` SET `Dislike_count` = GREATEST(`Dislike_count` - 1, 0) WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
            }
        } else {
            // Update to the new action
            if ($current_status == 'like') {
                // Change from like to dislike
                mysqli_query($con, "UPDATE `comment_like` SET `Like_status` = 'dislike' WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
                mysqli_query($con, "UPDATE `comment_like` SET `Like_count` = `Like_count` - 1, `Dislike_count` = `Dislike_count` + 1 WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
            } else {
                // Change from dislike to like
                mysqli_query($con, "UPDATE `comment_like` SET `Like_status` = 'like' WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
                mysqli_query($con, "UPDATE `comment_like` SET `Dislike_count` = GREATEST(`Dislike_count` - 1, 0), `Like_count` = `Like_count` + 1 WHERE `Id` = '$CommentID' AND `UserId` = '$user_id'");
            }
        }
    } else {
        // User has never liked or disliked, insert new record
        if ($action === 'like') {
            $insert = mysqli_query($con, "INSERT INTO `comment_like` (`Id`, `UserId`, `Like_status`, `Like_count`) VALUES ('$CommentID', '$user_id', '$action', 1)");
        } else {
            $insert = mysqli_query($con, "INSERT INTO `comment_like` (`Id`, `UserId`, `Like_status`, `Dislike_count`) VALUES ('$CommentID', '$user_id', '$action', 1)");
        }
    }

    // Send a response back
    echo json_encode(['success' => true]);
}
