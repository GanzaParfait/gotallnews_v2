<?php
session_start();
include "config.php";

if (!isset($_SESSION['log_uni_id'])) {
    echo "<script>alert('You must login first.')</script>";
    echo "<script>window.location.href = '../../default/login.php'</script>";
}

$r_id = $_GET['r_id'];
$de_record = mysqli_query($con, "DELETE FROM `category` WHERE `CategoryID` = '$r_id'");

if ($de_record) {
    echo "<script>alert('Record deleted successfully in the system!')</script>";
    echo "<script>window.location.href = '../view_category.php'</script>";
} else {
    echo "<script>alert('Sorry, something went wrong in deleting record in the system!')</script>";
    echo "<script>window.location.href = '../view_category.php'</script>";
}
