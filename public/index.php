<?php

// Special case for debug scripts - if direct file access
$requestUri = $_SERVER['REQUEST_URI'];
$debugFiles = ['debug_database.php', 'debug_session.php', 'fix_database.php', 'fix_session.php', 'check_login.php'];

foreach ($debugFiles as $file) {
    if (strpos($requestUri, $file) !== false) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            require $fullPath;
            exit;
        }
    }
}

// Continue with normal application flow
require_once __DIR__ . "/../vendor/autoload.php";

use app\controllers\AuthController;
use app\controllers\HomeController;
use app\controllers\ReportController;
use app\controllers\SatelliteController;
use app\controllers\AccountController;
use app\core\Application;
use app\models\ImportedFileModel;

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Special handler for direct PHP file access
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/VBIS-main/public/test-satellite-tracker.php') !== false) {
    include __DIR__ . '/test-satellite-tracker.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/direct-tracker.php') !== false) {
    include __DIR__ . '/direct-tracker.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/satellite-debug.php') !== false) {
    include __DIR__ . '/satellite-debug.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/simple-test.php') !== false) {
    include __DIR__ . '/simple-test.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/test-simple.php') !== false) {
    include __DIR__ . '/test-simple.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/direct-delete-satellites.php') !== false) {
    include __DIR__ . '/direct-delete-satellites.php';
    exit;
}
if (strpos($requestUri, '/VBIS-main/public/direct-import.php') !== false) {
    include __DIR__ . '/direct-import.php';
    exit;
}

$app = new Application();

// Initialize database and tables if needed
try {
    $app->db->ensureDatabase();
    
    // Also make sure the imported_files table is created
    ImportedFileModel::createTable();
} catch (Exception $e) {
    // Log error but continue - we'll show proper errors later if DB access fails
    error_log("Database initialization error: " . $e->getMessage());
}

// Home routes
$app->router->get("/", [HomeController::class, 'home']);

// Auth routes
$app->router->get("/login", [AuthController::class, 'login']);
$app->router->get("/registration", [AuthController::class, 'registration']);
$app->router->post("/processLogin", [AuthController::class, 'processLogin']);
$app->router->post("/processRegistration", [AuthController::class, 'processRegistration']);
$app->router->get("/processLogout", [AuthController::class, 'processLogout']);
$app->router->get("/accessDenied", [AuthController::class, 'accessDenied']);
$app->router->get("/login-verify", [AuthController::class, 'verifyLogin']);

// Testing routes for session diagnostics
$app->router->get("/loginTest", [AuthController::class, 'loginTest']);
$app->router->get("/loginTestAction", [AuthController::class, 'loginTestAction']);

// Satellite routes
$app->router->get("/satellites", [SatelliteController::class, 'list']);
$app->router->post("/satellites", [SatelliteController::class, 'list']);
$app->router->get("/satelliteDetail", [SatelliteController::class, 'detail']);
$app->router->get("/importSatellites", [SatelliteController::class, 'import']);
$app->router->post("/processImport", [SatelliteController::class, 'processImport']);
$app->router->get("/exportJson", [SatelliteController::class, 'exportJson']);
$app->router->get("/exportXml", [SatelliteController::class, 'exportXml']);
$app->router->post("/reassignSatellites", [SatelliteController::class, 'reassignSatellites']);
$app->router->get("/deleteAllSatellites", [SatelliteController::class, 'deleteAllSatellites']);
$app->router->get("/removeDuplicates", [SatelliteController::class, 'removeDuplicates']);
$app->router->post("/removeDuplicates", [SatelliteController::class, 'removeDuplicates']);

// Report routes
$app->router->get("/satelliteStatistics", [ReportController::class, 'satelliteStatistics']);
$app->router->get("/exportSatelliteStatsJson", [ReportController::class, 'exportSatelliteStatsJson']);
$app->router->get("/exportSatelliteStatsXml", [ReportController::class, 'exportSatelliteStatsXml']);
$app->router->get("/importStatistics", [ReportController::class, 'importStatistics']);
// Route removed: $app->router->get("/positionStatistics", [ReportController::class, 'positionStatistics']);
$app->router->get("/filterPositions", [ReportController::class, 'filterPositions']);

// Account routes - ensure they are properly prefixed and match the menu links
$app->router->get("/account", [AccountController::class, 'account']);
$app->router->post("/updateAccount", [AccountController::class, 'updateAccount']);
$app->router->get("/deleteAccount", [AccountController::class, 'deleteAccount']);
$app->router->get("/accounts", [AccountController::class, 'manageAccounts']);
$app->router->get("/editAccount", [AccountController::class, 'editAccount']);
$app->router->post("/updateUserAccount", [AccountController::class, 'updateUserAccount']);
$app->router->get("/deleteUserAccount", [AccountController::class, 'deleteUserAccount']);
$app->router->get("/createAccount", [AccountController::class, 'createAccount']);
$app->router->post("/saveAccount", [AccountController::class, 'saveAccount']);
$app->router->get("/checkUserSatellites", [AccountController::class, 'checkUserSatellites']);

// Debug output for account routes
error_log("Account routes registered: " . json_encode([
    "/account" => "account",
    "/updateAccount" => "updateAccount",
    "/deleteAccount" => "deleteAccount", 
    "/accounts" => "manageAccounts",
    "/editAccount" => "editAccount",
    "/updateUserAccount" => "updateUserAccount",
    "/deleteUserAccount" => "deleteUserAccount",
    "/createAccount" => "createAccount",
    "/saveAccount" => "saveAccount"
]));

// Test routes
$app->router->get("/test-satellite-tracker", function() {
    include __DIR__ . '/test-satellite-tracker.php';
});
$app->router->get("/direct-tracker", function() {
    include __DIR__ . '/direct-tracker.php';
});
$app->router->get("/satellite-debug", function() {
    include __DIR__ . '/satellite-debug.php';
});
$app->router->get("/simple-test", function() {
    include __DIR__ . '/simple-test.php';
});
$app->router->get("/test-simple", function() {
    include __DIR__ . '/test-simple.php';
});
$app->router->get("/fix-roles", function() {
    include __DIR__ . '/fix_roles.php';
});
$app->router->get("/fix-database", function() {
    $path = __DIR__ . '/fix_database.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

// Debug routes setup
$app->router->get("/debug-database", function() {
    $path = __DIR__ . '/debug_database.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

$app->router->get("/debug-session", function() {
    $path = __DIR__ . '/debug_session.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

$app->router->get("/fix-session", function() {
    $path = __DIR__ . '/fix_session.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

$app->router->get("/check-login", function() {
    $path = __DIR__ . '/check_login.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

$app->router->get("/fix-passwords", function() {
    $path = __DIR__ . '/fix_passwords.php';
    if (file_exists($path)) {
        include $path;
        exit;
    } else {
        echo "Debug file not found at: " . $path;
    }
});

$app->run(); 