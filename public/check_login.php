<?php
// Script to check login sessions and roles

require_once __DIR__ . "/../vendor/autoload.php";

use app\core\Session;
use app\core\Database;

// Initialize session
$session = new Session();
$userData = $session->get('user');

echo "<h1>Session and Role Checker</h1>";
echo "<h2>Session User Data</h2>";

if ($userData) {
    echo "<pre>";
    print_r($userData);
    echo "</pre>";
    
    echo "<h3>Role Checks</h3>";
    echo "Has Administrator role: " . ($session->isInRole('Administrator') ? 'Yes' : 'No') . "<br>";
    echo "Has User role: " . ($session->isInRole('User') ? 'Yes' : 'No') . "<br>";
    
    // Get user ID from session
    $userId = $userData[0]['id'];
    echo "<h3>User ID: $userId</h3>";
    
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check user in database
    echo "<h3>User Data from Database</h3>";
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($row as $field => $value) {
            echo "<tr><td>$field</td><td>" . htmlspecialchars($value ?? 'null') . "</td></tr>";
        }
        echo "</table>";
        
        // Get role name if role_id exists
        if ($row['role_id']) {
            $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->bind_param("i", $row['role_id']);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            
            if ($roleResult && $roleResult->num_rows > 0) {
                $roleRow = $roleResult->fetch_assoc();
                echo "<p>Role name from database: " . $roleRow['name'] . "</p>";
            }
        }
        
        // Check user_roles table
        echo "<h3>User Roles from user_roles Table</h3>";
        $rolesStmt = $conn->prepare("SELECT r.id, r.name FROM roles r 
                                   JOIN user_roles ur ON r.id = ur.id_role 
                                   WHERE ur.id_user = ?");
        $rolesStmt->bind_param("i", $userId);
        $rolesStmt->execute();
        $rolesResult = $rolesStmt->get_result();
        
        if ($rolesResult && $rolesResult->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Role ID</th><th>Role Name</th></tr>";
            while ($roleRow = $rolesResult->fetch_assoc()) {
                echo "<tr><td>" . $roleRow['id'] . "</td><td>" . $roleRow['name'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No roles found in user_roles table</p>";
        }
    } else {
        echo "<p>User not found in database</p>";
    }
} else {
    echo "<p>No user logged in</p>";
    
    // Show login form
    echo '<h3>Login Form</h3>
    <form action="' . '/VBIS-main/public/processLogin' . '" method="post">
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div style="margin-top: 10px;">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div style="margin-top: 10px;">
            <button type="submit">Login</button>
        </div>
    </form>';
}

echo "<p><a href='/VBIS-main/public'>Return to Home</a></p>";
echo "<p><a href='/VBIS-main/public/fix-database'>Run Database Fix Script</a></p>";
echo "<p><a href='/VBIS-main/public/processLogout'>Logout</a></p>"; 