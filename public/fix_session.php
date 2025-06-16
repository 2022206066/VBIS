<?php
// Script to fix user session data

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../vendor/autoload.php";

use app\core\Session;
use app\core\Database;

// Initialize session
$session = new Session();

echo "<h1>Session Fix</h1>";

// Check if user is logged in
$userData = $session->get('user');
if ($userData) {
    // Get user ID from session
    $userId = $userData[0]['id'] ?? null;
    
    if ($userId) {
        echo "<p>Found user with ID $userId in session</p>";
        
        // Connect to DB
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get user data from database
        $stmt = $conn->prepare("SELECT id, email, first_name, last_name, role_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Get role name
            $roleName = "User"; // Default
            $roleId = $row['role_id'] ?? null;
            
            if ($roleId) {
                $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
                $roleStmt->bind_param("i", $roleId);
                $roleStmt->execute();
                $roleResult = $roleStmt->get_result();
                
                if ($roleRow = $roleResult->fetch_assoc()) {
                    $roleName = $roleRow['name'];
                }
            } else {
                // Check user_roles table
                $urStmt = $conn->prepare("SELECT r.id, r.name FROM roles r 
                                        JOIN user_roles ur ON r.id = ur.id_role 
                                        WHERE ur.id_user = ? LIMIT 1");
                $urStmt->bind_param("i", $userId);
                $urStmt->execute();
                $urResult = $urStmt->get_result();
                
                if ($urRow = $urResult->fetch_assoc()) {
                    $roleId = $urRow['id'];
                    $roleName = $urRow['name'];
                    
                    // Update user's role_id
                    $updateStmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $roleId, $userId);
                    $updateStmt->execute();
                    echo "<p>Updated user's role_id to $roleId in database</p>";
                } else {
                    // Default to User role
                    $roleId = 2;
                    $roleName = "User";
                    
                    // Update user's role_id
                    $updateStmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $roleId, $userId);
                    $updateStmt->execute();
                    echo "<p>No role found for user, set default role_id = 2 (User)</p>";
                    
                    // Add to user_roles table
                    $insertStmt = $conn->prepare("INSERT IGNORE INTO user_roles (id_user, id_role) VALUES (?, ?)");
                    $insertStmt->bind_param("ii", $userId, $roleId);
                    $insertStmt->execute();
                }
            }
            
            // Create new session data
            $newSessionData = [
                [
                    'id' => $row['id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'role' => $roleName,
                    'role_id' => $roleId
                ]
            ];
            
            // Update session
            $session->set('user', $newSessionData);
            echo "<p><strong>Session updated with correct role information!</strong></p>";
            
            echo "<h3>New Session Data:</h3>";
            echo "<pre>";
            print_r($newSessionData);
            echo "</pre>";
        } else {
            echo "<p>Error: User not found in database!</p>";
        }
    } else {
        echo "<p>Error: No user ID in session data!</p>";
    }
} else {
    echo "<p>Error: No user logged in!</p>";
}

echo "<hr>";
echo "<p><a href='/VBIS-main/public/debug_session.php'>Return to Session Debug</a></p>";
echo "<p><a href='/VBIS-main/public'>Return to Application</a></p>";
?> 