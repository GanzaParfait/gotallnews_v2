<?php

include "backend/php/config.php";


// Start generating the XML file
header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

?>
<url>
    <loc>https://gotallnews.com/</loc>
    <lastmod>2024-10-08</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
</url>
<url>
    <loc>https://gotallnews.com/latest-news</loc>
    <lastmod>2024-10-01</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.2</priority>
</url>
<url>
    <loc>https://gotallnews.com/contact</loc>
    <lastmod>2024-10-01</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.2</priority>
</url>
<url>
    <loc>https://gotallnews.com/privacy-policy</loc>
    <lastmod>2024-10-01</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.2</priority>
</url>
<url>
    <loc>https://gotallnews.com/terms</loc>
    <lastmod>2024-10-01</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.2</priority>
</url>
<?php

$get_categories2 = mysqli_query($con, "SELECT * FROM `category`");
while ($row2 = mysqli_fetch_assoc($get_categories2)) {
    $category2 = strtolower($row2['Category']);
    $lastmod_category_date = date('Y-m-d', strtotime($row2['Created_at']));

?>
    <url>
        <loc>https://gotallnews.com/category/<?= $category2; ?>/</loc>
        <lastmod><?= $lastmod_category_date; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
<?php

}

$get_all_articles = mysqli_query($con, "SELECT * FROM `article`");

// Loop through all articles and add them to the sitemap
while ($row = mysqli_fetch_assoc($get_all_articles)) {
    $article_id = $row['ArticleID'];
    $article_link = $row['Article_link'];
    $url = "https://gotallnews.com/article/$article_id/$article_link";
    $lastmod = date('Y-m-d', strtotime($row['Last_updated']));

    echo "<url>";
    echo "<loc>$url</loc>";
    echo "<lastmod>$lastmod</lastmod>";
    echo "<priority>0.80</priority>";
    echo "</url>";
}

echo "</urlset>";
