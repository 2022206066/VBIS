<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display server variables to debug routing
echo "<h1>Server Variables</h1>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "</pre>";

// Test URL generation
echo "<h1>Test URLs</h1>";
echo "<pre>";
echo "Base URL should be: /VBIS-main/public\n";
echo "</pre>";

// Test paths with different formats
echo "<h1>Test Paths</h1>";
echo "<p>Click the links below to test different URL formats:</p>";
echo "<ul>";
echo "<li><a href='/VBIS-main/public/'>Root path (/VBIS-main/public/)</a></li>";
echo "<li><a href='/VBIS-main/public/login'>Login path (/VBIS-main/public/login)</a></li>";
echo "<li><a href='/VBIS-main/public/satellites'>Satellites path (/VBIS-main/public/satellites)</a></li>";
echo "<li><a href='/VBIS-main/public/satellites/'>Satellites path with trailing slash (/VBIS-main/public/satellites/)</a></li>";
echo "<li><a href='/VBIS-main/public/index.php/login'>Path with index.php (/VBIS-main/public/index.php/login)</a></li>";
echo "<li><a href='/VBIS-main/public/login.php'>Path with .php extension (/VBIS-main/public/login.php)</a></li>";
echo "</ul>";

// Test satellite tracker paths
echo "<h1>Satellite Tracker Paths</h1>";
echo "<ul>";
echo "<li><a href='/VBIS-main/public/sattelite-tracker/'>Satellite Tracker Home</a></li>";
echo "<li><a href='/VBIS-main/public/sattelite-tracker/single.html'>Single Satellite View</a></li>";
echo "</ul>";

// Check for mod_rewrite
echo "<h1>mod_rewrite Check</h1>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $mod_rewrite = in_array('mod_rewrite', $modules);
    echo "<p>mod_rewrite is " . ($mod_rewrite ? "enabled" : "not enabled") . "</p>";
} else {
    echo "<p>Unable to check if mod_rewrite is enabled</p>";
}

// Check for .htaccess files
echo "<h1>.htaccess Files Check</h1>";
$rootHtaccess = file_exists(__DIR__ . '/.htaccess');
$publicHtaccess = file_exists(__DIR__ . '/public/.htaccess');

echo "<p>Root .htaccess: " . ($rootHtaccess ? "exists" : "does not exist") . "</p>";
echo "<p>Public .htaccess: " . ($publicHtaccess ? "exists" : "does not exist") . "</p>";
?> 