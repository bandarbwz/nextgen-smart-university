-- Settings and System Modules Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/23-Settings.md and docs/FEATURES/24-System.md.
--
-- The two documents describe the same global configuration store twice:
-- Settings calls it SystemSetting with setting_key and setting_value, System
-- calls it SystemConfiguration with configuration_key and configuration_value.
-- One table is built. Two tables holding platform configuration would drift
-- apart, and nobody would know which one the application actually reads.
--
-- BackupHistory from the System document is deliberately not created. Nothing
-- in this platform can take a database backup: that is a scheduled operations
-- task with mysqldump, outside the application. A table recording backups the
-- application never takes would be a table full of lies.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS UserSetting (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    language ENUM('en', 'ar') NOT NULL DEFAULT 'en',
    theme ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'system',
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_user_setting_user (user_id),

    CONSTRAINT fk_user_setting_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS SystemSetting (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category ENUM('University', 'Academic', 'Security', 'Uploads', 'Maintenance')
        NOT NULL DEFAULT 'University',
    setting_key VARCHAR(100) NOT NULL,
    setting_value VARCHAR(500) NOT NULL,
    value_type ENUM('string', 'integer', 'boolean') NOT NULL DEFAULT 'string',
    description VARCHAR(255) NULL,
    is_editable BOOLEAN NOT NULL DEFAULT TRUE,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_system_setting_key (setting_key),
    KEY idx_system_setting_category (category),

    CONSTRAINT fk_system_setting_updated_by
        FOREIGN KEY (updated_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS SystemLog (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') NOT NULL DEFAULT 'info',
    message VARCHAR(500) NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_system_log_severity (severity),
    KEY idx_system_log_module (module),
    KEY idx_system_log_created (created_at),

    CONSTRAINT fk_system_log_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


INSERT INTO SystemSetting (category, setting_key, setting_value, value_type, description, is_editable)
VALUES
    ('University', 'university_name', 'NextGen Smart University', 'string',
     'Shown throughout the platform', TRUE),
    ('University', 'support_email', 'support@nextgen.edu', 'string',
     'Where users are told to ask for help', TRUE),
    ('Academic', 'academic_year', '2026/2027', 'string',
     'The current academic year', TRUE),
    ('Academic', 'max_credit_hours', '21', 'integer',
     'Credit hour ceiling for one semester registration', TRUE),
    ('Security', 'max_login_attempts', '5', 'integer',
     'Failed logins before an account is locked', TRUE),
    ('Security', 'lockout_minutes', '15', 'integer',
     'How long an account stays locked', TRUE),
    ('Security', 'session_timeout_minutes', '60', 'integer',
     'Access token lifetime', TRUE),
    ('Uploads', 'max_upload_mb', '25', 'integer',
     'Largest accepted upload', TRUE),
    ('Maintenance', 'maintenance_mode', 'false', 'boolean',
     'When on, only administrators can use the platform', TRUE),
    ('Maintenance', 'maintenance_message', 'The platform is under maintenance. Please try again shortly.',
     'string', 'Shown to users while maintenance mode is on', TRUE)
ON DUPLICATE KEY UPDATE description = VALUES(description);
