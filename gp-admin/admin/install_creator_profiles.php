<?php
/**
 * Creator Profiles Installation Script
 * Run this file to set up the database tables for the creator profiles system
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
    
    echo "<h2>Creator Profiles System Installation</h2>";
    echo "<p>Setting up database tables...</p>";
    
    // Read and execute the SQL schema
    $sqlFile = 'creator_profiles_schema.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(--|\/\*|DELIMITER)/', $statement)) {
                try {
                    if ($con->query($statement)) {
                        $successCount++;
                        echo "<p style='color: green;'>✓ " . substr($statement, 0, 50) . "...</p>";
                    } else {
                        $errorCount++;
                        echo "<p style='color: red;'>✗ Error: " . $con->error . "</p>";
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<hr>";
        echo "<h3>Installation Summary:</h3>";
        echo "<p><strong>Successful:</strong> $successCount statements</p>";
        echo "<p><strong>Errors:</strong> $errorCount statements</p>";
        
        if ($errorCount == 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🎉 Installation Complete!</h4>";
            echo "<p>The Creator Profiles system has been successfully installed.</p>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ol>";
            echo "<li>Go to <a href='creator_profiles.php'>Creator Profiles</a> to manage creators</li>";
            echo "<li>Create your first creator profile</li>";
            echo "<li>Explore the new features in your dashboard</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>⚠️ Installation Completed with Errors</h4>";
            echo "<p>Some database operations failed. Please check the error messages above.</p>";
            echo "<p>You may need to manually run the SQL commands or check your database permissions.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ SQL Schema File Not Found</h4>";
        echo "<p>The file 'creator_profiles_schema.sql' was not found in the current directory.</p>";
        echo "<p>Please ensure the SQL schema file is present before running this installation script.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Installation Failed</h4>";
    echo "<p>An error occurred during installation: " . $e->getMessage() . "</p>";
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
    <title>Creator Profiles Installation</title>
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
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        code {
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Creator Profiles System</h1>
        
        <div class="info">
            <h4>What's New?</h4>
            <p>This installation adds a comprehensive creator profiles system to your news website, including:</p>
            <ul>
                <li><strong>Creator Profiles:</strong> Extended information for content creators</li>
                <li><strong>Social Media Integration:</strong> Multiple platform support</li>
                <li><strong>Follower System:</strong> Follow/unfollow functionality</li>
                <li><strong>Statistics Tracking:</strong> Performance metrics and analytics</li>
                <li><strong>Achievements System:</strong> Gamification and recognition</li>
                <li><strong>Category Expertise:</strong> Creator specialization areas</li>
            </ul>
        </div>
        
        <div class="warning">
            <h4>⚠️ Important Notes</h4>
            <ul>
                <li>Make sure you have a backup of your database before running this installation</li>
                <li>Ensure your database user has sufficient privileges to create tables and triggers</li>
                <li>This system integrates with your existing admin and article tables</li>
                <li>All operations use soft delete (no data is permanently removed)</li>
            </ul>
        </div>
        
        <div class="success">
            <h4>✅ System Requirements</h4>
            <ul>
                <li>PHP 7.4+ with MySQLi extension</li>
                <li>MySQL 5.7+ or MariaDB 10.2+</li>
                <li>Existing database with admin and article tables</li>
                <li>Write permissions for the images/creators/ directory</li>
            </ul>
        </div>
        
        <hr>
        
        <h3>📋 Installation Steps</h3>
        <ol>
            <li><strong>Database Setup:</strong> Run this script to create all necessary tables</li>
            <li><strong>File Permissions:</strong> Ensure the images/creators/ directory is writable</li>
            <li><strong>Configuration:</strong> Update database connection details if needed</li>
            <li><strong>Testing:</strong> Visit the Creator Profiles section in your admin dashboard</li>
        </ol>
        
        <hr>
        
        <h3>🔧 Manual Installation (if automatic fails)</h3>
        <p>If the automatic installation fails, you can manually run the SQL commands:</p>
        <ol>
            <li>Open your database management tool (phpMyAdmin, MySQL Workbench, etc.)</li>
            <li>Select your database: <code>gotahhqa_gpnews</code></li>
            <li>Open the file: <code>creator_profiles_schema.sql</code></li>
            <li>Execute the SQL commands manually</li>
        </ol>
        
        <hr>
        
        <h3>📁 File Structure</h3>
        <p>After installation, you'll have these new files:</p>
        <ul>
            <li><code>php/includes/CreatorProfileManager.php</code> - Main class for managing profiles</li>
            <li><code>creator_profiles.php</code> - Main management page</li>
            <li><code>creator_profile_view.php</code> - Individual profile view</li>
            <li><code>images/creators/</code> - Directory for creator profile photos</li>
        </ul>
        
        <hr>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="creator_profiles.php" class="btn btn-success">🚀 Go to Creator Profiles</a>
            <a href="index.php" class="btn btn-warning">🏠 Back to Dashboard</a>
        </div>
        
        <div class="info">
            <h4>📞 Need Help?</h4>
            <p>If you encounter any issues during installation:</p>
            <ul>
                <li>Check the error messages above for specific database errors</li>
                <li>Verify your database connection details</li>
                <li>Ensure your database user has CREATE, ALTER, and INSERT privileges</li>
                <li>Check the server error logs for additional information</li>
            </ul>
        </div>
    </div>
</body>
</html>
