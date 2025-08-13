<?php

include "config.php";


$get_categories = mysqli_query($con, "SELECT * FROM `category`");

while ($row = mysqli_fetch_assoc($get_categories)) {
    $category = strtolower($row['Category']);
    $fileName = strtolower($category) . ".php"; // Convert to lowercase and create file name

    // Define the content you want to write in each file
    $content = "<?php\n";
    $content .= "// This is the page for $category category\n";
    $content .= "echo 'Welcome to the $category catefffffffffffgory page!';\n";
    $content .= "?>";

    // Create or overwrite the PHP file
    $file = fopen($fileName, "w");

    if ($file) {
        // Write the content to the file
        fwrite($file, $content);

        // Close the file
        fclose($file);

        echo "File '$fileName' created successfully.<br>";
    } else {
        echo "Failed to create file for $category.<br>";
    }
}
