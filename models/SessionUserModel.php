<?php

namespace app\models;

use app\core\BaseModel;

class SessionUserModel extends BaseModel
{
    public string $email = '';
    public array $roles = [];

    public function getSessionData()
    {
        error_log("SessionUserModel::getSessionData() called for email: " . $this->email);
        
        $query = "
            SELECT u.id, u.first_name, u.last_name, u.email, r.name as role
            FROM users u
            INNER JOIN user_roles ur on u.id = ur.id_user
            INNER JOIN roles r on ur.id_role = r.id
            WHERE u.email = ?
        ";
        
        // Use prepared statement for safety
        $stmt = $this->con->prepare($query);
        if (!$stmt) {
            error_log("Database prepare error: " . $this->con->error);
            return [];
        }
        
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if (!$result) {
            error_log("Database result error: " . $stmt->error);
            return [];
        }
        
        $resultArray = [];
        while ($row = $result->fetch_assoc()) {
            $resultArray[] = $row;
        }
        
        error_log("SessionUserModel found " . count($resultArray) . " rows for email: " . $this->email);
        if (count($resultArray) == 0) {
            // Do some debugging
            error_log("No rows found, checking if user exists...");
            $checkUserQuery = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $this->con->prepare($checkUserQuery);
            $checkStmt->bind_param("s", $this->email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $userId = $checkResult->fetch_assoc()['id'];
                error_log("User exists with ID: $userId, checking for roles...");
                
                $checkRolesQuery = "SELECT r.name FROM roles r INNER JOIN user_roles ur ON r.id = ur.id_role WHERE ur.id_user = ?";
                $rolesStmt = $this->con->prepare($checkRolesQuery);
                $rolesStmt->bind_param("i", $userId);
                $rolesStmt->execute();
                $rolesResult = $rolesStmt->get_result();
                
                if ($rolesResult->num_rows > 0) {
                    error_log("User has roles, but join query failed. Roles found:");
                    while ($role = $rolesResult->fetch_assoc()) {
                        error_log("- " . $role['name']);
                    }
                } else {
                    error_log("User has no roles assigned");
                }
            } else {
                error_log("User does not exist with email: " . $this->email);
            }
        }

        return $resultArray;
    }

    public function tableName()
    {
        return 'users';
    }

    public function readColumns()
    {
        return ['id', 'first_name', 'last_name', 'email'];
    }

    public function editColumns()
    {
        return ['email'];
    }

    public function validationRules()
    {
        return [
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL]
        ];
    }
} 