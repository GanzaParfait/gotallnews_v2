<?php

include "header/top_inner.php";


$category = ucfirst(mysqli_real_escape_string($con, $_POST['category']));
$date = mysqli_real_escape_string($con, $_POST['date']);



$check_already_exist_category = mysqli_query($con, "SELECT * FROM `category` WHERE `Category` = '$category'");

if (mysqli_num_rows($check_already_exist_category) > 0) {
    echo "Sorry! Category already exists!";
} else {

    $send_data = mysqli_query($con, "INSERT INTO `category`(`AdminId`, `Category`, `Date`)
    VALUES ('$user_uniqueid','$category','$date')");



    if ($send_data) {
        echo "New category added successfully!";
    } else {
        die("Denied" . mysqli_error($con));
    }
}
