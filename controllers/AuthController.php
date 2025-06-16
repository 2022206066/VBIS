<?php

namespace app\controllers;

use app\core\Application;
use app\core\BaseController;
use app\models\LoginModel;
use app\models\RegistrationModel;
use app\models\RoleModel;
use app\models\SessionUserModel;
use app\models\UserRoleModel;

class AuthController extends BaseController
{
    public function login()
    {
        if (Application::$app->session->get('user')) {
            header("location:" . Application::url('/'));
            exit;
        }

        $this->view->render('login', 'auth', new LoginModel());
    }

    public function registration()
    {
        if (Application::$app->session->get('user')) {
            header("location:" . Application::url('/'));
            exit;
        }

        $this->view->render('registration', 'auth', new RegistrationModel());
    }

    public function processLogin()
    {
        // For debugging
        error_log("processLogin started with session ID: " . session_id());
        
        $model = new LoginModel();
        $model->mapData($_POST);
        $model->validate();

        if ($model->errors) {
            error_log("Login validation failed: " . json_encode($model->errors));
            Application::$app->session->set('errorNotification', 'Login failed: Invalid form data');
            $this->view->render('login', 'auth', $model);
            exit;
        }

        $loginPassword = $model->password;
        $email = $model->email;
        error_log("Attempting to login with email: " . $email);

        // Direct database access for better control and debugging
        $conn = new \mysqli('localhost', 'root', '', 'satellite_tracker');
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            $model->errors['email'] = 'Database connection error';
            $this->view->render('login', 'auth', $model);
            exit;
        }

        // Get user with password and role_id for verification
        $userQuery = "SELECT id, email, password, first_name, last_name, role_id FROM users WHERE email = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            error_log("Email not found: " . $email);
            $model->errors['email'] = 'Email not found';
            Application::$app->session->set('errorNotification', 'Login failed: Email not found');
            $this->view->render('login', 'auth', $model);
            exit;
        }
        
        $user = $userResult->fetch_assoc();
        error_log("User found, verifying password");
        error_log("Password in DB length: " . strlen($user['password']));
        error_log("Password provided: " . substr($loginPassword, 0, 3) . "... (length: " . strlen($loginPassword) . ")");
        
        // Verify password
        $verifyResult = password_verify($loginPassword, $user['password']);
        error_log("Password verify result: " . ($verifyResult ? 'true' : 'false'));

        if (!$verifyResult) {
            $model->password = '';
            $model->errors['password'] = 'Invalid password';
            Application::$app->session->set('errorNotification', 'Login failed: Invalid password');
            error_log("Invalid password for email: " . $email);
            $this->view->render('login', 'auth', $model);
            exit;
        }
        
        // Check user's role from role_id
        $roleId = $user['role_id'];
        error_log("User role_id: " . ($roleId ?: 'null'));
        
        if (!$roleId) {
            // If role_id is not set, check user_roles table
            error_log("No role_id found, checking user_roles table");
            
            $rolesQuery = "SELECT r.id, r.name FROM roles r 
                           INNER JOIN user_roles ur ON r.id = ur.id_role 
                           WHERE ur.id_user = ?";
            $rolesStmt = $conn->prepare($rolesQuery);
            $rolesStmt->bind_param("i", $user['id']);
            $rolesStmt->execute();
            $rolesResult = $rolesStmt->get_result();
            
            if ($rolesResult->num_rows === 0) {
                // No roles found
                error_log("No roles found for user with ID: " . $user['id']);
                $model->errors['email'] = 'No roles assigned to user';
                Application::$app->session->set('errorNotification', 'Login failed: User has no roles assigned');
                $model->password = '';
                $this->view->render('login', 'auth', $model);
                exit;
            }
            
            // Get first role from result (should only have one primary role)
            $role = $rolesResult->fetch_assoc();
            $roleId = $role['id'];
            $roleName = $role['name'];
            
            // Update the user's role_id in the database
            $updateStmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $roleId, $user['id']);
            $updateStmt->execute();
            error_log("Updated user's role_id to $roleId");
        } else {
            // Get role name from role_id
            $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->bind_param("i", $roleId);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            
            if ($roleResult->num_rows === 0) {
                error_log("Role with ID $roleId not found");
                $roleName = "Unknown";
            } else {
                $roleRow = $roleResult->fetch_assoc();
                $roleName = $roleRow['name'];
            }
        }
        
        error_log("User role determined as: $roleName (ID: $roleId)");
        
        // Build session data with correct role information
        $sessionData = [
            [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $roleName,
                'role_id' => $roleId
            ]
        ];
        
        // Set session data
        Application::$app->session->set('user', $sessionData);
        Application::$app->session->set('successNotification', 'Login successful!');
        
        // For debugging, log the session data
        error_log("Setting session data: " . json_encode($sessionData));
        
        // Force flush output buffer to ensure session data is saved
        if (ob_get_level()) {
            ob_end_flush();
        }
        session_write_close();
        
        // Redirect directly to home
        header("Location: " . Application::url('/'));
        exit;
    }

    public function verifyLogin()
    {
        error_log("Login verification started with session ID: " . session_id());
        
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        
        $sessionToken = Application::$app->session->get('verificationToken');
        $userData = Application::$app->session->get('user');
        
        error_log("Verification check: Token match=" . ($token === $sessionToken ? 'true' : 'false') . 
                  ", Has user data=" . (!empty($userData) ? 'true' : 'false'));
        
        // If verification successful, redirect to home
        if ($token === $sessionToken && !empty($userData)) {
            // Clear verification token
            Application::$app->session->delete('verificationToken');
            
            $homeUrl = Application::url('/');
            error_log("Verification successful, redirecting to: " . $homeUrl);
            
            header("Location: " . $homeUrl);
            echo <<<HTML
            <script>
                console.log("Login verified! Redirecting to home...");
                window.location.href = "{$homeUrl}";
            </script>
            <p>Login successful! If you are not redirected automatically, <a href="{$homeUrl}">click here</a>.</p>
HTML;
            exit;
        } else {
            // If verification failed, try to recover
            error_log("Verification failed, trying to recover session");
            
            // Try to recover user data based on email
            if (!empty($email)) {
                $sessionUserModel = new SessionUserModel();
                $sessionUserModel->email = $email;
                $sessionData = $sessionUserModel->getSessionData();
                
                if (!empty($sessionData)) {
                    Application::$app->session->set('user', $sessionData);
                    Application::$app->session->set('successNotification', 'Login recovered successfully!');
                    
                    $homeUrl = Application::url('/');
                    header("Location: " . $homeUrl);
                    echo <<<HTML
                    <script>
                        console.log("Login recovered! Redirecting to home...");
                        window.location.href = "{$homeUrl}";
                    </script>
                    <p>Login recovered! If you are not redirected automatically, <a href="{$homeUrl}">click here</a>.</p>
HTML;
                    exit;
                }
            }
            
            // If recovery failed, redirect to login
            $loginUrl = Application::url('/login');
            Application::$app->session->set('errorNotification', 'Session verification failed. Please login again.');
            header("Location: " . $loginUrl);
            echo <<<HTML
            <script>
                console.log("Verification failed! Redirecting to login...");
                window.location.href = "{$loginUrl}";
            </script>
            <p>Verification failed! If you are not redirected automatically, <a href="{$loginUrl}">click here</a>.</p>
HTML;
            exit;
        }
    }

    public function processRegistration()
    {
        $model = new RegistrationModel();
        $model->mapData($_POST);
        $model->validate();

        if ($model->errors) {
            $this->view->render('registration', 'auth', $model);
            exit;
        }

        // Check if email already exists
        $existingUser = new LoginModel();
        $result = $existingUser->one("WHERE email = '$model->email'");
        
        if ($result) {
            $model->errors['email'] = 'Email is already registered';
            $this->view->render('registration', 'auth', $model);
            exit;
        }

        // Hash password
        $model->password = password_hash($model->password, PASSWORD_DEFAULT);

        // Insert user
        $result = $model->insert();
        
        if ($result) {
            // Get the User role ID (should be 2)
            $roleModel = new RoleModel();
            $roleResult = $roleModel->one("WHERE name = 'User'");
            $roleId = $roleResult ? $roleModel->id : 2; // Default to 2 if not found
            
            // Update the user's role_id in the database
            $conn = new \mysqli('localhost', 'root', '', 'satellite_tracker');
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
            } else {
                $updateStmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $roleId, $model->id);
                $updateStmt->execute();
                error_log("Registration: Set role_id to $roleId for user ID " . $model->id);
            }
            
            // Also assign to user_roles table for compatibility
            $userRoleModel = new UserRoleModel();
            $userRoleModel->id_user = $model->id;
            $userRoleModel->id_role = $roleId;
            $userRoleModel->insert();
            
            Application::$app->session->set('successNotification', 'Registration successful! Please login.');
            header("location:" . Application::url('/login'));
            exit;
        } else {
            Application::$app->session->set('errorNotification', 'Registration failed! Please try again.');
            $this->view->render('registration', 'auth', $model);
        }
    }

    public function processLogout()
    {
        Application::$app->session->delete('user');
        header("location:" . Application::url('/login'));
    }

    public function accessDenied()
    {
        $this->view->render('accessDenied', 'auth', null);
    }

    public function accessRole(): array
    {
        return [];
    }

    // Emergency login method for testing
    public function loginTest()
    {
        // Show current session info
        echo "<h1>Session Diagnostic Page</h1>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>Session Status: " . session_status() . " (1=disabled, 2=active)</p>";
        
        // Show if user is logged in
        echo "<p>User logged in: " . (Application::$app->session->get('user') ? 'Yes' : 'No') . "</p>";
        
        // Show session data
        echo "<h2>Session Data:</h2>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        // Show cookie info
        echo "<h2>Cookie Information:</h2>";
        echo "<pre>";
        print_r($_COOKIE);
        echo "</pre>";
        
        // Add a manual login link
        echo "<h2>Actions:</h2>";
        echo "<p><a href='" . Application::url('/loginTestAction?email=admin@example.com') . "'>Test Login as Admin</a></p>";
        echo "<p><a href='" . Application::url('/loginTestAction?email=user@example.com') . "'>Test Login as User</a></p>";
        echo "<p><a href='" . Application::url('/processLogout') . "'>Logout</a></p>";
        
        // Show php info
        echo "<h2>PHP Info:</h2>";
        echo "<p>PHP Version: " . phpversion() . "</p>";
        echo "<p>Session Save Path: " . ini_get('session.save_path') . "</p>";
        echo "<p>Session Save Handler: " . ini_get('session.save_handler') . "</p>";
        
        // Provide links
        echo "<p><a href='" . Application::url('/') . "'>Return to Home</a></p>";
    }
    
    // Test login action (emergency backdoor)
    public function loginTestAction()
    {
        $email = $_GET['email'] ?? 'admin@example.com';
        
        echo "<h1>Login Test Action</h1>";
        echo "<p>Attempting to login with email: $email</p>";
        
        // Check database connection
        $conn = new \mysqli('localhost', 'root', '', 'vbis');
        if ($conn->connect_error) {
            echo "<p>Error: Database connection failed: " . $conn->connect_error . "</p>";
            echo "<p><a href='" . Application::url('/loginTest') . "'>Back to Test Page</a></p>";
            exit;
        }
        
        echo "<p>Database connection successful</p>";
        
        // Check if user exists
        $userQuery = "SELECT id, email, first_name, last_name FROM users WHERE email = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            echo "<p>Error: User not found with email: $email</p>";
            echo "<p><a href='" . Application::url('/loginTest') . "'>Back to Test Page</a></p>";
            exit;
        }
        
        $user = $userResult->fetch_assoc();
        echo "<p>User found: ID = {$user['id']}, Name = {$user['first_name']} {$user['last_name']}</p>";
        
        // Check user roles
        $rolesQuery = "SELECT r.id, r.name FROM roles r 
                      INNER JOIN user_roles ur ON r.id = ur.id_role 
                      WHERE ur.id_user = ?";
        $rolesStmt = $conn->prepare($rolesQuery);
        $rolesStmt->bind_param("i", $user['id']);
        $rolesStmt->execute();
        $rolesResult = $rolesStmt->get_result();
        
        if ($rolesResult->num_rows === 0) {
            echo "<p>Error: No roles found for user</p>";
            
            // See if there are any roles in the system
            $allRolesQuery = "SELECT * FROM roles";
            $allRolesResult = $conn->query($allRolesQuery);
            echo "<p>Available roles in system: " . $allRolesResult->num_rows . "</p>";
            if ($allRolesResult->num_rows > 0) {
                echo "<ul>";
                while ($role = $allRolesResult->fetch_assoc()) {
                    echo "<li>Role ID: {$role['id']}, Name: {$role['name']}</li>";
                }
                echo "</ul>";
            }
            
            // Check user_roles table
            $userRolesQuery = "SELECT * FROM user_roles WHERE id_user = ?";
            $userRolesStmt = $conn->prepare($userRolesQuery);
            $userRolesStmt->bind_param("i", $user['id']);
            $userRolesStmt->execute();
            $userRolesResult = $userRolesStmt->get_result();
            
            echo "<p>User-Role assignments: " . $userRolesResult->num_rows . "</p>";
            if ($userRolesResult->num_rows > 0) {
                echo "<ul>";
                while ($userRole = $userRolesResult->fetch_assoc()) {
                    echo "<li>ID: {$userRole['id']}, User ID: {$userRole['id_user']}, Role ID: {$userRole['id_role']}</li>";
                }
                echo "</ul>";
            } else {
                // Assign a role if none exists
                echo "<p>Adding 'Korisnik' role to user...</p>";
                $insertRoleQuery = "INSERT INTO user_roles (id_user, id_role) VALUES (?, 2)";
                $insertRoleStmt = $conn->prepare($insertRoleQuery);
                $insertRoleStmt->bind_param("i", $user['id']);
                
                if ($insertRoleStmt->execute()) {
                    echo "<p>Role added successfully!</p>";
                } else {
                    echo "<p>Error adding role: " . $conn->error . "</p>";
                }
            }
            
            echo "<p><a href='" . Application::url('/loginTestAction') . "?email=$email'>Try again</a></p>";
            echo "<p><a href='" . Application::url('/loginTest') . "'>Back to Test Page</a></p>";
            exit;
        }
        
        // Get roles for session
        $roles = [];
        while ($role = $rolesResult->fetch_assoc()) {
            echo "<p>Role found: ID = {$role['id']}, Name = {$role['name']}</p>";
            $roles[] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $role['name']
            ];
        }
        
        // Force login
        Application::$app->session->set('user', $roles);
        Application::$app->session->set('successNotification', 'Login successful via test method!');
        
        echo "<p>Session data set:</p>";
        echo "<pre>";
        print_r($roles);
        echo "</pre>";
        
        // Add a direct link to the home page
        echo "<p><a href='" . Application::url('/') . "'>Go to Home Page</a></p>";
        
        // Flush and redirect
        if (ob_get_level()) {
            ob_end_flush();
        }
        session_write_close();
    }
} 