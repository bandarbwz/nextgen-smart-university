-- Grade Approval Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/20-Grade-Approval.md.
--
-- This module is the gate between a lecturer finishing marking and the grades
-- becoming part of a student's official record. Approval is what finally
-- writes Transcript rows, which until now nothing in the platform ever did,
-- leaving every GPA at zero.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS GradeApproval (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    lecturer_id BIGINT UNSIGNED NOT NULL,
    coordinator_id BIGINT UNSIGNED NULL,
    submitted_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    published_at DATETIME NULL,
    approval_status ENUM('Pending', 'Approved', 'Rejected', 'Returned for Revision')
        NOT NULL DEFAULT 'Pending',
    remarks VARCHAR(500) NULL,
    student_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_grade_approval_section (section_id),
    KEY idx_grade_approval_status (approval_status),

    CONSTRAINT fk_grade_approval_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_grade_approval_lecturer
        FOREIGN KEY (lecturer_id) REFERENCES Lecturer (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_grade_approval_coordinator
        FOREIGN KEY (coordinator_id) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- Append only. Rows are never updated or deleted, because the point of the
-- log is that it cannot be tidied up after the fact.
CREATE TABLE IF NOT EXISTS GradeApprovalLog (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    grade_approval_id BIGINT UNSIGNED NOT NULL,
    action ENUM('Submitted', 'Approved', 'Rejected', 'Returned for Revision', 'Resubmitted',
        'Published') NOT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    remarks VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_grade_approval_log_approval (grade_approval_id),

    CONSTRAINT fk_grade_approval_log_approval
        FOREIGN KEY (grade_approval_id) REFERENCES GradeApproval (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_grade_approval_log_performed_by
        FOREIGN KEY (performed_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
