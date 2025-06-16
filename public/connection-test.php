<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', __DIR__ . '/..');

echo "<h1>App Connection Test</h1>";

// Test direct database connection
echo "<h2>1. Direct Database Connection</h2>";
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
    
    // Close connection
    $mysqli->close();
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test namespace autoloading
echo "<h2>2. Namespace Autoloading</h2>";
try {
    // Add autoloader to handle app namespaces
    spl_autoload_register(function($class) {
        // Convert namespace to path
        $path = str_replace('\\', '/', $class);
        
        // Replace app with the actual path
        $path = str_replace('app/', '', $path);
        
        // Load the file
        $file = BASE_PATH . '/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    });
    
    // Test loading BaseModel class
    echo "<p>Testing loading of app\\core\\BaseModel class...</p>";
    if (class_exists('app\\core\\BaseModel')) {
        echo "<p style='color: green; font-weight: bold;'>✓ BaseModel class loaded successfully</p>";
    } else {
        throw new Exception("Failed to load BaseModel class");
    }
    
    // Test loading Database class
    echo "<p>Testing loading of app\\core\\Database class...</p>";
    if (class_exists('app\\core\\Database')) {
        echo "<p style='color: green; font-weight: bold;'>✓ Database class loaded successfully</p>";
    } else {
        throw new Exception("Failed to load Database class");
    }
    
    // Test loading SatelliteModel class
    echo "<p>Testing loading of app\\models\\SatelliteModel class...</p>";
    if (class_exists('app\\models\\SatelliteModel')) {
        echo "<p style='color: green; font-weight: bold;'>✓ SatelliteModel class loaded successfully</p>";
    } else {
        throw new Exception("Failed to load SatelliteModel class");
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test class instantiation
echo "<h2>3. Class Instantiation</h2>";
try {
    // Test DbConnection instantiation
    echo "<p>Testing app\\core\\DbConnection instantiation...</p>";
    $dbConnection = new app\core\DbConnection();
    echo "<p style='color: green; font-weight: bold;'>✓ DbConnection instantiated successfully</p>";
    
    // Test Database instantiation
    echo "<p>Testing app\\core\\Database instantiation...</p>";
    $database = new app\core\Database($dbConnection->connect());
    echo "<p style='color: green; font-weight: bold;'>✓ Database instantiated successfully</p>";
    
    // Test running a query through Database class
    echo "<p>Testing Database query...</p>";
    $result = $database->query("SELECT 1 as test");
    if (!$result) {
        throw new Exception("Database query failed");
    }
    echo "<p style='color: green; font-weight: bold;'>✓ Database query successful</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='/VBIS-main/public/' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Return to Home Page</a></p>";
?> 