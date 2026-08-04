-- AI Examination Module Schema
-- NextGen Smart University Platform
--
-- The two documents disagree on this module. docs/FEATURES/08-AI-Exam.md
-- defines Exam, ExamQuestion, ExamSubmission, ExamSession and AIViolation with
-- columns. docs/DATABASE/01-Tables.md instead lists Examination,
-- ExaminationSession, AIViolation, FaceDetection, EyeTracking, HeadPose,
-- BrowserActivity, ExamRecording and AIReport. The API specification has
-- endpoints for both sets, so the union is implemented here using the FEATURES
-- names, which are the ones that come with column definitions.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Exam (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    passing_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    duration SMALLINT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    require_camera BOOLEAN NOT NULL DEFAULT TRUE,
    status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_exam_section (section_id),
    KEY idx_exam_status (status),

    CONSTRAINT fk_exam_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_exam_duration
        CHECK (duration > 0),

    CONSTRAINT chk_exam_period
        CHECK (start_time < end_time)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ExamQuestion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    question_type ENUM('Multiple Choice', 'True / False', 'Short Answer', 'Essay') NOT NULL,
    marks DECIMAL(6,2) NOT NULL,
    correct_answer VARCHAR(500) NULL,
    options JSON NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_exam_question_exam (exam_id),

    CONSTRAINT fk_exam_question_exam
        FOREIGN KEY (exam_id) REFERENCES Exam (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_exam_question_marks
        CHECK (marks > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ExamSession (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    session_start DATETIME NOT NULL,
    session_end DATETIME NULL,
    expires_at DATETIME NOT NULL,
    paused_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    browser VARCHAR(100) NULL,
    device VARCHAR(100) NULL,
    identity_verified BOOLEAN NOT NULL DEFAULT FALSE,
    verification_note VARCHAR(255) NULL,
    violation_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'paused', 'submitted', 'terminated', 'expired')
        NOT NULL DEFAULT 'active',
    termination_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_exam_session_exam (exam_id),
    KEY idx_exam_session_student (student_id),
    KEY idx_exam_session_status (status),

    CONSTRAINT fk_exam_session_exam
        FOREIGN KEY (exam_id) REFERENCES Exam (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_session_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ExamSubmission (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    reset_at DATETIME NULL,
    answers JSON NULL,
    auto_scored_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    score DECIMAL(6,2) NULL,
    submission_status ENUM('Submitted', 'Auto Submitted', 'Pending Review', 'Graded')
        NOT NULL DEFAULT 'Submitted',
    submitted_at DATETIME NOT NULL,
    graded_by BIGINT UNSIGNED NULL,
    graded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_exam_submission_student (exam_id, student_id, attempt_number),
    KEY idx_exam_submission_student (student_id),
    KEY idx_exam_submission_exam (exam_id),

    CONSTRAINT fk_exam_submission_exam
        FOREIGN KEY (exam_id) REFERENCES Exam (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_submission_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_exam_submission_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_submission_graded_by
        FOREIGN KEY (graded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AIViolation (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    violation_type ENUM(
        'Multiple Faces', 'Face Not Detected', 'Looking Away', 'Head Pose Warning',
        'Tab Switching', 'Fullscreen Exit', 'Camera Disabled'
    ) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'warning',
    confidence_score DECIMAL(4,3) NULL,
    evidence_path VARCHAR(255) NULL,
    detail VARCHAR(255) NULL,
    detected_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_violation_session (session_id),
    KEY idx_violation_type (violation_type),
    KEY idx_violation_detected (detected_at),

    CONSTRAINT fk_violation_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS FaceDetection (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    faces_detected TINYINT UNSIGNED NOT NULL,
    confidence_score DECIMAL(4,3) NULL,
    captured_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_face_session (session_id),

    CONSTRAINT fk_face_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS EyeTracking (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    gaze_direction ENUM('centre', 'left', 'right', 'up', 'down', 'off-screen') NOT NULL,
    off_screen_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    confidence_score DECIMAL(4,3) NULL,
    captured_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_eye_session (session_id),

    CONSTRAINT fk_eye_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS HeadPose (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    yaw DECIMAL(6,2) NOT NULL,
    pitch DECIMAL(6,2) NOT NULL,
    roll DECIMAL(6,2) NOT NULL,
    confidence_score DECIMAL(4,3) NULL,
    captured_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_head_session (session_id),

    CONSTRAINT fk_head_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS BrowserActivity (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('tab_hidden', 'tab_visible', 'fullscreen_exit', 'fullscreen_enter',
        'window_blur', 'window_focus', 'copy', 'paste') NOT NULL,
    detail VARCHAR(255) NULL,
    occurred_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_browser_session (session_id),
    KEY idx_browser_type (activity_type),

    CONSTRAINT fk_browser_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ExamRecording (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    recorded_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_recording_session (session_id),

    CONSTRAINT fk_recording_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AIReport (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    integrity_score TINYINT UNSIGNED NOT NULL,
    total_violations SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    critical_violations SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    summary TEXT NOT NULL,
    identity_verified BOOLEAN NOT NULL DEFAULT FALSE,
    generated_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ai_report_session (session_id),

    CONSTRAINT fk_ai_report_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_ai_report_score
        CHECK (integrity_score BETWEEN 0 AND 100)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
