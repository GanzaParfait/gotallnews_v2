<?php

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/config/env.php";

$client = new Google\Client;

$client->setClientId(env('GOOGLE_CLIENT_ID'));
$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri("https://gotallnews.com/redirect.php");

$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

header("Location: $url");
