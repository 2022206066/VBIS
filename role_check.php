<?php
require_once __DIR__ . "/vendor/autoload.php";

use app\core\Database;
use app\core\Session;

// Create database connection
$db = new Database();
$con = $db->getConnection();

// Start session
$session = new Session();

// Check current user session
echo "<h1>Current User Session</h1>";
echo "<pre>";
var_dump($session->get('user'));
echo "</pre>";

// Check current user roles from database
$userData = $session->get('user');
if ($userData) {
    $userId = $userData[0]['id'];
    echo "<h1>User Roles from Database for User ID: $userId</h1>";
    
    $query = "SELECT u.id, u.first_name, u.last_name, r.id as role_id, r.name as role_name 
              FROM users u
              LEFT JOIN user_roles ur ON u.id = ur.id_user
              LEFT JOIN roles r ON ur.id_role = r.id
              WHERE u.id = $userId";
    
    $result = $con->query($query);
    echo "<pre>";
    $roles = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
    }
    var_dump($roles);
    echo "</pre>";
}

// Check all users and their roles
echo "<h1>All Users and Roles</h1>";
$query = "SELECT u.id, u.first_name, u.last_name, r.name as role_name 
          FROM users u
          LEFT JOIN user_roles ur ON u.id = ur.id_user
          LEFT JOIN roles r ON ur.id_role = r.id
          ORDER BY u.id";

$result = $con->query($query);
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . ($row['role_name'] ?: 'No Role') . "</td>";
        echo "</tr>";
    }
}
echo "</table>"; 