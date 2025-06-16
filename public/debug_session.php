<?php
// Session debugging tool

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../vendor/autoload.php";

use app\core\Session;
use app\core\Database;
use app\core\Application;

// Initialize session
$session = new Session();

echo "<h1>Session Debug Info</h1>";

// Dump session data
echo "<h2>Raw Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
$userData = $session->get('user');
if ($userData) {
    echo "<h2>User Session Data</h2>";
    echo "<pre>";
    print_r($userData);
    echo "</pre>";
    
    // Check role functions
    echo "<h2>Role Checks</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Method</th><th>Result</th></tr>";
    
    // Direct check from session
    $hasAdminInSession = false;
    if (isset($userData[0]['role'])) {
        $hasAdminInSession = ($userData[0]['role'] === 'Administrator');
    }
    
    // Check role_id from session
    $hasAdminRoleId = false;
    if (isset($userData[0]['role_id'])) {
        $hasAdminRoleId = ($userData[0]['role_id'] == 1);
    }
    
    // Check using isInRole method
    $isAdminMethod = $session->isInRole('Administrator');
    $isUserMethod = $session->isInRole('User');
    
    // Display results
    echo "<tr><td>From session['role']</td><td>" . ($hasAdminInSession ? "Is Administrator" : "Not Administrator") . "</td></tr>";
    echo "<tr><td>From session['role_id']</td><td>" . ($hasAdminRoleId ? "Is Administrator (role_id=1)" : "Not Administrator") . "</td></tr>";
    echo "<tr><td>isInRole('Administrator')</td><td>" . ($isAdminMethod ? "Yes" : "No") . "</td></tr>";
    echo "<tr><td>isInRole('User')</td><td>" . ($isUserMethod ? "Yes" : "No") . "</td></tr>";
    echo "</table>";
    
    // Add user data from DB
    echo "<h2>Database User Data</h2>";
    $db = new Database();
    $conn = $db->getConnection();
    
    $userId = $userData[0]['id'];
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name, role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($row as $field => $value) {
            echo "<tr><td>" . htmlspecialchars($field) . "</td><td>" . htmlspecialchars($value ?? 'null') . "</td></tr>";
        }
        echo "</table>";
        
        // Get role name
        if ($row['role_id']) {
            $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->bind_param("i", $row['role_id']);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            
            if ($roleRow = $roleResult->fetch_assoc()) {
                echo "<p>Database role name: " . htmlspecialchars($roleRow['name']) . "</p>";
            }
        }
    } else {
        echo "<p>User not found in database!</p>";
    }
    
    // Check user_roles table
    echo "<h2>User Roles Table Data</h2>";
    $roleStmt = $conn->prepare("SELECT r.id, r.name FROM roles r 
                               JOIN user_roles ur ON r.id = ur.id_role 
                               WHERE ur.id_user = ?");
    $roleStmt->bind_param("i", $userId);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    
    if ($roleResult->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Role ID</th><th>Role Name</th></tr>";
        while ($role = $roleResult->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($role['id']) . "</td><td>" . htmlspecialchars($role['name']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No roles found in user_roles table!</p>";
    }
} else {
    echo "<h2>No User Session Found</h2>";
    echo "<p>You are not logged in</p>";
}

// Create a "Re-Login" form
echo "<h2>Re-Login to Refresh Session</h2>";
echo "<form action='/VBIS-main/public/processLogin' method='post'>";
echo "<div><label>Email: <input type='email' name='email' required></label></div>";
echo "<div style='margin-top: 10px;'><label>Password: <input type='password' name='password' required></label></div>";
echo "<div style='margin-top: 10px;'><button type='submit'>Login Again</button></div>";
echo "</form>";

echo "<h2>Quick Fix</h2>";
echo "<form action='fix_session.php' method='post'>";
echo "<input type='hidden' name='fix' value='1'>";
echo "<button type='submit'>Reset & Fix Session</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='/VBIS-main/public/debug_database.php'>Check Database Structure</a></p>";
echo "<p><a href='/VBIS-main/public/fix_database.php'>Fix Database Structure</a></p>";
echo "<p><a href='/VBIS-main/public/processLogout'>Logout</a></p>";
echo "<p><a href='/VBIS-main/public'>Return to Application</a></p>";
?> 