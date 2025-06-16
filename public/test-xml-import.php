<?php
// Test script for direct XML import without using form

require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../controllers/SatelliteController.php';
require_once __DIR__ . '/../models/SatelliteModel.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../models/ImportedFileModel.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct XML Import Test</h1>";

// Create test directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Create a sample XML file for testing
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

$testFilePath = $uploadDir . 'test-direct-import.xml';
file_put_contents($testFilePath, $testXmlContent);
echo "<p>Created test XML file at: $testFilePath</p>";

try {
    // Create controller instance for testing
    $controller = new \app\controllers\SatelliteController();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass('\app\controllers\SatelliteController');
    $parseXml = $reflection->getMethod('parseXml');
    $parseXml->setAccessible(true);
    
    // Parse the test XML file
    $satellites = $parseXml->invoke($controller, $testFilePath);
    
    echo "<p>Parsing result:</p>";
    echo "<pre>";
    print_r($satellites);
    echo "</pre>";
    
    echo "<hr>";
    if (count($satellites) > 0) {
        echo "<p style='color:green'>Success! Found " . count($satellites) . " satellites in the test XML file.</p>";
    } else {
        echo "<p style='color:red'>Error: No satellites found in the test XML file.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Display link to try the actual import page
echo "<p><a href='/VBIS-main/public/importSatellites' class='btn btn-primary'>Go to Import Page</a></p>";
?> 