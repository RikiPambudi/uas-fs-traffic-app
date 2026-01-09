<?php

namespace App\Models;

use CodeIgniter\Model;

class ObservationModel extends Model
{
    protected $table = 'traffic_observations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'vehicle_type_id', 'license_plate', 'observation_datetime', 'location_address', 'latitude', 'longitude', 'direction', 'speed_kmh', 'lane_number', 'metadata', 'observed_by'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
