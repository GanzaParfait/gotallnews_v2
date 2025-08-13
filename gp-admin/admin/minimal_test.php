<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gotahhqa_gpnews';

$con = new mysqli($host, $username, $password, $database);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

echo "Database connected successfully<br>";

// Test with just 3 parameters first
$sql = "INSERT IGNORE INTO creator_statistics (ProfileID, Date, ArticlesPublished) VALUES (?, ?, ?)";
echo "SQL: $sql<br>";

$stmt = $con->prepare($sql);
if ($stmt) {
    echo "Statement prepared<br>";
    
    $profileId = 3;
    $date = '2025-08-13';
    $articlesPublished = 1;
    
    echo "Binding 3 parameters...<br>";
    $bindResult = $stmt->bind_param("isi", $profileId, $date, $articlesPublished);
    
    if ($bindResult) {
        echo "Parameters bound successfully<br>";
        
        if ($stmt->execute()) {
            echo "Insert executed successfully<br>";
        } else {
            echo "Execute failed: " . $stmt->error . "<br>";
        }
    } else {
        echo "Bind parameters failed: " . $stmt->error . "<br>";
    }
} else {
    echo "Prepare failed: " . $con->error . "<br>";
}
?>
