-- Academic Module Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Faculty (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    dean_name VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_faculty_name (name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Department (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    faculty_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_department_faculty_name (faculty_id, name),
    KEY idx_department_faculty (faculty_id),

    CONSTRAINT fk_department_faculty
        FOREIGN KEY (faculty_id) REFERENCES Faculty (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Program (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    degree VARCHAR(100) NOT NULL,
    required_credit_hours SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_program_department_name (department_id, name),
    KEY idx_program_department (department_id),

    CONSTRAINT fk_program_department
        FOREIGN KEY (department_id) REFERENCES Department (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_program_credit_hours
        CHECK (required_credit_hours > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Semester (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_start DATETIME NULL,
    registration_end DATETIME NULL,
    current_semester BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('upcoming', 'active', 'closed') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_semester_name_year (name, academic_year),
    KEY idx_semester_current (current_semester),
    KEY idx_semester_status (status),

    CONSTRAINT chk_semester_dates
        CHECK (start_date < end_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Course (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id BIGINT UNSIGNED NOT NULL,
    program_id BIGINT UNSIGNED NULL,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    credit_hours TINYINT UNSIGNED NOT NULL,
    course_type ENUM('Core', 'Elective', 'University Requirement', 'Faculty Requirement') NOT NULL,
    level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    course_status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_course_code (course_code),
    KEY idx_course_department (department_id),
    KEY idx_course_program (program_id),
    KEY idx_course_status (course_status),

    CONSTRAINT fk_course_department
        FOREIGN KEY (department_id) REFERENCES Department (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_course_program
        FOREIGN KEY (program_id) REFERENCES Program (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_course_credit_hours
        CHECK (credit_hours > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS CoursePrerequisite (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    prerequisite_course_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_course_prerequisite (course_id, prerequisite_course_id),
    KEY idx_prerequisite_course (prerequisite_course_id),

    CONSTRAINT fk_prerequisite_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_prerequisite_required_course
        FOREIGN KEY (prerequisite_course_id) REFERENCES Course (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Lecturer (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    faculty_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    office VARCHAR(100) NULL,
    specialization VARCHAR(255) NULL,
    employment_status ENUM('full_time', 'part_time', 'visiting') NOT NULL DEFAULT 'full_time',
    hire_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_lecturer_user (user_id),
    KEY idx_lecturer_department (department_id),
    KEY idx_lecturer_faculty (faculty_id),

    CONSTRAINT fk_lecturer_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_lecturer_faculty
        FOREIGN KEY (faculty_id) REFERENCES Faculty (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_lecturer_department
        FOREIGN KEY (department_id) REFERENCES Department (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Coordinator (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lecturer_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    assigned_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_coordinator_lecturer_department (lecturer_id, department_id),
    KEY idx_coordinator_department (department_id),

    CONSTRAINT fk_coordinator_lecturer
        FOREIGN KEY (lecturer_id) REFERENCES Lecturer (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_coordinator_department
        FOREIGN KEY (department_id) REFERENCES Department (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Student (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    faculty_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    program_id BIGINT UNSIGNED NOT NULL,
    advisor_id BIGINT UNSIGNED NULL,
    current_semester_id BIGINT UNSIGNED NULL,
    study_mode ENUM('full_time', 'part_time') NOT NULL DEFAULT 'full_time',
    academic_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    admission_date DATE NOT NULL,
    expected_graduation_date DATE NULL,
    academic_status ENUM('active', 'suspended', 'graduated', 'withdrawn', 'deferred')
        NOT NULL DEFAULT 'active',
    total_credit_hours SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_credit_hours SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    current_gpa DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    cumulative_gpa DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_student_user (user_id),
    UNIQUE KEY uq_student_number (student_number),
    KEY idx_student_program (program_id),
    KEY idx_student_department (department_id),
    KEY idx_student_advisor (advisor_id),
    KEY idx_student_status (academic_status),

    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_student_faculty
        FOREIGN KEY (faculty_id) REFERENCES Faculty (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_student_department
        FOREIGN KEY (department_id) REFERENCES Department (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_student_program
        FOREIGN KEY (program_id) REFERENCES Program (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_student_advisor
        FOREIGN KEY (advisor_id) REFERENCES Lecturer (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_student_current_semester
        FOREIGN KEY (current_semester_id) REFERENCES Semester (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_student_gpa
        CHECK (current_gpa BETWEEN 0.00 AND 4.00 AND cumulative_gpa BETWEEN 0.00 AND 4.00)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Section (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    lecturer_id BIGINT UNSIGNED NOT NULL,
    semester_id BIGINT UNSIGNED NOT NULL,
    section_number VARCHAR(20) NOT NULL,
    classroom VARCHAR(50) NULL,
    building VARCHAR(100) NULL,
    delivery_mode ENUM('Physical', 'Online', 'Hybrid') NOT NULL DEFAULT 'Physical',
    capacity SMALLINT UNSIGNED NOT NULL,
    registered_students SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    waiting_list SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('open', 'closed', 'cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_section_course_semester_number (course_id, semester_id, section_number),
    KEY idx_section_semester (semester_id),
    KEY idx_section_lecturer (lecturer_id),
    KEY idx_section_status (status),

    CONSTRAINT fk_section_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_section_lecturer
        FOREIGN KEY (lecturer_id) REFERENCES Lecturer (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_section_semester
        FOREIGN KEY (semester_id) REFERENCES Semester (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_section_capacity
        CHECK (capacity > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ClassSchedule (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id BIGINT UNSIGNED NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_schedule_section (section_id),
    KEY idx_schedule_day (day_of_week),

    CONSTRAINT fk_schedule_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_schedule_times
        CHECK (start_time < end_time)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Enrollment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    registration_date DATETIME NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    enrollment_status ENUM('Pending', 'Approved', 'Rejected', 'Dropped', 'Withdrawn', 'Completed')
        NOT NULL DEFAULT 'Pending',
    final_grade VARCHAR(5) NULL,
    grade_points DECIMAL(3,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_enrollment_student_section (student_id, section_id),
    KEY idx_enrollment_student (student_id),
    KEY idx_enrollment_section (section_id),
    KEY idx_enrollment_status (enrollment_status),

    CONSTRAINT fk_enrollment_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_enrollment_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_enrollment_approver
        FOREIGN KEY (approved_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Transcript (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    semester_id BIGINT UNSIGNED NOT NULL,
    grade VARCHAR(5) NOT NULL,
    grade_points DECIMAL(3,2) NOT NULL,
    earned_credit_hours TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_transcript_student_course_semester (student_id, course_id, semester_id),
    KEY idx_transcript_student (student_id),
    KEY idx_transcript_semester (semester_id),

    CONSTRAINT fk_transcript_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_transcript_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_transcript_semester
        FOREIGN KEY (semester_id) REFERENCES Semester (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
