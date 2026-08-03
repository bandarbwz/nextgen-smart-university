-- Learning Management System Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS CourseMaterial (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    lecturer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    original_name VARCHAR(255) NOT NULL,
    visibility ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
    upload_date DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_material_section (section_id),
    KEY idx_material_course (course_id),

    CONSTRAINT fk_material_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_material_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_material_lecturer
        FOREIGN KEY (lecturer_id) REFERENCES Lecturer (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Assignment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_marks DECIMAL(6,2) NOT NULL,
    due_date DATETIME NOT NULL,
    allow_late_submission BOOLEAN NOT NULL DEFAULT FALSE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_assignment_section (section_id),
    KEY idx_assignment_due (due_date),

    CONSTRAINT fk_assignment_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_assignment_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_assignment_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_assignment_total_marks
        CHECK (total_marks > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AssignmentSubmission (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NULL,
    original_name VARCHAR(255) NULL,
    comment TEXT NULL,
    submitted_at DATETIME NOT NULL,
    submission_status ENUM('Submitted', 'Late', 'Missing', 'Graded') NOT NULL DEFAULT 'Submitted',
    marks DECIMAL(6,2) NULL,
    feedback TEXT NULL,
    graded_by BIGINT UNSIGNED NULL,
    graded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_submission_assignment_student (assignment_id, student_id),
    KEY idx_submission_assignment (assignment_id),
    KEY idx_submission_student (student_id),

    CONSTRAINT fk_submission_assignment
        FOREIGN KEY (assignment_id) REFERENCES Assignment (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_submission_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_submission_graded_by
        FOREIGN KEY (graded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Quiz (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    duration SMALLINT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_quiz_section (section_id),

    CONSTRAINT fk_quiz_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_quiz_duration
        CHECK (duration > 0),

    CONSTRAINT chk_quiz_period
        CHECK (start_time < end_time)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS QuizQuestion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    question_type ENUM('Multiple Choice', 'True / False', 'Short Answer', 'Essay') NOT NULL,
    marks DECIMAL(6,2) NOT NULL,
    correct_answer VARCHAR(500) NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_question_quiz (quiz_id),

    CONSTRAINT fk_question_quiz
        FOREIGN KEY (quiz_id) REFERENCES Quiz (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_question_marks
        CHECK (marks > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS QuizOption (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id BIGINT UNSIGNED NOT NULL,
    option_label VARCHAR(10) NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_option_question_label (question_id, option_label),
    KEY idx_option_question (question_id),

    CONSTRAINT fk_option_question
        FOREIGN KEY (question_id) REFERENCES QuizQuestion (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS QuizSubmission (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    score DECIMAL(6,2) NULL,
    auto_scored_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    status ENUM('Submitted', 'Graded') NOT NULL DEFAULT 'Submitted',
    submitted_at DATETIME NOT NULL,
    graded_by BIGINT UNSIGNED NULL,
    graded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_quiz_submission_attempt (quiz_id, student_id, attempt_number),
    KEY idx_quiz_submission_quiz (quiz_id),
    KEY idx_quiz_submission_student (student_id),

    CONSTRAINT fk_quiz_submission_quiz
        FOREIGN KEY (quiz_id) REFERENCES Quiz (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_submission_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_submission_graded_by
        FOREIGN KEY (graded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS QuizAnswer (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    awarded_marks DECIMAL(6,2) NULL,
    is_correct BOOLEAN NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_answer_submission_question (submission_id, question_id),
    KEY idx_answer_question (question_id),

    CONSTRAINT fk_answer_submission
        FOREIGN KEY (submission_id) REFERENCES QuizSubmission (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_answer_question
        FOREIGN KEY (question_id) REFERENCES QuizQuestion (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Grade (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    assessment_type ENUM('Assignment', 'Quiz', 'Midterm', 'Final', 'Project', 'Other') NOT NULL,
    assessment_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    marks DECIMAL(6,2) NOT NULL,
    total_marks DECIMAL(6,2) NOT NULL,
    grade_letter VARCHAR(5) NULL,
    grade_points DECIMAL(3,2) NULL,
    published_at DATETIME NULL,
    published_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_grade_student_assessment (student_id, section_id, assessment_type, assessment_id),
    KEY idx_grade_student (student_id),
    KEY idx_grade_section (section_id),

    CONSTRAINT fk_grade_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_grade_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_grade_published_by
        FOREIGN KEY (published_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_grade_marks
        CHECK (marks >= 0 AND total_marks > 0 AND marks <= total_marks)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Announcement (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    lecturer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    published_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_announcement_section (section_id),
    KEY idx_announcement_published (published_at),

    CONSTRAINT fk_announcement_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_announcement_lecturer
        FOREIGN KEY (lecturer_id) REFERENCES Lecturer (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Resource (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    link VARCHAR(500) NOT NULL,
    resource_type ENUM('PDF', 'Video', 'Website', 'Document', 'External Link') NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_resource_section (section_id),

    CONSTRAINT fk_resource_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_resource_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
