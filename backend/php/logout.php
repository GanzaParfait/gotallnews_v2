<?php
session_start();
session_destroy(); // Destroy the session
setcookie("user_access", "", time() - 3600, "/"); // Clear the cookie

header("Location: ../../"); // Redirect to login or homepage
exit();
