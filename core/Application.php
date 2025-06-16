<?php

namespace app\core;

class Application
{
    public Router $router;
    public Session $session;
    public Database $db;
    public static Application $app;
    public static string $BASE_URL = '/VBIS-main/public';
    public static string $ROOT_DIR;
    public Request $request;
    public Response $response;
    public ?Controller $controller;

    public function __construct()
    {
        $this->router = new Router();
        $this->session = new Session();
        $this->db = new Database();
        self::$app = $this;
        
        $this->addCustomRoutes();
        
        // Add no-cache headers for development
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Register error handler
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function run()
    {
        try {
            // Global authentication check
            $this->checkAuthentication();
            
            $this->router->resolve();
        } catch (\Exception $e) {
            $this->displayError($e->getMessage(), $e->getCode());
        }
    }
    
    private function checkAuthentication()
    {
        $path = $this->router->request->path();
        
        // Skip authentication for static assets
        if (strpos($path, '/assets/') === 0 || 
            strpos($path, '/sattelite-tracker/') === 0) {
            return;
        }
        
        // List of paths that don't require authentication
        $publicPaths = [
            '/login', 
            '/processLogin', 
            '/registration', 
            '/processRegistration',
            '/login-verify',
            '/debug-database',
            '/debug-session',
            '/fix-database',
            '/fix-session',
            '/fix-passwords',
            '/check-login',
            '/test-satellite-tracker',
            '/direct-tracker',
            '/satellite-debug',
            '/simple-test',
            '/test-simple'
        ];
        
        // Check if current path is public
        foreach ($publicPaths as $publicPath) {
            if ($path === $publicPath) {
                return; // Allow access to public paths
            }
        }
        
        // For all other paths, check if user is logged in
        if (!$this->session->get('user')) {
            error_log("User not authenticated, redirecting to login from path: $path");
            header("Location: " . self::url('/login'));
            exit;
        }
    }
    
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->displayError("Error [$errno]: $errstr in $errfile on line $errline", 500);
        return true; // Don't execute PHP's internal error handler
    }
    
    public function handleException($exception) 
    {
        $this->displayError($exception->getMessage(), $exception->getCode() ?: 500);
    }
    
    protected function displayError($message, $code = 404)
    {
        http_response_code($code);
        echo "<h1>Error $code</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
        echo "<hr>";
        echo "<p><a href='" . self::url('/') . "'>Return to Home Page</a></p>";
        exit;
    }
    
    public static function url($path = '')
    {
        // If it's already an absolute URL, return as is
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        
        // Make sure the path starts with a slash
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return self::$BASE_URL . $path;
    }

    private function addCustomRoutes()
    {
        $this->router->get('/accessDenied', [AccountController::class, 'accessDenied']);
        $this->router->get('/debug-database', function () {
            include __DIR__ . '/../public/debug_database.php';
        });
        $this->router->get('/debug-session', function () {
            include __DIR__ . '/../public/debug_session.php';
        });
        $this->router->get('/fix-database', function () {
            include __DIR__ . '/../public/fix_database.php';
        });
        $this->router->get('/fix-session', function () {
            include __DIR__ . '/../public/fix_session.php';
        });
        $this->router->get('/fix-passwords', function () {
            include __DIR__ . '/../public/fix_passwords.php';
        });
        $this->router->get('/reassign-satellites', function () {
            include __DIR__ . '/../public/reassign_satellites.php';
        });
    }
} 