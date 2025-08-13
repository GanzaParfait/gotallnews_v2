<?php
session_start();
include "config.php";

$phone = mysqli_real_escape_string($con, $_POST["phone"]);
$password = mysqli_real_escape_string($con, $_POST["password"]);

if (!empty($phone) && !empty($password)) {
    $sql = mysqli_query($con, "SELECT * FROM `admin` WHERE `PhoneNumber` = '$phone'");
    $row = mysqli_fetch_assoc($sql);

    if (mysqli_num_rows($sql) > 0) {
        if (password_verify($password, $row['Password'])) {
            if (isset($_POST['rememberme'])) {
                setcookie('PhoneNumber', $phone, time() + 60 * 60 * 7, "/"); // 7 days or (86400 * 30) 30 days
                setcookie('Password', $password, time() + 3600 * 2, "/"); // 2 hours
            } else {
                setcookie('Email', $phone, time() - 10, "/"); // Exprire cookie if not checked box
                setcookie('Password', $password, time() - 10, "/");
            }
            // Change user status to active now if logged in again

            $_SESSION['log_uni_id'] = $row['Unique_id'];
            echo "success";
        } else {
            echo "Wrong Password";
        }
    } else {
        echo "Incorrect Phone number";
    }
} else {
    echo "All inputs are required";
}
