-- Attendance Module Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS QRSession (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    qr_token VARCHAR(64) NOT NULL,
    session_date DATE NOT NULL,
    generated_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    allowed_radius SMALLINT UNSIGNED NOT NULL DEFAULT 150,
    status ENUM('active', 'closed', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_qr_session_token (qr_token),
    KEY idx_qr_session_section (section_id),
    KEY idx_qr_session_status (status),

    CONSTRAINT fk_qr_session_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_qr_session_opened_by
        FOREIGN KEY (opened_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    qr_session_id BIGINT UNSIGNED NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    attendance_status ENUM('Present', 'Late', 'Absent', 'Excused', 'Online', 'Pending')
        NOT NULL DEFAULT 'Present',
    attendance_method ENUM('QR', 'GPS', 'Face', 'Manual', 'Online') NOT NULL DEFAULT 'QR',
    verified_by BIGINT UNSIGNED NULL,
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_student_section_date (student_id, section_id, attendance_date),
    KEY idx_attendance_student (student_id),
    KEY idx_attendance_section (section_id),
    KEY idx_attendance_date (attendance_date),
    KEY idx_attendance_status (attendance_status),

    CONSTRAINT fk_attendance_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_qr_session
        FOREIGN KEY (qr_session_id) REFERENCES QRSession (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_verified_by
        FOREIGN KEY (verified_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AttendanceExcuse (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    attendance_id BIGINT UNSIGNED NOT NULL,
    excuse_type ENUM('Medical', 'Family', 'Official', 'Other') NOT NULL,
    reason VARCHAR(500) NOT NULL,
    document_path VARCHAR(255) NULL,
    approved_by BIGINT UNSIGNED NULL,
    approval_date DATETIME NULL,
    review_note VARCHAR(255) NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_excuse_attendance (attendance_id),
    KEY idx_excuse_student (student_id),
    KEY idx_excuse_status (status),

    CONSTRAINT fk_excuse_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_excuse_attendance
        FOREIGN KEY (attendance_id) REFERENCES Attendance (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_excuse_approved_by
        FOREIGN KEY (approved_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
