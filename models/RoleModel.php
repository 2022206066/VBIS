<?php

namespace app\models;

use app\core\BaseModel;

class RoleModel extends BaseModel
{
    public $id;
    public $name;

    public function tableName(): string
    {
        return 'roles';
    }

    public function readColumns()
    {
        return ['id', 'name'];
    }

    public function editColumns()
    {
        return ['name'];
    }

    public function validationRules()
    {
        return [
            "name" => [self::RULE_REQUIRED]
        ];
    }
} 