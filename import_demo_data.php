<?php

require_once __DIR__ . "/vendor/autoload.php";

use app\models\SatelliteModel;
use app\models\ImportedFileModel;

// Sample TLE data for initial import
$tlesContent = <<<EOT
ISS (ZARYA)
1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995
2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009
NOAA 15
1 25338U 98030A   20129.85968832  .00000063  00000-0  44811-4 0  9995
2 25338  98.7202 155.1056 0010749  24.5196 335.6492 14.25963351143595
NOAA 18
1 28654U 05018A   20129.84960270 +.00000081 +00000-0 +68418-4 0  9999
2 28654 099.0471 185.9015 0013785 347.0501 013.0314 14.12508683771320
METOP-A
1 29499U 06044A   20129.88840373  .00000002  00000-0  20280-4 0  9990
2 29499  98.5262 178.0263 0001477  74.9454  39.3236 14.21497035703266
NOAA 19
1 33591U 09005A   20129.85068839  .00000035  00000-0  44620-4 0  9994
2 33591  99.1964 134.1676 0013925 182.7585 177.3511 14.12406851579801
EOT;

// Parse the TLE data
function parseTles($tles) {
    $tleArr = [];
    $linesArr = explode("\n", $tles);
    $tle = "placeholder";
    
    foreach ($linesArr as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (substr($line, 0, 2) === "1 ") {
            $tle['line1'] = $line;
        } elseif (substr($line, 0, 2) === "2 ") {
            $tle['line2'] = $line;
        } else {
            if ($tle !== "placeholder") {
                $tleArr[] = $tle;
            }
            $tle = [];
            $tle['name'] = $line;
        }
    }
    
    // Add the last TLE
    if ($tle !== "placeholder" && isset($tle['name']) && isset($tle['line1']) && isset($tle['line2'])) {
        $tleArr[] = $tle;
    }
    
    return $tleArr;
}

$tleArr = parseTles($tlesContent);

// Categorize satellites based on name
function categorize($name) {
    $name = strtoupper($name);
    
    if (strpos($name, 'NOAA') !== false || strpos($name, 'METOP') !== false || strpos($name, 'METEOR') !== false) {
        return 'Weather';
    } elseif (strpos($name, 'ISS') !== false) {
        return 'Space Station';
    } elseif (strpos($name, 'GPS') !== false || strpos($name, 'NAVSTAR') !== false || strpos($name, 'GLONASS') !== false) {
        return 'Navigation';
    } elseif (strpos($name, 'IRIDIUM') !== false || strpos($name, 'INTELSAT') !== false || strpos($name, 'INMARSAT') !== false) {
        return 'Communication';
    } elseif (strpos($name, 'HUBBLE') !== false || strpos($name, 'HST') !== false) {
        return 'Scientific';
    } else {
        return 'Other';
    }
}

// Insert satellites into database
$satelliteModel = new SatelliteModel();
$importModel = new ImportedFileModel();
$satelliteCount = 0;

// Admin user ID (assuming ID 1 is admin)
$adminId = 1;

foreach ($tleArr as $tle) {
    if (isset($tle['name']) && isset($tle['line1']) && isset($tle['line2'])) {
        $satelliteModel = new SatelliteModel();
        $satelliteModel->name = $tle['name'];
        $satelliteModel->line1 = $tle['line1'];
        $satelliteModel->line2 = $tle['line2'];
        $satelliteModel->category = categorize($tle['name']);
        $satelliteModel->added_by = $adminId;
        
        try {
            $satelliteModel->insert();
            $satelliteCount++;
            echo "Imported: {$tle['name']}\n";
        } catch (\Exception $e) {
            echo "Error importing {$tle['name']}: {$e->getMessage()}\n";
        }
    }
}

// Record the import
$importModel = new ImportedFileModel();
$importModel->filename = 'Initial demo data import';
$importModel->uploaded_by = $adminId;
$importModel->satellite_count = $satelliteCount;
$importModel->insert();

echo "Import complete. Imported $satelliteCount satellites.\n"; 