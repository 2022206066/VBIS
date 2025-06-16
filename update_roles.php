<?php
header("Content-Type: text/html");
require_once __DIR__ . "/vendor/autoload.php";

use app\core\Database;

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// First, check if we have both Anonymous and User roles
echo "<h1>Role Update Script</h1>";
$result = $conn->query("SELECT id, name FROM roles WHERE name IN ('Anonymous', 'User') ORDER BY id");

if ($result) {
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[$row['name']] = $row['id'];
    }
    
    echo "<h2>Current Roles</h2>";
    echo "<pre>";
    print_r($roles);
    echo "</pre>";
    
    if (isset($roles['Anonymous']) && isset($roles['User'])) {
        // If both exist, we need to:
        // 1. Update user_roles table to change Anonymous to User
        // 2. Delete the Anonymous role
        
        $stmt = $conn->prepare("UPDATE user_roles SET id_role = ? WHERE id_role = ?");
        $stmt->bind_param("ii", $roles['User'], $roles['Anonymous']);
        
        if ($stmt->execute()) {
            echo "Updated all Anonymous role assignments to User role.<br>";
            
            // Now delete the Anonymous role
            if ($conn->query("DELETE FROM roles WHERE id = " . $roles['Anonymous'])) {
                echo "Deleted Anonymous role successfully.<br>";
            } else {
                echo "Error deleting Anonymous role: " . $conn->error . "<br>";
            }
        } else {
            echo "Error updating role assignments: " . $stmt->error . "<br>";
        }
    } else if (isset($roles['Anonymous']) && !isset($roles['User'])) {
        // If only Anonymous exists, rename it to User
        if ($conn->query("UPDATE roles SET name = 'User' WHERE id = " . $roles['Anonymous'])) {
            echo "Renamed Anonymous role to User successfully.<br>";
        } else {
            echo "Error renaming Anonymous role: " . $conn->error . "<br>";
        }
    } else if (!isset($roles['Anonymous']) && isset($roles['User'])) {
        // If only User exists, nothing to do
        echo "User role already exists and Anonymous role doesn't exist. Nothing to do.<br>";
    } else {
        // Neither exists, create User role
        if ($conn->query("INSERT INTO roles (name) VALUES ('User')")) {
            echo "Created User role successfully.<br>";
        } else {
            echo "Error creating User role: " . $conn->error . "<br>";
        }
    }
} else {
    echo "Error querying roles: " . $conn->error;
}

// Display final role list
echo "<h2>Updated Roles</h2>";
$result = $conn->query("SELECT id, name FROM roles ORDER BY id");
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