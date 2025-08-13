<?php
/**
 * Test Creator Profiles Installation
 * This script tests if the Creator Profiles system is properly installed
 */

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gotahhqa_gpnews';

try {
    $con = new mysqli($host, $username, $password, $database);
    
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }
    
    echo "<h2>🧪 Testing Creator Profiles Installation</h2>";
    
    // Test 1: Check if tables exist
    echo "<h3>Test 1: Database Tables</h3>";
    $tables = [
        'creator_profiles',
        'creator_social_links', 
        'creator_followers',
        'creator_statistics',
        'creator_achievements',
        'creator_categories'
    ];
    
    $tableStatus = [];
    foreach ($tables as $table) {
        $result = $con->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $tableStatus[$table] = '✓ Exists';
        } else {
            $tableStatus[$table] = '✗ Missing';
        }
    }
    
    foreach ($tableStatus as $table => $status) {
        $color = strpos($status, '✓') !== false ? 'green' : 'red';
        echo "<p style='color: $color;'>$table: $status</p>";
    }
    
    // Test 2: Check if view exists
    echo "<h3>Test 2: Database View</h3>";
    $viewResult = $con->query("SHOW TABLES LIKE 'creator_articles_summary'");
    if ($viewResult && $viewResult->num_rows > 0) {
        echo "<p style='color: green;'>✓ View 'creator_articles_summary' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ View 'creator_articles_summary' missing</p>";
    }
    
    // Test 3: Check if triggers exist
    echo "<h3>Test 3: Database Triggers</h3>";
    $triggers = [
        'update_creator_stats_on_article_publish',
        'update_follower_counts_on_follow',
        'update_follower_counts_on_unfollow'
    ];
    
    foreach ($triggers as $trigger) {
        $result = $con->query("SHOW TRIGGERS LIKE '$trigger'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✓ Trigger '$trigger' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Trigger '$trigger' missing</p>";
        }
    }
    
    // Test 4: Check if indexes exist
    echo "<h3>Test 4: Database Indexes</h3>";
    $indexes = [
        'idx_creator_profiles_views',
        'idx_creator_profiles_followers',
        'idx_creator_profiles_engagement'
    ];
    
    foreach ($indexes as $index) {
        $result = $con->query("SHOW INDEX FROM creator_profiles WHERE Key_name = '$index'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✓ Index '$index' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Index '$index' missing</p>";
        }
    }
    
    // Test 5: Check if directories exist
    echo "<h3>Test 5: File System</h3>";
    $uploadDir = 'images/creators/';
    if (is_dir($uploadDir)) {
        echo "<p style='color: green;'>✓ Directory '$uploadDir' exists</p>";
        if (is_writable($uploadDir)) {
            echo "<p style='color: green;'>✓ Directory '$uploadDir' is writable</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Directory '$uploadDir' is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Directory '$uploadDir' missing</p>";
    }
    
    // Test 6: Check if CreatorProfileManager class exists
    echo "<h3>Test 6: PHP Classes</h3>";
    if (file_exists('php/includes/CreatorProfileManager.php')) {
        echo "<p style='color: green;'>✓ CreatorProfileManager.php exists</p>";
        
        // Try to include and instantiate the class
        try {
            include_once 'php/includes/CreatorProfileManager.php';
            $manager = new CreatorProfileManager($con);
            echo "<p style='color: green;'>✓ CreatorProfileManager class can be instantiated</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error instantiating CreatorProfileManager: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ CreatorProfileManager.php missing</p>";
    }
    
    // Test 7: Check if main pages exist
    echo "<h3>Test 7: Main Pages</h3>";
    $pages = [
        'creator_profiles.php',
        'creator_profile_view.php'
    ];
    
    foreach ($pages as $page) {
        if (file_exists($page)) {
            echo "<p style='color: green;'>✓ $page exists</p>";
        } else {
            echo "<p style='color: red;'>✗ $page missing</p>";
        }
    }
    
    // Summary
    echo "<hr>";
    echo "<h3>📊 Test Summary</h3>";
    
    $totalTests = 0;
    $passedTests = 0;
    
    // Count passed tests
    foreach ($tableStatus as $status) {
        $totalTests++;
        if (strpos($status, '✓') !== false) $passedTests++;
    }
    
    // Add other tests
    $totalTests += 1; // View test
    $totalTests += 3; // Trigger tests
    $totalTests += 3; // Index tests
    $totalTests += 2; // Directory tests
    $totalTests += 1; // Class test
    $totalTests += 2; // Page tests
    
    if ($viewResult && $viewResult->num_rows > 0) $passedTests++;
    foreach ($triggers as $trigger) {
        $result = $con->query("SHOW TRIGGERS LIKE '$trigger'");
        if ($result && $result->num_rows > 0) $passedTests++;
    }
    foreach ($indexes as $index) {
        $result = $con->query("SHOW INDEX FROM creator_profiles WHERE Key_name = '$index'");
        if ($result && $result->num_rows > 0) $passedTests++;
    }
    if (is_dir($uploadDir)) $passedTests++;
    if (is_writable($uploadDir)) $passedTests++;
    if (file_exists('php/includes/CreatorProfileManager.php')) $passedTests++;
    if (file_exists('creator_profiles.php')) $passedTests++;
    if (file_exists('creator_profile_view.php')) $passedTests++;
    
    $percentage = round(($passedTests / $totalTests) * 100, 1);
    
    if ($percentage >= 90) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🎉 Excellent! Installation is working well</h4>";
        echo "<p><strong>Score:</strong> $passedTests/$totalTests ($percentage%)</p>";
        echo "</div>";
    } elseif ($percentage >= 70) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ Good installation with minor issues</h4>";
        echo "<p><strong>Score:</strong> $passedTests/$totalTests ($percentage%)</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ Installation has significant issues</h4>";
        echo "<p><strong>Score:</strong> $passedTests/$totalTests ($percentage%)</p>";
        echo "</div>";
    }
    
    echo "<p><strong>Total Tests:</strong> $totalTests</p>";
    echo "<p><strong>Passed:</strong> $passedTests</p>";
    echo "<p><strong>Failed:</strong> " . ($totalTests - $passedTests) . "</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Test Failed</h4>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Close connection
if (isset($con)) {
    $con->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Creator Profiles Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        h2, h3, h4 {
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center; margin: 30px 0;">
            <a href="install_creator_profiles_fixed.php" class="btn btn-success">🔄 Run Installation Again</a>
            <a href="creator_profiles.php" class="btn">🚀 Go to Creator Profiles</a>
            <a href="index.php" class="btn">🏠 Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
