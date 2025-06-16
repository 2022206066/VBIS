<?php

require_once __DIR__ . "/vendor/autoload.php";

use app\core\DbConnection;

// Test database connection
echo "<h1>Database Connection Test</h1>";

try {
    $db = new DbConnection();
    $conn = $db->connect();
    echo "<p style='color:green'>Connection successful!</p>";
    
    // Check if the roles table exists and has the User role
    $roleCheck = $conn->query("SELECT * FROM roles WHERE name = 'User'");
    if ($roleCheck && $roleCheck->num_rows > 0) {
        echo "<p>User role exists in the database.</p>";
    } else {
        echo "<p>User role does not exist. Creating it now...</p>";
        $conn->query("INSERT INTO roles (name) VALUES ('User')");
        echo "<p>User role created.</p>";
    }
    
    // Check if the Administrator role exists
    $adminCheck = $conn->query("SELECT * FROM roles WHERE name = 'Administrator'");
    if ($adminCheck && $adminCheck->num_rows > 0) {
        echo "<p>Administrator role exists in the database.</p>";
    } else {
        echo "<p>Administrator role does not exist. Creating it now...</p>";
        $conn->query("INSERT INTO roles (name) VALUES ('Administrator')");
        echo "<p>Administrator role created.</p>";
    }
    
    // Check users table structure
    echo "<h2>Users Table Structure</h2>";
    $tableStructure = $conn->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($field = $tableStructure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check user_roles table structure
    echo "<h2>User Roles Table Structure</h2>";
    $tableStructure = $conn->query("DESCRIBE user_roles");
    if ($tableStructure) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($field = $tableStructure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $field['Field'] . "</td>";
            echo "<td>" . $field['Type'] . "</td>";
            echo "<td>" . $field['Null'] . "</td>";
            echo "<td>" . $field['Key'] . "</td>";
            echo "<td>" . $field['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>user_roles table doesn't exist!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>1. <a href='/VBIS-main/public/registration'>Try registering a new user</a></p>";
echo "<p>2. <a href='/VBIS-main/public/login'>Try logging in</a></p>";
echo "<p>3. <a href='/VBIS-main/public/'>Go to the home page</a></p>";
echo "<p>4. <a href='/VBIS-main/public/satellites'>View satellites</a></p>";
echo "<p>5. <a href='/VBIS-main/public/sattelite-tracker/single.html'>Test satellite tracker</a></p>";
?> 