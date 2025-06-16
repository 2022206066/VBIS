<?php

namespace app\core;

use mysqli;
use Exception;

class DbConnection
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "satellite_tracker";
    private static $connection = null;
    private static $instance = null;
    
    public function connect()
    {
        try {
            // If connection already exists and is valid, return it
            if (self::$connection !== null) {
                if (self::$connection->ping()) {
                    return self::$connection;
                }
                // If ping fails, close the connection and create a new one
                self::$connection->close();
                self::$connection = null;
            }
            
            // Create a new connection - using p: prefix for persistent connections can cause socket issues
            // So we'll avoid the persistent connection prefix
            self::$connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            // Check connection
            if (self::$connection->connect_errno) {
                $errorMsg = "Failed to connect to MySQL: " . self::$connection->connect_error;
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            // Set charset to ensure proper character encoding
            self::$connection->set_charset("utf8mb4");
            
            return self::$connection;
        } catch (Exception $e) {
            $errorMsg = "Database connection error: " . $e->getMessage();
            error_log($errorMsg);
            die($errorMsg);
        }
    }
    
    // Explicitly close the connection when needed
    public function close()
    {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
    
    // Singleton pattern to get instance
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}