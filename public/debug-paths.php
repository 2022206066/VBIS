<?php
require_once __DIR__ . "/../vendor/autoload.php";
use app\core\Application;

$app = new Application();

// Display server variables
echo "<h1>Server Variables</h1>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "</pre>";

// Display application base URL
echo "<h1>Application Base URL</h1>";
echo "<pre>";
echo "BASE_URL: " . Application::$BASE_URL . "\n";
echo "</pre>";

// Test URL generation
echo "<h1>Test URLs</h1>";
echo "<pre>";
echo "Root URL: " . Application::url('/') . "\n";
echo "Assets URL: " . Application::url('/assets/css/argon-dashboard.css') . "\n";
echo "Satellite Tracker URL: " . Application::url('/sattelite-tracker/single.html') . "\n";
echo "</pre>";

// Display script paths
echo "<h1>Script Directory</h1>";
echo "<pre>";
echo "dirname(SCRIPT_NAME): " . dirname($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
echo "dirname(PHP_SELF): " . dirname($_SERVER['PHP_SELF'] ?? '') . "\n";
echo "str_replace: " . str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')) . "\n";
echo "</pre>";
?> 