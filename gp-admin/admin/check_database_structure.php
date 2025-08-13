<?php
include "php/header/top.php";

echo "<h2>Database Structure Check</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

// Check all tables
echo "<h3>All Tables in Database:</h3>";
$result = $con->query("SHOW TABLES");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table Name</th></tr>";
    while ($row = $result->fetch_array()) {
        echo "<tr><td>" . htmlspecialchars($row[0]) . "</td></tr>";
    }
    echo "</table>";
}

// Check if users table exists and its structure
echo "<h3>Users Table Structure:</h3>";
$result = $con->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    $result = $con->query("DESCRIBE users");
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
        
        // Show sample data
        echo "<h4>Sample Users Data:</h4>";
        $sampleResult = $con->query("SELECT * FROM users LIMIT 3");
        if ($sampleResult->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $first = true;
            while ($row = $sampleResult->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $key) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p>Users table does not exist!</p>";
}

// Check if admin table exists and its structure
echo "<h3>Admin Table Structure:</h3>";
$result = $con->query("SHOW TABLES LIKE 'admin'");
if ($result->num_rows > 0) {
    $result = $con->query("DESCRIBE admin");
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
} else {
    echo "<p>Admin table does not exist!</p>";
}

// Check creator_profiles table
echo "<h3>Creator Profiles Table Structure:</h3>";
$result = $con->query("SHOW TABLES LIKE 'creator_profiles'");
if ($result->num_rows > 0) {
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
        
        // Show sample data
        echo "<h4>Sample Creator Profiles Data:</h4>";
        $sampleResult = $con->query("SELECT * FROM creator_profiles LIMIT 3");
        if ($sampleResult->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $first = true;
            while ($row = $sampleResult->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $key) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p>Creator profiles table does not exist!</p>";
}

// Check what the current user_uniqueid variable contains
echo "<h3>Current User Variables:</h3>";
echo "<p><strong>user_uniqueid:</strong> " . (isset($user_uniqueid) ? $user_uniqueid : 'NOT SET') . "</p>";
echo "<p><strong>names:</strong> " . (isset($names) ? $names : 'NOT SET') . "</p>";
echo "<p><strong>user_id:</strong> " . (isset($user_id) ? $user_id : 'NOT SET') . "</p>";
?>
