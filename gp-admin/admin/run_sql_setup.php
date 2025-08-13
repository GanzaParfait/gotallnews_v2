<?php
include "php/header/top.php";

echo "<h2>Setting up ProfileID in Article Table</h2>";

// Test database connection
if (!$con) {
    die("Database connection failed");
}

try {
    // Read the SQL file
    $sqlFile = 'add_profileid_to_article.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found: $sqlFile");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split SQL statements (basic splitting by semicolon)
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
    
    echo "<h3>Executing SQL statements...</h3>";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty lines
        }
        
        // Skip DELIMITER and trigger creation for now (we'll handle them separately)
        if (strpos($statement, 'DELIMITER') !== false || strpos($statement, 'CREATE TRIGGER') !== false) {
            continue;
        }
        
        if (strpos($statement, 'END$$') !== false) {
            continue;
        }
        
        echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
        
        if ($con->query($statement)) {
            echo "<p>✓ Success</p>";
        } else {
            echo "<p>✗ Error: " . $con->error . "</p>";
        }
    }
    
    // Handle triggers separately
    echo "<h3>Creating triggers...</h3>";
    
    // Trigger 1: Set ProfileID on insert
    $trigger1 = "
    CREATE TRIGGER `set_profileid_on_article_insert` 
    BEFORE INSERT ON `article` 
    FOR EACH ROW 
    BEGIN
        DECLARE profile_id INT;
        SELECT ProfileID INTO profile_id 
        FROM creator_profiles 
        WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted' 
        LIMIT 1;
        IF profile_id IS NOT NULL THEN
            SET NEW.ProfileID = profile_id;
        END IF;
    END";
    
    if ($con->query($trigger1)) {
        echo "<p>✓ Created trigger: set_profileid_on_article_insert</p>";
    } else {
        echo "<p>✗ Error creating trigger: " . $con->error . "</p>";
    }
    
    // Trigger 2: Update ProfileID on AdminId change
    $trigger2 = "
    CREATE TRIGGER `update_profileid_on_adminid_change` 
    BEFORE UPDATE ON `article` 
    FOR EACH ROW 
    BEGIN
        DECLARE profile_id INT;
        IF OLD.AdminId != NEW.AdminId THEN
            SELECT ProfileID INTO profile_id 
            FROM creator_profiles 
            WHERE AdminId = NEW.AdminId AND isDeleted = 'notDeleted' 
            LIMIT 1;
            IF profile_id IS NOT NULL THEN
                SET NEW.ProfileID = profile_id;
            ELSE
                SET NEW.ProfileID = NULL;
            END IF;
        END IF;
    END";
    
    if ($con->query($trigger2)) {
        echo "<p>✓ Created trigger: update_profileid_on_adminid_change</p>";
    } else {
        echo "<p>✗ Error creating trigger: " . $con->error . "</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Now run the data population script:</p>";
    echo "<p><a href='populate_creator_data.php' class='btn btn-primary'>Populate Creator Data</a></p>";
    echo "<p><a href='creator_profile_view.php?id=3' class='btn btn-success'>View Creator Profile</a></p>";
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
