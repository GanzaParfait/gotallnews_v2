<?php
session_start();
include "backend/php/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: ./"); // Redirect to login page if user is not logged in
    exit();
} else {
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect_url = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']); // Clear the redirect URL from the session
    } else {
        $redirect_url = './';
    }

    // Redirect to the saved URL or default page
    header("Location: $redirect_url");
    exit();
}
