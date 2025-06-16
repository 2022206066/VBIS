<?php
// Direct script to delete all satellites from the database

// Include required files
require_once __DIR__ . '/../core/Database.php';

// Start session to check for admin role
session_start();
$isAdmin = isset($_SESSION['user']) && $_SESSION['user'][0]['role'] === 'Administrator';

if (!$isAdmin) {
    echo "Access denied. Only administrators can run this script.";
    exit;
}

// Output HTML header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete All Satellites</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .card { border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        a.btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background-color: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Delete All Satellites</h1>
    
    <div class="card">
<?php

try {
    // Connect to database
    $db = new app\core\Database();
    $conn = $db->getConnection();
    
    // Count satellites before deletion
    $countResult = $conn->query("SELECT COUNT(*) as count FROM satellites");
    $countBefore = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['count'] : 0;
    
    // Truncate the satellites table
    $result = $conn->query("TRUNCATE TABLE satellites");
    
    if ($result) {
        echo "<p class='success'>✅ Successfully deleted all $countBefore satellites from the database.</p>";
    } else {
        echo "<p class='error'>❌ Error deleting satellites: " . $conn->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

?>
        <a href="/VBIS-main/public/satellites" class="btn">Return to Satellites Page</a>
    </div>
</body>
</html> 