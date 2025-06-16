<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>XML Processing Test</h1>";

try {
    // Check if file was uploaded
    if (!isset($_FILES['xmlFile']) || $_FILES['xmlFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed. Error code: " . ($_FILES['xmlFile']['error'] ?? 'Unknown'));
    }
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($_FILES['xmlFile']['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'xml') {
        throw new Exception("Invalid file type. Expected XML file.");
    }
    
    // Move uploaded file
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = 'test_' . uniqid() . '.xml';
    $uploadFile = $uploadDir . $filename;
    
    if (!move_uploaded_file($_FILES['xmlFile']['tmp_name'], $uploadFile)) {
        throw new Exception("Failed to save the uploaded file.");
    }
    
    echo "<p>File uploaded successfully: " . htmlspecialchars($_FILES['xmlFile']['name']) . " (Size: " . number_format($_FILES['xmlFile']['size']) . " bytes)</p>";
    
    // Process XML file
    echo "<h2>Parsing XML</h2>";
    
    // Enable libxml error tracking
    libxml_use_internal_errors(true);
    
    // Load XML
    $xml = simplexml_load_file($uploadFile);
    
    if ($xml === false) {
        echo "<div style='padding: 10px; margin: 10px 0; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
            <strong>XML Parsing Error:</strong>
        </div>";
        
        echo "<ul style='color: #a94442;'>";
        foreach(libxml_get_errors() as $error) {
            echo "<li>" . htmlspecialchars($error->message) . " at line " . $error->line . "</li>";
        }
        echo "</ul>";
        
        // Try alternative parsing as a fallback
        $xmlContent = file_get_contents($uploadFile);
        $xmlContent = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $xmlContent);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new Exception("Failed to parse XML even after cleaning.");
        }
        
        echo "<p>XML was successfully parsed after removing invalid characters.</p>";
    }
    
    // Display XML structure 
    echo "<h2>XML Structure</h2>";
    echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto;'>";
    echo htmlspecialchars(print_r($xml, true));
    echo "</pre>";
    
    // Extract satellites
    echo "<h2>Extracted Satellite Data</h2>";
    
    $satellites = [];
    $count = 0;
    
    // Check the root element name and adapt processing accordingly
    $rootName = $xml->getName();
    echo "<p>Root element: " . htmlspecialchars($rootName) . "</p>";
    
    // Handle different XML formats
    if ($rootName === 'satellites') {
        foreach ($xml->satellite as $satellite) {
            processSatellite($satellite, $satellites, $count);
        }
    } else if ($rootName === 'ndm') {
        // Handle OMM format
        echo "<p>Detected OMM format (ndm root).</p>";
        
        $segments = $xml->xpath('//segment') ?: $xml->xpath('//body');
        foreach ($segments as $segment) {
            processSatelliteOMM($segment, $satellites, $count);
        }
    } else {
        // Try generic approach - look at direct children
        echo "<p>Using generic XML processing approach.</p>";
        
        foreach ($xml->children() as $element) {
            processSatellite($element, $satellites, $count);
        }
    }
    
    // Display results
    if (count($satellites) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<thead><tr style='background-color: #f5f5f5;'>
            <th>Name</th><th>Line 1</th><th>Line 2</th><th>Category</th>
        </tr></thead><tbody>";
        
        foreach ($satellites as $satellite) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($satellite['name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($satellite['line1'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($satellite['line2'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($satellite['category'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "<p>Total satellites found: " . count($satellites) . "</p>";
    } else {
        echo "<p>No satellite data was found in the XML.</p>";
        
        // Dump first level elements to help diagnose format issues
        echo "<h3>Available Elements</h3>";
        echo "<ul>";
        foreach ($xml->children() as $child) {
            echo "<li>" . htmlspecialchars($child->getName()) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 10px; margin: 10px 0; background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; border-radius: 4px;'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "<p><a href='/VBIS-main/public/test-xml.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Back to XML Tester</a></p>";

/**
 * Process a satellite element from an XML document
 */
function processSatellite($satellite, &$satellites, &$count) {
    $data = [];
    
    // Extract satellite details
    $nameFields = ['name', 'OBJECT_NAME', 'SATELLITE_NAME', 'satelliteName', 'objectName', 'n'];
    foreach ($nameFields as $field) {
        if (isset($satellite->$field)) {
            $data['name'] = (string)$satellite->$field;
            break;
        }
    }
    
    // Try to extract line elements
    $line1Fields = ['line1', 'TLE_LINE1', 'tleLine1'];
    foreach ($line1Fields as $field) {
        if (isset($satellite->$field)) {
            $data['line1'] = (string)$satellite->$field;
            break;
        }
    }
    
    $line2Fields = ['line2', 'TLE_LINE2', 'tleLine2'];
    foreach ($line2Fields as $field) {
        if (isset($satellite->$field)) {
            $data['line2'] = (string)$satellite->$field;
            break;
        }
    }
    
    // Extract category if available
    $categoryFields = ['category', 'OBJECT_TYPE', 'objectType', 'SATELLITE_TYPE', 'satelliteType'];
    foreach ($categoryFields as $field) {
        if (isset($satellite->$field)) {
            $data['category'] = (string)$satellite->$field;
            break;
        }
    }
    
    if (isset($data['name']) && (isset($data['line1']) || isset($data['line2']))) {
        $satellites[] = $data;
        $count++;
    }
}

/**
 * Process a satellite segment in OMM format
 */
function processSatelliteOMM($segment, &$satellites, &$count) {
    $data = [];
    
    $metadata = $segment->metadata ?? null;
    $segmentData = $segment->data ?? null;
    
    if ($metadata) {
        // Extract name
        if (isset($metadata->OBJECT_NAME)) {
            $data['name'] = (string)$metadata->OBJECT_NAME;
        }
        
        // Extract category if available
        if (isset($metadata->OBJECT_TYPE)) {
            $data['category'] = (string)$metadata->OBJECT_TYPE;
        }
    }
    
    if ($segmentData) {
        // Extract TLE data if provided directly
        $meanElements = $segmentData->meanElements ?? null;
        if ($meanElements) {
            // In a real implementation, we'd convert these to TLE format
            if (isset($meanElements->MEAN_MOTION)) {
                $data['tle_data_available'] = "true";
            }
        }
    }
    
    // For demonstration purposes, show what was found
    if (isset($data['name'])) {
        if (!isset($data['line1'])) $data['line1'] = "[Would be constructed from orbital elements]";
        if (!isset($data['line2'])) $data['line2'] = "[Would be constructed from orbital elements]";
        $satellites[] = $data;
        $count++;
    }
}
?> 