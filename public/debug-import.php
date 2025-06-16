<?php
// Debug utility for satellite imports

require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../controllers/SatelliteController.php';
require_once __DIR__ . '/../models/SatelliteModel.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../models/ImportedFileModel.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Satellite Import Debug Utility</h1>";

function showForm() {
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<div style="margin-bottom: 15px;">';
    echo '<label style="display: block; margin-bottom: 5px;">Test file upload:</label>';
    echo '<input type="file" name="tleFile">';
    echo '</div>';
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<label style="display: block; margin-bottom: 5px;">Or use existing file path:</label>';
    echo '<input type="text" name="filePath" style="width: 400px;" placeholder="/path/to/file.xml">';
    echo '</div>';
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<label style="display: block; margin-bottom: 5px;">Default category:</label>';
    echo '<input type="text" name="category" value="Uncategorized">';
    echo '</div>';
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<label>';
    echo '<input type="checkbox" name="auto_categorize" value="1" checked>';
    echo ' Auto-categorize satellites';
    echo '</label>';
    echo '</div>';
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<button type="submit" name="action" value="parse">Test Parse Only</button>';
    echo ' <button type="submit" name="action" value="import">Test Full Import</button>';
    echo '</div>';
    echo '</form>';
}

// Initialize a controller for testing
class TestController extends \app\controllers\SatelliteController {
    public function testParseXml($filePath) {
        return $this->parseXml($filePath);
    }
    
    public function testParseText($filePath) {
        $content = file_get_contents($filePath);
        return $this->parseTles($content);
    }
}

// Process the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new TestController();
    $filePath = '';
    
    // Handle file upload or direct path
    if (!empty($_FILES['tleFile']['tmp_name'])) {
        if ($_FILES['tleFile']['error'] !== UPLOAD_ERR_OK) {
            echo "<p style='color:red'>Error uploading file: " . $_FILES['tleFile']['error'] . "</p>";
            showForm();
            exit;
        }
        
        $uploadDir = __DIR__ . '/../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filePath = $uploadDir . 'debug_' . uniqid() . '_' . $_FILES['tleFile']['name'];
        move_uploaded_file($_FILES['tleFile']['tmp_name'], $filePath);
        echo "<p>Uploaded file to: $filePath</p>";
    } else if (!empty($_POST['filePath'])) {
        $filePath = $_POST['filePath'];
        echo "<p>Using existing file: $filePath</p>";
    } else {
        echo "<p style='color:red'>No file provided</p>";
        showForm();
        exit;
    }
    
    if (!file_exists($filePath)) {
        echo "<p style='color:red'>File does not exist: $filePath</p>";
        showForm();
        exit;
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Test parsing only
    if ($_POST['action'] === 'parse') {
        echo "<h2>Testing Parse Only</h2>";
        
        if ($fileExtension === 'xml') {
            $satellites = $controller->testParseXml($filePath);
        } else {
            $satellites = $controller->testParseText($filePath);
        }
        
        echo "<p>Found " . count($satellites) . " satellites</p>";
        
        if (count($satellites) > 0) {
            echo "<h3>First 5 Satellites:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Name</th><th>Category</th><th>Line 1</th><th>Line 2</th></tr>";
            
            $count = 0;
            foreach ($satellites as $satellite) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($satellite['name']) . "</td>";
                echo "<td>" . htmlspecialchars($satellite['category'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($satellite['line1']) . "</td>";
                echo "<td>" . htmlspecialchars($satellite['line2']) . "</td>";
                echo "</tr>";
                
                $count++;
                if ($count >= 5) break;
            }
            
            echo "</table>";
        }
    }
    // Test full import
    else if ($_POST['action'] === 'import') {
        echo "<h2>Testing Full Import</h2>";
        
        try {
            // Mock session user data
            $app = new \app\core\Application();
            $app->session = new \app\core\Session();
            $app->session->set('user', [['id' => 1, 'username' => 'admin']]);
            
            // Set up $_FILES and $_POST for controller
            $_FILES['tleFile'] = [
                'name' => basename($filePath),
                'tmp_name' => $filePath,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($filePath),
                'type' => $fileExtension === 'xml' ? 'text/xml' : 'text/plain'
            ];
            
            $_POST['category'] = $_POST['category'] ?? 'Uncategorized';
            $_POST['auto_categorize'] = $_POST['auto_categorize'] ?? '0';
            
            // Buffer output to capture headers
            ob_start();
            
            // Call processImport
            $controller->processImport();
            
            // Get buffer content
            $output = ob_get_clean();
            
            echo "<p style='color:green'>Import completed successfully!</p>";
            echo "<p>Check your database for the imported satellites.</p>";
        } catch (\Exception $e) {
            echo "<p style='color:red'>Error during import: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
    echo "<hr>";
}

// Show the form
showForm();

// Show links
echo "<div style='margin-top: 20px;'>";
echo "<a href='/VBIS-main/public/importSatellites'>Go to Import Page</a> | ";
echo "<a href='/VBIS-main/public/satellites'>Go to Satellites Page</a>";

// Add link to test Space-Track sample
$samplePath = __DIR__ . '/../uploads/sample-spacetrack.xml';
if (file_exists($samplePath)) {
    echo " | <a href='?action=parse&filePath=" . urlencode($samplePath) . "'>Test Space-Track Sample</a>";
}

echo "</div>";
?> 