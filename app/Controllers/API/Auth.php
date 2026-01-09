<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Services\JwtService;

class Auth extends BaseController
{
    use ApiResponseTrait;

    public function login()
    {
        $rules = [
            'identity' => 'required',
            'password' => 'required'
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Validation failed', 422, $this->validator->getErrors());
        }

        $identity = $this->request->getVar('identity');
        $password = $this->request->getVar('password');

        $db = \Config\Database::connect();
        $user = $db->table('users')
            ->where('username', $identity)
            ->orWhere('email', $identity)
            ->get()
            ->getRowArray();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return $this->respondError('Invalid credentials', 401);
        }

        $jwt = new JwtService();
        $accessToken = $jwt->generateToken(['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']]);

        // generate refresh token and store its hash
        $refreshToken = $jwt->generateRefreshToken();
        $refreshHash = $jwt->hashRefreshToken($refreshToken);

        $refreshExpiryDays = $jwt->getRefreshExpirationDays();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$refreshExpiryDays} days"));

        $db = \Config\Database::connect();
        $db->table('users')->where('id', $user['id'])->update([
            'refresh_token_hash' => $refreshHash,
            'refresh_token_expires_at' => $expiresAt,
            'refresh_token_issued_at' => date('Y-m-d H:i:s')
        ]);

        return $this->respondSuccess([
            'access_token' => $accessToken,
            'expires_in_minutes' => $jwt->getExpirationMinutes(),
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $expiresAt
        ], 'Authenticated');
    }

    public function refresh()
    {
        $token = $this->request->getVar('refresh_token');
        if (! $token) {
            return $this->respondError('Missing refresh_token', 400);
        }

        $jwt = new JwtService();
        $hash = $jwt->hashRefreshToken($token);

        $db = \Config\Database::connect();
        $user = $db->table('users')->where('refresh_token_hash', $hash)->get()->getRowArray();
        if (! $user) {
            return $this->respondError('Invalid refresh token', 401);
        }

        if ($user['refresh_token_expires_at'] && strtotime($user['refresh_token_expires_at']) < time()) {
            return $this->respondError('Refresh token expired', 401);
        }

        // rotate refresh token
        $newRefresh = $jwt->generateRefreshToken();
        $newHash = $jwt->hashRefreshToken($newRefresh);
        $refreshExpiryDays = $jwt->getRefreshExpirationDays();
        $newExpiresAt = date('Y-m-d H:i:s', strtotime("+{$refreshExpiryDays} days"));

        $db->table('users')->where('id', $user['id'])->update([
            'refresh_token_hash' => $newHash,
            'refresh_token_expires_at' => $newExpiresAt,
            'refresh_token_issued_at' => date('Y-m-d H:i:s')
        ]);

        $accessToken = $jwt->generateToken(['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']]);

        return $this->respondSuccess([
            'access_token' => $accessToken,
            'expires_in_minutes' => $jwt->getExpirationMinutes(),
            'refresh_token' => $newRefresh,
            'refresh_expires_at' => $newExpiresAt
        ], 'Token refreshed');
    }

    public function logout()
    {
        // require JWT; user attached by filter
        $user = $this->request->user ?? null;
        if (! $user) return $this->respondError('Unauthorized', 401);

        $db = \Config\Database::connect();
        $db->table('users')->where('id', $user['id'])->update([
            'refresh_token_hash' => null,
            'refresh_token_expires_at' => null,
            'refresh_token_issued_at' => null
        ]);

        return $this->respondSuccess(null, 'Logged out');
    }
}
