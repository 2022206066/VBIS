<?php
// Database structure fix script

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Structure Fix</h1>";

// Connect directly without framework
$conn = new mysqli('localhost', 'root', '', 'satellite_tracker');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Log and output helper function
function logAndOutput($message) {
    echo $message . "<br>";
    error_log($message);
}

logAndOutput("Step 1: Checking Current Structure");

// Check if role_id column exists in users table
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'role_id'");
$roleColumnExists = ($columns && $columns->num_rows > 0);
logAndOutput("Role column in users table: " . ($roleColumnExists ? "Exists" : "Does not exist"));

// Check current roles
$roles = $conn->query("SELECT * FROM roles");
logAndOutput("<h3>Current Roles:</h3>");
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th></tr>";
while ($role = $roles->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($role['id']) . "</td>";
    echo "<td>" . htmlspecialchars($role['name']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 2: Fix roles table if needed
logAndOutput("<h3>Step 2: Reorganizing Roles Table</h3>");

// Check if role with ID 3 exists
$roleThree = $conn->query("SELECT * FROM roles WHERE id = 3");
if ($roleThree && $roleThree->num_rows > 0) {
    // Disable foreign key checks temporarily
    logAndOutput("Temporarily disabled foreign key checks");
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    $role = $roleThree->fetch_assoc();
    if ($role && $role['name'] === 'User') {
        logAndOutput("User role ID is currently 3, need to change to 2");
        
        // Check if there's a role with ID 2
        $roleTwo = $conn->query("SELECT * FROM roles WHERE id = 2");
        if ($roleTwo && $roleTwo->num_rows > 0) {
            // Delete role with ID 2 if it exists
            $conn->query("DELETE FROM roles WHERE id = 2");
            logAndOutput("Removed existing role with ID 2");
        }
        
        // Update User role to ID 2
        $conn->query("UPDATE roles SET id = 2 WHERE id = 3 AND name = 'User'");
        logAndOutput("Updated User role ID to 2");
        
        // Update references in user_roles
        $conn->query("UPDATE user_roles SET id_role = 2 WHERE id_role = 3");
        logAndOutput("Updated user_roles table to use role ID 2");
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    logAndOutput("Re-enabled foreign key checks");
}

// Step 3: Add role_id column to users table if it doesn't exist
if (!$roleColumnExists) {
    logAndOutput("<h3>Step 3: Adding Role Column to Users Table</h3>");
    $conn->query("ALTER TABLE users ADD COLUMN role_id INT NULL");
    logAndOutput("Added role_id column to users table");
}

// Step 4: Update user role values
logAndOutput("<h3>Step 4: Updating User Role Values</h3>");

// Get users
$users = $conn->query("SELECT id, email FROM users");
while ($user = $users->fetch_assoc()) {
    // Get role for this user
    $userRole = $conn->query("SELECT r.id FROM roles r 
                             JOIN user_roles ur ON r.id = ur.id_role 
                             WHERE ur.id_user = {$user['id']} LIMIT 1");
    
    if ($userRole && $userRole->num_rows > 0) {
        $role = $userRole->fetch_assoc();
        $roleId = $role['id'];
        
        // Update user's role_id
        $conn->query("UPDATE users SET role_id = {$roleId} WHERE id = {$user['id']}");
        logAndOutput("Updated user ID {$user['id']} with role ID {$roleId}");
    } else {
        // Default to User role (ID 2)
        $conn->query("UPDATE users SET role_id = 2 WHERE id = {$user['id']}");
        logAndOutput("User ID {$user['id']} has no role, defaulted to User role (ID 2)");
        
        // Also add entry to user_roles table
        $conn->query("INSERT IGNORE INTO user_roles (id_user, id_role) VALUES ({$user['id']}, 2)");
        logAndOutput("Added User role entry to user_roles table for user ID {$user['id']}");
    }
}

// Step 5: Check for Anonymous role
logAndOutput("<h3>Step 5: Removing Anonymous Roles</h3>");
$anonymousRole = $conn->query("SELECT * FROM roles WHERE name = 'Anonymous'");

if ($anonymousRole && $anonymousRole->num_rows > 0) {
    // Get Anonymous role ID
    $anonRole = $anonymousRole->fetch_assoc();
    $anonRoleId = $anonRole['id'];
    
    // Update any users with Anonymous role to User role
    $conn->query("UPDATE user_roles SET id_role = 2 WHERE id_role = {$anonRoleId}");
    
    // Delete Anonymous role
    $conn->query("DELETE FROM roles WHERE id = {$anonRoleId}");
    logAndOutput("Removed Anonymous role and updated users to User role");
} else {
    logAndOutput("No Anonymous role found");
}

// Final check
logAndOutput("<h2>Final Database Structure</h2>");

// Check users with roles
logAndOutput("<h3>Users with Roles:</h3>");
$usersWithRoles = $conn->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
");
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

// Check final roles
logAndOutput("<h3>Final Roles:</h3>");
$roles = $conn->query("SELECT * FROM roles");
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th></tr>";
while ($role = $roles->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($role['id']) . "</td>";
    echo "<td>" . htmlspecialchars($role['name']) . "</td>";
    echo "</tr>";
}
echo "</table>";

logAndOutput("<h2>All done!</h2>");
echo "<p><a href='/VBIS-main/public/debug_database.php'>Return to Database Check</a></p>";
echo "<p><a href='/VBIS-main/public'>Return to the application</a></p>";
?> 