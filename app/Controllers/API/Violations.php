<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\ViolationModel;
use App\Models\SystemConfigModel;

class Violations extends BaseController
{
    use ApiResponseTrait;

    protected ViolationModel $model;

    public function __construct()
    {
        $this->model = new ViolationModel();
    }

    public function index()
    {
        $page = (int) $this->request->getVar('page') ?? 1;

        // Read pagination from system configurations, fallback to 20
        $configModel = new SystemConfigModel();
        $conf = $configModel->where('config_key', 'pagination.violations_per_page')->get()->getRowArray();
        $perPage = $conf['config_value'] ? (int) $conf['config_value'] : 20;

        $data = $this->model->where('deleted_at', null)
            ->orderBy('violation_datetime', 'DESC')
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
            'type' => 'required',
            'location_address' => 'required',
            'violation_datetime' => 'required|valid_date[Y-m-d H:i:s]'
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Validation failed', 422, $this->validator->getErrors());
        }

        $type = strtolower($this->request->getVar('type'));
        $allowed = ['contraflow' => 'CONTRAFLOW', 'overspeed' => 'OVERSPEED', 'traffic_jam' => 'TRAFFIC_BLOCK'];

        if (! isset($allowed[$type])) {
            return $this->respondError('Invalid violation type', 422);
        }

        $db = \Config\Database::connect();
        $vt = $db->table('violation_types')->where('code', $allowed[$type])->get()->getRowArray();
        if (! $vt) {
            return $this->respondError('Violation type not found in system', 422);
        }

        // map vehicle type if provided
        $vehicle_type_id = null;
        $vehicle = $this->request->getVar('vehicle_type');
        if ($vehicle) {
            $map = ['truk' => 'TRUCK', 'mobil' => 'CAR', 'motor' => 'MOTORCYCLE'];
            $code = $map[strtolower($vehicle)] ?? null;
            if ($code) {
                $vt2 = $db->table('vehicle_types')->where('code', $code)->get()->getRowArray();
                $vehicle_type_id = $vt2['id'] ?? null;
            }
        }

        // created_by from filter-attached user
        $user = $this->request->user ?? null;
        $createdBy = $user['id'] ?? null;

        $payload = [
            'violation_type_id' => $vt['id'],
            'location_address' => $this->request->getVar('location_address'),
            'latitude' => $this->request->getVar('latitude') ?: null,
            'longitude' => $this->request->getVar('longitude') ?: null,
            'violation_datetime' => $this->request->getVar('violation_datetime'),
            'description' => $this->request->getVar('description') ?: null,
            'vehicle_plate' => $this->request->getVar('vehicle_plate') ?: null,
            'vehicle_type_id' => $vehicle_type_id,
            'created_by' => $createdBy
        ];

        $id = $this->model->insert($payload);
        if (! $id) {
            return $this->respondError('Failed to create violation', 500);
        }

        $created = $this->model->find($id);
        return $this->respondSuccess($created, 'Violation created', 201);
    }

    public function show($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Violation not found', 404);
        }
        return $this->respondSuccess($item);
    }

    public function update($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Violation not found', 404);
        }

        $rules = [
            'location_address' => 'required',
            'violation_datetime' => 'required|valid_date[Y-m-d H:i:s]'
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Validation failed', 422, $this->validator->getErrors());
        }

        $data = [
            'location_address' => $this->request->getVar('location_address'),
            'latitude' => $this->request->getVar('latitude') ?: null,
            'longitude' => $this->request->getVar('longitude') ?: null,
            'violation_datetime' => $this->request->getVar('violation_datetime'),
            'description' => $this->request->getVar('description') ?: null,
            'vehicle_plate' => $this->request->getVar('vehicle_plate') ?: null,
            'vehicle_type_id' => $this->request->getVar('vehicle_type_id') ?: $item['vehicle_type_id']
        ];

        $this->model->update($id, $data);
        $updated = $this->model->find($id);
        return $this->respondSuccess($updated, 'Violation updated');
    }

    public function delete($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) {
            return $this->respondError('Violation not found', 404);
        }

        $this->model->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->respondSuccess(null, 'Violation deleted');
    }
}
