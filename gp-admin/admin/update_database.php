<?php
/**
 * Database Update Script for Image Processing System
 * This script will update your database to support multiple image sizes
 */

require_once 'php/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update - GotAllNews CMS</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #007bff; }
        .step h3 { margin-top: 0; color: #007bff; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
        .progress { background: #e9ecef; border-radius: 4px; height: 20px; margin: 10px 0; }
        .progress-bar { background: #007bff; height: 100%; border-radius: 4px; transition: width 0.3s; }
        .back-link { margin-top: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗄️ Database Update for Image Processing System</h1>
        <p>This script will update your database to support multiple image sizes and WebP optimization.</p>";

// Check database connection
if (!$con) {
    echo "<div class='error'>❌ Database connection failed!</div>";
    exit;
}

echo "<div class='success'>✅ Database connected successfully</div>";

// Step 1: Check current table structure
echo "<div class='step'>
    <h3>Step 1: Checking Current Table Structure</h3>";

$check_columns = mysqli_query($con, "SHOW COLUMNS FROM `article` LIKE 'image_large'");
$has_large = mysqli_num_rows($check_columns) > 0;

$check_columns = mysqli_query($con, "SHOW COLUMNS FROM `article` LIKE 'image_medium'");
$has_medium = mysqli_num_rows($check_columns) > 0;

$check_columns = mysqli_query($con, "SHOW COLUMNS FROM `article` LIKE 'image_small'");
$has_small = mysqli_num_rows($check_columns) > 0;

$check_columns = mysqli_query($con, "SHOW COLUMNS FROM `article` LIKE 'image_thumbnail'");
$has_thumbnail = mysqli_num_rows($check_columns) > 0;

if ($has_large && $has_medium && $has_small && $has_thumbnail) {
    echo "<div class='success'>✅ All required columns already exist!</div>";
    echo "<p>Your database is already updated. You can proceed to use the image processing system.</p>";
} else {
    echo "<div class='warning'>⚠️ Some columns are missing. Database update required.</div>";
    echo "<p>Missing columns:</p><ul>";
    if (!$has_large) echo "<li>image_large</li>";
    if (!$has_medium) echo "<li>image_medium</li>";
    if (!$has_small) echo "<li>image_small</li>";
    if (!$has_thumbnail) echo "<li>image_thumbnail</li>";
    echo "</ul>";
}

echo "</div>";

// Step 2: Add missing columns
if (!($has_large && $has_medium && $has_small && $has_thumbnail)) {
    echo "<div class='step'>
        <h3>Step 2: Adding Missing Columns</h3>";
    
    $columns_added = 0;
    $errors = [];
    
    // Add image_large column
    if (!$has_large) {
        $sql = "ALTER TABLE `article` ADD COLUMN `image_large` VARCHAR(255) NULL AFTER `Image`";
        if (mysqli_query($con, $sql)) {
            echo "<div class='success'>✅ Added image_large column</div>";
            $columns_added++;
        } else {
            $error = "Failed to add image_large: " . mysqli_error($con);
            echo "<div class='error'>❌ $error</div>";
            $errors[] = $error;
        }
    }
    
    // Add image_medium column
    if (!$has_medium) {
        $sql = "ALTER TABLE `article` ADD COLUMN `image_medium` VARCHAR(255) NULL AFTER `image_large`";
        if (mysqli_query($con, $sql)) {
            echo "<div class='success'>✅ Added image_medium column</div>";
            $columns_added++;
        } else {
            $error = "Failed to add image_medium: " . mysqli_error($con);
            echo "<div class='error'>❌ $error</div>";
            $errors[] = $error;
        }
    }
    
    // Add image_small column
    if (!$has_small) {
        $sql = "ALTER TABLE `article` ADD COLUMN `image_small` VARCHAR(255) NULL AFTER `image_medium`";
        if (mysqli_query($con, $sql)) {
            echo "<div class='success'>✅ Added image_small column</div>";
            $columns_added++;
        } else {
            $error = "Failed to add image_small: " . mysqli_error($con);
            echo "<div class='error'>❌ $error</div>";
            $errors[] = $error;
        }
    }
    
    // Add image_thumbnail column
    if (!$has_thumbnail) {
        $sql = "ALTER TABLE `article` ADD COLUMN `image_thumbnail` VARCHAR(255) NULL AFTER `image_small`";
        if (mysqli_query($con, $sql)) {
            echo "<div class='success'>✅ Added image_thumbnail column</div>";
            $columns_added++;
        } else {
            $error = "Failed to add image_thumbnail: " . mysqli_error($con);
            echo "<div class='error'>❌ $error</div>";
            $errors[] = $error;
        }
    }
    
    if (empty($errors)) {
        echo "<div class='success'>🎉 Successfully added $columns_added new columns!</div>";
    } else {
        echo "<div class='error'>❌ Some columns failed to add. Please check the errors above.</div>";
    }
    
    echo "</div>";
}

// Step 3: Update existing records
echo "<div class='step'>
    <h3>Step 3: Updating Existing Records</h3>";

$count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM article WHERE Image IS NOT NULL AND Image != ''");
$total_articles = mysqli_fetch_assoc($count_query)['total'];

echo "<p>Found $total_articles articles with images.</p>";

if ($total_articles > 0) {
    // Update existing records to have default values
    $update_sql = "UPDATE `article` SET 
        `image_large` = `Image`,
        `image_medium` = `Image`,
        `image_small` = `Image`,
        `image_thumbnail` = `Image`
        WHERE `image_large` IS NULL AND `Image` IS NOT NULL AND `Image` != ''";
    
    if (mysqli_query($con, $update_sql)) {
        $affected_rows = mysqli_affected_rows($con);
        echo "<div class='success'>✅ Updated $affected_rows existing articles with default image values</div>";
        echo "<p>Note: These are temporary values. Run the batch processing script to generate actual WebP versions.</p>";
    } else {
        echo "<div class='error'>❌ Failed to update existing records: " . mysqli_error($con) . "</div>";
    }
} else {
    echo "<div class='info'>ℹ️ No articles with images found.</div>";
}

echo "</div>";

// Step 4: Create index for performance
echo "<div class='step'>
    <h3>Step 4: Creating Performance Index</h3>";

// Check if index already exists
$index_query = mysqli_query($con, "SHOW INDEX FROM `article` WHERE Key_name = 'idx_image_sizes'");
$index_exists = mysqli_num_rows($index_query) > 0;

if (!$index_exists) {
    $index_sql = "CREATE INDEX `idx_image_sizes` ON `article` (`image_large`, `image_medium`, `image_small`, `image_thumbnail`)";
    if (mysqli_query($con, $index_sql)) {
        echo "<div class='success'>✅ Created performance index for image columns</div>";
    } else {
        echo "<div class='error'>❌ Failed to create index: " . mysqli_error($con) . "</div>";
    }
} else {
    echo "<div class='success'>✅ Performance index already exists</div>";
}

echo "</div>";

// Step 5: Verification
echo "<div class='step'>
    <h3>Step 5: Final Verification</h3>";

$final_check = mysqli_query($con, "SHOW COLUMNS FROM `article`");
$all_columns = [];
while ($row = mysqli_fetch_assoc($final_check)) {
    $all_columns[] = $row['Field'];
}

$required_columns = ['image_large', 'image_medium', 'image_small', 'image_thumbnail'];
$missing_columns = array_diff($required_columns, $all_columns);

if (empty($missing_columns)) {
    echo "<div class='success'>🎉 Database update completed successfully!</div>";
    echo "<p>All required columns are now present:</p><ul>";
    foreach ($required_columns as $col) {
        echo "<li>✅ $col</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='error'>❌ Some columns are still missing: " . implode(', ', $missing_columns) . "</div>";
}

echo "</div>";

// Step 6: Next steps
echo "<div class='step'>
    <h3>Step 6: Next Steps</h3>
    <p>Your database is now ready for the image processing system. Here's what you can do next:</p>
    <ol>
        <li><strong>Test the system:</strong> <a href='test_image_processing.php'>Run the image processing test</a></li>
        <li><strong>Test image retrieval:</strong> <a href='test_image_retrieval.php'>Test retrieving images by Article ID</a></li>
        <li><strong>Process existing images:</strong> <a href='batch_process_existing_images.php'>Convert existing images to WebP</a></li>
        <li><strong>Create new articles:</strong> <a href='new_article.php'>Test the new article creation with image processing</a></li>
    </ol>
</div>";

// Summary
echo "<div class='step'>
    <h3>📊 Update Summary</h3>
    <div style='background: #e7f3ff; padding: 15px; border-radius: 4px;'>
        <p><strong>Database:</strong> " . ($con ? 'Connected' : 'Failed') . "</p>
        <p><strong>Required columns:</strong> " . (empty($missing_columns) ? 'All present' : 'Some missing') . "</p>
        <p><strong>Articles with images:</strong> $total_articles</p>
        <p><strong>Status:</strong> " . (empty($missing_columns) ? 'Ready to use' : 'Update required') . "</p>
    </div>
</div>";

echo "<div class='back-link'>
    <a href='index.php'>← Back to Dashboard</a> | 
    <a href='test_image_processing.php'>Test Image Processing</a> | 
    <a href='test_image_retrieval.php'>Test Image Retrieval</a>
</div>";

echo "</div></body></html>";
?>
