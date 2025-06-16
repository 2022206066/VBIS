<?php

namespace app\models;

use app\core\Application;
use app\core\Database;

/**
 * Model to track satellite file imports
 */
class ImportedFileModel
{
    public $id;
    public $filename;
    public $uploaded_by;
    public $upload_date;
    public $satellite_count;
    
    private Database $db;
    
    public function __construct()
    {
        $this->db = Application::$app->db;
        $this->upload_date = date('Y-m-d H:i:s');
    }
    
    /**
     * Inserts a new imported file record into the database
     * @return bool True on success, false on failure
     */
    public function insert(): bool
    {
        try {
            $query = "INSERT INTO imported_files (filename, uploaded_by, upload_date, satellite_count) 
                      VALUES (?, ?, ?, ?)";
            
            $statement = $this->db->prepare($query);
            $statement->bind_param("sisi", 
                $this->filename, 
                $this->uploaded_by,
                $this->upload_date,
                $this->satellite_count
            );
            
            if ($statement->execute()) {
                $this->id = $this->db->insert_id;
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error inserting imported file record: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets all imported files with uploader details
     * @return array Array of imported file records
     */
    public static function getAll(): array
    {
        $db = Application::$app->db;
        $query = "SELECT f.*, u.username, u.email 
                  FROM imported_files f 
                  LEFT JOIN users u ON f.uploaded_by = u.id 
                  ORDER BY f.upload_date DESC";
        
        try {
            $result = $db->query($query);
            $files = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $files[] = $row;
                }
                $result->close();
            }
            
            return $files;
        } catch (\Exception $e) {
            error_log("Error fetching imported files: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gets import statistics aggregated by day
     * @param int $days Number of days to include in statistics (0 for all-time)
     * @return array Array of statistics
     */
    public function getImportStatistics(int $days = 365): array
    {
        try {
            error_log("=== Starting getImportStatistics() in ImportedFileModel ===");
            
            // Simplified direct query approach that works reliably
            // Get all records from the imported_files table
            $allRecordsQuery = "SELECT id, filename, uploaded_by, upload_date, satellite_count FROM imported_files";
            error_log("Executing query: $allRecordsQuery");
            
            $result = $this->db->query($allRecordsQuery);
            if (!$result) {
                error_log("Error executing query: " . $this->db->getConnection()->error);
                throw new \Exception("Database error: " . $this->db->getConnection()->error);
            }
            
            // Check if we have any data
            if ($result->num_rows === 0) {
                error_log("No records found in imported_files table, returning empty data");
                return [
                    'dailyStats' => [],
                    'calendarData' => [],
                    'summary' => [
                        'total_imports' => 0,
                        'total_satellites' => 0,
                        'avg_satellites_per_import' => 0,
                        'last_import_date' => null
                    ],
                    'topImporters' => [],
                    'satelliteData' => []
                ];
            }
            
            // Process all records and aggregate by date
            $dailyStats = [];
            $calendarData = [];
            $satelliteData = [];
            $dateData = [];
            $totalImports = 0;
            $totalSatellites = 0;
            $lastImportDate = null;
            
            // Process each record
            while ($row = $result->fetch_assoc()) {
                // Extract data
                $uploadDate = $row['upload_date'];
                $satelliteCount = (int)$row['satellite_count'];
                $uploadedBy = (int)$row['uploaded_by'];
                
                // Get date portion only
                $dateOnly = date('Y-m-d', strtotime($uploadDate));
                
                // Store raw date for sorting
                $dateTimestamp = strtotime($dateOnly);
                
                // Track total counts
                $totalImports++;
                $totalSatellites += $satelliteCount;
                
                // Track last import date
                if ($lastImportDate === null || strtotime($uploadDate) > strtotime($lastImportDate)) {
                    $lastImportDate = $uploadDate;
                }
                
                // Aggregate counts by date
                if (!isset($dateData[$dateOnly])) {
                    $dateData[$dateOnly] = [
                        'date' => $dateOnly,
                        'count' => 0,
                        'total_satellites' => 0,
                        'timestamp' => $dateTimestamp
                    ];
                    $calendarData[$dateOnly] = 0;
                    $satelliteData[$dateOnly] = 0;
                }
                
                $dateData[$dateOnly]['count']++;
                $dateData[$dateOnly]['total_satellites'] += $satelliteCount;
                $calendarData[$dateOnly]++;
                $satelliteData[$dateOnly] += $satelliteCount;
            }
            
            // Close the result set
            $result->close();
            
            // Convert date data to daily stats array
            foreach ($dateData as $date => $data) {
                $dailyStats[] = $data;
            }
            
            // Sort daily stats by date (most recent first)
            usort($dailyStats, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            // Calculate average satellites per import
            $avgSatellitesPerImport = $totalImports > 0 ? $totalSatellites / $totalImports : 0;
            
            // Get top importers
            $topImporters = [];
            $importersQuery = "SELECT 
                uploaded_by as user_id,
                COUNT(*) as import_count,
                SUM(satellite_count) as total_satellites
                FROM imported_files
                GROUP BY uploaded_by
                ORDER BY import_count DESC
                LIMIT 5";
                
            $result = $this->db->query($importersQuery);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['import_count'] = (int)$row['import_count'];
                    $row['total_satellites'] = (int)$row['total_satellites'];
                    $topImporters[] = $row;
                }
                $result->close();
            }
            
            // Create the summary data
            $summary = [
                'total_imports' => $totalImports,
                'total_satellites' => $totalSatellites,
                'avg_satellites_per_import' => $avgSatellitesPerImport,
                'last_import_date' => $lastImportDate
            ];
            
            // Create the final data structure
            $returnData = [
                'dailyStats' => $dailyStats,
                'calendarData' => $calendarData,
                'summary' => $summary,
                'topImporters' => $topImporters,
                'satelliteData' => $satelliteData
            ];
            
            // Debug info
            error_log("Final data counts: dailyStats=" . count($dailyStats) . 
                      ", calendarData=" . count($calendarData) . 
                      ", satelliteData=" . count($satelliteData));
            
            return $returnData;
        } catch (\Exception $e) {
            error_log("Error in getImportStatistics: " . $e->getMessage());
            return [
                'dailyStats' => [],
                'calendarData' => [],
                'summary' => [
                    'total_imports' => 0,
                    'total_satellites' => 0,
                    'avg_satellites_per_import' => 0,
                    'last_import_date' => null
                ],
                'topImporters' => [],
                'satelliteData' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Creates the imported_files table if it doesn't exist
     * @return bool True on success, false on failure
     */
    public static function createTable(): bool
    {
        $db = Application::$app->db;
        
        $query = "CREATE TABLE IF NOT EXISTS imported_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            uploaded_by INT NOT NULL,
            upload_date DATETIME NOT NULL,
            satellite_count INT NOT NULL DEFAULT 0,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            return $db->query($query) ? true : false;
        } catch (\Exception $e) {
            error_log("Error creating imported_files table: " . $e->getMessage());
            return false;
        }
    }
} 