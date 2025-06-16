<?php

namespace app\core;

class Session
{
    public function __construct()
    {
        // Use output buffering to prevent "headers already sent" errors
        if (!ob_get_level()) {
            ob_start();
        }
        
        // Simple session start
        if (session_status() === PHP_SESSION_NONE) {
            // For local development, ease cookie restrictions
            ini_set('session.use_strict_mode', 0);
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Start the session
            session_start();
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function showSuccessNotification()
    {
        $message = $this->get("successNotification");
        if ($message) {
            echo "
            <script>
                toastr.success('$message')
            </script>
            ";

            $this->delete("successNotification");
        }
    }

    public function showErrorNotification()
    {
        $message = $this->get("errorNotification");

        if ($message) {
            echo "
            <script>
                toastr.error('$message')
            </script>
            ";

            $this->delete("errorNotification");
        }
    }

    public function isInRole($roleName)
    {
        error_log("isInRole checking for role: $roleName");
        
        $userData = $this->get('user');
        if (!$userData) {
            error_log("isInRole: No user data in session");
            return false;
        }
        
        // Get the user's ID from session
        if (!isset($userData[0]['id'])) {
            error_log("isInRole: No user ID found in session data");
            return false;
        }
        
        // First, check if role_id is directly available in the session
        if (isset($userData[0]['role_id'])) {
            $roleId = $userData[0]['role_id'];
            error_log("isInRole: Found role_id in session: $roleId");
            
            // Map role names to IDs
            $roleMap = [
                'Administrator' => 1,
                'User' => 2
            ];
            
            if (isset($roleMap[$roleName]) && $roleMap[$roleName] == $roleId) {
                error_log("isInRole: Direct match for role $roleName with ID $roleId");
                return true;
            }
        }
        
        // Check if 'role' is directly available in session
        if (isset($userData[0]['role']) && $userData[0]['role'] === $roleName) {
            error_log("isInRole: Direct match for role property: $roleName");
            return true;
        }
        
        // If no match in session data, check database
        $userId = $userData[0]['id'];
        error_log("isInRole: Checking database for user ID: $userId and role: $roleName");
        
        // Connect to database directly for the most accurate permissions
        $dbObj = new Database();
        $conn = $dbObj->getConnection();
        
        try {
            // First check users table for role_id
            $roleMap = [
                'Administrator' => 1,
                'User' => 2
            ];
            
            if (isset($roleMap[$roleName])) {
                $roleId = $roleMap[$roleName];
                $stmt = $conn->prepare("SELECT 1 FROM users WHERE id = ? AND role_id = ? LIMIT 1");
                $stmt->bind_param("ii", $userId, $roleId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    error_log("isInRole: User has role $roleName (ID: $roleId) in users table");
                    return true;
                }
            }
            
            // Fall back to user_roles table check
            $stmt = $conn->prepare("SELECT 1 FROM user_roles ur 
                                   INNER JOIN roles r ON ur.id_role = r.id 
                                   WHERE ur.id_user = ? AND r.name = ? LIMIT 1");
                                   
            $stmt->bind_param("is", $userId, $roleName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $hasRole = $result && $result->num_rows > 0;
            
            error_log("isInRole: User_roles table check result: " . ($hasRole ? "HAS ROLE" : "DOES NOT HAVE ROLE"));
            return $hasRole;
        } catch (\Exception $e) {
            error_log("isInRole: Database error: " . $e->getMessage());
            return false;
        }
    }
} 