<?php
include "php/header/top.php";

echo "<h2>Testing Creator Profiles Data</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

// Check if creator_profiles table exists
$result = $con->query("SHOW TABLES LIKE 'creator_profiles'");
if ($result->num_rows === 0) {
    die("creator_profiles table does not exist");
}

// Check table structure
echo "<h3>Creator Profiles Table Structure:</h3>";
$result = $con->query("DESCRIBE creator_profiles");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Get all profiles
$result = $con->query("SELECT * FROM creator_profiles");
if ($result->num_rows === 0) {
    echo "<p>No profiles found in the database.</p>";
} else {
    echo "<h3>Found " . $result->num_rows . " profiles:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ProfileID</th><th>AdminId</th><th>Username</th><th>DisplayName</th><th>Bio</th><th>Location</th><th>Expertise</th><th>Status</th><th>ProfilePhoto</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ProfileID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['AdminId']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['DisplayName'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Bio'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Location'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Expertise'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Status'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['ProfilePhoto'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check admin table
echo "<h3>Admin Table:</h3>";
$result = $con->query("SELECT AdminId, FirstName, LastName FROM admin LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>AdminId</th><th>FirstName</th><th>LastName</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['AdminId']) . "</td>";
        echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test the CreatorProfileManager directly
echo "<h3>Testing CreatorProfileManager:</h3>";
try {
    include "php/includes/CreatorProfileManager.php";
    $creatorManager = new CreatorProfileManager($con);
    
    // Test getting profile with ID 3
    $profile = $creatorManager->getProfile(3);
    if ($profile) {
        echo "<p><strong>Profile 3 retrieved successfully:</strong></p>";
        echo "<pre>" . htmlspecialchars(print_r($profile, true)) . "</pre>";
    } else {
        echo "<p><strong>Profile 3 not found</strong></p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Error testing CreatorProfileManager:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
