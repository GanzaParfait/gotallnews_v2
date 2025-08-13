<?php
session_start();
include "config.php";

if (!isset($_SESSION['user'])) {
    $allow_access = 'disabled';
    $user_id = 0;
} else {
    $allow_access = '';
    $get_user = mysqli_query($con, "SELECT * FROM `users` WHERE `UserId` = {$_SESSION['user']}");
    $get_user_row = mysqli_fetch_assoc($get_user);
    $user_id = $get_user_row['UserId'];
}


$article_id = $_GET['gp'];
$query = mysqli_query($con, "SELECT * FROM `comment`
INNER JOIN users ON users.UserId = comment.UserId
WHERE `ArticleID` = '$article_id' ORDER BY `comment`.`Id` ASC");


$output = "";

if (mysqli_num_rows($query) > 0) {

    while ($row = mysqli_fetch_assoc($query)) {
        $comment_id_track = $row['Id'];
        $date = date('h:i', strtotime($row['Posted_at']));

        $calculate_total_replies = mysqli_query($con, "SELECT * FROM `comment_reply` WHERE `Id` = '$comment_id_track'");
        $total_replies = mysqli_num_rows($calculate_total_replies);

        $comm_id = $row['Id'];



        // Query to count total likes and dislikes for the comment
        $query_likes = mysqli_query($con, "SELECT 
                                                            SUM(Like_count) AS total_likes,
                                                            SUM(DisLike_count) AS total_dislikes
                                                        FROM comment_like 
                                                        WHERE Id = '$comm_id'");

        $query_likes_row = mysqli_fetch_assoc($query_likes);

        if ($query_likes_row) {
            $t_likes = $query_likes_row['total_likes'] ?: 0; // Use null coalescing to default to 0
            $t_dislikes = $query_likes_row['total_dislikes'] ?: 0; // Use null coalescing to default to 0
        } else {
            $t_likes = 0;
            $t_dislikes = 0;
        }



        if (!isset($_SESSION['user'])) {
            $like_comment = 'onclick="notifyUserToLogin(1)"';
            $dislike_comment = 'onclick="notifyUserToLogin(2)"';
        } else {
            $like_comment = 'onclick="likeComment(' . $row['Id'] . ')"';
            $dislike_comment = 'onclick="dislikeComment(' . $row['Id'] . ')"';
        }

        if ($total_replies > 1) {
            $keyW = 'Replies';
        } else {
            $keyW = 'Reply';
        }

        if ($total_replies > 0) {
            $show_btn = '<div class="view_reply_comments" onclick="showRepliedComm(' . $row['Id'] . ')">View all ' . $keyW . '(' . $total_replies . ')</div>';
        } else {
            $show_btn = '';
        }


        // Assign color on like or dislike icon depends on result

        $check_icon = mysqli_query($con, "SELECT * FROM `comment_like` WHERE `Id` = '$comment_id_track' AND `UserId` = '$user_id'");

        if (mysqli_num_rows($check_icon) > 0) {
            $check_icon_row = mysqli_fetch_assoc($check_icon);

            if ($check_icon_row['Like_status'] == 'like') {
                $class_to_apply_like = 'likeColor';
            } else {
                $class_to_apply_like = 'noneColor';
            }

            if ($check_icon_row['Like_status'] == 'dislike') {
                $class_to_apply_dislike = 'dislikeColor';
            } else {
                $class_to_apply_dislike = 'noneColor';
            }
        } else {
            $class_to_apply_like = 'noneColor';
            $class_to_apply_dislike = 'noneColor';
        }



        // Timestamp day's count
        $lastUpdatedAt = $row['Posted_at'];

        // Convert the last_updated_at to a DateTime object
        $lastUpdatedDate = new DateTime($lastUpdatedAt);
        $now = new DateTime(); // Current date and time

        // Calculate the difference between now and last_updated_at
        $diff = $now->diff($lastUpdatedDate);

        // Calculate the total difference in seconds
        $diffInSeconds = $now->getTimestamp() - $lastUpdatedDate->getTimestamp();

        // Format the difference in a human-readable way
        if ($diffInSeconds < 60) {
            // Less than 1 minute ago
            $last_updated = 'Just now';
        } elseif ($diffInSeconds < 3600) {
            // Less than 1 hour ago
            $minutes = floor($diffInSeconds / 60);
            $last_updated = ($minutes == 1) ? '1 minute ago' : "$minutes minutes ago";
        } elseif ($diffInSeconds < 86400) {
            // Less than 1 day ago
            $hours = floor($diffInSeconds / 3600);
            $last_updated = ($hours == 1) ? '1 hour ago' : "$hours hours ago";
        } elseif ($diff->days == 1) {
            // 1 day ago
            $last_updated = 'Yesterday';
        } elseif ($diff->days < 7) {
            // Within this week
            $last_updated = $lastUpdatedDate->format('l'); // Returns the day of the week (e.g., Monday)
        } elseif ($diff->days < 14) {
            // More than a week ago but less than 2 weeks
            $last_updated = '1 week ago';
        } elseif ($diff->days < 21) {
            // More than 2 weeks ago
            $last_updated = '2 weeks ago';
        } elseif ($diff->days < 28) {
            // More than 3 weeks ago
            $last_updated = '3 weeks ago';
        } elseif ($diff->days < 365) {
            // More than a month ago but within the same year
            $last_updated = $lastUpdatedDate->format('F d'); // Returns the month and day (e.g., October 01)
        } else {
            // More than a year ago
            $last_updated = $lastUpdatedDate->format('Y-m-d'); // Full date (e.g., 2023-09-15)
        }


        $output .= '
        
            <div class="inner-comment">
                <div class="reply-icon" onclick="showReplyForm(' . $row['Id'] . ')">
                    <i class="fa-solid fa-reply"></i>
                </div>
                <div class="user">
                    <div class="user-img">
                        <img src="' . $row['PictureUrl'] . '" alt="user_img">
                    </div>
                    <div class="details">
                        <span class="names">' . $row['FullName'] . '<i class="totalReply">(' . $total_replies . ' ' . $keyW . ')</i></span>
                        <span class="othtxt">' . $last_updated . '</span>
                    </div>
                </div>

                <div class="comment">
                    ' . $row['Comment'] . '
                    <div class="like-comment-area sub-area-color">
                        <div class="up-area" title="Like" ' . $like_comment . '>
                            <span class="total-likes">' . $t_likes . '</span>
                            <i id="like-icon-' . $row['Id'] . '" class="fa-solid fa-thumbs-up likeIcon ' . $class_to_apply_like . '"></i>
                        </div>
                        <div class="up-area" title="Dislike" ' . $dislike_comment . '>
                            <span class="total-likes">' . $t_dislikes . '</span>
                            <i id="dislike-icon-' . $row['Id'] . '" class="fa-regular fa-thumbs-down likeIcon ' . $class_to_apply_dislike . '"></i>
                        </div>
                    </div>
                </div>
                
                
                ' . $show_btn . '

                <div class="reply-section" id="reply-form-' . $row['Id'] . '">
                    <form id="testForm" onsubmit="return false">
                        <div class="reply-form">
                            <input type="hidden" id="user_id" value="' . $user_id . '">
                            <input type="hidden" id="article_id" value="' . $row['ArticleID'] . '">
                            <div class="field">
                                <textarea ' . $allow_access . ' id="reply-text-' . $row['Id'] . '" name="reply_comment" placeholder="Reply to ' . $row['FullName'] . '..." id="comment_field" required></textarea>
                            </div>

                            <div class="reply-btn-area ' . $allow_access . '">
                                <button class="reply-btn n-full-width" onclick="submitReply(' . $row['Id'] . ')">Reply</button>
                            </div>
                        </div>
                    </form>
                </div>
        
        ';

        $output .= '<div class="reply-cont-container" id="replied-comm-' . $row['Id'] . '">';

        $CommID = $row['Id'];
        $get_replies = mysqli_query($con, "SELECT * FROM `comment_reply`
        INNER JOIN comment ON `comment`.`Id` = `comment_reply`.`Id`
        INNER JOIN users ON `users`.`UserId` = `comment_reply`.`UserId`
        WHERE `comment_reply`.`Id` = '$CommID'
        ORDER BY comment_reply.ReplyId ASC");

        if (mysqli_num_rows($get_replies) > 0) {
            while ($row_reply = mysqli_fetch_assoc($get_replies)) {

                $ddd_id = $row_reply['ReplyId'];

                if (!isset($_SESSION['user'])) {
                    $like_re = 'onclick="notifyUserToLogin()"';
                    $dis_like_re = 'onclick="notifyUserToLogin()"';
                } else {
                    $like_re = 'onclick="likeRepliedComment(' . $row_reply['ReplyId'] . ')"';
                    $dis_like_re = 'onclick="dislikeRepliedComment(' . $row_reply['ReplyId'] . ')"';
                }


                // Query to count total likes and dislikes for the comment
                $query_likes = mysqli_query($con, "SELECT 
                                                                    SUM(Like_count) AS total_likes,
                                                                    SUM(DisLike_count) AS total_dislikes
                                                                FROM comment_reply_like 
                                                                WHERE ReplyId = '$ddd_id'");

                $query_likes_row = mysqli_fetch_assoc($query_likes);

                if ($query_likes_row) {
                    $t_likes = $query_likes_row['total_likes'] ?: 0; // Use null coalescing to default to 0
                    $t_dislikes = $query_likes_row['total_dislikes'] ?: 0; // Use null coalescing to default to 0
                } else {
                    $t_likes = 0;
                    $t_dislikes = 0;
                }



                // Assign color on like or dislike icon depends on result

                $check_icon = mysqli_query($con, "SELECT * FROM `comment_reply_like` WHERE `ReplyId` = '$ddd_id' AND `UserId` = '$user_id'");

                if (mysqli_num_rows($check_icon) > 0) {
                    $check_icon_row = mysqli_fetch_assoc($check_icon);

                    if ($check_icon_row['Like_status'] == 'like') {
                        $class_to_apply_like = 'likeColor';
                    } else {
                        $class_to_apply_like = 'noneColor';
                    }

                    if ($check_icon_row['Like_status'] == 'dislike') {
                        $class_to_apply_dislike = 'dislikeColor';
                    } else {
                        $class_to_apply_dislike = 'noneColor';
                    }
                } else {
                    $class_to_apply_like = 'noneColor';
                    $class_to_apply_dislike = 'noneColor';
                }

                $date = date('h:i:s', strtotime($row_reply['Replied_at']));


                // Timestamp day's count
                $lastUpdatedAt = $row_reply['Replied_at'];

                // Convert the last_viewed_at to a DateTime object
                $lastUpdatedDate = new DateTime($lastUpdatedAt);
                $now = new DateTime(); // Current date and time

                // Calculate the difference between now and last_viewed_at
                $diff = $now->diff($lastUpdatedDate);

                // Format the difference in a human-readable way
                if ($diff->days == 0) {
                    // Same day
                    $last_updated = 'Today';
                } elseif ($diff->days == 1) {
                    // 1 day ago
                    $last_updated = 'Yesterday';
                } elseif ($diff->days < 7) {
                    // Within this week
                    $last_updated = $lastUpdatedDate->format('l'); // Returns the day of the week (e.g., Monday)
                } elseif ($diff->days < 14) {
                    // More than a week ago but less than 2 weeks
                    $last_updated = '1 week ago';
                } elseif ($diff->days < 21) {
                    // More than 2 weeks ago
                    $last_updated = '2 week ago';
                } elseif ($diff->days < 28) {
                    // More than 3 weeks ago
                    $last_updated = '3 week ago';
                } elseif ($diff->days < 365) {
                    // More than a month ago but within the same year
                    $last_updated = $lastUpdatedDate->format('F d'); // Returns the month and day (e.g., October 01)
                } else {
                    // More than a year ago
                    $last_updated = $lastUpdatedDate->format('Y-m-d'); // Full date (e.g., 2023-09-15)
                }

                $output .= '
                    <div class="replied-comments">
                        <header><i class="fa-solid fa-reply"></i> Replied</header>
        
                        <div class="user-reply">
                            <div class="user-img-reply">
                                <img src="' . $row_reply['PictureUrl'] . '" alt="user_img">
                            </div>
                            <div class="details-reply">
                                <span class="names-reply">' . $row_reply['FullName'] . '</span>
                                <span class="othtxt-reply">' . $last_updated . ' ' . $date . '</span>
                            </div>
                        </div>
        
                        <div class="comment-reply">
                            ' . $row_reply['Reply'] . '
                        </div>
                        <div class="like-comment-area">
                            <div class="up-area" title="Like" ' . $like_re . '>
                                <span class="total-likes" id="like-count-' . $row_reply['ReplyId'] . '">' . $t_likes . '</span>
                                <i class="fa-solid fa-thumbs-up likeIcon ' . $class_to_apply_like . '"></i>
                            </div>
                            <div class="up-area" title="Dislike" ' . $dis_like_re . '>
                                <span class="total-likes" id="dislike-count-' . $row_reply['ReplyId'] . '">' . $t_dislikes . '</span>
                                <i class="fa-regular fa-thumbs-down likeIcon ' . $class_to_apply_dislike . '"></i>
                            </div>
                        </div>
        
                    </div>
                ';
            }
        }


        $output .= '</div>';
        $output .= '</div>'; // Close the comment div
    }
} else {
    $output .= "<span class='text-muted'>Let's talk about it, Be the first one 👋!</span>";
}


echo $output;
