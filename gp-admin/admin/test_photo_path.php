<?php
echo "<h2>Testing Profile Photo Path</h2>";

$profilePhoto = "images/creators/creator_1755016753_689b6e313c408.webp";

echo "<p><strong>Profile Photo Path:</strong> $profilePhoto</p>";

// Check if file exists
if (file_exists($profilePhoto)) {
    echo "<p>✓ File exists: $profilePhoto</p>";
    echo "<p>File size: " . filesize($profilePhoto) . " bytes</p>";
} else {
    echo "<p>✗ File NOT found: $profilePhoto</p>";
}

// Check with current directory
$currentPath = "./images/creators/creator_1755016753_689b6e313c408.webp";
if (file_exists($currentPath)) {
    echo "<p>✓ File exists with current path: $currentPath</p>";
} else {
    echo "<p>✗ File NOT found with current path: $currentPath</p>";
}

// Check directory contents
$dir = "images/creators/";
if (is_dir($dir)) {
    echo "<p>✓ Directory exists: $dir</p>";
    $files = scandir($dir);
    echo "<p>Files in directory:</p><ul>";
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>✗ Directory does not exist: $dir</p>";
}

// Check working directory
echo "<p><strong>Current Working Directory:</strong> " . getcwd() . "</p>";

// Try to create a test image tag
echo "<h3>Test Image Display:</h3>";
echo "<img src='$profilePhoto' alt='Test Profile Photo' style='width: 100px; height: 100px; border: 1px solid #ccc;' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"inline\";'><span style='display:none; color:red;'>Image failed to load</span>";
?>
