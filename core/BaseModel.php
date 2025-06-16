<?php

namespace app\core;

use mysqli;

abstract class BaseModel
{
    public const RULE_EMAIL = "rule_email";
    public const RULE_REQUIRED = "rule_required";
    public const RULE_UNIQUE_EMAIL = "rule_unique_email";
    public const GREATER_THEN_ZERO = "greater_then_zero";

    public $errors;

    private DbConnection $db;
    public mysqli $con;

    public function __construct()
    {
        $this->db = new DbConnection();
        $this->con = $this->db->connect();
    }

    abstract public function tableName();

    abstract public function readColumns();

    abstract public function editColumns();

    abstract public function validationRules();

    public function one($where)
    {
        $tableName = $this->tableName();
        $columns = $this->readColumns();

        $query = "select " . implode(',', $columns) . " from  $tableName $where limit 1";

        $dbResult = $this->con->query($query);
        $result = $dbResult->fetch_assoc();

        if ($result != null) {
            $this->mapData($result);
        }
    }

    public function all($where): array
    {
        $tableName = $this->tableName();
        $columns = $this->readColumns();

        $query = "select " . implode(',', $columns) . " from  $tableName $where";

        $dbResult = $this->con->query($query);

        $resultArray = [];

        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }

        return $resultArray;
    }

    public function update($where)
    {
        $tableName = $this->tableName();
        $columns = $this->editColumns();
        
        // Handle both array formats for columns
        $columnNames = [];
        if (isset($columns[0]) && is_string($columns[0])) {
            // Simple array of column names
            $columnNames = $columns;
        } else {
            // Key-value array
            $columnNames = array_keys($columns);
        }
        
        $columnsHelper = array_map(fn($attr) => ":$attr", $columnNames);

        $commonHelper = [];

        for ($i = 0; $i < count($columnsHelper); $i++) {
            $commonHelper[] = "$columnNames[$i] = $columnsHelper[$i]";
        }

        $query = "update $tableName set  " . implode(',', $commonHelper) . " $where";

        foreach ($columnNames as $attribute) {
            $query = str_replace(":$attribute", is_string($this->{$attribute}) ? '"' . $this->{$attribute} . '"' : $this->{$attribute}, $query);
        }

        $this->con->query($query);
    }

    public function insert()
    {
        $tableName = $this->tableName();
        $columns = $this->editColumns();
        
        // Handle both array formats for columns
        $columnNames = [];
        if (isset($columns[0]) && is_string($columns[0])) {
            // Simple array of column names
            $columnNames = $columns;
        } else {
            // Key-value array
            $columnNames = array_keys($columns);
        }
        
        $columnsHelper = array_map(fn($attr) => ":$attr", $columnNames);

        $query = "insert into $tableName (" . implode(",", $columnNames) . ") values (" . implode(",", $columnsHelper) . ")";

        foreach ($columnNames as $attribute) {
            $query = str_replace(":$attribute", is_string($this->{$attribute}) ? '"' . $this->{$attribute} . '"' : $this->{$attribute}, $query);
        }

        $this->con->query($query);
    }

    public function mapData($data)
    {
        if ($data != null) {
            foreach ($data as $key => $value) {
                try {
                    // Step 1: Try direct property access first
                    if (property_exists($this, $key)) {
                        $this->{$key} = $value;
                        continue;
                    }
                    
                    // Step 2: If direct access fails, try with sanitized key
                    $sanitizedKey = str_replace(' ', '_', $key);
                    $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $sanitizedKey);
                    
                    if (property_exists($this, $sanitizedKey)) {
                        $this->{$sanitizedKey} = $value;
                        continue;
                    }
                    
                    // Step 3: Look for special properties that might need specific handling
                    // For SatelliteModel, map properties like "VANGUARD 1" to "name"
                    if (get_class($this) === 'app\models\SatelliteModel') {
                        if (!is_numeric($key) && !in_array($key, ['id', 'line1', 'line2', 'category', 'added_by'])) {
                            $this->name = $key;
                            error_log("Mapped property '$key' to 'name' in SatelliteModel");
                        }
                    }
                } catch (\Error $e) {
                    // Log with detailed context for easier debugging
                    error_log("Error in " . get_class($this) . "::mapData - Key: '$key', Value: '$value', Error: " . $e->getMessage());
                }
            }
        }
    }

    public function validate()
    {
        $allRules = $this->validationRules();

        foreach ($allRules as $attribute => $rules) {
            $value = $this->{$attribute};

            foreach ($rules as $rule) {
                if ($rule == self::RULE_REQUIRED) {
                    if (!$value) {
                        $this->errors[$attribute][] = "This field is required";
                    }
                }

                if ($rule == self::RULE_EMAIL) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$attribute][] = "Email must be in Email format";
                    }
                }

                if ($rule == self::RULE_UNIQUE_EMAIL) {
                    if ($this->checkUniqueEmail($value)) {
                        $this->errors[$attribute][] = "This Email already exists";
                    }
                }

                if ($rule == self::GREATER_THEN_ZERO) {
                    if ($value <= 0) {
                        $this->errors[$attribute][] = "This field must be greater then 0";
                    }
                }
            }
        }
    }

    public function checkUniqueEmail($email)
    {
        $query = "select email from users where email = '$email'";

        $dbResult = $this->con->query($query);
        $result = $dbResult->fetch_assoc();

        if ($result != null) {
            return true;
        }

        return false;
    }
} 