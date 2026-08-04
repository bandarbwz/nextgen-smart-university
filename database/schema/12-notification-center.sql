-- Notification Center Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/16-Notification-Center.md and docs/API/15-Notification-API.md.
--
-- SystemAnnouncement is not named Announcement because the LMS module already
-- owns that table for course announcements scoped to a section. This one is
-- university wide, so the two are genuinely different things.
--
-- The feature document defines Notification and NotificationPreference only.
-- The API specification has announcement endpoints with no table behind them,
-- so SystemAnnouncement is added here.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Notification (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
    module VARCHAR(50) NOT NULL,
    priority ENUM('Low', 'Normal', 'High', 'Critical') NOT NULL DEFAULT 'Normal',
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at DATETIME NULL,
    archived_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_notification_user (user_id),
    KEY idx_notification_unread (user_id, is_read),
    KEY idx_notification_created (created_at),
    KEY idx_notification_module (module),

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS NotificationPreference (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    in_app_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    email_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    push_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_notification_preference_user (user_id),

    CONSTRAINT fk_notification_preference_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS SystemAnnouncement (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    audience ENUM('All', 'Student', 'Lecturer', 'Coordinator', 'Administrator',
        'Restaurant Owner', 'STAD Staff') NOT NULL DEFAULT 'All',
    priority ENUM('Low', 'Normal', 'High', 'Critical') NOT NULL DEFAULT 'Normal',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_by BIGINT UNSIGNED NOT NULL,
    published_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_system_announcement_status (status),
    KEY idx_system_announcement_audience (audience),

    CONSTRAINT fk_system_announcement_published_by
        FOREIGN KEY (published_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
