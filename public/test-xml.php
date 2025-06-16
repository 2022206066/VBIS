<?php
// Test script for XML import/export functionality

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Satellite XML Import/Export Test</h1>";

// Create a sample XML file
$testXmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<satellites>
  <satellite>
    <id>1</id>
    <n><![CDATA[ISS (ZARYA)]]></n>
    <line1><![CDATA[1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995]]></line1>
    <line2><![CDATA[2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009]]></line2>
    <category><![CDATA[Space Station]]></category>
    <added_by>1</added_by>
  </satellite>
</satellites>';

$testFilePath = __DIR__ . '/../uploads/test-satellite.xml';
file_put_contents($testFilePath, $testXmlContent);
echo "<p>Created test XML file at: $testFilePath</p>";

// Test XML parsing
try {
    echo "<h2>Testing XML Parsing</h2>";
    
    // Include necessary files
    require_once __DIR__ . '/../controllers/SatelliteController.php';
    require_once __DIR__ . '/../models/SatelliteModel.php';
    require_once __DIR__ . '/../core/BaseController.php';
    require_once __DIR__ . '/../core/BaseModel.php';
    require_once __DIR__ . '/../core/Application.php';
    
    // Create controller instance for testing
    $controller = new \app\controllers\SatelliteController();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass('\app\controllers\SatelliteController');
    $method = $reflection->getMethod('parseXml');
    $method->setAccessible(true);
    
    // Parse the test XML file
    $satellites = $method->invoke($controller, $testFilePath);
    
    echo "<p>Parsing result:</p>";
    echo "<pre>";
    print_r($satellites);
    echo "</pre>";
    
    // Test XML export
    echo "<h2>Testing XML Export</h2>";
    
    $model = new \app\models\SatelliteModel();
    $xmlOutput = $model->exportToXml();
    
    echo "<p>Sample of XML output:</p>";
    echo "<pre>";
    echo htmlspecialchars(substr($xmlOutput, 0, 1000));
echo "</pre>";

    // Log success
    echo "<p style='color: green;'>Tests completed. Check the error log for more details.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Navigation links
echo "<hr>";
echo "<p>Options:</p>";
echo "<ul>";
echo "<li><a href='/VBIS-main/public/importSatellites'>Go to Import Page</a></li>";
echo "<li><a href='/VBIS-main/public/exportXml'>Test Export XML</a></li>";
echo "<li><a href='/VBIS-main/public/satellites'>Return to Satellites</a></li>";
echo "</ul>";
?> 