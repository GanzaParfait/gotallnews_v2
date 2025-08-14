<?php
session_start();
include "php/config.php";

if (!isset($_SESSION['log_uni_id'])) {
    echo "<script>alert('You must login first.')</script>";
    echo "<script>window.location.href = '../default/login.php'</script>";
} else {
    // Get user data from creator_profiles table
    $selectuser = mysqli_query($con, "SELECT * FROM `creator_profiles` WHERE `Unique_id` = '{$_SESSION['log_uni_id']}' AND `isDeleted` = 'notDeleted'");
    $user = mysqli_fetch_assoc($selectuser);
    
    if (!$user) {
        // User not found, redirect to login
        session_destroy();
        session_unset();
        echo "<script>alert('User session expired. Please login again.')</script>";
        echo "<script>window.location.href = '../default/login.php'</script>";
        exit;
    }
    
    $unid = $user['Unique_id'];
    $names = $user['Username'];

    $user_f_name = $user['DisplayName'];
    
    // Set user_profileid for compatibility with existing code
    $user_profileid = $user['ProfileID'];
    $user_uniqueid = $user['ProfileID']; // Keep for backward compatibility
    
    if ($user['Access'] == 'Revoked') {
        echo "<script>alert('No longer access to the system. For more information can ask Admin.')</script>";
        session_destroy();
        session_unset();
        header("Location: ../default/login.php");
        exit;
    }
}
?>
