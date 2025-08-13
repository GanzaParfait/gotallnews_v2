<?php

include "header/top_inner.php";

$category_id = $_POST['category_id'];
$title = mysqli_real_escape_string($con, $_POST['title']);


function generateSeoUrl($title)
{
    // Convert title to lowercase
    $seo_url = strtolower($title);

    // Replace any non-alphanumeric characters (except spaces) with an empty string
    $seo_url = preg_replace('/[^a-z0-9\s]/', '', $seo_url);

    // Replace multiple spaces with a single space
    $seo_url = preg_replace('/\s+/', ' ', $seo_url);

    // Replace spaces with hyphens
    $seo_url = str_replace(" ", "-", $seo_url);

    return $seo_url;
}

$seo_url = generateSeoUrl($title);


$date = mysqli_real_escape_string($con, $_POST['date']);
$content = mysqli_real_escape_string($con, $_POST['content']);

$file_name = $_FILES['image']['name'];
$tmp_name = $_FILES['image']['tmp_name'];


$img_explode = explode('.', $file_name);
$img_extension = strtolower(end($img_explode));

$extensions = ['png', 'jpeg', 'jpg', 'gif', 'webp'];

if (in_array($img_extension, $extensions) === true) {
    $time = time();
    $new_file_name = $time . '_gpnews_' . $file_name;

    if (move_uploaded_file($tmp_name, '../images/uploaded/' . $new_file_name)) {
        $new_article = mysqli_query($con, "INSERT INTO `article`(`CategoryID`, `Title`, `Article_link`, `Image`, `Content`, `Published`, `AdminId`, `Date`)
        VALUES ('$category_id','$title','$seo_url','$new_file_name','$content','draft','$user_uniqueid','$date')");

        if ($new_article) {
            echo "New article saved successfully!";
        } else {
            die("Denied" . mysqli_error($con));
        }
    } else {
        die("File not moved" . mysqli_error($con));
    }
}
