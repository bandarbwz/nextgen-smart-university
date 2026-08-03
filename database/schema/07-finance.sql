-- Finance Module Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS TuitionFee (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    program_id BIGINT UNSIGNED NOT NULL,
    semester_id BIGINT UNSIGNED NOT NULL,
    fee_type ENUM('Tuition', 'Registration', 'Laboratory', 'Library', 'Examination', 'Other')
        NOT NULL DEFAULT 'Tuition',
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_fee_program_semester_type (program_id, semester_id, fee_type),
    KEY idx_fee_program (program_id),
    KEY idx_fee_semester (semester_id),

    CONSTRAINT fk_fee_program
        FOREIGN KEY (program_id) REFERENCES Program (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_fee_semester
        FOREIGN KEY (semester_id) REFERENCES Semester (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_fee_amount
        CHECK (amount > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Scholarship (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    scholarship_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
    awarded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_scholarship_student (student_id),
    KEY idx_scholarship_status (status),

    CONSTRAINT fk_scholarship_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_scholarship_awarded_by
        FOREIGN KEY (awarded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_scholarship_amount
        CHECK (amount > 0),

    CONSTRAINT chk_scholarship_dates
        CHECK (start_date < end_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Invoice (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    semester_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    gross_amount DECIMAL(10,2) NOT NULL,
    scholarship_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled')
        NOT NULL DEFAULT 'Pending',
    issued_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_invoice_number (invoice_number),
    UNIQUE KEY uq_invoice_student_semester (student_id, semester_id),
    KEY idx_invoice_student (student_id),
    KEY idx_invoice_status (status),
    KEY idx_invoice_due (due_date),

    CONSTRAINT fk_invoice_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_invoice_semester
        FOREIGN KEY (semester_id) REFERENCES Semester (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_invoice_issued_by
        FOREIGN KEY (issued_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_invoice_amounts
        CHECK (total_amount >= 0 AND paid_amount >= 0 AND paid_amount <= total_amount)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Payment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_reference VARCHAR(100) NOT NULL,
    payment_method ENUM('Cash', 'Online Banking', 'Credit Card', 'Debit Card', 'E-Wallet')
        NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME NOT NULL,
    payment_status ENUM('completed', 'pending', 'failed') NOT NULL DEFAULT 'completed',
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_reference (payment_reference),
    KEY idx_payment_invoice (invoice_id),
    KEY idx_payment_date (payment_date),

    CONSTRAINT fk_payment_invoice
        FOREIGN KEY (invoice_id) REFERENCES Invoice (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_payment_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_payment_amount
        CHECK (amount > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS FinancialHold (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL,
    applied_date DATETIME NOT NULL,
    released_date DATETIME NULL,
    status ENUM('active', 'released') NOT NULL DEFAULT 'active',
    applied_by BIGINT UNSIGNED NULL,
    released_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_hold_student (student_id),
    KEY idx_hold_status (status),

    CONSTRAINT fk_hold_student
        FOREIGN KEY (student_id) REFERENCES Student (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_hold_applied_by
        FOREIGN KEY (applied_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_hold_released_by
        FOREIGN KEY (released_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
