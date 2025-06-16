<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', __DIR__ . '/../..');

// Add autoloader to handle app namespaces
spl_autoload_register(function($class) {
    // Convert namespace to path
    $path = str_replace('\\', '/', $class);
    
    // Replace app with the actual path
    $path = str_replace('app/', '', $path);
    
    // Load the file
    $file = BASE_PATH . '/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Required classes for direct use
require_once BASE_PATH . '/core/DbConnection.php';
require_once BASE_PATH . '/core/Database.php';

use app\core\Database;
use app\core\DbConnection;

// Set the content type to JavaScript
header("Content-Type: application/javascript");

// Optional filtering by category (from query string)
$category = $_GET['category'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
$search = $_GET['search'] ?? null;

try {
    // Connect to database
    $db = new Database(DbConnection::getInstance());
    
    // Build the query based on filters
    $query = "SELECT name, line1, line2, category FROM satellites WHERE 1=1";
    $params = [];
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $query .= " AND name LIKE ?";
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY name ASC";
    
    if ($limit) {
        $query .= " LIMIT ?";
        $params[] = $limit;
    }
    
    // Execute query
    $result = $db->query($query, $params);
    
    // Start building the JavaScript
    echo "// TLE data loaded from database\n";
    echo "// Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    echo "var tles = `";
    
    // Add each satellite to the TLE string
    if (!empty($result)) {
        foreach ($result as $satellite) {
            echo $satellite['name'] . "\n";
            echo $satellite['line1'] . "\n";
            echo $satellite['line2'] . "\n";
        }
    } else {
        // Default entries if database is empty
        echo "ISS (ZARYA)\n";
        echo "1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995\n";
        echo "2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009\n";
    }
    
    echo "`;";
    
    // Add some metadata
    echo "\n\n// Metadata";
    echo "\nvar satelliteCount = " . count($result) . ";";
    echo "\nvar dataSource = 'database';";
    echo "\nvar lastUpdated = '" . date('Y-m-d H:i:s') . "';";
    
    if ($category) {
        echo "\nvar filteredCategory = '" . htmlspecialchars($category) . "';";
    }
    
} catch (Exception $e) {
    // In case of error, output a valid JavaScript with error information
    echo "// ERROR: Unable to load satellite data\n";
    echo "console.error('Failed to load satellite data from database: " . str_replace("'", "\\'", $e->getMessage()) . "');\n";
    echo "var tles = `";
    // Fallback to default entries
    echo "ISS (ZARYA)\n";
    echo "1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995\n";
    echo "2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009\n";
    echo "`;";
    echo "\nvar dataSource = 'fallback';";
}
?> 