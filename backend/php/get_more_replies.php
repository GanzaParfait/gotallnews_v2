<?php

include "config.php";

$comment_id = $_GET['comment_id'];
$current_limit = $_GET['limit'];

// Fetch the additional replies starting from the current limit
$get_more_replies = mysqli_query($con, "SELECT * FROM `comment_reply`
    INNER JOIN comment ON `comment`.`Id` = `comment_reply`.`Id`
    INNER JOIN users ON `users`.`UserId` = `comment_reply`.`UserId`
    WHERE `comment_reply`.`Id` = '$comment_id'
    ORDER BY comment_reply.ReplyId ASC LIMIT $current_limit, 1");

if (mysqli_num_rows($get_more_replies) > 0) {
    while ($row_reply = mysqli_fetch_assoc($get_more_replies)) {
        // Output the additional replies
        echo '
            <div class="replied-comments">
                <header><i class="fa-solid fa-reply"></i> Replied</header>
                <div class="user-reply">
                    <div class="user-img-reply">  
                        <img src="' . $row_reply['PictureUrl'] . '" alt="user_img">
                    </div>
                    <div class="details-reply">
                        <span class="names-reply">' . $row_reply['FullName'] . '</span>
                        <span class="othtxt-reply">' . date('h:i:s', strtotime($row_reply['Replied_at'])) . '</span>
                    </div>
                </div>
                <div class="comment-reply">
                    ' . $row_reply['Reply'] . '
                </div>
            </div>
        ';
    }
}
