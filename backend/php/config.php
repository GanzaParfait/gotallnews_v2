<?php
    $con = mysqli_connect("localhost", "root", "", "gotahhqa_gpnews");
    // ini_set("display_errors", "Off");

    if ($con) {
        // echo "Connected succesfully";
    } else {
        die("Failed to connect." .mysqli_connect_error());
    }
    
    // $con = mysqli_connect("localhost", "root", "", "ptsa");
    // // ini_set("display_errors", "Off");

    // if ($con) {
    //     // echo "Connected succesfully";
    // } else {
    //     die("Failed to connect." .mysqli_connect_error());
    // }
?>