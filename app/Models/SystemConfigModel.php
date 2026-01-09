<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemConfigModel extends Model
{
    protected $table = 'system_configurations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['config_key', 'config_value', 'data_type', 'description', 'category', 'is_public', 'is_encrypted', 'updated_by'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
}
