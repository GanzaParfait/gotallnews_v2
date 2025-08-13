<?php

include "header/top_inner.php";

$f_r_id = $_POST['f_r_id'];
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


$date = $_POST['date'];
$date = mysqli_real_escape_string($con, $_POST['date']);
$description = mysqli_real_escape_string($con, $_POST['description']);

$update = mysqli_query($con, "UPDATE `article` SET `CategoryID`='$category_id',`Title`='$title',
            `Article_link`='$seo_url',`Content`='$description', Created_at = NOW() WHERE `ArticleID`='$f_r_id'");

if ($update) {
    echo "An article updated successfully!";
} else {
    die("Denied" . mysqli_error($con));
}

if (isset($_FILES['image'])) {
    $file_name = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];

    $img_explode = explode('.', $file_name);
    $img_extension = strtolower(end($img_explode));

    $extensions = ['png', 'jpeg', 'jpg', 'gif', 'webp'];

    if (in_array($img_extension, $extensions) === true) {
        $time = time();
        $new_file_name = $time . '_gpnews_' . $file_name;

        if (move_uploaded_file($tmp_name, '../images/uploaded/' . $new_file_name)) {
            $update = mysqli_query($con, "UPDATE `article` SET `CategoryID`='$category_id',`Title`='$title',
            `Article_link`='$seo_url',`Image`='$new_file_name',`Content`='$description', Last_updated = NOW() WHERE `ArticleID`='$f_r_id'");

            if ($update) {
                echo "An article updated successfully!";
            } else {
                die("Denied" . mysqli_error($con));
            }
        } else {
            die("File not uploaded" . mysqli_error($con));
        }
    }
}
