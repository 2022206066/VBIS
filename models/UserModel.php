<?php

namespace app\models;

use app\core\BaseModel;

class UserModel extends BaseModel
{
    public int $id;
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public ?int $role_id = null;
    public array $roles = [];
    public string $current_password = '';
    public string $new_password = '';
    public string $confirm_password = '';
    
    public function tableName()
    {
        return 'users';
    }

    public function readColumns()
    {
        return ['id', 'first_name', 'last_name', 'email', 'password', 'role_id'];
    }

    public function editColumns()
    {
        return ['first_name', 'last_name', 'email', 'password', 'role_id'];
    }

    public function validationRules()
    {
        return [
            "first_name" => [self::RULE_REQUIRED],
            "last_name" => [self::RULE_REQUIRED],
            "email" => [self::RULE_REQUIRED, self::RULE_EMAIL]
        ];
    }
    
    public function getUserById($id)
    {
        return $this->one("WHERE id = {$id}");
    }
    
    public function getUserWithRoles($id)
    {
        $this->getUserById($id);
        
        // Get user roles
        $query = "SELECT r.id, r.name FROM roles r 
                 INNER JOIN user_roles ur ON r.id = ur.id_role 
                 WHERE ur.id_user = {$id}";
        
        $result = $this->con->query($query);
        
        $this->roles = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $this->roles[] = $row;
            }
        }
        
        return $this;
    }
    
    public function updateUser($data)
    {
        $this->mapData($data);
        $this->validate();
        
        if ($this->errors) {
            return false;
        }
        
        // If password update is requested
        if (!empty($this->new_password)) {
            // Verify current password
            if (!password_verify($this->current_password, $this->password)) {
                $this->errors['current_password'] = 'Current password is incorrect';
                return false;
            }
            
            // Verify new password and confirmation match
            if ($this->new_password !== $this->confirm_password) {
                $this->errors['confirm_password'] = 'Password confirmation does not match';
                return false;
            }
            
            // Hash new password
            $this->password = password_hash($this->new_password, PASSWORD_DEFAULT);
        }
        
        // Update user with WHERE clause
        $where = "WHERE id = {$this->id}";
        $this->update($where);
        return true;
    }
    
    public function deleteUser($id)
    {
        try {
            // Delete user roles
            $query = "DELETE FROM user_roles WHERE id_user = {$id}";
            $this->con->query($query);
            
            // Delete user
            $query = "DELETE FROM users WHERE id = {$id}";
            $result = $this->con->query($query);
            
            if (!$result) {
                throw new \Exception($this->con->error);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Re-throw the exception to be handled by the controller
            throw $e;
        }
    }
    
    public function getAllUsers()
    {
        // Get database connection
        $dbObj = new \app\core\Database();
        $conn = $dbObj->getConnection();
        
        // Use prepared statement for security
        $query = "SELECT id, first_name, last_name, email FROM users ORDER BY id";
        $result = $conn->query($query);
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Get user roles using prepared statement
                $stmt = $conn->prepare("SELECT r.id, r.name FROM roles r 
                                      INNER JOIN user_roles ur ON r.id = ur.id_role 
                                      WHERE ur.id_user = ?");
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $rolesResult = $stmt->get_result();
                $roles = [];
                
                if ($rolesResult && $rolesResult->num_rows > 0) {
                    while ($roleRow = $rolesResult->fetch_assoc()) {
                        $roles[] = $roleRow['name'];
                    }
                }
                
                $row['roles'] = $roles;
                $users[] = $row;
            }
        }
        
        return $users;
    }
} 