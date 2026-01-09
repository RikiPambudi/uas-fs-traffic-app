<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    // Secret key - override via environment variable JWT_SECRET in production
    public string $secret = "CHANGE_ME_PLEASE";

    // Issuer
    public string $issuer = 'violation_system';

    // Expiration in minutes (2 days = 2880 minutes)
    public int $expirationMinutes = 2880;

    // Refresh token expiration in days (default 7 days)
    public int $refreshExpirationDays = 7;
}
