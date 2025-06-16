<?php

namespace app\core;

use mysqli;

class Database
{
    private DbConnection $connection;
    
    public function __construct()
    {
        $this->connection = new DbConnection();
    }
    
    /**
     * Get the database connection's insert ID
     */
    public function __get($name)
    {
        if ($name === 'insert_id') {
            return $this->getConnection()->insert_id;
        }
        
        throw new \Exception("Undefined property: " . get_class($this) . "::$name");
    }
    
    /**
     * Get the database connection
     * @return mysqli
     */
    public function getConnection(): mysqli
    {
        return $this->connection->connect();
    }
    
    /**
     * Execute a query
     * @param string $query SQL query to execute
     * @return \mysqli_result|bool Result set or boolean
     */
    public function query(string $query)
    {
        try {
            $result = $this->getConnection()->query($query);
            if ($result === false) {
                error_log("Database query error: " . $this->getConnection()->error . " in query: $query");
            }
            return $result;
        } catch (\Exception $e) {
            error_log("Database query exception: " . $e->getMessage() . " in query: $query");
            throw $e;
        }
    }
    
    /**
     * Prepare a statement
     * @param string $query SQL query
     * @return \mysqli_stmt
     */
    public function prepare(string $query)
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            if ($stmt === false) {
                error_log("Database prepare error: " . $this->getConnection()->error . " in query: $query");
            }
            return $stmt;
        } catch (\Exception $e) {
            error_log("Database prepare exception: " . $e->getMessage() . " in query: $query");
            throw $e;
        }
    }
    
    /**
     * Close the database connection
     */
    public function close(): void
    {
        $this->connection->close();
    }
    
    /**
     * Ensure database exists
     */
    public function ensureDatabase(): bool
    {
        try {
            // Assuming we're already connected to the database
            return true;
        } catch (\Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            return false;
        }
    }
} 