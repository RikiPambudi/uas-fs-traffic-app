<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\ObservationModel;
use App\Models\SystemConfigModel;

class Observations extends BaseController
{
    use ApiResponseTrait;

    protected ObservationModel $model;

    public function __construct()
    {
        $this->model = new ObservationModel();
    }

    public function index()
    {
        // Read pagination from system configurations, fallback to 10
        $configModel = new SystemConfigModel();
        $conf = $configModel->where('config_key', 'pagination.observations_per_page')->get()->getRowArray();
        $perPage = $conf['config_value'] ? (int) $conf['config_value'] : 10;

        $data = $this->model->where('deleted_at', null)
            ->orderBy('observation_datetime', 'DESC')
            ->paginate($perPage);

        $pager = $this->model->pager;

        return $this->respondSuccess([
            'items' => $data,
            'pagination' => [
                'currentPage' => (int) $pager->getCurrentPage(),
                'total' => (int) $pager->getTotal(),
                'perPage' => (int) $pager->getPerPage(),
                'totalPages' => (int) $pager->getPageCount(),
            ]
        ]);
    }

    public function create()
    {
        $rules = [
            'vehicle_type' => 'required',
            'license_plate' => 'required',
            'observation_datetime' => 'required|valid_date[Y-m-d H:i:s]',
            'location_address' => 'required'
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Validation failed', 422, $this->validator->getErrors());
        }

        $type = strtolower($this->request->getVar('vehicle_type'));
        $map = ['truk' => 'TRUCK', 'mobil' => 'CAR', 'motor' => 'MOTORCYCLE'];
        if (! isset($map[$type])) {
            return $this->respondError('Invalid vehicle type', 422);
        }

        $db = \Config\Database::connect();
        $vt = $db->table('vehicle_types')->where('code', $map[$type])->get()->getRowArray();
        if (! $vt) {
            return $this->respondError('Vehicle type not found in system', 422);
        }

        $user = $this->request->user ?? null;
        $observedBy = $user['id'] ?? null;

        $payload = [
            'vehicle_type_id' => $vt['id'],
            'license_plate' => $this->request->getVar('license_plate'),
            'observation_datetime' => $this->request->getVar('observation_datetime'),
            'location_address' => $this->request->getVar('location_address'),
            'latitude' => $this->request->getVar('latitude') ?: null,
            'longitude' => $this->request->getVar('longitude') ?: null,
            'direction' => $this->request->getVar('direction') ?: null,
            'speed_kmh' => $this->request->getVar('speed_kmh') ?: null,
            'lane_number' => $this->request->getVar('lane_number') ?: null,
            'observed_by' => $observedBy
        ];

        $id = $this->model->insert($payload);
        if (! $id) {
            return $this->respondError('Failed to create observation', 500);
        }

        $created = $this->model->find($id);
        return $this->respondSuccess($created, 'Observation created', 201);
    }

    public function show($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Observation not found', 404);
        }
        return $this->respondSuccess($item);
    }

    public function update($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Observation not found', 404);
        }

        $rules = [
            'license_plate' => 'required',
            'observation_datetime' => 'required|valid_date[Y-m-d H:i:s]',
            'location_address' => 'required'
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Validation failed', 422, $this->validator->getErrors());
        }

        $data = [
            'license_plate' => $this->request->getVar('license_plate'),
            'observation_datetime' => $this->request->getVar('observation_datetime'),
            'location_address' => $this->request->getVar('location_address'),
            'latitude' => $this->request->getVar('latitude') ?: null,
            'longitude' => $this->request->getVar('longitude') ?: null,
            'direction' => $this->request->getVar('direction') ?: null,
            'speed_kmh' => $this->request->getVar('speed_kmh') ?: null,
            'lane_number' => $this->request->getVar('lane_number') ?: null
        ];

        $this->model->update($id, $data);
        $updated = $this->model->find($id);
        return $this->respondSuccess($updated, 'Observation updated');
    }

    public function delete($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Observation not found', 404);
        }

        $this->model->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->respondSuccess(null, 'Observation deleted');
    }
}
