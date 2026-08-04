-- Assessment System Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/18-Assessment-System.md.
--
-- This module is the grading scheme that sits above the LMS. An Assessment is
-- a weighted component of a course section, such as Midterm at 30 per cent.
-- AssessmentResult is one student's marks against that component, and the
-- weighted total across a section's assessments produces the course result.
--
-- The existing Grade table in the Academic module records individual graded
-- items with no weighting. It is left alone. This module adds the weighting
-- and the course level calculation that Grade never had.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Assessment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    assessment_type ENUM('Assignment', 'Quiz', 'Midterm', 'Final', 'Project', 'Participation')
        NOT NULL,
    total_marks DECIMAL(6,2) NOT NULL,
    weight_percentage DECIMAL(5,2) NOT NULL,
    due_date DATETIME NULL,
    status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_assessment_section (section_id),
    KEY idx_assessment_status (status),

    CONSTRAINT fk_assessment_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_assessment_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_assessment_total_marks
        CHECK (total_marks > 0),

    CONSTRAINT chk_assessment_weight
        CHECK (weight_percentage >= 0 AND weight_percentage <= 100)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AssessmentRubric (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    assessment_id BIGINT UNSIGNED NOT NULL,
    criterion VARCHAR(200) NOT NULL,
    description TEXT NULL,
    maximum_marks DECIMAL(6,2) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_assessment_rubric_assessment (assessment_id),

    CONSTRAINT fk_assessment_rubric_assessment
        FOREIGN KEY (assessment_id) REFERENCES Assessment (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_assessment_rubric_marks
        CHECK (maximum_marks > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AssessmentResult (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    assessment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    marks DECIMAL(6,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    grade VARCHAR(5) NULL,
    feedback TEXT NULL,
    graded_by BIGINT UNSIGNED NOT NULL,
    graded_at DATETIME NOT NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_result (assessment_id, student_id),
    KEY idx_assessment_result_assessment (assessment_id),
    KEY idx_assessment_result_student (student_id),

    CONSTRAINT fk_assessment_result_assessment
        FOREIGN KEY (assessment_id) REFERENCES Assessment (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_assessment_result_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_assessment_result_graded_by
        FOREIGN KEY (graded_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_assessment_result_marks
        CHECK (marks >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
