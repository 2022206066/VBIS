<?php
/**
 * Import Sample Data
 * 
 * This script inserts sample import data into the database for testing the import statistics feature.
 * It will create realistic satellite import data for the past year.
 */

// Set up database connection directly
$dbHost = 'localhost';
$dbName = 'satellite_tracker';  // Updated to match core database name
$dbUser = 'root';  // Change to your actual database user
$dbPass = '';      // Change to your actual database password

// Connect to database
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error . "\n");
}

echo "Connected to database successfully.\n";

// Check if the imported_files table exists
$tableExists = false;
$result = $mysqli->query("SHOW TABLES LIKE 'imported_files'");
if ($result && $result->num_rows > 0) {
    $tableExists = true;
    echo "Table 'imported_files' already exists.\n";
} else {
    // Create the table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `imported_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename` varchar(255) NOT NULL,
        `uploaded_by` int(11) NOT NULL,
        `upload_date` datetime NOT NULL,
        `satellite_count` int(11) NOT NULL DEFAULT 0,
        `hash` varchar(64) DEFAULT NULL,
        `file_size` int(11) DEFAULT NULL,
        `status` enum('pending','processed','error') NOT NULL DEFAULT 'pending',
        `notes` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `uploaded_by` (`uploaded_by`),
        KEY `upload_date` (`upload_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($mysqli->query($createTableSQL)) {
        $tableExists = true;
        echo "Table 'imported_files' created successfully.\n";
    } else {
        die("Failed to create table: " . $mysqli->error . "\n");
    }
}

// Check if users table exists and has records
$usersExist = false;
$userIds = [];

$userResult = $mysqli->query("SHOW TABLES LIKE 'users'");
if ($userResult && $userResult->num_rows > 0) {
    $userCountResult = $mysqli->query("SELECT id FROM users LIMIT 5");
    if ($userCountResult && $userCountResult->num_rows > 0) {
        while ($row = $userCountResult->fetch_assoc()) {
            $userIds[] = $row['id'];
        }
        $usersExist = true;
        echo "Found " . count($userIds) . " users in the database.\n";
    }
}

// If no users found, create a default user ID
if (empty($userIds)) {
    $userIds = [1, 2, 3]; // Default user IDs
    echo "No users found in database. Using default user IDs: " . implode(", ", $userIds) . "\n";
}

// Check if there are already records in the table
$countResult = $mysqli->query("SELECT COUNT(*) as count FROM imported_files");
$recordCount = 0;
if ($countResult && $countResult->num_rows > 0) {
    $row = $countResult->fetch_assoc();
    $recordCount = $row['count'];
    echo "Found {$recordCount} existing records in the imported_files table.\n";
}

// Ask for confirmation before proceeding if records exist
if ($recordCount > 0) {
    echo "Do you want to add more sample data? This will not delete existing records. (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (strtolower(trim($line)) != 'y') {
        echo "Operation canceled.\n";
        exit;
    }
}

// Generate sample data for the past year
$today = new DateTime();
$oneYearAgo = (new DateTime())->modify('-365 days');

// Function to generate a random date within a range
function randomDate($start, $end) {
    $startTimestamp = $start->getTimestamp();
    $endTimestamp = $end->getTimestamp();
    $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
    $date = new DateTime();
    $date->setTimestamp($randomTimestamp);
    return $date;
}

// Function to generate satellite counts following a realistic distribution
function generateSatelliteCount() {
    // Most imports have a low count, some have medium, few have high
    $rand = mt_rand(1, 100);
    if ($rand <= 70) {
        // 70% chance of 1-10 satellites
        return mt_rand(1, 10);
    } else if ($rand <= 95) {
        // 25% chance of 11-50 satellites
        return mt_rand(11, 50);
    } else {
        // 5% chance of 51-200 satellites
        return mt_rand(51, 200);
    }
}

// Generate import data with realistic patterns
$importCount = 0;
$insertedRecords = 0;
$targetCount = 100; // Number of imports to generate

// Create samples with realistic frequency
// 1. More recent dates have more imports
// 2. Some days have multiple imports, many have none
// 3. Occasional "busy days" with many imports

$currentDate = clone $oneYearAgo;
$insertSQL = "INSERT INTO imported_files (filename, uploaded_by, upload_date, satellite_count, hash, file_size, status) VALUES ";
$values = [];

while ($currentDate <= $today && $importCount < $targetCount) {
    // Determine if this day should have imports
    // Higher probability for more recent dates
    $daysAgo = $today->diff($currentDate)->days;
    $dateChance = 90 - min(80, $daysAgo / 4); // 10-90% chance depending on how recent
    
    if (mt_rand(1, 100) <= $dateChance) {
        // Determine how many imports on this day (usually 1-2, occasionally more)
        $dayImportCount = 1;
        $extraImportChance = mt_rand(1, 100);
        
        if ($extraImportChance > 80) {
            $dayImportCount = mt_rand(2, 3); // 20% chance of 2-3 imports
        }
        if ($extraImportChance > 95) {
            $dayImportCount = mt_rand(4, 8); // 5% chance of 4-8 imports (busy day)
        }
        
        // Create imports for this day
        for ($i = 0; $i < $dayImportCount && $importCount < $targetCount; $i++) {
            $userId = $userIds[array_rand($userIds)];
            $satelliteCount = generateSatelliteCount();
            $filename = "satellite_import_" . $currentDate->format('Ymd') . "_" . ($i + 1) . ".txt";
            $hash = md5($filename . $userId . $currentDate->format('YmdHis'));
            $fileSize = mt_rand(5000, 500000); // Random file size between 5KB and 500KB
            
            // Add some randomness to the time of day
            $hour = mt_rand(8, 17); // Between 8 AM and 5 PM
            $minute = mt_rand(0, 59);
            $second = mt_rand(0, 59);
            $currentDate->setTime($hour, $minute, $second);
            
            // Add to values array for batch insert
            $values[] = "(
                '" . $mysqli->real_escape_string($filename) . "',
                {$userId},
                '" . $currentDate->format('Y-m-d H:i:s') . "',
                {$satelliteCount},
                '" . $mysqli->real_escape_string($hash) . "',
                {$fileSize},
                'processed'
            )";
            
            $importCount++;
        }
    }
    
    // Move to the next day
    $currentDate->modify('+1 day');
    $currentDate->setTime(0, 0, 0);
    
    // If we've collected 20 records or reached the end, insert them
    if (count($values) >= 20 || ($currentDate > $today || $importCount >= $targetCount)) {
        if (!empty($values)) {
            $sql = $insertSQL . implode(",", $values) . ";";
            if ($mysqli->query($sql)) {
                $insertedRecords += count($values);
                echo "Inserted " . count($values) . " records. Total: {$insertedRecords}\n";
            } else {
                echo "Error inserting records: " . $mysqli->error . "\n";
            }
            $values = []; // Reset for next batch
        }
    }
}

// Summary
echo "\nImport sample data generation complete.\n";
echo "Generated {$importCount} sample imports over the past year.\n";
echo "Successfully inserted {$insertedRecords} records.\n";
echo "You can now check the import statistics page to see your data.\n";

// Close the database connection
$mysqli->close(); 