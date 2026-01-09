-- =============================================
-- DATABASE: violation_system
-- PRODUCTION-READY, CLEAN, SENIOR-LEVEL ARCHITECTURE
-- Advanced database design for fullstack violation system
-- =============================================

-- HAPUS DATABASE LAMA JIKA ADA
DROP DATABASE IF EXISTS violation_system;

-- BUAT DATABASE BARU
CREATE DATABASE violation_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE violation_system;

-- =============================================
-- TABLE: users
-- User management with integrated token system
-- =============================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') DEFAULT 'operator',
    is_active BOOLEAN DEFAULT TRUE,

    -- Token fields integrated
    refresh_token_hash VARCHAR(255) NULL COMMENT 'SHA-256 hash of refresh token',
    refresh_token_expires_at TIMESTAMP NULL,
    refresh_token_issued_at TIMESTAMP NULL,
    refresh_token_device_id VARCHAR(255) NULL,

    -- Security fields
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    login_attempts TINYINT UNSIGNED DEFAULT 0,
    locked_until TIMESTAMP NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(255) NULL,

    -- Metadata
    metadata JSON DEFAULT (JSON_OBJECT()) COMMENT 'Flexible user attributes',

    -- Timestamps with timezone awareness
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,

    -- Indexes
    INDEX idx_user_username (username),
    INDEX idx_user_email (email),
    INDEX idx_user_role_active (role, is_active),
    INDEX idx_user_refresh_token (refresh_token_hash),
    INDEX idx_user_deleted (deleted_at),
    INDEX idx_user_created_by (created_by),
    INDEX idx_user_uuid (uuid),

    -- Foreign key
    CONSTRAINT fk_user_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: violation_types
-- Master data for violation categories
-- =============================================
CREATE TABLE violation_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    fine_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    penalty_points TINYINT UNSIGNED NOT NULL DEFAULT 0,
    severity_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_violation_type_code (code),
    INDEX idx_violation_type_active (is_active, deleted_at),
    INDEX idx_violation_type_severity (severity_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: vehicle_types
-- Master data for vehicle classifications
-- =============================================
CREATE TABLE vehicle_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon_class VARCHAR(50) NULL,
    color_code VARCHAR(7) DEFAULT '#6c757d',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_vehicle_type_code (code),
    INDEX idx_vehicle_type_active (is_active, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: violations
-- Core violation records with geospatial support
-- =============================================
CREATE TABLE violations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    violation_type_id TINYINT UNSIGNED NOT NULL,
    violation_number VARCHAR(50) UNIQUE NOT NULL,

    -- Location data
    location_address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    geo_hash VARCHAR(12) NULL COMMENT 'Geohash for spatial indexing',
    location_metadata JSON NULL COMMENT 'Additional location details',

    -- Violation details
    violation_datetime DATETIME NOT NULL,
    description TEXT NULL,
    status ENUM('registered', 'processing', 'completed', 'cancelled') DEFAULT 'registered',
    evidence_file_path VARCHAR(255) NULL,

    -- Vehicle information (if available)
    vehicle_plate VARCHAR(20) NULL,
    vehicle_type_id TINYINT UNSIGNED NULL,

    -- Business metadata
    metadata JSON NULL COMMENT 'Flexible data storage',

    -- Audit trail
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_violation_number (violation_number),
    INDEX idx_violation_datetime (violation_datetime),
    INDEX idx_violation_status (status),
    INDEX idx_violation_created_by (created_by),
    INDEX idx_violation_type_status (violation_type_id, status),
    INDEX idx_violation_geo_hash (geo_hash),
    INDEX idx_violation_vehicle_plate (vehicle_plate),
    INDEX idx_violation_deleted (deleted_at),
    INDEX idx_violation_uuid (uuid),

    -- Foreign keys
    CONSTRAINT fk_violation_type
        FOREIGN KEY (violation_type_id) REFERENCES violation_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_violation_vehicle_type
        FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_violation_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: traffic_observations
-- Vehicle traffic observation records
-- =============================================
CREATE TABLE traffic_observations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    vehicle_type_id TINYINT UNSIGNED NOT NULL,
    license_plate VARCHAR(20) NOT NULL,

    -- Observation details
    observation_datetime DATETIME NOT NULL,
    location_address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    geo_point POINT NOT NULL DEFAULT POINT(0, 0) COMMENT 'Spatial point for geographical queries',
    geo_hash VARCHAR(12) NULL COMMENT 'Geohash for spatial indexing',

    -- Traffic metrics
    direction ENUM('north', 'south', 'east', 'west') NULL,
    speed_kmh DECIMAL(6,2) NULL,
    lane_number TINYINT UNSIGNED NULL,

    -- Metadata
    metadata JSON NULL,

    -- Audit trail
    observed_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_observation_license_plate (license_plate),
    INDEX idx_observation_datetime (observation_datetime),
    INDEX idx_observation_location (location_address),
    INDEX idx_observation_observed_by (observed_by),
    INDEX idx_observation_geo_hash (geo_hash),
    INDEX idx_observation_deleted (deleted_at),
    INDEX idx_observation_uuid (uuid),

    -- Foreign keys
    CONSTRAINT fk_observation_vehicle_type
        FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_observation_observed_by
        FOREIGN KEY (observed_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: system_configurations
-- Application configuration management
-- =============================================
CREATE TABLE system_configurations (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NULL,
    data_type ENUM('string', 'integer', 'float', 'boolean', 'json', 'array') DEFAULT 'string',
    description VARCHAR(255) NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT FALSE,
    min_value VARCHAR(50) NULL,
    max_value VARCHAR(50) NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_config_key (config_key),
    INDEX idx_config_category (category),
    INDEX idx_config_public (is_public),

    CONSTRAINT fk_config_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TRIGGERS: Minimal & Efficient
-- =============================================

-- HAPUS TRIGGER JIKA SUDAH ADA
DROP TRIGGER IF EXISTS violation_system.trg_users_before_insert;
DROP TRIGGER IF EXISTS violation_system.trg_violations_before_insert;
DROP TRIGGER IF EXISTS violation_system.trg_violations_before_update;
DROP TRIGGER IF EXISTS violation_system.trg_observations_before_insert;
DROP TRIGGER IF EXISTS violation_system.trg_observations_before_update;
DROP TRIGGER IF EXISTS violation_system.trg_users_before_refresh_token_update;

DELIMITER $$

-- Trigger untuk users (UUID otomatis)
CREATE TRIGGER trg_users_before_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    -- Generate UUID jika belum ada
    IF NEW.uuid IS NULL THEN
        SET NEW.uuid = UUID();
    END IF;
END $$

-- Trigger untuk violations (UUID + violation_number + geo_hash)
CREATE TRIGGER trg_violations_before_insert
BEFORE INSERT ON violations
FOR EACH ROW
BEGIN
    DECLARE v_sequence INT;
    DECLARE v_prefix VARCHAR(10);
    
    -- Generate UUID
    IF NEW.uuid IS NULL THEN
        SET NEW.uuid = UUID();
    END IF;
    
    -- Generate violation_number
    IF NEW.violation_number IS NULL THEN
        SET v_prefix = CONCAT('VIO-', DATE_FORMAT(NEW.violation_datetime, '%Y%m'), '-');
        
        SELECT COALESCE(MAX(CAST(SUBSTRING(violation_number, LENGTH(v_prefix) + 1) AS UNSIGNED)), 0) + 1
        INTO v_sequence
        FROM violations
        WHERE violation_number LIKE CONCAT(v_prefix, '%');
        
        SET NEW.violation_number = CONCAT(v_prefix, LPAD(v_sequence, 6, '0'));
    END IF;
    
    -- Generate geo_hash jika ada latitude dan longitude
    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
        SET NEW.geo_hash = CONCAT(
            SUBSTRING(HEX(ROUND((NEW.latitude + 90) * 1000)), 1, 4),
            SUBSTRING(HEX(ROUND((NEW.longitude + 180) * 1000)), 1, 4)
        );
    END IF;
END $$

CREATE TRIGGER trg_violations_before_update
BEFORE UPDATE ON violations
FOR EACH ROW
BEGIN
    -- Update geo_hash jika latitude/longitude berubah
    IF (NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL) AND 
       (OLD.latitude != NEW.latitude OR OLD.longitude != NEW.longitude) THEN
        SET NEW.geo_hash = CONCAT(
            SUBSTRING(HEX(ROUND((NEW.latitude + 90) * 1000)), 1, 4),
            SUBSTRING(HEX(ROUND((NEW.longitude + 180) * 1000)), 1, 4)
        );
    END IF;
END $$

-- Trigger untuk traffic_observations (UUID + geo_point + geo_hash)
CREATE TRIGGER trg_observations_before_insert
BEFORE INSERT ON traffic_observations
FOR EACH ROW
BEGIN
    -- Generate UUID
    IF NEW.uuid IS NULL THEN
        SET NEW.uuid = UUID();
    END IF;
    
    -- Generate geo_point dan geo_hash jika ada latitude/longitude
    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
        SET NEW.geo_point = POINT(NEW.longitude, NEW.latitude);
        SET NEW.geo_hash = CONCAT(
            SUBSTRING(HEX(ROUND((NEW.latitude + 90) * 1000)), 1, 4),
            SUBSTRING(HEX(ROUND((NEW.longitude + 180) * 1000)), 1, 4)
        );
    END IF;
END $$

CREATE TRIGGER trg_observations_before_update
BEFORE UPDATE ON traffic_observations
FOR EACH ROW
BEGIN
    -- Update geo_point dan geo_hash jika latitude/longitude berubah
    IF (NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL) AND 
       (OLD.latitude != NEW.latitude OR OLD.longitude != NEW.longitude) THEN
        SET NEW.geo_point = POINT(NEW.longitude, NEW.latitude);
        SET NEW.geo_hash = CONCAT(
            SUBSTRING(HEX(ROUND((NEW.latitude + 90) * 1000)), 1, 4),
            SUBSTRING(HEX(ROUND((NEW.longitude + 180) * 1000)), 1, 4)
        );
    END IF;
END $$

-- Update user's refresh token expiry on token refresh
CREATE TRIGGER trg_users_before_refresh_token_update
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    -- Jika refresh token dihapus, juga hapus expiry
    IF NEW.refresh_token_hash IS NULL AND OLD.refresh_token_hash IS NOT NULL THEN
        SET NEW.refresh_token_expires_at = NULL;
        SET NEW.refresh_token_issued_at = NULL;
        SET NEW.refresh_token_device_id = NULL;
    END IF;
END $$

DELIMITER ;

-- =============================================
-- OPTIMIZATION INDEXES
-- =============================================

-- HAPUS INDEX LAMA JIKA ADA
DROP INDEX IF EXISTS idx_violations_covering ON violations;
DROP INDEX IF EXISTS idx_violations_active ON violations;
DROP INDEX IF EXISTS idx_observations_geo_composite ON traffic_observations;
DROP INDEX IF EXISTS idx_violations_location ON violations;
DROP INDEX IF EXISTS idx_observations_location ON traffic_observations;
DROP INDEX IF EXISTS idx_violations_composite ON violations;

-- Covering index untuk common violation queries
CREATE INDEX idx_violations_covering
ON violations (deleted_at, violation_datetime, status, violation_type_id, created_by);

-- Index composite untuk performa query yang sering digunakan
CREATE INDEX idx_violations_composite
ON violations (deleted_at, status, violation_datetime);

-- Geospatial composite index
CREATE INDEX idx_observations_geo_composite
ON traffic_observations (geo_hash, observation_datetime, deleted_at);

-- Index untuk pencarian cepat berdasarkan location (prefix index)
CREATE INDEX idx_violations_location 
ON violations (location_address(100));

CREATE INDEX idx_observations_location 
ON traffic_observations (location_address(100));

-- =============================================
-- STORED PROCEDURES: Disabled for MariaDB 10.4.28 compatibility
-- NOTE: Use application code instead for business logic
-- =============================================

-- Stored procedures removed to avoid mysql.proc corruption issue
-- All business logic (authentication, pagination, etc.) should be handled
-- in your application code (PHP, Node.js, etc.)

-- =============================================
-- INITIAL DATA SEEDING
-- =============================================

-- System configurations
INSERT INTO system_configurations (config_key, config_value, data_type, description, category, is_public) VALUES
('security.jwt_expiration_minutes', '2880', 'integer', 'JWT token expiration in minutes (48 hours)', 'security', FALSE),
('security.refresh_token_expiration_days', '7', 'integer', 'Refresh token expiration in days', 'security', FALSE),
('pagination.violations_per_page', '20', 'integer', 'Default items per page for violations', 'ui', TRUE),
('pagination.observations_per_page', '10', 'integer', 'Default items per page for traffic observations', 'ui', TRUE),
('security.max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lock', 'security', FALSE),
('geo.default_search_radius_km', '5', 'float', 'Default radius for location-based searches', 'geo', TRUE),
('business.violation_number_prefix', 'VIO', 'string', 'Prefix for auto-generated violation numbers', 'business', FALSE),
('ui.default_theme', 'light', 'string', 'Default user interface theme', 'ui', TRUE);

-- Violation types
INSERT INTO violation_types (code, name, description, fine_amount, penalty_points, severity_level) VALUES
('CONTRAFLOW', 'Contraflow Violation', 'Driving against traffic flow', 750000.00, 10, 'high'),
('OVERSPEED', 'Overspeeding', 'Exceeding speed limits', 500000.00, 8, 'medium'),
('TRAFFIC_BLOCK', 'Traffic Obstruction', 'Causing traffic obstruction or jam', 350000.00, 5, 'medium'),
('NO_HELMET', 'No Helmet', 'Riding motorcycle without helmet', 250000.00, 5, 'low'),
('ILLEGAL_PARKING', 'Illegal Parking', 'Parking in prohibited areas', 200000.00, 3, 'low'),
('RED_LIGHT', 'Red Light Violation', 'Running red traffic light', 600000.00, 8, 'high'),
('NO_LICENSE', 'No Driving License', 'Driving without valid license', 1000000.00, 12, 'critical');

-- Vehicle types
INSERT INTO vehicle_types (code, name, icon_class, color_code) VALUES
('TRUCK', 'Truck', 'truck-icon', '#6f42c1'),
('CAR', 'Car', 'car-icon', '#20c997'),
('MOTORCYCLE', 'Motorcycle', 'motorcycle-icon', '#fd7e14'),
('BUS', 'Bus', 'bus-icon', '#e83e8c'),
('TAXI', 'Taxi', 'taxi-icon', '#17a2b8'),
('EMERGENCY', 'Emergency Vehicle', 'ambulance-icon', '#dc3545'),
('GOVERNMENT', 'Government Vehicle', 'government-icon', '#28a745');

-- Insert admin user pertama (tanpa created_by)
INSERT INTO users (uuid, username, email, password_hash, role, metadata) VALUES
(UUID(), 'system.admin', 'admin@violation-system.local', '$2y$10$HASHED_PASSWORD_HERE', 'admin',
 '{"full_name": "System Administrator", "department": "IT", "phone": "+62123456789"}');

-- Sekarang insert user lain dengan created_by yang merujuk ke admin
INSERT INTO users (uuid, username, email, password_hash, role, metadata, created_by) VALUES
(UUID(), 'operator.jakarta', 'operator.jkt@violation-system.local', '$2y$10$HASHED_PASSWORD_HERE', 'operator',
 '{"full_name": "Jakarta Operator", "region": "Jakarta", "shift": "Day"}', 1),
(UUID(), 'operator.bandung', 'operator.bdg@violation-system.local', '$2y$10$HASHED_PASSWORD_HERE', 'operator',
 '{"full_name": "Bandung Operator", "region": "Bandung", "shift": "Night"}', 1);

-- Sample violations (pastikan violation_type_id dan created_by valid)
INSERT INTO violations (uuid, violation_type_id, violation_number, location_address, latitude, longitude, violation_datetime, description, status, vehicle_plate, vehicle_type_id, created_by) VALUES
(UUID(), 1, 'VIO-202601-000001', 'Jl. Sudirman No. 123, Jakarta Pusat', -6.208763, 106.845599, '2026-01-01 10:30:00', 'Contraflow during rush hour', 'completed', 'B 1234 ABC', 2, 2),
(UUID(), 2, 'VIO-202601-000002', 'Jl. Thamrin, Jakarta', -6.186486, 106.822091, '2026-01-02 14:45:00', 'Speed 120km/h in 60km/h zone', 'processing', 'D 5678 XYZ', 2, 2),
(UUID(), 3, 'VIO-202601-000003', 'Jl. Gatot Subroto, Jakarta Selatan', -6.221650, 106.811126, '2025-12-31 09:15:00', 'Truck breakdown causing 3km traffic', 'registered', NULL, 1, 2),
(UUID(), 4, 'VIO-202601-000004', 'Jl. Merdeka, Bandung', -6.917464, 107.619125, '2026-01-03 08:00:00', 'Motorcycle rider without helmet', 'completed', 'F 9012 DEF', 3, 3),
(UUID(), 5, 'VIO-202601-000005', 'Jl. Asia Afrika, Bandung', -6.921851, 107.607529, '2026-01-03 11:30:00', 'Car parked in bus lane', 'processing', 'Z 3456 GHI', 2, 3);

-- Sample traffic observations
INSERT INTO traffic_observations (uuid, vehicle_type_id, license_plate, observation_datetime, location_address, latitude, longitude, direction, speed_kmh, observed_by) VALUES
(UUID(), 2, 'B 1234 TSF', NOW() - INTERVAL 30 MINUTE, 'Jl. Sudirman, Jakarta', -6.208763, 106.845599, 'north', 55.5, 2),
(UUID(), 3, 'D 5678 ABC', NOW() - INTERVAL 45 MINUTE, 'Jl. Thamrin, Jakarta', -6.186486, 106.822091, 'south', 72.3, 2),
(UUID(), 4, 'F 9012 XYZ', NOW() - INTERVAL 1 HOUR, 'Jl. Gatot Subroto, Jakarta', -6.221650, 106.811126, 'east', 65.0, 2),
(UUID(), 2, 'Z 3456 DEF', NOW() - INTERVAL 2 HOUR, 'Jl. Merdeka, Bandung', -6.917464, 107.619125, 'west', 50.2, 3),
(UUID(), 1, 'T 7890 GHI', NOW() - INTERVAL 3 HOUR, 'Jl. Asia Afrika, Bandung', -6.921851, 107.607529, 'north', 68.7, 3);

-- Update geo_hash untuk data yang sudah ada
UPDATE violations 
SET geo_hash = CONCAT(
    SUBSTRING(HEX(ROUND((latitude + 90) * 1000)), 1, 4),
    SUBSTRING(HEX(ROUND((longitude + 180) * 1000)), 1, 4)
)
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;

UPDATE traffic_observations 
SET geo_hash = CONCAT(
    SUBSTRING(HEX(ROUND((latitude + 90) * 1000)), 1, 4),
    SUBSTRING(HEX(ROUND((longitude + 180) * 1000)), 1, 4)
),
    geo_point = POINT(longitude, latitude)
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;

-- =============================================
-- SPATIAL INDEX untuk geo_point
-- =============================================

-- Buat spatial index setelah data dimasukkan
CREATE SPATIAL INDEX idx_observations_geo_point ON traffic_observations(geo_point);

-- =============================================
-- DATABASE EVENT FOR MAINTENANCE: Disabled
-- NOTE: Maintenance tasks should be handled by cron jobs or app scheduler
-- =============================================

-- Events removed to avoid compatibility issues
-- Use application background jobs (Laravel jobs, Node cron, etc.) instead

-- =============================================
-- VERIFIKASI DATABASE
-- =============================================

-- Verifikasi tabel dan data
SELECT 
    'users' as table_name, 
    COUNT(*) as record_count 
FROM users 
WHERE deleted_at IS NULL
UNION ALL
SELECT 
    'violations' as table_name, 
    COUNT(*) as record_count 
FROM violations 
WHERE deleted_at IS NULL
UNION ALL
SELECT 
    'traffic_observations' as table_name, 
    COUNT(*) as record_count 
FROM traffic_observations 
WHERE deleted_at IS NULL
UNION ALL
SELECT 
    'violation_types' as table_name, 
    COUNT(*) as record_count 
FROM violation_types 
WHERE deleted_at IS NULL
UNION ALL
SELECT 
    'vehicle_types' as table_name, 
    COUNT(*) as record_count 
FROM vehicle_types 
WHERE deleted_at IS NULL
UNION ALL
SELECT 
    'system_configurations' as table_name, 
    COUNT(*) as record_count 
FROM system_configurations;

-- =============================================
-- FINISH
-- =============================================