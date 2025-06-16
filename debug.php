<?php
require_once __DIR__ . "/vendor/autoload.php";

use app\core\Session;

// Start session if not already started
$session = new Session();

echo "<h1>Session Debug</h1>";

// Display user session data
echo "<h2>User Session Data</h2>";
echo "<pre>";
$userData = $session->get('user');
var_dump($userData);
echo "</pre>";

// Check roles
echo "<h2>Role Checks</h2>";
echo "Is in role 'Administrator': " . ($session->isInRole('Administrator') ? 'Yes' : 'No') . "<br>";
echo "Is in role 'User': " . ($session->isInRole('User') ? 'Yes' : 'No') . "<br>";

// Display all session data 
echo "<h2>All Session Data</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>"; 