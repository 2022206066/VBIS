<?php
// Standalone XML import debugging tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../models/SatelliteModel.php';
require_once __DIR__ . '/../models/ImportedFileModel.php';
require_once __DIR__ . '/../core/Database.php';

// Check if a file was uploaded
$processingResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xmlFile'])) {
    // Handle file upload
    if ($_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $tempFile = $_FILES['xmlFile']['tmp_name'];
        $fileName = uniqid() . '_' . $_FILES['xmlFile']['name'];
        $uploadedFile = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($tempFile, $uploadedFile)) {
            // Process the XML file
            $xmlContent = file_get_contents($uploadedFile);
            $processingResult = [
                'file' => $uploadedFile,
                'size' => filesize($uploadedFile),
                'content_preview' => htmlspecialchars(substr($xmlContent, 0, 500)) . '...'
            ];
            
            // Try to parse with DOM
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            
            if ($dom->loadXML($xmlContent)) {
                $processingResult['dom_load'] = 'Success';
                $processingResult['root_element'] = $dom->documentElement->nodeName;
                
                // Check for key elements
                $processingResult['has_data_elements'] = $dom->getElementsByTagName('data')->length;
                $processingResult['has_tle_elements'] = $dom->getElementsByTagName('tle')->length;
                $processingResult['has_satellites'] = $dom->getElementsByTagName('satellite')->length;
            } else {
                $processingResult['dom_load'] = 'Failed';
                $errors = libxml_get_errors();
                $errorMessages = [];
                
                foreach ($errors as $error) {
                    $errorMessages[] = "Line {$error->line}: {$error->message}";
                }
                
                $processingResult['xml_errors'] = $errorMessages;
            }
            
            libxml_clear_errors();
        }
    }
}

// Display the debugging interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>XML Import Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        .error { color: #e74c3c; }
        .success { color: #27ae60; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .sample-xml { background: #f1f8e9; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"] { margin-bottom: 10px; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #45a049; }
        .result-table { width: 100%; border-collapse: collapse; }
        .result-table th, .result-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .result-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>XML Import Debug Tool</h1>
    
    <div class="card">
        <h2>Upload XML File for Testing</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="xmlFile">Select XML file:</label>
                <input type="file" name="xmlFile" id="xmlFile" accept=".xml" required>
            </div>
            <button type="submit">Analyze XML</button>
        </form>
    </div>
    
    <?php if ($processingResult): ?>
    <div class="card">
        <h2>Processing Results</h2>
        <table class="result-table">
            <tr>
                <th>File Path</th>
                <td><?= $processingResult['file'] ?></td>
            </tr>
            <tr>
                <th>File Size</th>
                <td><?= $processingResult['size'] ?> bytes</td>
            </tr>
            <tr>
                <th>DOM Load</th>
                <td class="<?= $processingResult['dom_load'] === 'Success' ? 'success' : 'error' ?>">
                    <?= $processingResult['dom_load'] ?>
                </td>
            </tr>
            <?php if ($processingResult['dom_load'] === 'Success'): ?>
            <tr>
                <th>Root Element</th>
                <td><?= $processingResult['root_element'] ?></td>
            </tr>
            <tr>
                <th>Data Elements</th>
                <td><?= $processingResult['has_data_elements'] ?></td>
            </tr>
            <tr>
                <th>TLE Elements</th>
                <td><?= $processingResult['has_tle_elements'] ?></td>
            </tr>
            <tr>
                <th>Satellite Elements</th>
                <td><?= $processingResult['has_satellites'] ?></td>
            </tr>
            <?php else: ?>
            <tr>
                <th>XML Errors</th>
                <td class="error">
                    <ul>
                        <?php foreach ($processingResult['xml_errors'] as $error): ?>
                        <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Content Preview</th>
                <td>
                    <pre><?= $processingResult['content_preview'] ?></pre>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="card sample-xml">
        <h2>Sample Space-Track.org XML Format</h2>
        <p>This is an example of a properly formatted Space-Track.org XML file:</p>
        <pre><?= htmlspecialchars(file_get_contents(__DIR__ . '/../uploads/sample-spacetrack.xml') ?: '<Sample file not found>') ?></pre>
    </div>
    
    <div class="card">
        <h2>Links</h2>
        <ul>
            <li><a href="/VBIS-main/public/satellites">Return to Satellites Page</a></li>
            <li><a href="/VBIS-main/public/importSatellites">Go to Import Page</a></li>
        </ul>
    </div>
</body>
</html> 