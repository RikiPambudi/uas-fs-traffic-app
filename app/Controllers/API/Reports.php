<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;

class Reports extends BaseController
{
    use ApiResponseTrait;

    public function violationsByType()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('violations v')
            ->select('vt.id, vt.name, vt.code, COUNT(v.id) as total')
            ->join('violation_types vt', 'vt.id = v.violation_type_id')
            ->where('v.deleted_at', null)
            ->groupBy('vt.id')
            ->orderBy('total', 'DESC');

        $data = $builder->get()->getResultArray();
        return $this->respondSuccess($data);
    }

    public function observationsByVehicle()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('traffic_observations o')
            ->select('vt.id, vt.name, vt.code, COUNT(o.id) as total')
            ->join('vehicle_types vt', 'vt.id = o.vehicle_type_id')
            ->where('o.deleted_at', null)
            ->groupBy('vt.id')
            ->orderBy('total', 'DESC');

        $data = $builder->get()->getResultArray();
        return $this->respondSuccess($data);
    }

    public function dailyViolations()
    {
        $days = (int) $this->request->getVar('days') ?: 7;
        $db = \Config\Database::connect();
        $builder = $db->table('violations')
            ->select("DATE(violation_datetime) as day, COUNT(*) as total")
            ->where('deleted_at', null)
            ->where('violation_datetime >=', date('Y-m-d H:i:s', strtotime("-{$days} days")))
            ->groupBy('day')
            ->orderBy('day', 'ASC');

        $data = $builder->get()->getResultArray();
        return $this->respondSuccess($data);
    }
}
