<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;

class Dashboard extends BaseController
{
    use ApiResponseTrait;

    public function summary()
    {
        $db = \Config\Database::connect();

        $counts = [];
        $counts['users'] = (int) $db->table('users')->where('deleted_at', null)->countAllResults();
        $counts['violations'] = (int) $db->table('violations')->where('deleted_at', null)->countAllResults();
        $counts['observations'] = (int) $db->table('traffic_observations')->where('deleted_at', null)->countAllResults();
        $counts['violation_types_active'] = (int) $db->table('violation_types')->where('is_active', 1)->countAllResults();
        $counts['vehicle_types_active'] = (int) $db->table('vehicle_types')->where('is_active', 1)->countAllResults();

        $recentViolations = $db->table('violations')->where('deleted_at', null)->orderBy('violation_datetime', 'DESC')->limit(5)->get()->getResultArray();
        $recentObservations = $db->table('traffic_observations')->where('deleted_at', null)->orderBy('observation_datetime', 'DESC')->limit(5)->get()->getResultArray();

        return $this->respondSuccess(['counts' => $counts, 'recent_violations' => $recentViolations, 'recent_observations' => $recentObservations]);
    }
}
