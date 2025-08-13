<?php

include "admin/php/config.php";


// Start generating the XML file
header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$get_all_articles = mysqli_query($con, "SELECT * FROM `article`");

// Loop through all articles and add them to the sitemap
while ($row = mysqli_fetch_assoc($get_all_articles)) {
    $url = "https://gpnews.com?article=" . $row['Article_link'];
    $lastmod = date('Y-m-d', strtotime($row['Created_at']));

    echo "<url>";
    echo "<loc>$url</loc>";
    echo "<lastmod>$lastmod</lastmod>";
    echo "<priority>0.80</priority>";
    echo "</url>";
}

echo "</urlset>";
