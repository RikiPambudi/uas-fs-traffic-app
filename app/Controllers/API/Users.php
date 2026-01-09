<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\UserModel;

class Users extends BaseController
{
    use ApiResponseTrait;

    protected UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function index()
    {
        $perPage = (int) $this->request->getVar('per_page') ?: 20;
        $items = $this->model->where('deleted_at', null)->orderBy('username')->paginate($perPage);
        $pager = $this->model->pager;
        return $this->respondSuccess(['items' => $items, 'pagination' => ['currentPage' => (int)$pager->getCurrentPage(), 'total' => (int)$pager->getTotal(), 'perPage' => (int)$pager->getPerPage()]]);
    }

    public function show($id = null)
    {
        $item = $this->model->where('deleted_at', null)->find($id);
        if (! $item) return $this->respondError('User not found', 404);
        unset($item['password_hash']);
        return $this->respondSuccess($item);
    }

    public function create()
    {
        $rules = ['username' => 'required|alpha_numeric_punct|is_unique[users.username]', 'email' => 'required|valid_email|is_unique[users.email]', 'password' => 'required|min_length[6]'];
        if (! $this->validate($rules)) return $this->respondError('Validation failed', 422, $this->validator->getErrors());

        $payload = [
            'uuid' => null,
            'username' => $this->request->getVar('username'),
            'email' => $this->request->getVar('email'),
            'password_hash' => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT),
            'role' => $this->request->getVar('role') ?: 'operator',
            'is_active' => $this->request->getVar('is_active') ? 1 : 0,
            'metadata' => $this->request->getVar('metadata') ?: null,
            'created_by' => $this->request->user['id'] ?? null
        ];

        $id = $this->model->insert($payload);
        if (! $id) return $this->respondError('Failed to create user', 500);
        $user = $this->model->find($id);
        unset($user['password_hash']);
        return $this->respondSuccess($user, 'User created', 201);
    }

    public function update($id = null)
    {
        $user = $this->model->where('deleted_at', null)->find($id);
        if (! $user) return $this->respondError('User not found', 404);

        $data = [];
        if ($this->request->getVar('email')) $data['email'] = $this->request->getVar('email');
        if ($this->request->getVar('password')) $data['password_hash'] = password_hash($this->request->getVar('password'), PASSWORD_BCRYPT);
        if ($this->request->getVar('role')) $data['role'] = $this->request->getVar('role');
        if ($this->request->getVar('is_active') !== null) $data['is_active'] = $this->request->getVar('is_active') ? 1 : 0;

        $this->model->update($id, $data);
        $updated = $this->model->find($id);
        unset($updated['password_hash']);
        return $this->respondSuccess($updated, 'User updated');
    }

    public function delete($id = null)
    {
        $user = $this->model->where('deleted_at', null)->find($id);
        if (! $user) return $this->respondError('User not found', 404);
        $this->model->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->respondSuccess(null, 'User deleted');
    }
}
