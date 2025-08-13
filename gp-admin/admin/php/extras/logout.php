<?php
session_start();
include_once "../config.php";

if (isset($_SESSION['log_uni_id'])) {
    $logout_id = mysqli_real_escape_string($con, $_GET['logout_id']);

    if (isset($logout_id)) { // if logout_id caught
        session_destroy();
        session_unset();
        header("Location: ../../../default/login.php");
    } else {
        header("Location: ../../index.php");
    }
} else {
    header("Location: ../../../default/login.php");
}

?>