<?php

require_once __DIR__ . '/vendor/autoload.php';

use app\core\Application;

// Initialize the application to get database connection
$app = new Application(__DIR__);

// Turn on verbose error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check connection
try {
    $db = $app->db;
    $mysqli = $db->getConnection();
    echo "Connected to MySQL successfully<br>";
    
    // Check if database exists
    $result = $mysqli->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    echo "Database '{$row[0]}' exists<br>";
    
    // Table to check (default to imported_files or get from query param)
    $tableToCheck = $_GET['check_table'] ?? 'imported_files';
    
    // Check if table exists
    $result = $mysqli->query("SHOW TABLES LIKE '$tableToCheck'");
    if ($result->num_rows > 0) {
        echo "Table '$tableToCheck' exists<br>";
        
        // Get table structure
        $tableStructure = $mysqli->query("DESCRIBE $tableToCheck");
        if ($tableStructure) {
            echo "Table structure:<br>";
            echo "<table border='1' cellpadding='3'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            while ($field = $tableStructure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$field['Field']}</td>";
                echo "<td>{$field['Type']}</td>";
                echo "<td>{$field['Null']}</td>";
                echo "<td>{$field['Key']}</td>";
                echo "<td>{$field['Default']}</td>";
                echo "<td>{$field['Extra']}</td>";
                echo "</tr>";
            }
            
            echo "</table><br>";
        }
        
        // Count records
        $result = $mysqli->query("SELECT COUNT(*) AS count FROM $tableToCheck");
        $row = $result->fetch_assoc();
        echo "Table has {$row['count']} records<br>";
        
        // Show sample record if table has data
        if ($row['count'] > 0) {
            $result = $mysqli->query("SELECT * FROM $tableToCheck LIMIT 1");
            $row = $result->fetch_assoc();
            echo "Sample record:<br>";
            echo "<pre>";
            print_r($row);
            echo "</pre>";
        }
        
        // For users table, show all users
        if ($tableToCheck === 'users' && $row['count'] > 0) {
            $result = $mysqli->query("SELECT * FROM users");
            echo "All users:<br>";
            echo "<table border='1' cellpadding='3'>";
            
            // Get column names
            $firstRow = $result->fetch_assoc();
            echo "<tr>";
            foreach (array_keys($firstRow) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            
            // Display first row
            echo "<tr>";
            foreach ($firstRow as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
            
            // Display remaining rows
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>$value</td>";
                }
                echo "</tr>";
            }
            
            echo "</table><br>";
        }
    } else {
        echo "Table '$tableToCheck' does not exist<br>";
    }
    
    // Close the connection
    $mysqli->close();
    echo "Connection closed";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Add some navigation links
echo "<hr>";
echo "<p>";
echo "<a href='?check_table=imported_files'>Check imported_files table</a> | ";
echo "<a href='?check_table=users'>Check users table</a> | ";
echo "<a href='?check_table=satellites'>Check satellites table</a>";
echo "</p>";

// Add link to add_sample_imports.php
echo "<p><a href='add_sample_imports.php'>Add Sample Import Data</a></p>";
?> 