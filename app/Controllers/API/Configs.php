<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\SystemConfigModel;

class Configs extends BaseController
{
    use ApiResponseTrait;

    public function index()
    {
        $model = new SystemConfigModel();
        $items = $model->orderBy('config_key')->findAll();
        return $this->respondSuccess($items);
    }
}
