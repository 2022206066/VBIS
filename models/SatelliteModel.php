<?php

namespace app\models;

use app\core\BaseModel;

class SatelliteModel extends BaseModel
{
    public int $id;
    public string $name = '';
    public string $line1 = '';
    public string $line2 = '';
    public string $category = '';
    public int $added_by;

    // Magic method to handle dynamic property assignment for properties with spaces
    public function __set($name, $value)
    {
        // Handle special cases for SatelliteModel properties
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return;
        }
        
        // If the property name contains spaces or special characters,
        // it might be a satellite name being incorrectly used as a property
        if (preg_match('/[\s\(\)\d]/', $name)) {
            // Log the problem for debugging
            error_log("Attempted to set property with special characters: '$name' = '$value'");
            
            // In the context of TLE import, if a property with spaces is being set,
            // it's likely a satellite name - we'll map it to the name property
            if (!in_array($name, ['line1', 'line2', 'category', 'added_by'])) {
                $this->name = $name;
                error_log("Mapped property '$name' to 'name' in SatelliteModel::__set");
            }
            return;
        }
        
        // For any other property, try using a sanitized version
        $sanitizedName = str_replace(' ', '_', $name);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '', $sanitizedName);
        
        if (property_exists($this, $sanitizedName)) {
            $this->$sanitizedName = $value;
            return;
        }
        
        // If we reached here, it's a truly unknown property
        error_log("Warning: Attempted to set undefined property '$name' on SatelliteModel");
    }

    public function tableName()
    {
        return 'satellites';
    }

    public function readColumns()
    {
        return ['id', 'name', 'line1', 'line2', 'category', 'added_by'];
    }

    public function editColumns()
    {
        return ['name', 'line1', 'line2', 'category', 'added_by'];
    }

    public function validationRules()
    {
        return [
            "name" => [self::RULE_REQUIRED],
            "line1" => [self::RULE_REQUIRED],
            "line2" => [self::RULE_REQUIRED],
            "added_by" => [self::RULE_REQUIRED]
        ];
    }
    
    public function getSatellitesWithCategories()
    {
        $query = "SELECT * FROM satellites ORDER BY category, name";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }
        
        return $resultArray;
    }
    
    public function getSatellitesByCategory($category)
    {
        $query = "SELECT * FROM satellites WHERE category = '$category' ORDER BY name";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }
        
        return $resultArray;
    }
    
    /**
     * Clean all satellites that don't have a category (type) specified in the allowed list
     * @param array $allowedCategories List of category names to keep
     * @return int Number of satellites removed
     */
    public function cleanupSatellitesByCategory(array $allowedCategories = [])
    {
        if (empty($allowedCategories)) {
            return 0;
        }
        
        // Convert to SQL-friendly format
        $categoriesStr = implode("','", array_map(function($cat) {
            return $this->con->real_escape_string($cat);
        }, $allowedCategories));
        
        // Find satellites to remove
        $query = "SELECT id FROM satellites WHERE category NOT IN ('$categoriesStr')";
        $result = $this->con->query($query);
        
        $count = 0;
        if ($result && $result->num_rows > 0) {
            // Delete each satellite
            while ($row = $result->fetch_assoc()) {
                $satelliteId = $row['id'];
                
                // Also delete any related position data
                $this->con->query("DELETE FROM observed_positions WHERE satellite_id = $satelliteId");
                
                // Delete the satellite
                $this->con->query("DELETE FROM satellites WHERE id = $satelliteId");
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Remove satellite type/category from UI if not in data
     * @param bool $removeFromUi Whether to remove the category field from UI
     * @return bool True if successful
     */
    public function removeCategoryFromUi($removeFromUi = false)
    {
        // This would typically be implemented by modifying the view files
        // Since we can't directly modify views here, we can store this as a setting in database
        
        try {
            // Check if settings table exists, create if not
            $this->con->query("
                CREATE TABLE IF NOT EXISTS settings (
                    `key` VARCHAR(255) PRIMARY KEY,
                    `value` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Store setting
            $value = $removeFromUi ? '1' : '0';
            $query = "INSERT INTO settings (`key`, `value`) VALUES ('hide_satellite_category', '$value')
                     ON DUPLICATE KEY UPDATE `value` = '$value'";
            
            return $this->con->query($query) !== false;
        } catch (\Exception $e) {
            error_log("Error setting satellite category visibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unique satellite categories from database
     * @return array List of category names
     */
    public function getSatelliteCategories()
    {
        $query = "SELECT DISTINCT category FROM satellites ORDER BY category";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result['category'];
        }
        
        return $resultArray;
    }
    
    public function getSatellitesAsJsArray()
    {
        $satellites = $this->all("ORDER BY name");
        
        // Generate TLE data in the format expected by the original tracker
        $tlesContent = "var tles = `";
        
        foreach ($satellites as $satellite) {
            $tlesContent .= $satellite['name'] . "\n";
            $tlesContent .= $satellite['line1'] . "\n";
            $tlesContent .= $satellite['line2'] . "\n";
        }
        
        $tlesContent .= "`;";
        
        // Write TLE data to a file for the original tracker to use
        file_put_contents(__DIR__ . '/../public/sattelite-tracker/res/tles.js', $tlesContent);
        
        return $tlesContent;
    }
    
    public function exportToJson()
    {
        $satellites = $this->all("ORDER BY name");
        return json_encode($satellites, JSON_PRETTY_PRINT);
    }
    
    public function exportToXml()
    {
        $satellites = $this->all("ORDER BY name");
        
        // Create DOM document instead of SimpleXML
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // Create root element
        $root = $dom->createElement('satellites');
        $dom->appendChild($root);
        
        foreach ($satellites as $satellite) {
            $satelliteNode = $dom->createElement('satellite');
            $root->appendChild($satelliteNode);
            
            // Only include required and important fields
            // Skip internal IDs to avoid confusion during import
            
            // Fields that might contain special characters - use CDATA
            $nameNode = $dom->createElement('name');
            $nameNode->appendChild($dom->createCDATASection($satellite['name']));
            $satelliteNode->appendChild($nameNode);
            
            $line1Node = $dom->createElement('line1');
            $line1Node->appendChild($dom->createCDATASection($satellite['line1']));
            $satelliteNode->appendChild($line1Node);
            
            $line2Node = $dom->createElement('line2');
            $line2Node->appendChild($dom->createCDATASection($satellite['line2']));
            $satelliteNode->appendChild($line2Node);
            
            $categoryNode = $dom->createElement('category');
            $categoryNode->appendChild($dom->createCDATASection($satellite['category']));
            $satelliteNode->appendChild($categoryNode);
        }
        
        return $dom->saveXML();
    }
    
    /**
     * Insert a new satellite or update if name already exists
     * @return bool|int The ID of the inserted/updated satellite or false on error
     */
    public function insertOrUpdate()
    {
        try {
            // Check if satellite with this name already exists
            $existingId = $this->findByName($this->name);
            
            if ($existingId) {
                // Update existing satellite
                error_log("Updating existing satellite: {$this->name} (ID: $existingId)");
                $this->id = $existingId;
                $this->update("WHERE id = {$existingId}");
                return $existingId;
            } else {
                // Insert new satellite
                error_log("Inserting new satellite: {$this->name}");
                $this->insert();
                
                // Get the ID of the newly inserted satellite
                $query = "SELECT id FROM satellites WHERE name = '" . $this->con->real_escape_string($this->name) . "' LIMIT 1";
                $result = $this->con->query($query);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    return $row['id'];
                }
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error in SatelliteModel::insertOrUpdate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find a satellite by name
     * @param string $name The satellite name to search for
     * @return int|null The ID if found, null otherwise
     */
    public function findByName($name)
    {
        $name = $this->con->real_escape_string($name);
        $query = "SELECT id FROM satellites WHERE name = '$name' LIMIT 1";
        $result = $this->con->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return null;
    }
    
    /**
     * Batch process satellites for improved performance with large imports
     * @param array $satellites Array of satellite data to process
     * @param string $defaultCategory Default category if not specified
     * @param int $userId User ID who is importing
     * @return array Results with counts
     */
    public function batchProcessSatellites($satellites, $defaultCategory, $userId)
    {
        // First, get all existing satellite names in a single query for efficient duplicate checking
        $query = "SELECT id, name FROM satellites";
        $result = $this->con->query($query);
        
        $existingSatellites = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existingSatellites[$row['name']] = $row['id'];
            }
        }
        
        $insertValues = [];
        $updateStatements = [];
        $insertCount = 0;
        $updateCount = 0;
        $categoriesAdded = [];
        
        // Process satellites
        foreach ($satellites as $satellite) {
            if (!isset($satellite['name']) || !isset($satellite['line1']) || !isset($satellite['line2'])) {
                continue;
            }
            
            $name = $this->con->real_escape_string(trim($satellite['name']));
            $line1 = $this->con->real_escape_string($satellite['line1']);
            $line2 = $this->con->real_escape_string($satellite['line2']);
            $category = $this->con->real_escape_string($satellite['category'] ?? $defaultCategory);
            
            // Track categories for summary
            if (!in_array($category, $categoriesAdded)) {
                $categoriesAdded[] = $category;
            }
            
            // Check if satellite exists
            if (isset($existingSatellites[$satellite['name']])) {
                $id = $existingSatellites[$satellite['name']];
                $updateStatements[] = "UPDATE satellites SET 
                    line1 = '$line1', 
                    line2 = '$line2', 
                    category = '$category' 
                    WHERE id = $id";
                $updateCount++;
            } else {
                $insertValues[] = "('$name', '$line1', '$line2', '$category', $userId)";
                $insertCount++;
            }
            
            // Execute in batches of 100 to avoid memory issues
            if (count($insertValues) >= 100) {
                $this->executeBatchInsert($insertValues);
                $insertValues = [];
            }
            
            if (count($updateStatements) >= 100) {
                $this->executeBatchUpdates($updateStatements);
                $updateStatements = [];
            }
        }
        
        // Process any remaining inserts
        if (!empty($insertValues)) {
            $this->executeBatchInsert($insertValues);
        }
        
        // Process any remaining updates
        if (!empty($updateStatements)) {
            $this->executeBatchUpdates($updateStatements);
        }
        
        // Generate TLEs file for satellite tracker
        $this->getSatellitesAsJsArray();
        
        return [
            'insertCount' => $insertCount,
            'updateCount' => $updateCount,
            'categoriesAdded' => $categoriesAdded
        ];
    }
    
    /**
     * Execute batch insert of satellites
     * @param array $insertValues Array of value strings for insert
     * @return void
     */
    private function executeBatchInsert($insertValues)
    {
        if (empty($insertValues)) {
            return;
        }
        
        $query = "INSERT INTO satellites (name, line1, line2, category, added_by) VALUES " . 
                implode(", ", $insertValues);
        
        $this->con->query($query);
    }
    
    /**
     * Execute batch updates of satellites
     * @param array $updateStatements Array of UPDATE statements
     * @return void
     */
    private function executeBatchUpdates($updateStatements)
    {
        if (empty($updateStatements)) {
            return;
        }
        
        foreach ($updateStatements as $statement) {
            $this->con->query($statement);
        }
    }
} 