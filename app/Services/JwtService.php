<?php

namespace App\Services;

use Config\Jwt as JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected JwtConfig $config;

    public function __construct()
    {
        $this->config = config(JwtConfig::class);
        // allow env override
        if (getenv('JWT_SECRET')) {
            $this->config->secret = getenv('JWT_SECRET');
        }
        // allow expiration overrides from environment
        if (getenv('JWT_EXPIRATION_MINUTES')) {
            $this->config->expirationMinutes = (int) getenv('JWT_EXPIRATION_MINUTES');
        }
        if (getenv('JWT_REFRESH_EXPIRATION_DAYS')) {
            $this->config->refreshExpirationDays = (int) getenv('JWT_REFRESH_EXPIRATION_DAYS');
        }
    }

    public function generateToken(array $user): string
    {
        $now = time();
        $exp = $now + ($this->config->expirationMinutes * 60);

        $payload = [
            'iss' => $this->config->issuer,
            'iat' => $now,
            'exp' => $exp,
            'sub' => $user['id'] ?? null,
            'user' => [
                'id' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'role' => $user['role'] ?? null,
            ],
        ];

        return JWT::encode($payload, $this->config->secret, 'HS256');
    }

    public function validateToken(string $token): object
    {
        return JWT::decode($token, new Key($this->config->secret, 'HS256'));
    }

    /**
     * Generate a cryptographically secure refresh token (base64url)
     */
    public function generateRefreshToken(int $length = 64): string
    {
        $bytes = random_bytes($length);
        // base64url
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Hash refresh token for storage using SHA-256
     */
    public function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function getExpirationMinutes(): int
    {
        return (int) $this->config->expirationMinutes;
    }

    public function getRefreshExpirationDays(): int
    {
        return (int) $this->config->refreshExpirationDays;
    }
}
