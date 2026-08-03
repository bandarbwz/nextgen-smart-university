-- Calendar Module Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS CalendarEvent (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_type ENUM(
        'Class', 'Assignment', 'Quiz', 'Examination', 'Meeting',
        'Student Activity', 'Food Order Pickup', 'Payment Deadline',
        'Holiday', 'Personal Event'
    ) NOT NULL DEFAULT 'Personal Event',
    module VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    location VARCHAR(255) NULL,
    color VARCHAR(20) NULL,
    is_all_day BOOLEAN NOT NULL DEFAULT FALSE,
    reminder_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_event_source (user_id, module, reference_id, start_datetime),
    KEY idx_event_user (user_id),
    KEY idx_event_type (event_type),
    KEY idx_event_module (module),
    KEY idx_event_start (start_datetime),
    KEY idx_event_end (end_datetime),

    CONSTRAINT fk_event_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Reminder (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    calendar_event_id BIGINT UNSIGNED NOT NULL,
    reminder_time DATETIME NOT NULL,
    reminder_method ENUM('In-App Notification', 'Email', 'Push Notification')
        NOT NULL DEFAULT 'In-App Notification',
    reminder_status ENUM('pending', 'sent', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_reminder_event (calendar_event_id),
    KEY idx_reminder_time (reminder_time),
    KEY idx_reminder_status (reminder_status),

    CONSTRAINT fk_reminder_event
        FOREIGN KEY (calendar_event_id) REFERENCES CalendarEvent (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
