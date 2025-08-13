<?php
session_start();
include "config.php";

if (!isset($_SESSION['log_uni_id'])) {
    echo "<script>alert('You must login first.')</script>";
    echo "<script>window.location.href = '../default/login.php'</script>";
} else {
    $selectuser = mysqli_query($con, "SELECT * FROM `admin` WHERE `Unique_id` = {$_SESSION['log_uni_id']}");
    $user = mysqli_fetch_assoc($selectuser);
    $unid = $user['Unique_id'];
    $names = $user['FirstName'];

    $user_f_name = $user['FirstName'] . ' ' . $user['LastName'];
}

$user_uniqueid = $user['AdminId'];
if ($user['Access'] == 'Revoked') {
    echo "<script>alert('No longer access to the system. For more information can ask Admin.')</script>";
    session_destroy();
    session_unset();
    header("Location: ../default/login.php");
}
