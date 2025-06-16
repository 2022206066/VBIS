<?php

namespace app\controllers;

use app\core\Application;
use app\core\BaseController;
use app\models\ImportedFileModel;
use app\models\SatelliteModel;

class SatelliteController extends BaseController
{
    public function list()
    {
        $model = new SatelliteModel();
        $satellites = $model->getSatellitesWithCategories();
        $categories = $model->getSatelliteCategories();
        
        // Group satellites by category
        $satellitesByCategory = [];
        foreach ($categories as $category) {
            $satellitesByCategory[$category] = array_filter($satellites, function($sat) use ($category) {
                return $sat['category'] === $category;
            });
        }
        
        // Get active category from query string or default to the first one
        $activeCategory = $_GET['category'] ?? ($categories[0] ?? '');
        
        $this->view->render('satellites/list', 'main', [
            'satellites' => $satellites,
            'categories' => $categories,
            'satellitesByCategory' => $satellitesByCategory,
            'activeCategory' => $activeCategory
        ]);
    }
    
    public function detail()
    {
        $id = $_GET['id'] ?? 0;
        
        $model = new SatelliteModel();
        $model->one("WHERE id = $id");
        
        if (!$model->id) {
            header("location:" . Application::url('/satellites'));
            exit;
        }
        
        $this->view->render('satellites/detail', 'main', $model);
    }
    
    public function import()
    {
        $this->view->render('satellites/import', 'main');
    }
    
    public function processImport()
    {
        try {
            // Increase execution time limit for large files
            set_time_limit(600); // 10 minutes
            
            $this->logDebug("Starting processImport");
            
            if (!isset($_FILES['tleFile']) || $_FILES['tleFile']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = "File upload failed!";
                $this->logDebug("File upload failed", $_FILES['tleFile'] ?? 'No file data');
                
                if (isset($_FILES['tleFile']['error'])) {
                    switch ($_FILES['tleFile']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMessage .= " The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMessage .= " The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMessage .= " The uploaded file was only partially uploaded.";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMessage .= " No file was uploaded.";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMessage .= " Missing a temporary folder.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMessage .= " Failed to write file to disk.";
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMessage .= " A PHP extension stopped the file upload.";
                            break;
                    }
                }
                
                $this->logDebug("Import error: " . $errorMessage);
                Application::$app->session->set('errorNotification', $errorMessage);
                header("location:" . Application::url('/importSatellites'));
                exit;
            }
            
            // Log detailed information about the uploaded file
            $this->logDebug("Processing uploaded file", [
                'name' => $_FILES['tleFile']['name'],
                'size' => $_FILES['tleFile']['size'] . ' bytes',
                'type' => $_FILES['tleFile']['type'],
                'tmp_name' => $_FILES['tleFile']['tmp_name']
            ]);
            
            $uploadDir = __DIR__ . '/../uploads/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $this->logDebug("Failed to create upload directory: " . $uploadDir);
                    throw new \Exception("Failed to create upload directory");
                }
                $this->logDebug("Created upload directory: " . $uploadDir);
            }
            
            $filename = uniqid() . '_' . $_FILES['tleFile']['name'];
            $uploadFile = $uploadDir . $filename;
            $this->logDebug("Target upload file: " . $uploadFile);
            
            if (!move_uploaded_file($_FILES['tleFile']['tmp_name'], $uploadFile)) {
                $this->logDebug("Failed to move uploaded file to: " . $uploadFile);
                throw new \Exception("Failed to move uploaded file");
            }
            $this->logDebug("Successfully moved uploaded file to: " . $uploadFile);
            
            $fileSize = filesize($uploadFile);
            $fileExtension = strtolower(pathinfo($_FILES['tleFile']['name'], PATHINFO_EXTENSION));
            $originalFilename = $_FILES['tleFile']['name'];
            $satelliteData = [];
            
            $this->logDebug("File details", [
                'path' => $uploadFile,
                'original' => $originalFilename,
                'size' => $fileSize,
                'extension' => $fileExtension
            ]);
            
            // Process based on file type
            if ($fileExtension === 'xml') {
                $this->logDebug("Processing XML file");
                $satelliteData = $this->parseXml($uploadFile);
                $this->logDebug("XML parsing returned " . count($satelliteData) . " satellites");
                
                // If XML parsing failed, provide more details
                if (empty($satelliteData)) {
                    $this->logDebug("XML parsing produced no satellite data. Dumping file content:");
                    $fileContent = file_get_contents($uploadFile);
                    $this->logDebug(substr($fileContent, 0, 1000) . (strlen($fileContent) > 1000 ? '...' : ''));
                }
            } else {
                // Default to TLE processing for .txt files
                $this->logDebug("Processing TLE file");
                try {
                    $tleContent = file_get_contents($uploadFile);
                    if ($tleContent === false) {
                        $this->logDebug("Failed to read file contents from: " . $uploadFile);
                        throw new \Exception("Failed to read file contents");
                    }
                    
                    // Check if this is a 3le.txt file based on initial string pattern (0 name in first line)
                    if (substr(trim($tleContent), 0, 1) === '0') {
                        $this->logDebug("Detected 3LE format");
                        // Convert 3LE to standard TLE format
                        $tleContent = $this->convert3leToBinary($tleContent);
                    }
                    
                    $satelliteData = $this->parseTles($tleContent);
                    $this->logDebug("TLE parsing returned " . count($satelliteData) . " satellites");
                } catch (\Exception $e) {
                    $this->logDebug("Error processing TLE file: " . $e->getMessage());
                    throw $e;
                }
            }
            
            if (empty($satelliteData)) {
                $this->logDebug("No valid satellite data found in the file");
                throw new \Exception("No valid satellite data found in the file. Please check the format.");
            }
            
            // Get default category and auto-categorize settings
            $defaultCategory = $_POST['category'] ?? 'Uncategorized';
            $autoCategorize = isset($_POST['auto_categorize']) && $_POST['auto_categorize'] == '1';
            $this->logDebug("Import settings", [
                'defaultCategory' => $defaultCategory,
                'autoCategorize' => $autoCategorize ? 'yes' : 'no'
            ]);
            
            // Extract categories if available in data
            if ($autoCategorize) {
                $this->logDebug("Auto-categorizing satellites");
                $this->extractCategoriesFromData($satelliteData);
            }
            
            $sessionUserData = Application::$app->session->get('user');
            if (!$sessionUserData || !isset($sessionUserData[0]['id'])) {
                $this->logDebug("User not logged in or session expired", $sessionUserData);
                throw new \Exception("User not logged in or session expired");
            }
            
            $userId = $sessionUserData[0]['id'];
            $this->logDebug("User ID for import: " . $userId);
            
            // Use the new batch processing method for better performance
            $this->logDebug("Starting batch processing of " . count($satelliteData) . " satellites");
                    $satelliteModel = new SatelliteModel();
            $results = $satelliteModel->batchProcessSatellites($satelliteData, $defaultCategory, $userId);
            
            $satelliteCount = $results['insertCount'];
            $updatedCount = $results['updateCount'];
            $categoriesAdded = $results['categoriesAdded'];
            
            $this->logDebug("Import summary", [
                'new' => $satelliteCount,
                'updated' => $updatedCount,
                'total' => $satelliteCount + $updatedCount,
                'categories' => $categoriesAdded
            ]);
            
            // Create imported_files table if needed
            ImportedFileModel::createTable();
            
            // Record the import
            $importModel = new ImportedFileModel();
            $importModel->filename = $originalFilename;
            $importModel->uploaded_by = $userId;
            $importModel->satellite_count = $satelliteCount + $updatedCount;
            $importModel->insert();
            
            // Create a detailed import summary for modal display
            $categoriesStr = empty($categoriesAdded) ? 'None' : implode(', ', $categoriesAdded);
            $importSummary = [
                'new' => $satelliteCount,
                'updated' => $updatedCount,
                'total' => $satelliteCount + $updatedCount,
                'categories' => $categoriesStr,
                'filename' => $originalFilename
            ];
            
            // Store the import summary in session for the modal to display
            Application::$app->session->set('importSummary', $importSummary);
            
            // Also set a regular success notification as fallback
            $updateMsg = $updatedCount > 0 ? " and updated $updatedCount existing" : "";
            Application::$app->session->set('successNotification', "Successfully imported $satelliteCount new$updateMsg satellites!");
            
            header("location:" . Application::url('/satellites'));
            exit;
            
        } catch (\Exception $e) {
            $this->logDebug("Error in processImport: " . $e->getMessage());
            $this->logDebug("Stack trace: " . $e->getTraceAsString());
            Application::$app->session->set('errorNotification', 'An error occurred during import: ' . $e->getMessage());
            header("location:" . Application::url('/importSatellites'));
            exit;
        }
    }
    
    /**
     * Extract categories from satellite data based on name patterns
     * Updates the satelliteData array with category information
     * 
     * @param array &$satelliteData Satellite data array to categorize
     * @return void
     */
    private function extractCategoriesFromData(&$satelliteData) 
    {
        $categoryRules = [
            'Weather' => ['NOAA', 'METOP', 'GOES', 'HIMAWARI', 'METEOR', 'FY-', 'FENGYUN'],
            'Navigation' => ['GPS', 'NAVSTAR', 'GLONASS', 'GALILEO', 'BEIDOU', 'COMPASS', 'IRNSS'],
            'Communication' => ['INTELSAT', 'INMARSAT', 'IRIDIUM', 'GLOBALSTAR', 'ORBCOMM', 'STARLINK', 'ONEWEB'],
            'Earth Observation' => ['LANDSAT', 'SENTINEL', 'TERRA', 'AQUA', 'WORLDVIEW', 'SPOT', 'GEOEYE'],
            'Space Station' => ['ISS', 'ZARYA', 'TIANGONG', 'MIR', 'SALYUT'],
            'Science' => ['HUBBLE', 'CHANDRA', 'FERMI', 'SWIFT', 'JWST', 'TESS', 'COBE'],
            'Military' => ['USA', 'NROL', 'MILSTAR', 'VORTEX', 'TRUMPET', 'KEYHOLE', 'MERCURY'],
            'Amateur' => ['AMSAT', 'OSCAR', 'BIRD', 'FOX', 'CUBESAT', 'HAM']
        ];
        
        foreach ($satelliteData as &$satellite) {
            // Skip if category is already set and not empty
            if (isset($satellite['category']) && !empty($satellite['category']) && 
                $satellite['category'] !== 'Uncategorized') {
                continue;
            }
            
            $name = strtoupper($satellite['name']);
            $assigned = false;
            
            // Check against each category rule
            foreach ($categoryRules as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($name, $keyword) !== false) {
                        $satellite['category'] = $category;
                        $assigned = true;
                        break 2; // Break both loops
                    }
                }
            }
            
            // Set default category if no match found
            if (!$assigned) {
                $satellite['category'] = 'Uncategorized';
            }
        }
    }
    
    /**
     * Check if a string contains any of the keywords (case insensitive)
     * @param string $str The string to check
     * @param array $keywords Keywords to look for
     * @return boolean True if any keyword is found
     */
    private function containsKeyword($str, $keywords)
    {
        $str = strtolower($str);
        foreach ($keywords as $keyword) {
            if (strpos($str, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Parses an XML file containing satellite data
     * @param string $xmlFile Path to the XML file
     * @return array Array of satellite data (name, line1, line2)
     */
    private function parseXml($xmlFile)
    {
        $satellites = [];
        
        try {
            // Enable detailed error logging
            $this->logDebug("Starting XML parsing for file: " . $xmlFile);
            
            // Check if file exists and is readable
            if (!file_exists($xmlFile)) {
                $this->logDebug("XML file does not exist: " . $xmlFile);
                return $satellites;
            }
            
            if (!is_readable($xmlFile)) {
                $this->logDebug("XML file is not readable: " . $xmlFile);
                return $satellites;
            }
            
            // Log file size and content preview
            $fileSize = filesize($xmlFile);
            $contentPreview = file_get_contents($xmlFile, false, null, 0, min($fileSize, 500));
            $this->logDebug("XML file size: " . $fileSize . " bytes");
            $this->logDebug("XML content preview: " . $contentPreview);
            
            // Use DOM instead of SimpleXML for better CDATA handling
            $dom = new \DOMDocument();
            $previousLibxmlUseInternalErrors = libxml_use_internal_errors(true);
            
            if (!$dom->load($xmlFile)) {
                $errors = libxml_get_errors();
                $errorMsg = '';
                foreach ($errors as $error) {
                    $errorMsg .= $error->message . " at line " . $error->line . "\n";
                }
                libxml_clear_errors();
                libxml_use_internal_errors($previousLibxmlUseInternalErrors);
                $this->logDebug("XML parsing errors: " . $errorMsg);
                throw new \Exception("Failed to parse XML: " . $errorMsg);
            }
            
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlUseInternalErrors);
            
            $this->logDebug("XML loaded successfully, checking format");
            $this->logDebug("XML root element: " . $dom->documentElement->nodeName);
            
            // First, try to detect the XML format
            // Check if this is a Space-Track.org XML (has common Space-Track.org elements)
            if ($dom->documentElement->nodeName === 'result' || 
                $dom->getElementsByTagName('tle')->length > 0 ||
                $dom->getElementsByTagName('TLE')->length > 0 ||
                $dom->getElementsByTagName('SPACE-TRACK')->length > 0 ||
                $dom->getElementsByTagName('data')->length > 0) {
                
                $this->logDebug("Detected potential Space-Track.org XML format");
                $xml = simplexml_import_dom($dom);
                $this->parseSpaceTrackXml($xml, $satellites);
                
                // If no satellites were found, try other methods
                if (empty($satellites)) {
                    $this->logDebug("No satellites found with Space-Track parser, trying generic methods");
                }
            }
            // Check for our custom export format - this should be the most common
            else if ($dom->documentElement->nodeName === 'satellites') {
                $this->logDebug("Detected satellite export format");
                
                $satelliteNodes = $dom->getElementsByTagName('satellite');
                $this->logDebug("Found " . $satelliteNodes->length . " satellite nodes");
                
                foreach ($satelliteNodes as $satelliteNode) {
                    $satellite = [];
                    
                    // Extract satellite data
                    foreach ($satelliteNode->childNodes as $node) {
                        if ($node->nodeType === XML_ELEMENT_NODE) {
                            $nodeName = $node->nodeName;
                            
                            // Get node value, handling CDATA sections
                            if ($node->hasChildNodes() && $node->firstChild->nodeType === XML_CDATA_SECTION_NODE) {
                                $value = $node->firstChild->nodeValue;
                            } else {
                                $value = $node->textContent;
                            }
                            
                            // Map common node names
                            if ($nodeName === 'name' || $nodeName === 'n' || $nodeName === 'title') {
                                $satellite['name'] = $value;
                            } else if ($nodeName === 'line1' || $nodeName === 'tle1') {
                                $satellite['line1'] = $value;
                            } else if ($nodeName === 'line2' || $nodeName === 'tle2') {
                                $satellite['line2'] = $value;
                            } else if ($nodeName === 'category' || $nodeName === 'type') {
                                $satellite['category'] = $value;
                            }
                        }
                    }
                    
                    $this->logDebug("Extracted satellite: " . ($satellite['name'] ?? 'Unknown'), $satellite);
                    
                    // Validate required fields
                    if (!empty($satellite['name']) && !empty($satellite['line1']) && !empty($satellite['line2'])) {
                        $satellites[] = $satellite;
                    } else {
                        $this->logDebug("Skipping satellite with missing data", $satellite);
                    }
                }
            }
            // For other formats, convert the DOM to SimpleXML for backward compatibility
            else {
                $xml = simplexml_import_dom($dom);
                
                // Try other format parsers
                if ($xml->xpath('//segment') || 
                    $xml->xpath('//body') || 
                    $xml->xpath('//omm') || 
                    $xml->xpath('//data/meanElements')) {
                    $this->logDebug("Detected OMM format XML");
                    $this->parseOmmXml($xml, $satellites);
                } 
                // Check for general satellite data format with <satellite> tags
                else if ($xml->xpath('//satellite') || 
                        $xml->xpath('//Satellite')) {
                    $this->logDebug("Detected generic satellite format XML");
                    $this->parseGenericSatelliteXml($xml, $satellites);
                }
                // Last resort: try to find any elements that might contain satellite data
                else {
                    $this->logDebug("Unknown XML format, trying to extract any satellite data");
                    $this->parseUnknownFormatXml($xml, $satellites);
                }
            }
            
            $this->logDebug("Found " . count($satellites) . " satellites in XML file");
            
            // If no satellites were found, dump file content for debugging
            if (empty($satellites) && filesize($xmlFile) < 50000) { // Only dump if file is less than 50KB
                $this->logDebug("No satellites found, dumping full file content for debugging:");
                $this->logDebug(file_get_contents($xmlFile));
            }
        } catch (\Exception $e) {
            $this->logDebug("Exception in XML parsing: " . $e->getMessage());
            $this->logDebug("Stack trace: " . $e->getTraceAsString());
        }
        
        return $satellites;
    }
    
    /**
     * Parse XML in the OMM (Orbit Mean-Elements Message) format
     * @param SimpleXMLElement $xml The XML document
     * @param array &$satellites Reference to the satellites array to populate
     */
    private function parseOmmXml($xml, &$satellites)
    {
        // Handle space-track.org and other OMM formatted XML
        $segments = $xml->xpath('//segment') ?: $xml->xpath('//body');
        
        foreach ($segments as $segment) {
            $metadata = $segment->metadata ?? null;
            $data = $segment->data ?? null;
            
            if ($metadata && $data) {
                $satellite = [];
                
                // Extract name
                if (isset($metadata->OBJECT_NAME)) {
                    $satellite['name'] = (string)$metadata->OBJECT_NAME;
                }
                
                // Extract category/type if available
                if (isset($metadata->OBJECT_TYPE)) {
                    $satellite['category'] = $this->normalizeCategoryName((string)$metadata->OBJECT_TYPE);
                }
                
                // Try to get TLE data directly
                $meanElements = $data->meanElements ?? null;
                if ($meanElements) {
                    try {
                        $this->constructTLEFromOMM($satellite, $metadata, $meanElements);
                    } catch (\Exception $e) {
                        error_log("Error constructing TLE from OMM: " . $e->getMessage());
                        continue;
                    }
                }
                
                // Only add if we have the minimum required data
                if (isset($satellite['name']) && isset($satellite['line1']) && isset($satellite['line2'])) {
                    $satellites[] = $satellite;
                }
            }
        }
    }
    
    /**
     * Normalize category names from various formats
     * @param string $categoryName Raw category name from XML
     * @return string Normalized category name
     */
    private function normalizeCategoryName($categoryName)
    {
        $categoryName = trim(strtolower($categoryName));
        
        // Map specific raw categories to normalized ones
        $categoryMap = [
            'payload' => 'Payload',
            'rocket body' => 'Rocket Body',
            'debris' => 'Debris',
            'unknown' => 'Unknown',
            'weather' => 'Weather',
            'navigation' => 'Navigation',
            'gps' => 'Navigation',
            'communications' => 'Communication',
            'communication' => 'Communication',
            'scientific' => 'Scientific',
            'earth observation' => 'Earth Observation',
            'space station' => 'Space Station'
        ];
        
        foreach ($categoryMap as $raw => $normalized) {
            if (strpos($categoryName, $raw) !== false) {
                return $normalized;
            }
        }
        
        // If no match found, capitalize first letter of each word
        return ucwords($categoryName);
    }
    
    /**
     * Construct TLE Line 1 from orbital elements
     */
    private function constructTleLine1($satNum, $classification, $intlDesignator, $epoch, $meanMotDot, $meanMotDdot, $bstar, $elementSet)
    {
        // Ensure international designator is formatted properly or use placeholder
        if (empty($intlDesignator) || $intlDesignator === 'UNKNOWN') {
            $intlDesignator = date('y') . '000A';
        }
        
        // Format epoch properly
        $epochDate = new \DateTime($epoch);
        $year = $epochDate->format('y');
        $dayOfYear = $epochDate->format('z') + 1; // Add 1 because PHP day of year is 0-indexed
        $fractionOfDay = (((int)$epochDate->format('H') * 3600) + 
                          ((int)$epochDate->format('i') * 60) + 
                           (int)$epochDate->format('s')) / 86400;
        $formattedEpoch = $year . '.' . str_pad($dayOfYear + $fractionOfDay, 12, '0', STR_PAD_RIGHT);
        
        // Format mean motion dot
        $meanMotDotStr = $this->formatScientific($meanMotDot, 10);
        
        // Format bstar
        if (empty($bstar)) {
            $bstar = '0.00000000';
        }
        $bstarStr = $this->formatScientific($bstar, 10);
        
        // Format element set
        $elementSetStr = str_pad($elementSet, 4, ' ', STR_PAD_LEFT);
        
        // Basic TLE line 1
        $line = "1 " . str_pad($satNum, 5, ' ', STR_PAD_LEFT) . $classification . " " . 
                str_pad(substr($intlDesignator, 0, 8), 8, ' ', STR_PAD_RIGHT) . " " . 
                $formattedEpoch . " " . 
                $meanMotDotStr . " " .
                str_pad("00000-0", 8, ' ', STR_PAD_LEFT) . " " . 
                $bstarStr . " 0 " . 
                str_pad(substr($elementSetStr, -4), 4, ' ', STR_PAD_LEFT);
                
        // Calculate checksum
        $line .= $this->calculateChecksum($line);
        
        return $line;
    }
    
    /**
     * Construct TLE Line 2 from orbital elements
     */
    private function constructTleLine2($satNum, $inclination, $raAscNode, $eccentricity, $argPerigee, $meanAnomaly, $meanMotion, $revAtEpoch)
    {
        // Format inclusion as degrees
        $inclinationStr = str_pad(number_format((float)$inclination, 4, '.', ''), 8, ' ', STR_PAD_LEFT);
        
        // Format right ascension
        $raAscNodeStr = str_pad(number_format((float)$raAscNode, 4, '.', ''), 8, ' ', STR_PAD_LEFT);
        
        // Format eccentricity - remove decimal point
        $eccentricityStr = substr(number_format((float)$eccentricity, 7, '', ''), 0, 7);
        $eccentricityStr = str_pad($eccentricityStr, 7, '0', STR_PAD_LEFT);
        
        // Format argument of perigee
        $argPerigeeStr = str_pad(number_format((float)$argPerigee, 4, '.', ''), 8, ' ', STR_PAD_LEFT);
        
        // Format mean anomaly
        $meanAnomalyStr = str_pad(number_format((float)$meanAnomaly, 4, '.', ''), 8, ' ', STR_PAD_LEFT);
        
        // Format mean motion
        $meanMotionStr = str_pad(number_format((float)$meanMotion, 8, '.', ''), 11, ' ', STR_PAD_LEFT);
        
        // Format revolution number
        $revAtEpochStr = str_pad((int)$revAtEpoch, 5, ' ', STR_PAD_LEFT);
        
        // Basic TLE line 2
        $line = "2 " . str_pad($satNum, 5, ' ', STR_PAD_LEFT) . " " . 
                $inclinationStr . " " . 
                $raAscNodeStr . " " . 
                $eccentricityStr . " " . 
                $argPerigeeStr . " " . 
                $meanAnomalyStr . " " . 
                $meanMotionStr . $revAtEpochStr;
                
        // Calculate checksum
        $line .= $this->calculateChecksum($line);
        
        return $line;
    }
    
    /**
     * Format a number to scientific notation as used in TLEs
     */
    private function formatScientific($number, $length)
    {
        if (empty($number) || $number == '0' || $number == '0.0') {
            return str_pad('0.00000000', $length, ' ', STR_PAD_LEFT);
        }
        
        // Format as scientific notation
        $formatted = sprintf('%.8e', (float)$number);
        
        // Extract mantissa and exponent
        list($mantissa, $exponent) = explode('e', $formatted);
        $mantissa = number_format((float)$mantissa, 8, '.', '');
        
        // Format TLE style (mantissa with decimal, space, sign, exponent without leading 0)
        $exponentVal = (int)$exponent;
        $exponentSign = $exponentVal >= 0 ? '+' : '-';
        $exponentStr = abs($exponentVal);
        
        $result = $mantissa . " " . $exponentSign . $exponentStr;
        
        return $result;
    }
    
    /**
     * Calculate TLE checksum (modulo 10 sum of all digits)
     */
    private function calculateChecksum($line)
    {
        $sum = 0;
        
        for ($i = 0; $i < strlen($line); $i++) {
            $c = substr($line, $i, 1);
            if ($c === '-') {
                $sum += 1;
            } else if (is_numeric($c)) {
                $sum += (int)$c;
            }
        }
        
        return $sum % 10;
    }
    
    public function exportJson()
    {
        $model = new SatelliteModel();
        $json = $model->exportToJson();
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="satellites.json"');
        echo $json;
        exit;
    }
    
    public function exportXml()
    {
        try {
            $satelliteModel = new SatelliteModel();
            $xml = $satelliteModel->exportToXml();
            
            // Set proper headers for XML download
            header('Content-Type: text/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="satellites_export_' . date('Y-m-d') . '.xml"');
            header('Content-Length: ' . strlen($xml));
            header('Pragma: no-cache');
            
            echo $xml;
            exit;
        } catch (\Exception $e) {
            error_log("Error exporting XML: " . $e->getMessage());
            Application::$app->session->set('errorNotification', 'Error exporting satellites to XML: ' . $e->getMessage());
            header("location:" . Application::url('/satellites'));
            exit;
        }
    }
    
    private function parseTles($tles)
    {
        $tleArr = [];
        $linesArr = explode("\n", $tles);
        $totalLines = count($linesArr);
        
        $this->logDebug("Starting TLE parsing with " . $totalLines . " lines");
        
        // Track lines processed for performance monitoring
        $lineCounter = 0;
        $batchSize = 1000;
        $batchCounter = 0;
        
        // For each satellite (3 lines per satellite)
        for ($i = 0; $i < $totalLines; $i += 3) {
            // Update progress occasionally
            if (++$lineCounter >= $batchSize) {
                $batchCounter++;
                $this->logDebug("Processed " . ($batchCounter * $batchSize) . " of " . $totalLines . " lines");
                $lineCounter = 0;
            }
            
            // Skip if we don't have enough lines left
            if ($i + 2 >= $totalLines) {
                break;
            }
            
            $name = trim($linesArr[$i]);
            $line1 = trim($linesArr[$i + 1]);
            $line2 = trim($linesArr[$i + 2]);
            
            // Validate data
            if (empty($name) || empty($line1) || empty($line2)) {
                continue;
                    }
            
            // Validate line1 and line2 format
            if (!(substr($line1, 0, 2) === "1 " || substr($line1, 0, 1) === "1") || 
                !(substr($line2, 0, 2) === "2 " || substr($line2, 0, 1) === "2")) {
                continue;
            }
            
            $tleArr[] = [
                'name' => $name,
                'line1' => $line1,
                'line2' => $line2
            ];
            
            // Free memory periodically
            if ($i > $batchSize * 2) {
                for ($j = max(0, $i - $batchSize * 2); $j < $i - 3; $j++) {
                    unset($linesArr[$j]);
                }
            }
        }
        
        // Free memory
        unset($linesArr);
        
        $this->logDebug("Parsed " . count($tleArr) . " satellites from TLE data");
        
        return $tleArr;
    }

    public function accessRole(): array
    {
        $action = $this->getActionFromPath();
        
        switch ($action) {
            case "list":
            case "detail":
                return ["Administrator", "Manager", "Member", "User"];
            case "import":
            case "processImport":
            case "exportJson":
            case "exportXml":
            case "reassignSatellites":
            case "deleteAllSatellites":
            case "debugImport":
            case "removeDuplicates":
                return ["Administrator"];
            default:
                return ["Administrator"];
        }
    }
    
    private function getActionFromPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (strpos($path, '/satellites') === 0 || strpos($path, '/VBIS-main/public/satellites') === 0) {
            return 'list';
        }
        
        if (strpos($path, '/satelliteDetail') === 0 || strpos($path, '/VBIS-main/public/satelliteDetail') === 0) {
            return 'detail';
        }
        
        if (strpos($path, '/importSatellites') !== false) {
            return 'import';
        }
        
        if (strpos($path, '/processImport') !== false) {
            return 'processImport';
        }
        
        if (strpos($path, '/exportJson') !== false) {
            return 'exportJson';
        }
        
        if (strpos($path, '/exportXml') !== false) {
            return 'exportXml';
        }
        
        if (strpos($path, '/reassignSatellites') !== false) {
            return 'reassignSatellites';
        }
        
        if (strpos($path, '/debugImport') !== false) {
            return 'debugImport';
        }
        
        if (strpos($path, '/deleteAllSatellites') !== false) {
            return 'deleteAllSatellites';
        }
        
        if (strpos($path, '/removeDuplicates') !== false) {
            return 'removeDuplicates';
        }
        
        return '';
    }

    /**
     * Construct TLE lines from OMM XML data
     * @param array &$satellite Reference to the satellite array
     * @param SimpleXMLElement $metadata XML metadata element
     * @param SimpleXMLElement $meanElements XML mean elements
     * @throws Exception If required elements are missing
     */
    private function constructTLEFromOMM(&$satellite, $metadata, $meanElements)
    {
        // Extract necessary parameters
        $noradCatId = (string)$metadata->OBJECT_ID ?? '';
        if (empty($noradCatId)) {
            throw new \Exception("Missing NORAD ID");
        }
        
        // Extract required elements
        $epoch = (string)$meanElements->EPOCH ?? '';
        $meanMotion = (string)$meanElements->MEAN_MOTION ?? '';
        $eccentricity = (string)$meanElements->ECCENTRICITY ?? '';
        $inclination = (string)$meanElements->INCLINATION ?? '';
        $raAscNode = (string)$meanElements->RA_OF_ASC_NODE ?? '';
        $argPericenter = (string)$meanElements->ARG_OF_PERICENTER ?? '';
        $meanAnomaly = (string)$meanElements->MEAN_ANOMALY ?? '';
        
        // Extract optional elements
        $classification = (string)$metadata->CLASSIFICATION_TYPE ?? 'U';
        $intlDesignator = (string)$metadata->INTERNATIONAL_DESIGNATOR ?? '';
        $elementSetNo = (string)$metadata->ELEMENT_SET_NO ?? '999';
        $revAtEpoch = (string)$meanElements->REV_AT_EPOCH ?? '0';
        $bstar = (string)$meanElements->BSTAR ?? '0.0';
        $meanMotionDot = (string)$meanElements->MEAN_MOTION_DOT ?? '0.0';
        $meanMotionDdot = (string)$meanElements->MEAN_MOTION_DDOT ?? '0.0';
        
        // Check if all required elements are present
        if (empty($epoch) || empty($meanMotion) || empty($eccentricity) || 
            empty($inclination) || empty($raAscNode) || empty($argPericenter) || empty($meanAnomaly)) {
            throw new \Exception("Missing required orbital elements");
        }
        
        // Format TLE according to specs
        $satelliteNum = str_pad($noradCatId, 5, '0', STR_PAD_LEFT);
        
        $satellite['line1'] = $this->constructTleLine1(
            $satelliteNum,
            $classification,
            $intlDesignator,
            $epoch,
            $meanMotionDot,
            $meanMotionDdot,
            $bstar,
            $elementSetNo
        );
        
        $satellite['line2'] = $this->constructTleLine2(
            $satelliteNum,
            $inclination,
            $raAscNode,
            $eccentricity,
            $argPericenter,
            $meanAnomaly,
            $meanMotion,
            $revAtEpoch
        );
    }
    
    /**
     * Convert orbital elements from XML to TLE format
     * @param SimpleXMLElement $segment XML segment containing orbital elements
     * @param array &$satellite Reference to the satellite array to populate
     * @throws Exception If required elements are missing
     */
    private function convertOrbitalElementsToTLE($segment, &$satellite)
    {
        // Extract orbital parameters from XML with flexible field names
        $fields = [
            'noradCatId' => ['NORAD_CAT_ID', 'CATALOG_NUM', 'norad_id', 'catalogNumber'],
            'classification' => ['CLASSIFICATION_TYPE', 'classification'],
            'intlDesignator' => ['OBJECT_ID', 'INTERNATIONAL_DESIGNATOR', 'intlDesignator'],
            'epoch' => ['EPOCH', 'epoch'],
            'meanMotion' => ['MEAN_MOTION', 'meanMotion', 'mm'],
            'meanMotionDot' => ['MEAN_MOTION_DOT', 'meanMotionDot', 'mm_dot'],
            'meanMotionDdot' => ['MEAN_MOTION_DDOT', 'meanMotionDdot', 'mm_ddot'],
            'bstar' => ['BSTAR', 'bstar'],
            'elementSetNo' => ['ELEMENT_SET_NO', 'elementSetNum'],
            'inclination' => ['INCLINATION', 'inclination'],
            'raAscNode' => ['RA_OF_ASC_NODE', 'raan', 'rightAscension'],
            'eccentricity' => ['ECCENTRICITY', 'eccentricity'],
            'argPericenter' => ['ARG_OF_PERICENTER', 'argPerigee', 'perigee'],
            'meanAnomaly' => ['MEAN_ANOMALY', 'meanAnomaly', 'ma'],
            'revAtEpoch' => ['REV_AT_EPOCH', 'revNum', 'revolution']
        ];
        
        // Extract values with multiple potential field names
        $params = [];
        foreach ($fields as $param => $fieldNames) {
            $params[$param] = '';
            foreach ($fieldNames as $field) {
                if (isset($segment->$field)) {
                    $params[$param] = (string)$segment->$field;
                    break;
                }
            }
        }
        
        // Apply defaults for optional parameters
        $params['classification'] = $params['classification'] ?: 'U';
        $params['elementSetNo'] = $params['elementSetNo'] ?: '999';
        $params['meanMotionDdot'] = $params['meanMotionDdot'] ?: '0.0';
        $params['revAtEpoch'] = $params['revAtEpoch'] ?: '0';
        
        // Check for required parameters
        $requiredParams = ['noradCatId', 'epoch', 'meanMotion', 'eccentricity', 
                          'inclination', 'raAscNode', 'argPericenter', 'meanAnomaly'];
        
        foreach ($requiredParams as $required) {
            if (empty($params[$required])) {
                throw new \Exception("Missing required parameter: $required");
            }
        }
        
        // Format TLE lines according to spec
        $satelliteNum = str_pad($params['noradCatId'], 5, '0', STR_PAD_LEFT);
        
        $satellite['line1'] = $this->constructTleLine1(
            $satelliteNum,
            $params['classification'],
            $params['intlDesignator'],
            $params['epoch'],
            $params['meanMotionDot'],
            $params['meanMotionDdot'],
            $params['bstar'],
            $params['elementSetNo']
        );
        
        $satellite['line2'] = $this->constructTleLine2(
            $satelliteNum,
            $params['inclination'],
            $params['raAscNode'],
            $params['eccentricity'],
            $params['argPericenter'],
            $params['meanAnomaly'],
            $params['meanMotion'],
            $params['revAtEpoch']
        );
    }

    /**
     * Converts 3LE format to standard TLE format
     * 3LE format has line 0 with name, then lines 1 and 2 with TLE data
     * 
     * @param string $content The 3LE content
     * @return string Standard TLE content
     */
    private function convert3leToBinary($content) 
    {
        // Process line by line to reduce memory usage
        $lines = explode("\n", $content);
        $result = [];
        $currentSet = [];
        $totalLines = count($lines);
        
        $this->logDebug("Converting 3LE format - processing $totalLines lines");
        
        // Process in batches to reduce memory usage
        $batchSize = 300; // Process 100 satellites (3 lines each) at a time
        
        for ($i = 0; $i < $totalLines; $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            // Check if this is a name line (starts with 0)
            if (substr($line, 0, 2) === "0 ") {
                // If we have a previous set, add it to results
                if (!empty($currentSet)) {
                    $result[] = implode("\n", $currentSet);
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
        
            // If we've reached the batch size or the end, process and clear memory
            if (count($result) >= $batchSize || $i === $totalLines - 1) {
        if (!empty($currentSet)) {
                    $result[] = implode("\n", $currentSet);
                    $currentSet = [];
                }
                
                // Free up memory by clearing the lines we've already processed
                if ($i >= $batchSize) {
                    $sliceStart = max(0, $i - $batchSize);
                    for ($j = $sliceStart; $j <= $i; $j++) {
                        unset($lines[$j]);
                    }
                }
            }
        }
        
        // Free memory
        unset($lines);
        
        return implode("\n", $result);
    }

    /**
     * Parse Space-Track TLE XML format
     * @param SimpleXMLElement $xml The XML document
     * @param array &$satellites Reference to the satellites array to populate
     */
    private function parseSpaceTrackXml($xml, &$satellites)
    {
        $this->logDebug("Parsing Space-Track XML format");
        
        // Try various XPaths for Space-Track.org XML formats
        $tleElements = $xml->xpath('//tle') ?: 
                       $xml->xpath('//TLE') ?: 
                       $xml->xpath('//data/tle');
        
        if (empty($tleElements)) {
            $this->logDebug("No direct TLE elements found, trying alternate Space-Track format");
            
            // Try to find data/sat_name and data/tle_line1, data/tle_line2 pattern (common in Space-Track exports)
            $dataElements = $xml->xpath('//data');
            if (!empty($dataElements)) {
                foreach ($dataElements as $data) {
                    $satellite = [];
                    
                    // Look for different possible field names in Space-Track.org exports
                    $nameFields = ['OBJECT_NAME', 'SATNAME', 'SATELLITE_NAME', 'sat_name', 'OBJECT_ID'];
                    $line1Fields = ['TLE_LINE1', 'tle_line1', 'LINE1'];
                    $line2Fields = ['TLE_LINE2', 'tle_line2', 'LINE2'];
                    
                    // Try to extract the name
                    foreach ($nameFields as $field) {
                        if (isset($data->$field) && !empty((string)$data->$field)) {
                            $satellite['name'] = (string)$data->$field;
                            break;
                        }
                    }
                    
                    // If no name found, try to create one from NORAD ID or OBJECT_ID
                    if (empty($satellite['name'])) {
                        if (isset($data->NORAD_CAT_ID)) {
                            $satellite['name'] = "NORAD " . (string)$data->NORAD_CAT_ID;
                        } elseif (isset($data->OBJECT_ID)) {
                            $satellite['name'] = "ID " . (string)$data->OBJECT_ID;
                        } else {
                            continue; // Skip if no name can be determined
                        }
                    }
                    
                    // Try to extract line1
                    foreach ($line1Fields as $field) {
                        if (isset($data->$field) && !empty((string)$data->$field)) {
                            $satellite['line1'] = (string)$data->$field;
                            break;
                        }
                    }
                    
                    // Try to extract line2
                    foreach ($line2Fields as $field) {
                        if (isset($data->$field) && !empty((string)$data->$field)) {
                            $satellite['line2'] = (string)$data->$field;
                            break;
                        }
                    }
                    
                    // Try to extract category/object type
                    if (isset($data->OBJECT_TYPE)) {
                        $satellite['category'] = $this->normalizeCategoryName((string)$data->OBJECT_TYPE);
                    }
                    
                    // Only add if we have the minimum required data
                    if (isset($satellite['name']) && isset($satellite['line1']) && isset($satellite['line2'])) {
                        $this->logDebug("Found satellite in Space-Track format", $satellite);
                        $satellites[] = $satellite;
                    } else {
                        $this->logDebug("Incomplete satellite data in Space-Track format", $satellite);
                    }
                }
            }
            return;
        }
        
        // Process standard TLE elements if found
        foreach ($tleElements as $tle) {
            $satellite = [];
            
            // Try to find name
            if (isset($tle->OBJECT_NAME)) {
                $satellite['name'] = (string)$tle->OBJECT_NAME;
            } elseif (isset($tle->SATNAME)) {
                $satellite['name'] = (string)$tle->SATNAME;
            } elseif (isset($tle->SATELLITE_NAME)) {
                $satellite['name'] = (string)$tle->SATELLITE_NAME;
            } elseif (isset($tle->name)) {
                $satellite['name'] = (string)$tle->name;
            } elseif (isset($tle->OBJECT_ID)) {
                $satellite['name'] = "ID " . (string)$tle->OBJECT_ID;
            } else {
                continue; // Skip if no name can be determined
            }
            
            // Try to extract TLE lines
            if (isset($tle->TLE_LINE1) && isset($tle->TLE_LINE2)) {
                $satellite['line1'] = (string)$tle->TLE_LINE1;
                $satellite['line2'] = (string)$tle->TLE_LINE2;
            } elseif (isset($tle->LINE1) && isset($tle->LINE2)) {
                $satellite['line1'] = (string)$tle->LINE1;
                $satellite['line2'] = (string)$tle->LINE2;
            } elseif (isset($tle->line1) && isset($tle->line2)) {
                $satellite['line1'] = (string)$tle->line1;
                $satellite['line2'] = (string)$tle->line2;
            } else {
                continue; // Skip if no TLE lines found
            }
            
            // Try to extract category
            if (isset($tle->OBJECT_TYPE)) {
                $satellite['category'] = $this->normalizeCategoryName((string)$tle->OBJECT_TYPE);
            }
            
            // Only add if we have the minimum required data
            if (!empty($satellite['name']) && !empty($satellite['line1']) && !empty($satellite['line2'])) {
                $this->logDebug("Found satellite in TLE format", $satellite);
                $satellites[] = $satellite;
            }
        }
    }
    
    /**
     * Parse generic satellite XML format
     * @param SimpleXMLElement $xml The XML document
     * @param array &$satellites Reference to the satellites array to populate
     */
    private function parseGenericSatelliteXml($xml, &$satellites)
    {
        $satelliteElements = $xml->xpath('//satellite') ?: 
                             $xml->xpath('//Satellite') ?: 
                             $xml->xpath('//*[contains(local-name(), "satellite")]');
        
        foreach ($satelliteElements as $satElement) {
            $satellite = [];
            
            // Try multiple possible tag names for name
            $nameValue = null;
            $possibleNameTags = ['name', 'NAME', 'n', 'title', 'id', 'identifier'];
            
            foreach ($possibleNameTags as $tag) {
                if (isset($satElement->$tag) && !empty((string)$satElement->$tag)) {
                    $nameValue = (string)$satElement->$tag;
                    break;
                }
            }
            
            if ($nameValue) {
                $satellite['name'] = $nameValue;
            } else {
                continue; // Skip if no name is found
            }
            
            // Try to extract TLE lines
            if (isset($satElement->line1) && isset($satElement->line2)) {
                $satellite['line1'] = (string)$satElement->line1;
                $satellite['line2'] = (string)$satElement->line2;
            } else if (isset($satElement->tle) && isset($satElement->tle->line1) && isset($satElement->tle->line2)) {
                $satellite['line1'] = (string)$satElement->tle->line1;
                $satellite['line2'] = (string)$satElement->tle->line2;
            } else if (isset($satElement->tle1) && isset($satElement->tle2)) {
                $satellite['line1'] = (string)$satElement->tle1;
                $satellite['line2'] = (string)$satElement->tle2;
            }
            
            // Try to extract category
            $categoryValue = null;
            $possibleCategoryTags = ['category', 'type', 'class', 'group'];
            
            foreach ($possibleCategoryTags as $tag) {
                if (isset($satElement->$tag) && !empty((string)$satElement->$tag)) {
                    $categoryValue = (string)$satElement->$tag;
                    break;
                }
            }
            
            if ($categoryValue) {
                $satellite['category'] = $this->normalizeCategoryName($categoryValue);
            }
            
            // Only add if we have the minimum required data
            if (isset($satellite['name']) && isset($satellite['line1']) && isset($satellite['line2'])) {
                $satellites[] = $satellite;
            }
        }
    }
    
    /**
     * Parse unknown format XML - try to extract satellite data by looking for key elements
     * @param SimpleXMLElement $xml The XML document
     * @param array &$satellites Reference to the satellites array to populate
     */
    private function parseUnknownFormatXml($xml, &$satellites)
    {
        // Convert the XML to array for easier processing
        $xmlArray = json_decode(json_encode($xml), true);
        
        // Look recursively for line1, line2, and name patterns
        $this->extractSatelliteDataRecursive($xmlArray, $satellites);
    }
    
    /**
     * Recursively search XML array to find potential satellite data
     * @param array $element Current element being processed
     * @param array &$satellites Reference to satellites array
     * @param array $currentData Current satellite data being built
     */
    private function extractSatelliteDataRecursive($element, &$satellites, $currentData = [])
    {
        if (!is_array($element)) {
            return;
        }
        
        // Check if this element contains the required TLE lines and name
        $hasLine1 = false;
        $hasLine2 = false;
        $hasName = false;
        $tempData = $currentData;
        
        foreach ($element as $key => $value) {
            $keyLower = strtolower($key);
            
            if (is_string($value)) {
                // Check if this is a TLE line 1
                if ($keyLower === 'line1' || (strpos($keyLower, 'line') !== false && strpos($keyLower, '1') !== false)) {
                    if (substr(trim($value), 0, 2) === '1 ' || substr(trim($value), 0, 1) === '1') {
                        $tempData['line1'] = $value;
                        $hasLine1 = true;
                    }
                }
                // Check if this is a TLE line 2
                else if ($keyLower === 'line2' || (strpos($keyLower, 'line') !== false && strpos($keyLower, '2') !== false)) {
                    if (substr(trim($value), 0, 2) === '2 ' || substr(trim($value), 0, 1) === '2') {
                        $tempData['line2'] = $value;
                        $hasLine2 = true;
                    }
                }
                // Check if this is a name
                else if ($keyLower === 'name' || $keyLower === 'n' || $keyLower === 'title' || 
                        strpos($keyLower, 'name') !== false || strpos($keyLower, 'title') !== false) {
                    $tempData['name'] = $value;
                    $hasName = true;
                }
                // Check if this is a category
                else if ($keyLower === 'category' || $keyLower === 'type' || strpos($keyLower, 'category') !== false) {
                    $tempData['category'] = $this->normalizeCategoryName($value);
                }
            }
        }
        
        // If we've found a complete satellite entry, add it
        if ($hasLine1 && $hasLine2 && $hasName) {
            $satellites[] = $tempData;
            // Reset current data to avoid duplicates
            $tempData = [];
        }
        
        // Continue recursively searching nested elements
        foreach ($element as $key => $value) {
            if (is_array($value)) {
                $this->extractSatelliteDataRecursive($value, $satellites, $tempData);
            }
        }
    }

    public function reassignSatellites()
    {
        // Check if user is admin
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        // Check if this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("location:" . Application::url('/satellites'));
            exit;
        }
        
        $fromUserId = isset($_POST['from_user']) ? intval($_POST['from_user']) : 0;
        $toUserId = isset($_POST['to_user']) ? intval($_POST['to_user']) : 0;
        
        if ($fromUserId <= 0 || $toUserId <= 0) {
            Application::$app->session->set('errorNotification', 'Invalid user IDs provided');
            header("location:" . Application::url('/satellites'));
            exit;
        }
        
        try {
            $db = new \app\core\Database();
            $conn = $db->getConnection();
            
            // Count satellites to be reassigned
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
            $countStmt->bind_param("i", $fromUserId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $satelliteCount = $countResult->fetch_assoc()['count'];
            
            if ($satelliteCount === 0) {
                Application::$app->session->set('warningNotification', 'No satellites found for user ID ' . $fromUserId);
                header("location:" . Application::url('/satellites'));
                exit;
            }
            
            // Update satellites
            $updateStmt = $conn->prepare("UPDATE satellites SET added_by = ? WHERE added_by = ?");
            $updateStmt->bind_param("ii", $toUserId, $fromUserId);
            
            if ($updateStmt->execute()) {
                $affectedRows = $updateStmt->affected_rows;
                Application::$app->session->set('successNotification', 'Successfully reassigned ' . $affectedRows . ' satellites from user ID ' . $fromUserId . ' to user ID ' . $toUserId);
            } else {
                Application::$app->session->set('errorNotification', 'Error reassigning satellites: ' . $conn->error);
            }
        } catch (\Exception $e) {
            Application::$app->session->set('errorNotification', 'Error: ' . $e->getMessage());
        }
        
        header("location:" . Application::url('/satellites'));
        exit;
    }

    /**
     * Helper function for detailed logging
     */
    private function logDebug($message, $data = null) {
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $dataStr = json_encode($data);
            } else {
                $dataStr = (string)$data;
            }
            error_log($message . ': ' . $dataStr);
        } else {
            error_log($message);
        }
    }

    public function debugImport()
    {
        // Load and include the debug import script
        include_once __DIR__ . '/../public/debug-import.php';
        exit;
    }

    public function deleteAllSatellites()
    {
        // Only administrators can delete all satellites
        if (!Application::$app->session->isInRole('Administrator')) {
            header("location:" . Application::url('/accessDenied'));
            exit;
        }
        
        try {
            $db = new \app\core\Database();
            $conn = $db->getConnection();
            
            // Truncate the satellites table
            $result = $conn->query("TRUNCATE TABLE satellites");
            
            if ($result) {
                Application::$app->session->set('successNotification', 'All satellites have been deleted from the database.');
            } else {
                Application::$app->session->set('errorNotification', 'Error deleting satellites: ' . $conn->error);
            }
            
            // Redirect back to satellites page
            header("location:" . Application::url('/satellites'));
            exit;
        } catch (\Exception $e) {
            Application::$app->session->set('errorNotification', 'Error: ' . $e->getMessage());
            header("location:" . Application::url('/satellites'));
            exit;
        }
    }

    /**
     * Remove duplicate satellites (keeping only one instance of each name)
     */
    public function removeDuplicates()
    {
        // Increase execution time for large databases
        set_time_limit(600); // 10 minutes
        
        // Track results
        $results = [
            'duplicatesFound' => 0,
            'duplicatesRemoved' => 0,
            'satellitesChecked' => 0,
            'uniqueNames' => 0,
            'errors' => []
        ];
        
        try {
            // Get the satellite model
            $satelliteModel = new SatelliteModel();
            
            // Connect to database
            $db = new \app\core\Database();
            $conn = $db->getConnection();
            
            // First, find all satellite names and how many instances of each exist
            $query = "SELECT name, COUNT(*) as count FROM satellites GROUP BY name HAVING COUNT(*) > 1";
            $result = $conn->query($query);
            
            if (!$result) {
                throw new \Exception("Error querying database: " . $conn->error);
            }
            
            $duplicateNames = [];
            $totalDuplicates = 0;
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $duplicateNames[] = [
                        'name' => $row['name'],
                        'count' => $row['count']
                    ];
                    $totalDuplicates += ($row['count'] - 1); // Count duplicates (all instances minus one to keep)
                }
            }
            
            $results['duplicatesFound'] = $totalDuplicates;
            $results['uniqueNames'] = count($duplicateNames);
            
            // Process form submission
            $action = $_POST['action'] ?? '';
            if ($action === 'remove') {
                // Process in batches to avoid timeouts
                $batchSize = 50;
                $batches = array_chunk($duplicateNames, $batchSize);
                
                $this->logDebug("Starting to process " . count($duplicateNames) . " duplicate names in " . count($batches) . " batches");
                
                foreach ($batches as $batchIndex => $batch) {
                    $this->logDebug("Processing batch " . ($batchIndex + 1) . " of " . count($batches));
                    
                    foreach ($batch as $duplicate) {
                        $name = $conn->real_escape_string($duplicate['name']);
                        
                        // Find the highest ID for this name
                        $findHighestQuery = "SELECT MAX(id) as max_id FROM satellites WHERE name = '$name'";
                        $highestResult = $conn->query($findHighestQuery);
                        
                        if ($highestResult && $row = $highestResult->fetch_assoc()) {
                            $keepId = $row['max_id'];
                            
                            // Delete all other satellites with this name
                            $deleteQuery = "DELETE FROM satellites WHERE name = '$name' AND id != $keepId";
                            if ($conn->query($deleteQuery)) {
                                $removedCount = $conn->affected_rows;
                                $results['duplicatesRemoved'] += $removedCount;
                            } else {
                                $results['errors'][] = "Error removing duplicates for '$name': " . $conn->error;
                            }
                        }
                        
                        // Free result set to avoid memory issues
                        $highestResult->free();
                    }
                    
                    // After each batch, free memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
                
                $this->logDebug("Finished processing all batches. Removed " . $results['duplicatesRemoved'] . " duplicates");
                
                // Show success message
                if (empty($results['errors'])) {
                    Application::$app->session->set('successNotification', "Successfully removed {$results['duplicatesRemoved']} duplicate satellites!");
                    
                    // Store removal summary for modal display
                    $removalSummary = [
                        'duplicatesRemoved' => $results['duplicatesRemoved'],
                        'uniqueNames' => $results['uniqueNames'],
                        'satellitesChecked' => $results['satellitesChecked'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    Application::$app->session->set('removalSummary', $removalSummary);
                } else {
                    $errorMsg = "Removed {$results['duplicatesRemoved']} duplicates but encountered errors: " . 
                                implode("; ", $results['errors']);
                    Application::$app->session->set('errorNotification', $errorMsg);
                }
                
                // Regenerate TLEs file
                $satelliteModel->getSatellitesAsJsArray();
                
                // Redirect back to satellites page
                header("location:" . Application::url('/satellites'));
                exit;
            }
            
            // Get total satellite count
            $countQuery = "SELECT COUNT(*) as total FROM satellites";
            $countResult = $conn->query($countQuery);
            if ($countResult && $row = $countResult->fetch_assoc()) {
                $results['satellitesChecked'] = $row['total'];
            }
            
            // Free result set
            if ($countResult) {
                $countResult->free();
            }
            
            // Render the page with duplicate info
            $this->view->render('satellites/remove-duplicates', 'main', [
                'results' => $results,
                'duplicates' => $duplicateNames
            ]);
            
        } catch (\Exception $e) {
            $this->logDebug("Error in removeDuplicates: " . $e->getMessage());
            Application::$app->session->set('errorNotification', "Error: " . $e->getMessage());
            header("location:" . Application::url('/satellites'));
            exit;
        }
    }
} 