<?php
// MySQL connection test - more verbose
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = ""; // Empty password is default for XAMPP
$database = "satellite_tracker";

echo "Testing connection to MySQL:\n";
echo "Host: $host\n";
echo "Username: $username\n";
echo "Database: $database\n\n";

// Test database connection without selecting a specific DB first
try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection to server failed: " . $conn->connect_error);
    }
    
    echo "Connection to MySQL server successful!\n\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$database'");
    if ($result && $result->num_rows > 0) {
        echo "Database '$database' exists.\n";
        
        // Now try to select the database
        if ($conn->select_db($database)) {
            echo "Successfully selected database '$database'.\n\n";
            
            // Test if the users table exists and has data
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            if ($result && $result->num_rows > 0) {
                echo "Table 'users' exists.\n";
                
                $result = $conn->query("SELECT id, email, password FROM users LIMIT 5");
                if ($result && $result->num_rows > 0) {
                    echo "Found " . $result->num_rows . " users in database:\n";
                    while ($row = $result->fetch_assoc()) {
                        echo "ID: " . $row["id"] . " - Email: " . $row["email"] . " - Password hash: " . substr($row["password"], 0, 15) . "...\n";
                    }
                } else {
                    echo "No users found in the table.\n";
                }
            } else {
                echo "Table 'users' does not exist.\n";
            }
        } else {
            echo "Failed to select database '$database'.\n";
        }
    } else {
        echo "Database '$database' does not exist. You need to create it and import the schema.\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 