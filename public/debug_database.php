<?php
// Simple database structure check

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect directly without framework
$conn = new mysqli('localhost', 'root', '', 'satellite_tracker');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Structure Check</h1>";

// Check tables
echo "<h2>Tables</h2>";
$tables = $conn->query("SHOW TABLES");
if ($tables) {
    echo "<ul>";
    while ($table = $tables->fetch_row()) {
        echo "<li>" . htmlspecialchars($table[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Error fetching tables</p>";
}

// Check users table structure
echo "<h2>Users Table Structure</h2>";
$columns = $conn->query("SHOW COLUMNS FROM users");
if ($columns) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($column = $columns->fetch_assoc()) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error fetching columns or table doesn't exist</p>";
}

// Check roles table
echo "<h2>Roles</h2>";
$roles = $conn->query("SELECT * FROM roles");
if ($roles) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($role = $roles->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($role['id']) . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error fetching roles or table doesn't exist</p>";
}

// Check users with roles
echo "<h2>Users with Roles</h2>";
$usersWithRoles = $conn->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
");
if ($usersWithRoles) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role ID</th><th>Role Name</th></tr>";
    while ($user = $usersWithRoles->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($user['role_name'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error fetching users with roles</p>";
}

// Check user_roles table
echo "<h2>User Roles Table</h2>";
$userRoles = $conn->query("
    SELECT ur.id_user, ur.id_role, u.email, r.name
    FROM user_roles ur
    JOIN users u ON ur.id_user = u.id
    JOIN roles r ON ur.id_role = r.id
");
if ($userRoles) {
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>User Email</th><th>Role ID</th><th>Role Name</th></tr>";
    while ($userRole = $userRoles->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($userRole['id_user']) . "</td>";
        echo "<td>" . htmlspecialchars($userRole['email']) . "</td>";
        echo "<td>" . htmlspecialchars($userRole['id_role']) . "</td>";
        echo "<td>" . htmlspecialchars($userRole['name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error fetching user roles or table doesn't exist</p>";
}

// Create a fix button
echo "<h2>Fix Database</h2>";
echo "<form action='fix_database.php' method='post'>";
echo "<input type='submit' name='fix' value='Fix Database Structure'>";
echo "</form>";

// Return links
echo "<p><a href='/VBIS-main/public'>Return to Home</a></p>";
echo "<p><a href='/VBIS-main/public/check_login.php'>Check Login Session</a></p>";
?> 