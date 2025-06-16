<?php

namespace app\controllers;

use app\core\Application;
use app\core\BaseController;
use app\models\ImportedFileModel;
use app\models\ObservedPositionModel;
use app\models\SatelliteModel;

class ReportController extends BaseController
{
    public function satelliteStatistics()
    {
        $satelliteModel = new SatelliteModel();
        $categories = $satelliteModel->getSatelliteCategories();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $satellites = $satelliteModel->getSatellitesByCategory($category);
            $categoryData[$category] = count($satellites);
        }
        
        // Get data for the scatter plot (launch year vs mean motion)
        $satellites = $satelliteModel->all("ORDER BY name");
        $scatterData = [];
        
        foreach ($satellites as $satellite) {
            // Extract launch year from line1 (columns 10-11 in TLE format)
            // Reference: https://en.wikipedia.org/wiki/Two-line_element_set
            if (!empty($satellite['line1'])) {
                $launchYearStr = substr($satellite['line1'], 9, 2); // International designator year (last 2 digits)
                $launchYear = intval($launchYearStr);
                
                // Convert 2-digit year to 4-digit year
                if ($launchYear < 57) { // Sputnik launched in 1957, anything before is 2000s
                    $launchYear += 2000;
                } else {
                    $launchYear += 1900;
                }
                
                // Extract mean motion (revolutions per day) from line2 (columns 53-63)
                if (!empty($satellite['line2'])) {
                    $meanMotionStr = substr($satellite['line2'], 52, 11);
                    $meanMotion = floatval($meanMotionStr);
                    
                    if ($meanMotion > 0) {
                        $scatterData[] = [
                            'x' => $launchYear,
                            'y' => $meanMotion,
                            'name' => $satellite['name']
                        ];
                    }
                }
            }
        }
        
        $this->view->render('reports/satellite_statistics', 'main', [
            'categories' => $categories,
            'categoryData' => $categoryData,
            'scatterData' => $scatterData
        ]);
    }
    
    public function importStatistics()
    {
        // Check if user is logged in (admins only have access to the link in navbar)
        if (!isset($_SESSION['user'])) {
            // Redirect to login page if not logged in
            header('Location: /VBIS-main/public/login');
            exit;
        }
        
        error_log("=== Starting importStatistics method in ReportController ===");
        
        // Get model data
        $importedFileModel = new ImportedFileModel();
        $statistics = $importedFileModel->getImportStatistics();
        
        error_log("Statistics from model: daily count=" . count($statistics['dailyStats'] ?? []) . 
                  ", calendar count=" . count($statistics['calendarData'] ?? []) .
                  ", satellite count=" . count($statistics['satelliteData'] ?? []));
        
        // Get current time for debug info
        $now = date('Y-m-d H:i:s');
        $hasData = !empty($statistics['calendarData']);
        
        // Debug: Check for the expected structure and data
        $debugInfo = "Has data: " . ($hasData ? 'Yes' : 'No') . 
                    ", Daily count: " . count($statistics['dailyStats'] ?? []) . 
                    ", Calendar count: " . count($statistics['calendarData'] ?? []) . 
                    ", Time: " . $now;
        error_log("Debug info for view: " . $debugInfo);
        
        // Directly output a sample of the data for debugging
        if ($hasData) {
            $sample = array_slice($statistics['calendarData'], 0, 3, true);
            error_log("Sample calendar data: " . json_encode($sample));
        } else {
            error_log("No calendar data available to sample");
        }
        
        // Create a model array with all the data
        $model = [
            'summary' => $statistics['summary'] ?? [
                'total_imports' => 0,
                'total_satellites' => 0,
                'avg_satellites_per_import' => 0,
                'last_import_date' => null
            ],
            'dailyStats' => $statistics['dailyStats'] ?? [],
            'calendarData' => $statistics['calendarData'] ?? [],
            'satelliteData' => $statistics['satelliteData'] ?? [],
            'topImporters' => $statistics['topImporters'] ?? [],
            'debugInfo' => $debugInfo
        ];
        
        // Render the view with the model data
        $this->view->render('reports/import_statistics', 'main', $model);
    }
    

    
    public function filterPositions()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $positionModel = new ObservedPositionModel();
        $positions = $positionModel->getPositionsByDateRange($startDate, $endDate);
        
        $this->view->render('reports/filter_positions', 'main', [
            'positions' => $positions,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    public function exportSatelliteStatsJson()
    {
        // Get the same data as in satelliteStatistics
        $satelliteModel = new SatelliteModel();
        $categories = $satelliteModel->getSatelliteCategories();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $satellites = $satelliteModel->getSatellitesByCategory($category);
            $categoryData[$category] = count($satellites);
        }
        
        // Get data for the scatter plot
        $satellites = $satelliteModel->all("ORDER BY name");
        $scatterData = [];
        
        foreach ($satellites as $satellite) {
            if (!empty($satellite['line1'])) {
                $launchYearStr = substr($satellite['line1'], 9, 2);
                $launchYear = intval($launchYearStr);
                
                // Convert 2-digit year to 4-digit year
                if ($launchYear < 57) {
                    $launchYear += 2000;
                } else {
                    $launchYear += 1900;
                }
                
                // Extract mean motion from line2
                if (!empty($satellite['line2'])) {
                    $meanMotionStr = substr($satellite['line2'], 52, 11);
                    $meanMotion = floatval($meanMotionStr);
                    
                    if ($meanMotion > 0) {
                        $scatterData[] = [
                            'name' => $satellite['name'],
                            'year' => $launchYear,
                            'speed' => $meanMotion,
                            'category' => $satellite['category']
                        ];
                    }
                }
            }
        }
        
        // Create the complete data structure
        $exportData = [
            'chart_data' => [
                'categories' => $categories,
                'category_counts' => $categoryData,
                'scatter_plot' => $scatterData
            ],
            'export_date' => date('Y-m-d H:i:s'),
            'total_satellites' => count($satellites)
        ];
        
        // Output as JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="satellite_statistics_' . date('Y-m-d') . '.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT);
        exit;
    }
    
    public function exportSatelliteStatsXml()
    {
        // Get the same data as in satelliteStatistics
        $satelliteModel = new SatelliteModel();
        $categories = $satelliteModel->getSatelliteCategories();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $satellites = $satelliteModel->getSatellitesByCategory($category);
            $categoryData[$category] = count($satellites);
        }
        
        // Get data for the scatter plot
        $satellites = $satelliteModel->all("ORDER BY name");
        $scatterData = [];
        
        foreach ($satellites as $satellite) {
            if (!empty($satellite['line1'])) {
                $launchYearStr = substr($satellite['line1'], 9, 2);
                $launchYear = intval($launchYearStr);
                
                // Convert 2-digit year to 4-digit year
                if ($launchYear < 57) {
                    $launchYear += 2000;
                } else {
                    $launchYear += 1900;
                }
                
                // Extract mean motion from line2
                if (!empty($satellite['line2'])) {
                    $meanMotionStr = substr($satellite['line2'], 52, 11);
                    $meanMotion = floatval($meanMotionStr);
                    
                    if ($meanMotion > 0) {
                        $scatterData[] = [
                            'name' => $satellite['name'],
                            'year' => $launchYear,
                            'speed' => $meanMotion,
                            'category' => $satellite['category']
                        ];
                    }
                }
            }
        }
        
        // Create XML document
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        $root = $dom->createElement('satellite_statistics');
        $dom->appendChild($root);
        
        // Add export metadata
        $metadata = $dom->createElement('metadata');
        $root->appendChild($metadata);
        
        $exportDate = $dom->createElement('export_date', date('Y-m-d H:i:s'));
        $metadata->appendChild($exportDate);
        
        $totalSatellites = $dom->createElement('total_satellites', count($satellites));
        $metadata->appendChild($totalSatellites);
        
        // Add categories data
        $categoriesElement = $dom->createElement('categories');
        $root->appendChild($categoriesElement);
        
        foreach ($categories as $category) {
            $categoryElement = $dom->createElement('category');
            $categoriesElement->appendChild($categoryElement);
            
            $nameElement = $dom->createElement('name');
            $nameElement->appendChild($dom->createCDATASection($category));
            $categoryElement->appendChild($nameElement);
            
            $countElement = $dom->createElement('count', $categoryData[$category]);
            $categoryElement->appendChild($countElement);
        }
        
        // Add scatter plot data
        $scatterElement = $dom->createElement('scatter_plot');
        $root->appendChild($scatterElement);
        
        foreach ($scatterData as $dataPoint) {
            $pointElement = $dom->createElement('data_point');
            $scatterElement->appendChild($pointElement);
            
            $satelliteNameElement = $dom->createElement('satellite_name');
            $satelliteNameElement->appendChild($dom->createCDATASection($dataPoint['name']));
            $pointElement->appendChild($satelliteNameElement);
            
            $yearElement = $dom->createElement('launch_year', $dataPoint['year']);
            $pointElement->appendChild($yearElement);
            
            $speedElement = $dom->createElement('speed', $dataPoint['speed']);
            $pointElement->appendChild($speedElement);
            
            $catElement = $dom->createElement('category');
            $catElement->appendChild($dom->createCDATASection($dataPoint['category']));
            $pointElement->appendChild($catElement);
        }
        
        // Output as XML
        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="satellite_statistics_' . date('Y-m-d') . '.xml"');
        echo $dom->saveXML();
        exit;
    }
    
    public function accessRole(): array
    {
        // Allow access to specific methods for all users
        $callingMethod = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? '';
        
        // Check the current URL path as a fallback
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        // These methods are available to all users
        if ($callingMethod === 'satelliteStatistics' || 
            strpos($currentPath, 'satelliteStatistics') !== false ||
            $callingMethod === 'exportSatelliteStatsJson' ||
            strpos($currentPath, 'exportSatelliteStatsJson') !== false ||
            $callingMethod === 'exportSatelliteStatsXml' ||
            strpos($currentPath, 'exportSatelliteStatsXml') !== false) {
            return [];
        }
        
        // Other reports remain restricted to administrators
        return ['Administrator'];
    }
} 