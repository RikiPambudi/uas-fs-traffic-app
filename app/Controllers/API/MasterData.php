<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\ViolationTypeModel;
use App\Models\VehicleTypeModel;

class MasterData extends BaseController
{
    use ApiResponseTrait;

    public function violationTypes()
    {
        $model = new ViolationTypeModel();
        $items = $model->where('deleted_at', null)->where('is_active', 1)->orderBy('name')->findAll();
        return $this->respondSuccess($items);
    }

    public function vehicleTypes()
    {
        $model = new VehicleTypeModel();
        $items = $model->where('deleted_at', null)->where('is_active', 1)->orderBy('name')->findAll();
        return $this->respondSuccess($items);
    }
}
