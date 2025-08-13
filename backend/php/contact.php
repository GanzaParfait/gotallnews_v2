<?php

include "config.php";

$user_id = mysqli_real_escape_string($con, $_POST['user_id']);
$name = mysqli_real_escape_string($con, $_POST['name']);
$email = mysqli_real_escape_string($con, $_POST['email']);
$subject = mysqli_real_escape_string($con, $_POST['subject']);
$message = mysqli_real_escape_string($con, $_POST['message']);


$send = mysqli_query($con, "INSERT INTO `message`(`UserId`,`Names`, `Email`, `Subject`, `Message`)
VALUES ('$user_id','$name','$email','$subject','$message')");


// Prepare data to send to Web3Forms
$data = [
    'name' => $name,
    'email' => $email,
    'message' => $message,
    'apikey' => '91c588eb-a8ea-418d-a41d-21c09d7aabad', // replace with your Web3Forms API key
    'subject' => $subject,
];

// Send the data to Web3Forms
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('https://api.web3forms.com/submit', false, $context);

if ($result === FALSE) {
    // Handle error
}

// echo $result;

if ($send) {
    echo "<script>window.location.href = '../../contact.php?msg=Thanks for your message!'</script>";
} else {
    echo "<script>alert('Something went wrong in sending your message! Try again later.')</script>";
    echo "<script>window.location.href = '../../contact.php'</script>";
}
