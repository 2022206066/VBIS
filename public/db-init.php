<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display a simple web interface
echo "<h1>Database Initialization</h1>";

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "satellite_tracker";

try {
    echo "<p>Attempting to initialize the database...</p>";
    
    // 1. Connect to MySQL server
    echo "<p>Connecting to MySQL server...</p>";
    $mysqli = new mysqli($host, $username, $password);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    echo "<p>Connected successfully to MySQL server.</p>";
    
    // 2. Create database if not exists
    echo "<p>Creating database if it doesn't exist...</p>";
    if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
        throw new Exception("Error creating database: " . $mysqli->error);
    }
    echo "<p>Database '$dbname' ready.</p>";
    
    // 3. Select the database
    $mysqli->select_db($dbname);
    
    // 4. Import SQL dump
    $sqlFile = __DIR__ . '/../mysql_dump_files/satellite_tracker.sql';
    echo "<p>Importing database schema from: $sqlFile</p>";
    
    // Check if file exists
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL dump file not found: $sqlFile");
    }
    
    // Read SQL file
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        throw new Exception("Could not read SQL file");
    }
    
    // Execute multi-query SQL commands
    echo "<p>Executing SQL commands...</p>";
    if (!$mysqli->multi_query($sql)) {
        throw new Exception("Error executing SQL: " . $mysqli->error);
    }
    
    // Process all result sets (required for multi_query)
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    echo "<p>SQL import completed successfully.</p>";
    
    // Close connection
    $mysqli->close();
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; border-radius: 4px;'>
        <strong>Success!</strong> Database has been initialized successfully.
    </div>";
    
    echo "<p><a href='/VBIS-main/public/' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Return to Home Page</a></p>";
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
    
    echo "<p>Please check the database configuration and make sure MySQL server is running.</p>";
    
    echo "<p><a href='/VBIS-main/public/db-init.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Try Again</a></p>";
}
?> 