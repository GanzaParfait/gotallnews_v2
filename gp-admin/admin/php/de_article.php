<?php

include "header/top_inner.php";

$r_id = $_GET['r_id'];


$get_file = mysqli_query($con, "SELECT * FROM `article` WHERE `ArticleID` = '$r_id'");
$get_file_row = mysqli_fetch_assoc($get_file);
$file_name_to_unlink = $get_file_row['Image'];


$file_path = '../images/uploaded/' . $file_name_to_unlink . '';

// echo $file_path;


// Check if file to unlink available

if (file_exists($file_path)) {
    unlink($file_path);
}

// This will prevent multiple files which are no longer needed


$de_record = mysqli_query($con, "DELETE FROM `article` WHERE `ArticleID` = '$r_id'");

if ($de_record) {
    echo "<script>alert('Record deleted successfully in the system!')</script>";
    echo "<script>window.location.href = '../view_article.php'</script>";
} else {
    echo "<script>alert('Sorry, something went wrong in deleting record in the system!')</script>";
    echo "<script>window.location.href = '../view_article.php'</script>";
}
