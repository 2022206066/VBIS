<?php
// Direct access script to fix roles
require_once __DIR__ . "/../vendor/autoload.php";

use app\core\Database;

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Change database to match what's configured
$db_name = "satellite_tracker";

echo "<h1>Database Role Fixer</h1>";
echo "<p>Working with database: " . $db_name . "</p>";

// Check if User role exists
$result = $conn->query("SELECT id FROM roles WHERE name = 'User'");
if ($result && $result->num_rows == 0) {
    // User role doesn't exist, add it
    if ($conn->query("INSERT INTO roles (name) VALUES ('User')")) {
        echo "<p style='color:green'>Successfully added User role</p>";
    } else {
        echo "<p style='color:red'>Error adding User role: " . $conn->error . "</p>";
    }
}

// Check for Anonymous role and change it to User if it exists
$result = $conn->query("SELECT id FROM roles WHERE name = 'Anonymous'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $anonId = $row['id'];
    
    // Get the User role ID
    $userRoleId = null;
    $result = $conn->query("SELECT id FROM roles WHERE name = 'User'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userRoleId = $row['id'];
    }
    
    if ($userRoleId) {
        // Update any users with Anonymous role to have User role
        if ($conn->query("UPDATE user_roles SET id_role = $userRoleId WHERE id_role = $anonId")) {
            echo "<p style='color:green'>Updated users from Anonymous to User role</p>";
        } else {
            echo "<p style='color:red'>Error updating users from Anonymous to User role: " . $conn->error . "</p>";
        }
        
        // Now delete the Anonymous role
        if ($conn->query("DELETE FROM roles WHERE id = $anonId")) {
            echo "<p style='color:green'>Successfully deleted Anonymous role</p>";
        } else {
            echo "<p style='color:red'>Error deleting Anonymous role: " . $conn->error . "</p>";
        }
    }
}

// Show all current roles
echo "<h2>Current Roles</h2>";
$result = $conn->query("SELECT * FROM roles ORDER BY id");
if ($result) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error listing roles: " . $conn->error . "</p>";
}

// Show all users and their assigned roles
echo "<h2>User Role Assignments</h2>";
$result = $conn->query("SELECT u.id, u.first_name, u.last_name, u.email, r.name as role_name 
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.id_user
                        LEFT JOIN roles r ON ur.id_role = r.id
                        ORDER BY u.id");
if ($result) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['role_name'] ?: 'No Role') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error listing users: " . $conn->error . "</p>";
}

// Check for the current login users
echo "<h2>Looking for login users: admin@example.com and user@example.com</h2>";
$result = $conn->query("SELECT id, first_name, last_name, email FROM users 
                        WHERE email IN ('admin@example.com', 'user@example.com')");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>No users found with those emails. Let's add them.</p>";
        
        // Add admin user if it doesn't exist
        $adminPass = password_hash('adminexamplepass', PASSWORD_DEFAULT);
        if ($conn->query("INSERT INTO users (first_name, last_name, email, password) 
                        VALUES ('Admin', 'Example', 'admin@example.com', '$adminPass')")) {
            $adminId = $conn->insert_id;
            echo "<p style='color:green'>Added admin user with ID: $adminId</p>";
            
            // Get admin role ID
            $result = $conn->query("SELECT id FROM roles WHERE name = 'Administrator'");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $adminRoleId = $row['id'];
                
                // Assign admin role
                if ($conn->query("INSERT INTO user_roles (id_user, id_role) VALUES ($adminId, $adminRoleId)")) {
                    echo "<p style='color:green'>Assigned Administrator role to admin user</p>";
                } else {
                    echo "<p style='color:red'>Error assigning admin role: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>Error adding admin user: " . $conn->error . "</p>";
        }
        
        // Add regular user if it doesn't exist
        $userPass = password_hash('userexamplepass', PASSWORD_DEFAULT);
        if ($conn->query("INSERT INTO users (first_name, last_name, email, password) 
                        VALUES ('User', 'Example', 'user@example.com', '$userPass')")) {
            $userId = $conn->insert_id;
            echo "<p style='color:green'>Added regular user with ID: $userId</p>";
            
            // Get user role ID
            $result = $conn->query("SELECT id FROM roles WHERE name = 'User'");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $userRoleId = $row['id'];
                
                // Assign user role
                if ($conn->query("INSERT INTO user_roles (id_user, id_role) VALUES ($userId, $userRoleId)")) {
                    echo "<p style='color:green'>Assigned User role to regular user</p>";
                } else {
                    echo "<p style='color:red'>Error assigning user role: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>Error adding regular user: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>Error checking users: " . $conn->error . "</p>";
}

echo "<h2>All done! <a href='" . "/VBIS-main/public" . "'>Return to the application</a></h2>"; 