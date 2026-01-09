<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleTypeModel extends Model
{
    protected $table = 'vehicle_types';
    protected $primaryKey = 'id';
    protected $allowedFields = ['code', 'name', 'icon_class', 'color_code', 'is_active'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
}
