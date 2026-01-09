<?php

namespace App\Models;

use CodeIgniter\Model;

class ViolationTypeModel extends Model
{
    protected $table = 'violation_types';
    protected $primaryKey = 'id';
    protected $allowedFields = ['code', 'name', 'description', 'fine_amount', 'penalty_points', 'severity_level', 'is_active'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
}
