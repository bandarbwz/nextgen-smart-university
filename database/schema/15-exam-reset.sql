-- Reset Examination Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/21-Reset-Exam.md.
--
-- A reset is how a student who was cut off, whether by a technical failure or
-- by the proctor terminating the session, gets a second sitting. Approval marks
-- the original submission as reset rather than deleting it, so the first
-- attempt stays on record and the audit trail survives.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS ExamResetRequest (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    request_reason TEXT NOT NULL,
    request_date DATETIME NOT NULL,
    approval_status ENUM('Pending', 'Recommended', 'Approved', 'Rejected', 'Completed')
        NOT NULL DEFAULT 'Pending',
    reviewed_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    completed_at DATETIME NULL,
    remarks VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_exam_reset_student (student_id),
    KEY idx_exam_reset_exam (exam_id),
    KEY idx_exam_reset_status (approval_status),

    CONSTRAINT fk_exam_reset_exam
        FOREIGN KEY (exam_id) REFERENCES Exam (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_session
        FOREIGN KEY (session_id) REFERENCES ExamSession (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_requested_by
        FOREIGN KEY (requested_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_approved_by
        FOREIGN KEY (approved_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- Append only, like the grade approval log. A reset that could be quietly
-- erased afterwards would be worse than no audit trail at all.
CREATE TABLE IF NOT EXISTS ExamResetLog (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reset_request_id BIGINT UNSIGNED NOT NULL,
    action ENUM('Requested', 'Recommended', 'Approved', 'Rejected', 'Reset Completed')
        NOT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    remarks VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_exam_reset_log_request (reset_request_id),

    CONSTRAINT fk_exam_reset_log_request
        FOREIGN KEY (reset_request_id) REFERENCES ExamResetRequest (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_exam_reset_log_performed_by
        FOREIGN KEY (performed_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
