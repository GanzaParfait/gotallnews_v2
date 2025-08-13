<?php
session_start();
include "backend/php/config.php";
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/config/env.php";

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// Google Client Configuration
$client = new Google\Client;
$client->setClientId(env('GOOGLE_CLIENT_ID'));
$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri("https://gotallnews.com/redirect.php");

if (!isset($_GET["code"])) {
    exit("Login failed");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
$client->setAccessToken($token["access_token"]);

// Get user information from Google
$oauth = new Google\Service\Oauth2($client);
$userinfo = $oauth->userinfo->get();

$email = $userinfo->email;
$first_name = $userinfo->givenName;
$last_name = $userinfo->familyName;
$full_name = $userinfo->name;
$gender = $userinfo->gender ?? 'Not provided';  // Gender is optional and might not always be returned
$picture_url = $userinfo->picture;

// Check if user already exists
$user = mysqli_query($con, "SELECT * FROM `users` WHERE `Email` = '$email'");
$user_row = mysqli_fetch_assoc($user);
$user_id = $user_row['UserId'];

if (mysqli_num_rows($user) > 0) {
    $_SESSION['user'] = $user_id;
    // echo "User already exists!";

    // Check for any available updates
    $update_user_details = mysqli_query($con, "UPDATE `users` SET `Email`='$email',`FirstName`='$first_name',
    `LastName`='$last_name',`FullName`='$full_name',`Gender`='$gender',`PictureUrl`=$picture_url WHERE `Email` = '$email'");

    header("Location: welcome");
} else {
    $add_user = mysqli_query($con, "INSERT INTO `users`(`Email`, `FirstName`, `LastName`, `FullName`, `Gender`, `PictureUrl`)
    VALUES ('$email', '$first_name', '$last_name', '$full_name','$gender','$picture_url')");

    $user = mysqli_query($con, "SELECT * FROM `users` WHERE `Email` = '$email'");
    $user_row = mysqli_fetch_assoc($user);
    $user_id = $user_row['UserId'];

    $_SESSION['user'] = $user_id;

    // echo "User added to the database!";


    // $mail = new PHPMailer(true); // Create a new PHPMailer instance

    // try {
    //     // Server settings
    //     $mail->isSMTP();                                            // Send using SMTP
    //     $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
    //     $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    //     $mail->Username   = 'ganzaparfait7@gmail.com';                 // Your Gmail address
    //     $mail->Password   = 'gparfaitohhac$2024_';                    // Your Gmail app-specific password
    //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
    //     $mail->Port       = 587;                       // TCP port to connect to

    //     // Recipients
    //     $mail->setFrom('ganzaparfait7@gmail.com', 'GP News'); // From email and name
    //     $mail->addAddress($userinfo->email);                        // Add the user's Gmail address

    //     // Content
    //     $mail->isHTML(true);                                        // Set email format to HTML
    //     $mail->Subject = 'Welcome Back to GP News';
    //     $mail->Body    = "Hi " . $userinfo->givenName . ",<br><br>You have successfully logged in to GP News.<br>Enjoy browsing!<br><br>Best Regards,<br>GP News Team";

    //     // Send email
    //     $mail->send();
    //     echo 'A welcome email has been sent to ' . $userinfo->email;
    // } catch (Exception $e) {
    //     echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    // }


    // header("Location: welcome");
    echo "<script>alert('Happy enjoying your journey!')</script>";
    echo "<script>window.location.href = 'welcome'</script>";
}


// Display user info for testing (optional)
// echo "<pre>";
// var_dump(
//     $userinfo->email,
//     $userinfo->name,       // full name
//     $userinfo->picture     // profile picture
// );
// echo "</pre>";
