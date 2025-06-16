<?php

namespace app\models;

use app\core\BaseModel;

class RegistrationModel extends BaseModel
{
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $confirmPassword;

    public function tableName(): string
    {
        return 'users';
    }

    public function readColumns()
    {
        return ['id', 'first_name', 'last_name', 'email', 'password'];
    }

    public function editColumns()
    {
        return ['first_name', 'last_name', 'email', 'password'];
    }

    public function validationRules()
    {
        return [
            "first_name" => [self::RULE_REQUIRED],
            "last_name" => [self::RULE_REQUIRED],
            "email" => [self::RULE_REQUIRED, self::RULE_EMAIL, self::RULE_UNIQUE_EMAIL],
            "password" => [self::RULE_REQUIRED]
        ];
    }

    public function validate()
    {
        parent::validate();

        if (!$this->first_name) {
            $this->errors['first_name'] = 'First name is required';
        }

        if (!$this->last_name) {
            $this->errors['last_name'] = 'Last name is required';
        }

        if (!$this->email) {
            $this->errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email is not valid';
        }

        if (!$this->password) {
            $this->errors['password'] = 'Password is required';
        } elseif (strlen($this->password) < 8) {
            $this->errors['password'] = 'Password must be at least 8 characters';
        }

        if ($this->password !== $this->confirmPassword) {
            $this->errors['confirmPassword'] = 'Passwords must match';
        }
    }
} 