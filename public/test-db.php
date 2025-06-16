<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "satellite_tracker";

echo "<h1>Database Connection Test</h1>";

try {
    // Connect to the database
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; border-radius: 4px;'>
        <strong>Success!</strong> Connected to the database.
    </div>";
    
    // Show tables
    echo "<h2>Database Tables</h2>";
    $result = $mysqli->query("SHOW TABLES");
    
    if ($result) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>
            <thead>
                <tr style='background-color: #f5f5f5;'>
                    <th>Table Name</th>
                </tr>
            </thead>
            <tbody>";
        
        $tableCount = 0;
        while ($row = $result->fetch_array()) {
            echo "<tr><td>" . htmlspecialchars($row[0]) . "</td></tr>";
            $tableCount++;
        }
        
        echo "</tbody></table>";
        
        if ($tableCount === 0) {
            echo "<p>No tables found in database. You may need to run the <a href='/VBIS-main/public/db-init.php'>database initialization script</a>.</p>";
        } else {
            // Check for specific tables
            $requiredTables = ['satellites', 'users', 'roles', 'user_roles'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                $check = $mysqli->query("SHOW TABLES LIKE '$table'");
                if ($check->num_rows === 0) {
                    $missingTables[] = $table;
                }
            }
            
            if (!empty($missingTables)) {
                echo "<p style='color: #a94442;'>Warning: The following required tables are missing: " . implode(', ', $missingTables) . "</p>";
                echo "<p>You may need to run the <a href='/VBIS-main/public/db-init.php'>database initialization script</a>.</p>";
            }
        }
        
        $result->free();
    } else {
        echo "<p>Error listing tables: " . $mysqli->error . "</p>";
    }
    
    // Close connection
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
    
    echo "<p>Please check the database configuration and make sure MySQL server is running.</p>";
    
    echo "<p>You may need to run the <a href='/VBIS-main/public/db-init.php'>database initialization script</a> to set up the database.</p>";
}

echo "<p><a href='/VBIS-main/public/' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Return to Home Page</a></p>";
?> 