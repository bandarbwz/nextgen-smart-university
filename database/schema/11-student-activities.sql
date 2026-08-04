-- Student Activities Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/06-Student-Activities.md and the API contract in
-- docs/API/06-Student-Activities-API.md.
--
-- EventQrSession is not in the feature document. Event attendance is documented
-- as QR based with a token that expires, and there was nowhere to record the
-- token, so it mirrors QRSession from the Attendance module.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Club (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    club_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    category VARCHAR(80) NULL,
    advisor_id BIGINT UNSIGNED NULL,
    president_id BIGINT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_club_name (club_name),
    KEY idx_club_category (category),

    CONSTRAINT fk_club_advisor
        FOREIGN KEY (advisor_id) REFERENCES Lecturer (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_club_president
        FOREIGN KEY (president_id) REFERENCES Student (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Event (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    club_id BIGINT UNSIGNED NULL,
    event_name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    event_type ENUM('Event', 'Competition', 'Workshop', 'Seminar', 'Volunteering')
        NOT NULL DEFAULT 'Event',
    venue VARCHAR(150) NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    registration_deadline DATETIME NOT NULL,
    maximum_participants SMALLINT UNSIGNED NOT NULL,
    award_points SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    qr_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    status ENUM('draft', 'published', 'cancelled', 'completed') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_event_date (event_date),
    KEY idx_event_club (club_id),
    KEY idx_event_status (status),

    CONSTRAINT fk_event_club
        FOREIGN KEY (club_id) REFERENCES Club (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_event_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_event_participants
        CHECK (maximum_participants > 0),

    CONSTRAINT chk_event_times
        CHECK (start_time < end_time)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS EventRegistration (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    registration_date DATETIME NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending',
    decision_reason VARCHAR(255) NULL,
    decided_by BIGINT UNSIGNED NULL,
    decided_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_event_registration (event_id, student_id),
    KEY idx_event_registration_student (student_id),
    KEY idx_event_registration_event (event_id),
    KEY idx_event_registration_status (status),

    CONSTRAINT fk_event_registration_event
        FOREIGN KEY (event_id) REFERENCES Event (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_event_registration_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_event_registration_decided_by
        FOREIGN KEY (decided_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS EventQrSession (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    qr_token VARCHAR(64) NOT NULL,
    generated_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    status ENUM('active', 'closed', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_event_qr_token (qr_token),
    KEY idx_event_qr_event (event_id),
    KEY idx_event_qr_status (status),

    CONSTRAINT fk_event_qr_event
        FOREIGN KEY (event_id) REFERENCES Event (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_event_qr_opened_by
        FOREIGN KEY (opened_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS EventAttendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_id BIGINT UNSIGNED NOT NULL,
    attendance_time DATETIME NOT NULL,
    attendance_method ENUM('QR', 'Manual') NOT NULL DEFAULT 'QR',
    verified_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_event_attendance_registration (registration_id),

    CONSTRAINT fk_event_attendance_registration
        FOREIGN KEY (registration_id) REFERENCES EventRegistration (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_event_attendance_verified_by
        FOREIGN KEY (verified_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ActivityPoint (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    points SMALLINT UNSIGNED NOT NULL,
    awarded_date DATE NOT NULL,
    awarded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_activity_point (student_id, event_id),
    KEY idx_activity_point_student (student_id),

    CONSTRAINT fk_activity_point_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_activity_point_event
        FOREIGN KEY (event_id) REFERENCES Event (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_activity_point_awarded_by
        FOREIGN KEY (awarded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_activity_point_points
        CHECK (points > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
