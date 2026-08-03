-- Reports and Download Center Schema
-- NextGen Smart University Platform
--
-- Reports are produced synchronously from live module data, so there is no
-- stored report artifact table here. ReportHistory satisfies the documented
-- rule that report generation activity is logged.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS ReportHistory (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    report_key VARCHAR(100) NOT NULL,
    export_format ENUM('view', 'csv', 'pdf', 'xlsx') NOT NULL DEFAULT 'view',
    parameters VARCHAR(500) NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    generated_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_report_history_user (user_id),
    KEY idx_report_history_key (report_key),
    KEY idx_report_history_generated (generated_at),

    CONSTRAINT fk_report_history_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS DownloadFile (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category ENUM(
        'Academic Documents', 'Course Materials', 'Student Forms', 'Lecturer Forms',
        'Examination Documents', 'Reports', 'Certificates', 'Policies', 'Templates',
        'Financial Documents'
    ) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    visibility ENUM('all', 'students', 'staff', 'administrators') NOT NULL DEFAULT 'all',
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_download_category (category),
    KEY idx_download_visibility (visibility),
    KEY idx_download_uploader (uploaded_by),

    CONSTRAINT fk_download_uploader
        FOREIGN KEY (uploaded_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- The file reference is intentionally nullable with ON DELETE SET NULL so that
-- history survives a purged file, as the business rules require.
CREATE TABLE IF NOT EXISTS DownloadHistory (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    file_id BIGINT UNSIGNED NULL,
    file_title VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    downloaded_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_history_file (file_id),
    KEY idx_history_user (user_id),
    KEY idx_history_downloaded (downloaded_at),

    CONSTRAINT fk_history_file
        FOREIGN KEY (file_id) REFERENCES DownloadFile (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_history_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
