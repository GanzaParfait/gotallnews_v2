<?php
session_start();
include "config.php";

$email = mysqli_real_escape_string($con, $_POST["phone"]); // Keep 'phone' as form field name for compatibility
$password = mysqli_real_escape_string($con, $_POST["password"]);

if (!empty($email) && !empty($password)) {
    // Authenticate against creator_profiles table
    $sql = mysqli_query($con, "SELECT * FROM `creator_profiles` WHERE `Email` = '$email' AND `isDeleted` = 'notDeleted'");
    $row = mysqli_fetch_assoc($sql);

    if (mysqli_num_rows($sql) > 0) {
        if (password_verify($password, $row['Password'])) {
            // Check if user has access
            if ($row['Access'] == 'Revoked') {
                echo "Access revoked. Please contact administrator.";
                exit;
            }
            
            if (isset($_POST['rememberme'])) {
                setcookie('Email', $email, time() + 60 * 60 * 7, "/"); // 7 days
                setcookie('Password', $password, time() + 3600 * 2, "/"); // 2 hours
            } else {
                setcookie('Email', $email, time() - 10, "/"); // Expire cookie if not checked box
                setcookie('Password', $password, time() - 10, "/");
            }
            
            // Set session variables for creator_profiles
            $_SESSION['log_uni_id'] = $row['Unique_id'];
            $_SESSION['profileid'] = $row['ProfileID'];
            $_SESSION['username'] = $row['Username'];
            $_SESSION['displayname'] = $row['DisplayName'];
            $_SESSION['email'] = $row['Email'];
            $_SESSION['access'] = $row['Access'];
            
            echo "success";
        } else {
            echo "Wrong Password";
        }
    } else {
        echo "Incorrect Email address";
    }
} else {
    echo "All inputs are required";
}
