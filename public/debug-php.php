<?php
// PHP Execution Diagnostic Script
header('Content-Type: text/html; charset=utf-8');

// Output basic HTML
echo '<!DOCTYPE html>';
echo '<html><head><title>PHP Diagnostics</title>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
</style>';
echo '</head><body>';
echo '<h1>PHP Execution Diagnostics</h1>';

// 1. Basic PHP execution test
echo '<div class="section">';
echo '<h2>1. Basic PHP Execution</h2>';
echo '<p class="success">PHP is executing correctly! This script is running.</p>';
echo '</div>';

// 2. PHP Version
echo '<div class="section">';
echo '<h2>2. PHP Version</h2>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
echo '</div>';

// 3. Directory and file permissions
echo '<div class="section">';
echo '<h2>3. Directory and File Permissions</h2>';
$currentDir = __DIR__;
echo '<p>Current directory: ' . $currentDir . '</p>';
echo '<p>Is readable: ' . (is_readable($currentDir) ? 'Yes' : 'No') . '</p>';
echo '<p>Is writable: ' . (is_writable($currentDir) ? 'Yes' : 'No') . '</p>';

$thisFile = __FILE__;
echo '<p>This file: ' . $thisFile . '</p>';
echo '<p>Is readable: ' . (is_readable($thisFile) ? 'Yes' : 'No') . '</p>';
echo '<p>File permissions: ' . substr(sprintf('%o', fileperms($thisFile)), -4) . '</p>';

// Check for important files
$importantFiles = [
    '../core/BaseModel.php',
    '../controllers/SatelliteController.php',
    '../models/SatelliteModel.php',
    'index.php',
    'test-direct-import.php'
];

echo '<h3>Important Files:</h3><ul>';
foreach ($importantFiles as $file) {
    $fullPath = realpath(__DIR__ . '/' . $file);
    echo '<li>' . $file . ' - ';
    if (file_exists($fullPath)) {
        echo 'Exists (Permissions: ' . substr(sprintf('%o', fileperms($fullPath)), -4) . ')';
    } else {
        echo '<span class="error">Missing</span>';
    }
    echo '</li>';
}
echo '</ul>';
echo '</div>';

// 4. PHP Extensions
echo '<div class="section">';
echo '<h2>4. PHP Extensions</h2>';
$requiredExtensions = ['mysqli', 'pdo', 'pdo_mysql', 'xml', 'json', 'session'];
echo '<ul>';
foreach ($requiredExtensions as $ext) {
    echo '<li>' . $ext . ': ' . (extension_loaded($ext) ? '<span class="success">Loaded</span>' : '<span class="error">Not loaded</span>') . '</li>';
}
echo '</ul>';
echo '</div>';

// 5. Configuration settings
echo '<div class="section">';
echo '<h2>5. PHP Configuration</h2>';
$configSettings = [
    'display_errors',
    'error_reporting',
    'max_execution_time',
    'memory_limit',
    'post_max_size',
    'upload_max_filesize',
    'max_file_uploads'
];
echo '<ul>';
foreach ($configSettings as $setting) {
    echo '<li>' . $setting . ': ' . ini_get($setting) . '</li>';
}
echo '</ul>';
echo '</div>';

// 6. Session test
echo '<div class="section">';
echo '<h2>6. Session Test</h2>';
session_start();
echo '<p>Session ID: ' . session_id() . '</p>';
$_SESSION['test'] = 'Session test value';
echo '<p>Session set test: ' . $_SESSION['test'] . '</p>';
echo '<p>Current session contents:</p>';
echo '<pre>' . print_r($_SESSION, true) . '</pre>';
echo '</div>';

// 7. Database connection test
echo '<div class="section">';
echo '<h2>7. Database Connection Test</h2>';
try {
    require_once __DIR__ . '/../core/Database.php';
    $db = new app\core\Database();
    $conn = $db->getConnection();
    echo '<p class="success">Database connection successful!</p>';
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM satellites");
    if ($result) {
        $row = $result->fetch_assoc();
        echo '<p>Satellites in database: ' . $row['count'] . '</p>';
    } else {
        echo '<p class="error">Error executing query: ' . $conn->error . '</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">Database connection error: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// 8. Additional Tests
echo '<div class="section">';
echo '<h2>8. Test Links</h2>';
echo '<p>Click these links to test various scripts:</p>';
echo '<ul>';
echo '<li><a href="test.php" target="_blank">Simple PHP Test</a></li>';
echo '<li><a href="phpinfo.php" target="_blank">PHP Info</a></li>';
echo '<li><a href="test-direct-import.php" target="_blank">Test Direct Import</a></li>';
echo '<li><a href="import.php" target="_blank">Simple Import</a></li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
echo '</body></html>';
?> 