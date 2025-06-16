<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', __DIR__ . '/..');

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
use app\models\SatelliteModel;

function getConnectionStatus() {
    try {
        $dbConnection = new DbConnection();
        $db = new Database($dbConnection);
        $result = $db->query("SELECT 1 as test");
        return [
            'status' => 'connected',
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

function getSampleTle() {
    return "ISS (ZARYA)
1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995
2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009
METOP-A
1 29499U 06044A   20129.88840373  .00000002  00000-0  20280-4 0  9990
2 29499  98.5262 178.0263 0001477  74.9454  39.3236 14.21497035703266";
}

function getSample3le() {
    return "0 VANGUARD 1
1 00005U 58002B   25157.90932835  .00000050  00000-0  37874-4 0  9997
2 00005  34.2615  32.6027 1841749 246.4822  93.2088 10.85926385402485
0 VANGUARD 2
1 00011U 59001A   25157.89111952  .00001385  00000-0  73610-3 0  9990
2 00011  32.8670  64.8742 1448250  82.1419 294.0577 11.89844282490745";
}

function parseTles($tles) {
    $tleArr = [];
    $linesArr = explode("\n", $tles);
    $tle = null;
    
    foreach ($linesArr as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (substr($line, 0, 2) === "1 " || substr($line, 0, 1) === "1") {
            if ($tle !== null) {
                $tle['line1'] = $line;
            }
        } elseif (substr($line, 0, 2) === "2 " || substr($line, 0, 1) === "2") {
            if ($tle !== null) {
                $tle['line2'] = $line;
                
                // Only add if we have all required fields
                if (isset($tle['name']) && isset($tle['line1']) && isset($tle['line2'])) {
                    $tleArr[] = $tle;
                }
                $tle = null;
            }
        } else {
            // This is a name line
            if ($tle !== null && isset($tle['name']) && isset($tle['line1'])) {
                // Only add if we have line1
                if (!isset($tle['line2'])) {
                    echo "<p>Warning: TLE data for {$tle['name']} is missing line2, skipping</p>";
                } else {
                    $tleArr[] = $tle;
                }
            }
            
            // Start a new satellite
            $tle = [
                'name' => $line
            ];
        }
    }
    
    // Add the last TLE if it's complete
    if ($tle !== null && isset($tle['name']) && isset($tle['line1']) && isset($tle['line2'])) {
        $tleArr[] = $tle;
    }
    
    return $tleArr;
}

function convert3leToBinary($content) {
    $lines = explode("\n", $content);
    $result = [];
    $currentSet = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Check if this is a name line (starts with 0)
        if (substr($line, 0, 2) === "0 ") {
            // If we have a previous set, add it to results
            if (!empty($currentSet)) {
                $result = array_merge($result, $currentSet);
                $currentSet = [];
            }
            
            // Add name without the "0 " prefix
            $currentSet[] = substr($line, 2);
        } 
        // Check if this is line 1 or line 2 of TLE
        else if (substr($line, 0, 2) === "1 " || substr($line, 0, 2) === "2 ") {
            $currentSet[] = $line;
        }
        // Handle lines that might be missing spaces
        else if ($line[0] === '1' || $line[0] === '2') {
            // Insert a space after the first character to make it standard TLE format
            $currentSet[] = substr($line, 0, 1) . ' ' . substr($line, 1);
        }
    }
    
    // Add the last set if not empty
    if (!empty($currentSet)) {
        $result = array_merge($result, $currentSet);
    }
    
    return implode("\n", $result);
}

// Handlers
$result = '';
$satellites = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'parse_tle':
                $tleInput = $_POST['tle_input'] ?? '';
                if (!empty($tleInput)) {
                    $satellites = parseTles($tleInput);
                    $result = json_encode($satellites, JSON_PRETTY_PRINT);
                }
                break;
                
            case 'parse_3le':
                $tleInput = $_POST['tle_input'] ?? '';
                if (!empty($tleInput)) {
                    $convertedTle = convert3leToBinary($tleInput);
                    $satellites = parseTles($convertedTle);
                    $result = json_encode($satellites, JSON_PRETTY_PRINT);
                }
                break;
        }
    }
}

// Connection status
$connection = getConnectionStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VBIS Import Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .status-connected { color: green; }
        .status-error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>VBIS Import Test</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Database Status</h5>
            </div>
            <div class="card-body">
                <p class="status-<?= $connection['status'] ?>">
                    <strong>Status:</strong> <?= htmlspecialchars($connection['message']) ?>
                </p>
                
                <?php if ($connection['status'] === 'connected'): ?>
                <p>If the connection is successful, you should be able to use the import functionality.</p>
                <?php else: ?>
                <p>Please check your database configuration and make sure MySQL is running.</p>
                <?php endif; ?>
                
                <p><a href="db-test.php" class="btn btn-outline-primary">Run Full Database Test</a></p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Test TLE Parser</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="tle_input" class="form-label">Enter TLE Data:</label>
                                <textarea class="form-control" id="tle_input" name="tle_input" rows="10"><?= htmlspecialchars($_POST['tle_input'] ?? getSampleTle()) ?></textarea>
                            </div>
                            <input type="hidden" name="action" value="parse_tle">
                            <button type="submit" class="btn btn-primary">Parse TLE</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Test 3LE Parser</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="tle_input" class="form-label">Enter 3LE Data:</label>
                                <textarea class="form-control" id="tle_input" name="tle_input" rows="10"><?= htmlspecialchars($_POST['tle_input'] ?? getSample3le()) ?></textarea>
                            </div>
                            <input type="hidden" name="action" value="parse_3le">
                            <button type="submit" class="btn btn-primary">Parse 3LE</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($result)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Parser Results</h5>
                <div class="small text-muted">Found <?= count($satellites) ?> satellites</div>
            </div>
            <div class="card-body">
                <pre><?= htmlspecialchars($result) ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Go to Import Pages</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="/VBIS-main/public/importSatellites" class="btn btn-success w-100">Main Import Page</a>
                    </div>
                    <div class="col-md-4">
                        <a href="/VBIS-main/public/sattelite-tracker/" class="btn btn-info w-100">Satellite Tracker</a>
                    </div>
                    <div class="col-md-4">
                        <a href="/VBIS-main/public/satellites" class="btn btn-secondary w-100">Satellites List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 