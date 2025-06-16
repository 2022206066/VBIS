<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    // Try direct connection
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "satellite_tracker";
    
    echo "<p>Attempting to connect to MySQL server...</p>";
    
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Direct connection failed: " . $mysqli->connect_error);
    }
    
    echo "<p style='color: green; font-weight: bold;'>✓ Direct connection successful</p>";
    
    // Test a simple query
    echo "<p>Testing a simple query...</p>";
    
    $result = $mysqli->query("SELECT 1 AS test");
    if (!$result) {
        throw new Exception("Query failed: " . $mysqli->error);
    }
    
    $row = $result->fetch_assoc();
    echo "<p style='color: green; font-weight: bold;'>✓ Query successful: " . $row['test'] . "</p>";
    
    // Check if tables exist
    echo "<p>Checking tables...</p>";
    
    $result = $mysqli->query("SHOW TABLES");
    if (!$result) {
        throw new Exception("Failed to list tables: " . $mysqli->error);
    }
    
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo "<p>Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Close the connection
    $mysqli->close();
    
    echo "<h2>Socket Limit Test</h2>";
    echo "<p>Testing repeated connections to check for socket issues...</p>";
    
    // Test opening and closing connections multiple times
    for ($i = 1; $i <= 5; $i++) {
        $conn = new mysqli($host, $user, $pass, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Connection $i failed: " . $conn->connect_error);
        }
        echo "<p>Connection $i: Success</p>";
        $conn->close();
    }
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; border-radius: 4px;'>
        <strong>All tests passed!</strong> Database connection is working properly.
    </div>";
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
    
    echo "<h2>Troubleshooting</h2>";
    
    echo "<p>1. Make sure MySQL is running</p>";
    echo "<p>2. Check your database credentials</p>";
    echo "<p>3. Make sure the database exists</p>";
    echo "<p>4. Check for socket or port issues</p>";
    
    // Check MySQL server status
    echo "<h3>MySQL Server Status</h3>";
    $mysql_status = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe" /FO LIST');
    if (empty($mysql_status)) {
        echo "<p style='color: #a94442;'>MySQL server process was not found. The service might not be running.</p>";
    } else {
        echo "<pre>" . htmlspecialchars($mysql_status) . "</pre>";
    }
}

echo "<p><a href='/VBIS-main/public/' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Return to Home Page</a></p>";
?> 