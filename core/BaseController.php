<?php

namespace app\core;

abstract class BaseController
{
    public View $view;

    abstract public function accessRole();

    public function __construct()
    {
        $this->view = new View();

        $controllerRoles = $this->accessRole();
        error_log("Controller access roles: " . print_r($controllerRoles, true));

        if ($controllerRoles == []) {
            error_log("No roles required, allowing access");
            return;
        }

        $sessionUserData = Application::$app->session->get('user');
        error_log("Session user data: " . ($sessionUserData ? json_encode($sessionUserData) : "null"));
        
        // Authentication is now handled at the Application level, so we only need to check roles
        if (!$sessionUserData) {
            error_log("No user session found in BaseController");
            return;
        }

        $action = $this->getAction();
        
        // Special debug for Account routes
        $className = get_class($this);
        if (strpos($className, 'AccountController') !== false) {
            error_log("AccountController: Current action determined to be: " . $action);
            error_log("AccountController: Request URI: " . $_SERVER['REQUEST_URI']);
            error_log("AccountController: Backtrace: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), true));
        }
        
        $hasAccess = false;

        if (isset($controllerRoles[$action])) {
            // Action-specific role check
            $allowedRoles = $controllerRoles[$action];
            error_log("Action-specific roles for '$action': " . json_encode($allowedRoles));
            
            // Check if the user has any of the required roles
            foreach ($allowedRoles as $role) {
                error_log("Checking if user has role: $role");
                if (Application::$app->session->isInRole($role)) {
                    $hasAccess = true;
                    error_log("User has required role $role, granting access");
                    break;
                } else {
                    error_log("User does NOT have required role $role");
                }
            }
        } else {
            // General controller access check
            error_log("No action-specific roles found, checking controller-level roles");
            
            foreach ($controllerRoles as $controllerRole) {
                if (is_array($controllerRole)) {
                    error_log("Skipping array entry in controller roles");
                    continue; // Skip action-specific entries
                }
                
                error_log("Checking if user has role: $controllerRole");
                if (Application::$app->session->isInRole($controllerRole)) {
                    $hasAccess = true;
                    error_log("User has required role $controllerRole, granting access");
                    break;
                } else {
                    error_log("User does NOT have required role $controllerRole");
                }
            }
        }

        if ($hasAccess) {
            error_log("Access granted");
            return;
        } else {
            error_log("Access denied, redirecting");
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
    }
    
    protected function getAction()
    {
        // Get the request URI
        $uri = $_SERVER['REQUEST_URI'];
        error_log("BaseController::getAction() - URI: $uri");
        
        // Extract the path from the URI
        $path = parse_url($uri, PHP_URL_PATH);
        error_log("BaseController::getAction() - Path: $path");
        
        // Remove the base path
        $basePath = "/VBIS-main/public";
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        error_log("BaseController::getAction() - Path after base removal: $path");
        
        // Ensure the path starts with /
        if (empty($path)) {
            $path = '/';
        } elseif ($path[0] !== '/') {
            $path = '/' . $path;
        }
        error_log("BaseController::getAction() - Normalized path: $path");
        
        // Special handling for the AccountController paths
        $className = get_class($this);
        if (strpos($className, 'AccountController') !== false) {
            error_log("BaseController::getAction() - Handling AccountController path");
            
            // Direct mappings for account paths
            $pathToActionMap = [
                '/account' => 'account',
                '/accounts' => 'manageAccounts',
                '/editAccount' => 'editAccount',
                '/updateAccount' => 'updateAccount',
                '/deleteAccount' => 'deleteAccount',
                '/updateUserAccount' => 'updateUserAccount',
                '/deleteUserAccount' => 'deleteUserAccount',
                '/createAccount' => 'createAccount',
                '/saveAccount' => 'saveAccount'
            ];
            
            if (isset($pathToActionMap[$path])) {
                $action = $pathToActionMap[$path];
                error_log("BaseController::getAction() - Found action in map: $action");
                return $action;
            }
        }
        
        // Try to get the action from the backtrace as a fallback
        $backtrace = debug_backtrace();
        if (isset($backtrace[2]['function'])) {
            $action = $backtrace[2]['function'];
            error_log("BaseController::getAction() - From backtrace: $action");
            return $action;
        }
        
        // Last resort: fall back to controller name as action
        $className = explode('\\', $className);
        $className = end($className);
        $className = str_replace('Controller', '', $className);
        $className = lcfirst($className);
        
        error_log("BaseController::getAction() - Fallback to controller name: $className");
        return $className;
    }
} 