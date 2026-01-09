<?php

namespace App\Models;

use CodeIgniter\Model;

class ViolationModel extends Model
{
    protected $table = 'violations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'violation_type_id', 'violation_number', 'location_address', 'latitude', 'longitude', 'violation_datetime', 'description', 'status', 'evidence_file_path', 'vehicle_plate', 'vehicle_type_id', 'metadata', 'created_by'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
