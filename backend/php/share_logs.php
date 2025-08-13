<?php
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $articleId = $input['articleId'];
    $platform = $input['platform']; // Optional: log which platform was used for sharing

    // Check if an article has ever been shared or not

    $check = mysqli_query($con, "SELECT * FROM `share_logs` WHERE `ArticleID` = '$articleId'");
    $check_row = mysqli_fetch_assoc($check);
    if (mysqli_num_rows($check) > 0) {

        function containsSubstring($sentence, $substring)
        {
            // Use stripos for a case-insensitive search or strpos for case-sensitive
            return stripos($sentence, $substring) !== false;
        }

        $sentence = $check_row['Platform'];
        $substring = $platform; // This is the substring you want to check for

        if (containsSubstring($sentence, $substring)) {
            $update_share_logs_query = mysqli_query($con, "UPDATE `share_logs` SET `Times` = Times + 1, Last_shared_at = NOW() WHERE `ArticleID` = '$articleId'");
        } else {
            $exist_platforms = $check_row['Platform'];
            $concatenate_platform = (string)$exist_platforms . ', ' . $platform;
            $update_share_logs_query = mysqli_query($con, "UPDATE `share_logs` SET `Platform` ='$concatenate_platform',`Times` = Times + 1, Last_shared_at = NOW() WHERE `ArticleID` = '$articleId'");
        }
    } else {
        // Increment share count in the articles table
        $sql = mysqli_query($con, "INSERT INTO `share_logs`(`ArticleID`, `Platform`, `Times`, `Last_shared_at`)
        VALUES ('$articleId','$platform',1, NOW())");
    }
}
