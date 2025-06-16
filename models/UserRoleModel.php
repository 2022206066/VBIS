<?php

namespace app\models;

use app\core\BaseModel;

class UserRoleModel extends BaseModel
{
    public $id;
    public $id_user;
    public $id_role;

    public function tableName(): string
    {
        return 'user_roles';
    }

    public function readColumns()
    {
        return ['id', 'id_user', 'id_role'];
    }

    public function editColumns()
    {
        return ['id_user', 'id_role'];
    }

    public function validationRules()
    {
        return [
            "id_user" => [self::RULE_REQUIRED],
            "id_role" => [self::RULE_REQUIRED]
        ];
    }
} 