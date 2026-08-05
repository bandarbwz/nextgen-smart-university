-- Demonstration data
-- NextGen Smart University Platform
--
-- The platform was verified module by module, which left one student, four
-- enrolments and three attendance rows. That is enough to prove a rule works
-- and far too little to show the system in use. This adds a cohort so the
-- interface, the reports and the exports all have something real to display.
--
-- Nothing here is required by the application or by the tests. It exists so a
-- demonstration and a screenshot show a working university rather than an
-- empty one.
--
-- Insert only. Every statement checks first and never updates an existing row,
-- so running it twice changes nothing. The password for every account is
-- Password123!.

USE nextgen_university;


-- ---------------------------------------------------------------- students --

INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified)
SELECT
    r.id,
    cohort.full_name,
    cohort.student_number,
    cohort.email,
    '$2y$12$SM035BgYaiapS0y3OfpbQO5FsUKPx.t/.VCpIvsyZGgjgsRmHZE1i',
    'active',
    TRUE
FROM Role r
JOIN (
    SELECT 'Nur Aisyah Binti Hassan' AS full_name, 'STU002' AS student_number, 'nur.aisyah@student.nextgen.edu' AS email UNION ALL
    SELECT 'Muhammad Iqbal Bin Yusof',  'STU003', 'iqbal.yusof@student.nextgen.edu'   UNION ALL
    SELECT 'Tan Wei Ling',              'STU004', 'tan.weiling@student.nextgen.edu'   UNION ALL
    SELECT 'Arjun Subramaniam',         'STU005', 'arjun.s@student.nextgen.edu'       UNION ALL
    SELECT 'Siti Nurhaliza Binti Omar', 'STU006', 'siti.omar@student.nextgen.edu'     UNION ALL
    SELECT 'Lim Jia Hao',               'STU007', 'lim.jiahao@student.nextgen.edu'    UNION ALL
    SELECT 'Amirah Binti Zulkifli',     'STU008', 'amirah.z@student.nextgen.edu'      UNION ALL
    SELECT 'Rajesh Kumar',              'STU009', 'rajesh.kumar@student.nextgen.edu'  UNION ALL
    SELECT 'Chong Mei Yee',             'STU010', 'chong.meiyee@student.nextgen.edu'  UNION ALL
    SELECT 'Danial Haziq Bin Rosli',    'STU011', 'danial.haziq@student.nextgen.edu'
) AS cohort
WHERE r.name = 'Student'
  AND NOT EXISTS (SELECT 1 FROM User u WHERE u.email = cohort.email)
  AND NOT EXISTS (SELECT 1 FROM User u WHERE u.university_id = cohort.student_number);


INSERT INTO Student (
    user_id, student_number, faculty_id, department_id, program_id,
    current_semester_id, study_mode, academic_level, admission_date,
    expected_graduation_date, academic_status, total_credit_hours,
    completed_credit_hours, current_gpa, cumulative_gpa
)
SELECT
    u.id,
    u.university_id,
    d.faculty_id,
    d.id,
    p.id,
    (SELECT id FROM Semester WHERE status = 'active' LIMIT 1),
    'full_time',
    cohort.academic_level,
    cohort.admission_date,
    DATE_ADD(cohort.admission_date, INTERVAL 4 YEAR),
    'active',
    cohort.completed,
    cohort.completed,
    cohort.gpa,
    cohort.gpa
FROM (
    SELECT 'STU002' AS student_number, 'Bachelor of Computer Science' AS programme, 2 AS academic_level, '2025-09-01' AS admission_date, 30 AS completed, 3.72 AS gpa UNION ALL
    SELECT 'STU003', 'Bachelor of Computer Science',     2, '2025-09-01', 27, 3.15 UNION ALL
    SELECT 'STU004', 'Bachelor of Software Engineering', 2, '2025-09-01', 33, 3.88 UNION ALL
    SELECT 'STU005', 'Bachelor of Computer Science',     1, '2026-02-01', 15, 2.94 UNION ALL
    SELECT 'STU006', 'Bachelor of Software Engineering', 3, '2024-09-01', 62, 3.45 UNION ALL
    SELECT 'STU007', 'Bachelor of Computer Science',     1, '2026-02-01', 12, 2.40 UNION ALL
    SELECT 'STU008', 'Bachelor of Artificial Intelligence', 2, '2025-09-01', 29, 3.61 UNION ALL
    SELECT 'STU009', 'Bachelor of Computer Science',     3, '2024-09-01', 58, 3.02 UNION ALL
    SELECT 'STU010', 'Bachelor of Software Engineering', 1, '2026-02-01', 14, 3.33 UNION ALL
    SELECT 'STU011', 'Bachelor of Computer Science',     2, '2025-09-01', 26, 2.77
) AS cohort
JOIN User u ON u.university_id = cohort.student_number
JOIN Program p ON p.name = cohort.programme
JOIN Department d ON d.id = p.department_id
WHERE NOT EXISTS (SELECT 1 FROM Student s WHERE s.user_id = u.id);


-- ------------------------------------------------------------- enrolments --
-- Everybody takes CS101. The upper level students add the courses that follow
-- from it, so the section lists are not identical.

INSERT INTO Enrollment (student_id, section_id, registration_date, approved_by, approved_at, enrollment_status)
SELECT
    s.id,
    sec.id,
    '2026-08-01 09:00:00',
    (SELECT id FROM User WHERE email = 'coordinator@nextgen.edu'),
    '2026-08-01 14:30:00',
    'Approved'
FROM Student s
JOIN Section sec ON sec.id IN (1)
WHERE s.student_number <> 'STU001'
  AND NOT EXISTS (SELECT 1 FROM Enrollment e WHERE e.student_id = s.id AND e.section_id = sec.id);

INSERT INTO Enrollment (student_id, section_id, registration_date, approved_by, approved_at, enrollment_status)
SELECT
    s.id,
    sec.id,
    '2026-08-01 09:12:00',
    (SELECT id FROM User WHERE email = 'coordinator@nextgen.edu'),
    '2026-08-01 14:30:00',
    'Approved'
FROM Student s
JOIN Section sec ON sec.id = 5
WHERE s.student_number IN ('STU002', 'STU003', 'STU004', 'STU006', 'STU008', 'STU009', 'STU011')
  AND NOT EXISTS (SELECT 1 FROM Enrollment e WHERE e.student_id = s.id AND e.section_id = sec.id);

INSERT INTO Enrollment (student_id, section_id, registration_date, approved_by, approved_at, enrollment_status)
SELECT
    s.id,
    sec.id,
    '2026-08-01 09:20:00',
    (SELECT id FROM User WHERE email = 'coordinator@nextgen.edu'),
    '2026-08-01 14:30:00',
    'Approved'
FROM Student s
JOIN Section sec ON sec.id = 4
WHERE s.student_number IN ('STU004', 'STU006', 'STU009')
  AND NOT EXISTS (SELECT 1 FROM Enrollment e WHERE e.student_id = s.id AND e.section_id = sec.id);

-- Two requests left waiting, so the approval queue is not empty on screen.
INSERT INTO Enrollment (student_id, section_id, registration_date, enrollment_status)
SELECT s.id, 6, '2026-08-04 11:05:00', 'Pending'
FROM Student s
WHERE s.student_number IN ('STU005', 'STU010')
  AND NOT EXISTS (SELECT 1 FROM Enrollment e WHERE e.student_id = s.id AND e.section_id = 6);


UPDATE Section sec
SET registered_students = (
    SELECT COUNT(*) FROM Enrollment e
    WHERE e.section_id = sec.id AND e.enrollment_status IN ('Approved', 'Completed')
);


-- ------------------------------------------------------------- attendance --
-- Four teaching days for CS101. Most students attend, a few are late and one
-- is absent, which is what an attendance report is supposed to reveal.

INSERT INTO Attendance (student_id, section_id, attendance_date, attendance_time, attendance_status, attendance_method, verified_by)
SELECT
    s.id,
    1,
    day.attendance_date,
    '09:05:00',
    CASE
        WHEN s.student_number = 'STU007' AND day.attendance_date IN ('2026-08-03', '2026-08-05') THEN 'Absent'
        WHEN s.student_number = 'STU005' AND day.attendance_date = '2026-08-04' THEN 'Late'
        WHEN s.student_number = 'STU011' AND day.attendance_date = '2026-08-05' THEN 'Late'
        ELSE 'Present'
    END,
    'QR',
    (SELECT id FROM User WHERE email = 'lecturer@nextgen.edu')
FROM Student s
CROSS JOIN (
    SELECT '2026-08-03' AS attendance_date UNION ALL
    SELECT '2026-08-04' UNION ALL
    SELECT '2026-08-05' UNION ALL
    SELECT '2026-08-06'
) AS day
WHERE s.student_number <> 'STU001'
  AND NOT EXISTS (
      SELECT 1 FROM Attendance a
      WHERE a.student_id = s.id AND a.section_id = 1 AND a.attendance_date = day.attendance_date
  );


-- ----------------------------------------------------------------- grades --
-- A published quiz for CS101 so the grade book and the grade distribution
-- report have a spread to show rather than a single mark.

INSERT INTO Grade (student_id, section_id, assessment_type, title, marks, total_marks, grade_letter, grade_points, published_at, published_by)
SELECT
    s.id,
    1,
    'Quiz',
    'Quiz 1 - Variables and Control Flow',
    result.marks,
    20.00,
    result.letter,
    result.points,
    '2026-08-06 16:00:00',
    (SELECT id FROM User WHERE email = 'lecturer@nextgen.edu')
FROM (
    SELECT 'STU002' AS student_number, 18.00 AS marks, 'A'  AS letter, 4.00 AS points UNION ALL
    SELECT 'STU003', 14.50, 'B',  3.00 UNION ALL
    SELECT 'STU004', 19.50, 'A',  4.00 UNION ALL
    SELECT 'STU005', 12.00, 'C+', 2.30 UNION ALL
    SELECT 'STU006', 17.00, 'A-', 3.70 UNION ALL
    SELECT 'STU007',  9.50, 'C',  2.00 UNION ALL
    SELECT 'STU008', 16.50, 'B+', 3.30 UNION ALL
    SELECT 'STU009', 15.00, 'B',  3.00 UNION ALL
    SELECT 'STU010', 17.50, 'A-', 3.70 UNION ALL
    SELECT 'STU011', 11.00, 'C+', 2.30
) AS result
JOIN Student s ON s.student_number = result.student_number
WHERE NOT EXISTS (
    SELECT 1 FROM Grade g
    WHERE g.student_id = s.id AND g.section_id = 1 AND g.title = 'Quiz 1 - Variables and Control Flow'
);


-- --------------------------------------------------------------- invoices --
-- Tuition for the active semester. Some paid in full, some part paid, two
-- outstanding, so the finance report and the outstanding balance list both
-- have something to say.

INSERT INTO Invoice (student_id, semester_id, invoice_number, gross_amount, scholarship_amount, total_amount, paid_amount, balance, due_date, status, issued_by)
SELECT
    s.id,
    (SELECT id FROM Semester WHERE status = 'active' LIMIT 1),
    CONCAT('INV-2026-', s.student_number),
    billing.gross,
    billing.scholarship,
    billing.gross - billing.scholarship,
    billing.paid,
    billing.gross - billing.scholarship - billing.paid,
    '2026-09-15',
    CASE
        WHEN billing.paid = 0 THEN 'Pending'
        WHEN billing.paid >= billing.gross - billing.scholarship THEN 'Paid'
        ELSE 'Partially Paid'
    END,
    (SELECT id FROM User WHERE email = 'admin@nextgen.edu')
FROM (
    SELECT 'STU002' AS student_number, 4798.92 AS gross, 1000.00 AS scholarship, 3798.92 AS paid UNION ALL
    SELECT 'STU003', 4798.92,    0.00, 2400.00 UNION ALL
    SELECT 'STU004', 4798.92, 1500.00, 3298.92 UNION ALL
    SELECT 'STU005', 4798.92,    0.00,    0.00 UNION ALL
    SELECT 'STU006', 4798.92,  500.00, 4298.92 UNION ALL
    SELECT 'STU007', 4798.92,    0.00,    0.00 UNION ALL
    SELECT 'STU008', 4798.92, 2000.00, 2798.92 UNION ALL
    SELECT 'STU009', 4798.92,    0.00, 1200.00 UNION ALL
    SELECT 'STU010', 4798.92,    0.00, 4798.92 UNION ALL
    SELECT 'STU011', 4798.92,    0.00, 2000.00
) AS billing
JOIN Student s ON s.student_number = billing.student_number
WHERE NOT EXISTS (
    SELECT 1 FROM Invoice i WHERE i.invoice_number = CONCAT('INV-2026-', s.student_number)
);
