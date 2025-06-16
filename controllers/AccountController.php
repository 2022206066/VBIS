<?php

namespace app\controllers;

use app\core\Application;
use app\core\BaseController;
use app\models\UserModel;
use app\models\RoleModel;
use app\models\UserRoleModel;
use app\core\Database;

class AccountController extends BaseController
{
    public function account()
    {
        error_log("AccountController::account() method called directly");
        
        // Get user session data
        $userData = Application::$app->session->get('user');
        error_log("AccountController::account() - User data: " . json_encode($userData));
        
        // Check if user is logged in
        if (!$userData) {
            Application::$app->session->set('errorNotification', 'You must be logged in to access this page');
            header("location:" . Application::url('/login'));
            exit;
        }
        
        // Get the user ID
        $userId = $userData[0]['id'];
        
        // Check access directly from session data first
        $hasAccess = false;
        
        // If we have role_id in session, use that
        if (isset($userData[0]['role_id'])) {
            $roleId = $userData[0]['role_id'];
            $hasAccess = ($roleId == 1 || $roleId == 2); // Administrator or User
            error_log("Checking role_id from session: $roleId, hasAccess: " . ($hasAccess ? 'true' : 'false'));
        }
        
        // If we have role in session, check that too
        if (!$hasAccess && isset($userData[0]['role'])) {
            $role = $userData[0]['role'];
            $hasAccess = ($role === 'Administrator' || $role === 'User');
            error_log("Checking role from session: $role, hasAccess: " . ($hasAccess ? 'true' : 'false'));
        }
        
        // If still no access, check database directly
        if (!$hasAccess) {
            error_log("No access determined from session, checking database");
            
            $dbObj = new Database();
            $conn = $dbObj->getConnection();
            
            // Check user's role_id
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $roleId = $row['role_id'];
                
                if ($roleId) {
                    error_log("User role_id from database: " . $roleId);
                    $hasAccess = ($roleId == 1 || $roleId == 2); // Administrator or User
                }
            }
            
            // Still no access? Check user_roles table
            if (!$hasAccess) {
                error_log("Still no access determined, checking user_roles table");
                
                $stmt = $conn->prepare("SELECT r.id, r.name FROM roles r 
                                      JOIN user_roles ur ON r.id = ur.id_role 
                                      WHERE ur.id_user = ? AND r.name IN ('Administrator', 'User')");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $hasAccess = ($result && $result->num_rows > 0);
                error_log("Access from user_roles table: " . ($hasAccess ? 'granted' : 'denied'));
            }
        }
        
        if (!$hasAccess) {
            error_log("User doesn't have required roles, redirecting to access denied");
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        // Get user data and render page
        $model = new UserModel();
        $model->getUserWithRoles($userId);
        
        error_log("Rendering account/edit view");
        $this->view->render('account/edit', 'main', $model);
    }
    
    public function updateAccount()
    {
        // Check if user is logged in
        $userData = Application::$app->session->get('user');
        if (!$userData) {
            Application::$app->session->set('errorNotification', 'You must be logged in to access this page');
            header("location:" . Application::url('/login'));
            exit;
        }
        
        $userId = $userData[0]['id'];
        $model = new UserModel();
        $model->getUserWithRoles($userId);
        
        // Handle update
        $updateSuccess = $model->updateUser($_POST);
        
        if ($updateSuccess) {
            // Update session data
            $newUserData = [];
            foreach ($userData as $user) {
                $user['first_name'] = $model->first_name;
                $user['last_name'] = $model->last_name;
                $user['email'] = $model->email;
                $newUserData[] = $user;
            }
            
            Application::$app->session->set('user', $newUserData);
            Application::$app->session->set('successNotification', 'Account updated successfully');
            header("location:" . Application::url('/account'));
            exit;
        } else {
            $this->view->render('account/edit', 'main', $model);
        }
    }
    
    public function deleteAccount()
    {
        // Check if user is logged in
        $userData = Application::$app->session->get('user');
        if (!$userData) {
            Application::$app->session->set('errorNotification', 'You must be logged in to access this page');
            header("location:" . Application::url('/login'));
            exit;
        }
        
        $userId = $userData[0]['id'];
        $model = new UserModel();
        
        // Delete user
        if ($model->deleteUser($userId)) {
            // Clear session
            Application::$app->session->delete('user');
            Application::$app->session->set('successNotification', 'Account deleted successfully');
            header("location:" . Application::url('/'));
            exit;
        } else {
            Application::$app->session->set('errorNotification', 'Failed to delete account');
            header("location:" . Application::url('/account'));
            exit;
        }
    }
    
    public function manageAccounts()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        $model = new UserModel();
        $users = $model->getAllUsers();
        
        $this->view->render('account/list', 'main', ['users' => $users]);
    }
    
    public function editAccount()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        $model = new UserModel();
        $model->getUserWithRoles($id);
        
        if (!$model->id) {
            Application::$app->session->set('errorNotification', 'User not found');
            header("location:" . Application::url('/accounts'));
            exit;
        }
        
        // Get available roles
        $roleModel = new RoleModel();
        $availableRoles = $roleModel->all("ORDER BY id ASC");
        
        $this->view->render('account/admin-edit', 'main', [
            'user' => $model,
            'availableRoles' => $availableRoles
        ]);
    }
    
    public function updateUserAccount()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        $id = $_POST['id'] ?? 0;
        $model = new UserModel();
        $model->getUserWithRoles($id);
        
        if (!$model->id) {
            Application::$app->session->set('errorNotification', 'User not found');
            header("location:" . Application::url('/accounts'));
            exit;
        }
        
        // Set the role_id in the model
        if (isset($_POST['role_id'])) {
            $roleId = (int) $_POST['role_id'];
            
            // Update user's role_id in the database
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $roleId, $id);
            $stmt->execute();
            
            // Delete all existing roles
            $stmt = $conn->prepare("DELETE FROM user_roles WHERE id_user = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Insert new role
            $userRoleModel = new UserRoleModel();
            $userRoleModel->id_user = $id;
            $userRoleModel->id_role = $roleId;
            $userRoleModel->insert();
        }
        
        // Handle update
        $updateSuccess = $model->updateUser($_POST);
        
        if ($updateSuccess) {
            Application::$app->session->set('successNotification', 'User account updated successfully');
            header("location:" . Application::url('/accounts'));
            exit;
        } else {
            // Get available roles for re-rendering the form
            $roleModel = new RoleModel();
            $availableRoles = $roleModel->all("ORDER BY id ASC");
            
            $this->view->render('account/admin-edit', 'main', [
                'user' => $model,
                'availableRoles' => $availableRoles
            ]);
        }
    }
    
    public function deleteUserAccount()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        // Check if this is a special user with data constraints
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if user has satellites
        $satelliteCheck = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
        $satelliteCheck->bind_param("i", $id);
        $satelliteCheck->execute();
        $satelliteResult = $satelliteCheck->get_result();
        $satelliteCount = $satelliteResult->fetch_assoc()['count'];
        
        if ($satelliteCount > 0) {
            // User has satellites, cannot be deleted directly
            Application::$app->session->set('errorNotification', 
                'Cannot delete user: This user has added ' . $satelliteCount . ' satellites to the system. ' .
                'You need to reassign or delete these satellites first.');
            header("location:" . Application::url('/editAccount?id=' . $id));
            exit;
        }
        
        // Check for other constraints
        try {
            $model = new UserModel();
            
            // Delete user
            if ($model->deleteUser($id)) {
                Application::$app->session->set('successNotification', 'User account deleted successfully');
            } else {
                Application::$app->session->set('errorNotification', 'Failed to delete user account');
            }
        } catch (\Exception $e) {
            // Check for foreign key constraint error
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                Application::$app->session->set('errorNotification', 
                    'Cannot delete user: This user has data associated with it in the system. ' .
                    'You need to remove these references first.');
            } else {
                Application::$app->session->set('errorNotification', 'Error deleting user: ' . $e->getMessage());
            }
        }
        
        header("location:" . Application::url('/accounts'));
        exit;
    }
    
    /**
     * AJAX endpoint to check if a user has satellites
     */
    public function checkUserSatellites()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        
        // Check if user has satellites
        $db = new Database();
        $conn = $db->getConnection();
        
        $satelliteCheck = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
        $satelliteCheck->bind_param("i", $id);
        $satelliteCheck->execute();
        $satelliteResult = $satelliteCheck->get_result();
        $satelliteCount = $satelliteResult->fetch_assoc()['count'];
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'hasSatellites' => $satelliteCount > 0,
            'count' => $satelliteCount
        ]);
        exit;
    }
    
    public function accessRole(): array
    {
        error_log("AccountController::accessRole() called");
        
        // Define action-specific roles here, with additional logging
        $roles = [
            'account' => ['User', 'Administrator'],
            'updateAccount' => ['User', 'Administrator'],
            'deleteAccount' => ['User', 'Administrator'],
            'manageAccounts' => ['Administrator'],
            'editAccount' => ['Administrator'],
            'updateUserAccount' => ['Administrator'],
            'deleteUserAccount' => ['Administrator'],
            'createAccount' => ['Administrator'],
            'saveAccount' => ['Administrator']
        ];
        
        error_log("AccountController::accessRole() returning roles: " . json_encode($roles));
        return $roles;
    }
    
    public function createAccount()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        // Get available roles
        $roleModel = new RoleModel();
        $availableRoles = $roleModel->all("ORDER BY id ASC");
        
        $model = new UserModel();
        
        $this->view->render('account/create', 'main', [
            'user' => $model,
            'availableRoles' => $availableRoles
        ]);
    }
    
    public function saveAccount()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        $model = new UserModel();
        $model->mapData($_POST);
        
        // Ensure password and confirm_password match
        if ($model->password != $_POST['confirm_password']) {
            $model->errors['confirm_password'] = 'Password confirmation does not match';
            
            // Get available roles for re-rendering the form
            $roleModel = new RoleModel();
            $availableRoles = $roleModel->all("ORDER BY id ASC");
            
            $this->view->render('account/create', 'main', [
                'user' => $model,
                'availableRoles' => $availableRoles
            ]);
            return;
        }
        
        // Validate the model
        $model->validate();
        
        if (!empty($model->errors)) {
            // Get available roles for re-rendering the form
            $roleModel = new RoleModel();
            $availableRoles = $roleModel->all("ORDER BY id ASC");
            
            $this->view->render('account/create', 'main', [
                'user' => $model,
                'availableRoles' => $availableRoles
            ]);
            return;
        }
        
        // Set role_id
        $roleId = $_POST['role_id'] ?? 2; // Default to User role
        $model->role_id = $roleId;
        
        // Hash the password only if it's not already hashed
        if (strlen($model->password) < 40) { // Simple check for non-hashed password
            $model->password = password_hash($model->password, PASSWORD_DEFAULT);
        }
        
        // Log the password for debugging
        error_log("Creating user: {$model->email} with password length: " . strlen($model->password));
        
        // Save the user
        $model->insert();
        
        // Get the user's ID from database after insertion
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $model->email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $userId = $row['id'];
            
            // Insert role relationship
            $userRoleModel = new UserRoleModel();
            $userRoleModel->id_user = $userId;
            $userRoleModel->id_role = $roleId;
            $userRoleModel->insert();
            
            // Double-check that user was created properly
            $checkStmt = $conn->prepare("SELECT id, email, password FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkRow = $checkResult->fetch_assoc();
            
            if ($checkRow) {
                error_log("User created: ID={$checkRow['id']}, Email={$checkRow['email']}, PwdLen=" . strlen($checkRow['password']));
            } else {
                error_log("Failed to verify created user with ID $userId");
            }
        }
        
        // Redirect back to accounts list with success message
        Application::$app->session->set('successNotification', 'User account created successfully!');
        header("location:" . Application::url('/accounts'));
        exit;
    }
} 