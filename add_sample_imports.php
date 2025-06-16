<?php

require_once __DIR__ . '/vendor/autoload.php';

use app\core\Application;
use app\models\ImportedFileModel;

// Initialize the application to get database connection
$app = new Application(__DIR__);

// Turn on verbose error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the table exists
$tableCheck = $app->db->query("SHOW TABLES LIKE 'imported_files'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "Table 'imported_files' exists.<br>";
} else {
    echo "Table 'imported_files' does not exist. Creating...<br>";
    if (ImportedFileModel::createTable()) {
        echo "Table created successfully.<br>";
    } else {
        echo "Failed to create table.<br>";
        exit;
    }
}

// Check if users table exists and has admin user
$usersCheck = $app->db->query("SHOW TABLES LIKE 'users'");
if ($usersCheck && $usersCheck->num_rows > 0) {
    // First check the columns of the users table
    $columnsCheck = $app->db->query("DESCRIBE users");
    $columns = [];
    if ($columnsCheck) {
        while ($column = $columnsCheck->fetch_assoc()) {
            $columns[$column['Field']] = $column;
        }
    }
    
    echo "Users table columns: " . implode(", ", array_keys($columns)) . "<br>";
    
    // Check for at least one user
    $userCountCheck = $app->db->query("SELECT COUNT(*) as count FROM users");
    $userCount = 0;
    if ($userCountCheck && $userCountCheck->num_rows > 0) {
        $countRow = $userCountCheck->fetch_assoc();
        $userCount = $countRow['count'];
        echo "Found $userCount users in the database.<br>";
    }
    
    if ($userCount > 0) {
        // Get the first user's ID to use for imports
        $firstUserCheck = $app->db->query("SELECT id FROM users LIMIT 1");
        if ($firstUserCheck && $firstUserCheck->num_rows > 0) {
            $firstUser = $firstUserCheck->fetch_assoc();
            $userId = $firstUser['id'];
            echo "Will use user ID $userId for imports.<br>";
        } else {
            echo "Error retrieving user ID.<br>";
            exit;
        }
    } else {
        // Create a user if needed
        echo "No users found. Creating a test user...<br>";
        
        // Check if the users table has email and password fields
        $hasEmail = isset($columns['email']);
        $hasPassword = isset($columns['password']);
        $hasUsername = isset($columns['username']);
        $hasRoleId = isset($columns['role_id']);
        
        // Build the INSERT statement based on available fields
        $fields = ['id']; // ID is probably always there
        $values = [1];
        
        if ($hasEmail) {
            $fields[] = 'email';
            $values[] = "'admin@example.com'";
        }
        
        if ($hasPassword) {
            $fields[] = 'password';
            $values[] = "'" . password_hash('admin', PASSWORD_DEFAULT) . "'";
        }
        
        if ($hasUsername) {
            $fields[] = 'username';
            $values[] = "'admin'";
        }
        
        if ($hasRoleId) {
            $fields[] = 'role_id';
            $values[] = 1;
        }
        
        // Add created_at if it exists
        if (isset($columns['created_at'])) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        
        $createUserSql = "INSERT INTO users (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
        echo "SQL: $createUserSql<br>";
        
        $createUserResult = $app->db->query($createUserSql);
        if ($createUserResult) {
            $userId = $app->db->getConnection()->insert_id ?: 1;
            echo "Created test user with ID: $userId<br>";
        } else {
            echo "Failed to create test user: " . $app->db->getConnection()->error . "<br>";
            exit;
        }
    }
} else {
    echo "WARNING: users table does not exist. Cannot add import records without users.<br>";
    exit;
}

// Check if there's already data in the table
$dataCheck = $app->db->query("SELECT COUNT(*) as count FROM imported_files");
if ($dataCheck && $dataCheck->num_rows > 0) {
    $row = $dataCheck->fetch_assoc();
    echo "Table has {$row['count']} records.<br>";
    
    if ($row['count'] > 0) {
        echo "Some data already exists in the table.<br>";
        
        // Check if data is in the future
        $futureCheck = $app->db->query("SELECT COUNT(*) as future_count FROM imported_files WHERE upload_date > NOW()");
        if ($futureCheck && $futureCheck->num_rows > 0) {
            $futureRow = $futureCheck->fetch_assoc();
            if ($futureRow['future_count'] > 0) {
                echo "WARNING: {$futureRow['future_count']} records have future dates. This might cause issues with date filtering.<br>";
                
                // Option to delete future records
                if (isset($_GET['delete_future']) && $_GET['delete_future'] == 1) {
                    $deleteResult = $app->db->query("DELETE FROM imported_files WHERE upload_date > NOW()");
                    if ($deleteResult) {
                        echo "Deleted future records successfully.<br>";
                    } else {
                        echo "Failed to delete future records: " . $app->db->getConnection()->error . "<br>";
                    }
                } else {
                    echo "<a href='?delete_future=1'>Click here to delete future records</a><br>";
                }
            }
        }
    }
}

// Add records directly with SQL for more reliable debugging
if (isset($_GET['add_sample']) && $_GET['add_sample'] == 1) {
    // Generate sample data for the past year with more recent entries
    $today = new \DateTime();
    
    $createdCount = 0;
    $dates = [];
    $errors = [];
    
    // Create 10 sample imports with more recent dates
    for ($i = 0; $i < 10; $i++) {
        try {
            // Generate a random date, weighted towards more recent dates
            $daysAgo = floor(pow(mt_rand(0, 100) / 100, 2) * 180); // Past 6 months, weighted recent
            $date = clone $today;
            $date->modify("-$daysAgo days");
            
            // Random time of day
            $hour = mt_rand(0, 23);
            $minute = mt_rand(0, 59);
            $second = mt_rand(0, 59);
            $date->setTime($hour, $minute, $second);
            
            $dateStr = $date->format('Y-m-d H:i:s');
            
            // Random satellite count between 10 and 1000
            $satelliteCount = mt_rand(10, 1000);
            $filename = "sample_import_$i.txt";
            
            // Add entry to database using direct SQL
            $sql = "INSERT INTO imported_files (filename, uploaded_by, upload_date, satellite_count) 
                    VALUES ('$filename', $userId, '$dateStr', $satelliteCount)";
            
            $result = $app->db->query($sql);
            if ($result) {
                $createdCount++;
                $dates[] = $dateStr;
            } else {
                $errors[] = "Error inserting record $i: " . $app->db->getConnection()->error;
            }
        } catch (\Exception $e) {
            $errors[] = "Exception on record $i: " . $e->getMessage();
        }
    }
    
    echo "Created $createdCount sample import records with current date range.<br>";
    
    if (!empty($errors)) {
        echo "Errors encountered:<br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
    
    if (!empty($dates)) {
        echo "Sample dates: <pre>" . print_r($dates, true) . "</pre>";
    }
} else {
    echo "<br><a href='?add_sample=1'>Add sample import data with current dates</a><br>";
}

echo "<br><a href='/VBIS-main/public/importStatistics'>View import statistics</a>"; 