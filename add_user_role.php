<?php

// A simple script to add a 'User' role to the database

require_once __DIR__ . "/vendor/autoload.php";

use app\core\Database;

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if User role already exists
$result = $conn->query("SELECT id FROM roles WHERE name = 'User'");
if ($result && $result->num_rows > 0) {
    echo "User role already exists.";
} else {
    // Add User role
    $result = $conn->query("INSERT INTO roles (name) VALUES ('User')");
    if ($result) {
        echo "User role added successfully.";
        
        // Get the ID of the new role
        $roleId = $conn->insert_id;
        echo "<br>New User role ID: " . $roleId;
        
        // Update the anonymous user to have User role as well
        $stmt = $conn->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (2, ?)");
        $stmt->bind_param("i", $roleId);
        if ($stmt->execute()) {
            echo "<br>Anonymous user now has User role.";
        } else {
            echo "<br>Error assigning User role to anonymous user: " . $conn->error;
        }
    } else {
        echo "Error adding User role: " . $conn->error;
    }
}

// Display all roles
echo "<h2>Current Roles</h2>";
$result = $conn->query("SELECT * FROM roles");
if ($result) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td></tr>";
    }
    echo "</table>";
}

// Display all user_roles
echo "<h2>User Role Assignments</h2>";
$result = $conn->query("SELECT ur.id, ur.id_user, u.first_name, u.last_name, ur.id_role, r.name as role_name 
                       FROM user_roles ur
                       JOIN users u ON ur.id_user = u.id
                       JOIN roles r ON ur.id_role = r.id");
if ($result) {
    echo "<table border='1'><tr><th>ID</th><th>User</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['id_user'] . " - " . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . $row['id_role'] . " - " . $row['role_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "Done.\n"; 