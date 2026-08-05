-- Teaching staff and section assignments
-- NextGen Smart University Platform
--
-- The sections were all attached to one lecturer while the modules were being
-- verified, which left a single person teaching every course in the catalogue.
-- This spreads them across a small department so the timetable reads like a
-- real one.
--
-- Dr. Sami Lecturer already exists and keeps CS101. Dr. Other Lecturer also
-- already exists and is deliberately left without a section: the security
-- tests use a second lecturer to prove that one cannot reach another's
-- section, and that check is clearer when they hold nothing.
--
-- Safe to run twice. The password for every account here is Password123!.

USE nextgen_university;


-- Insert only. Never ON DUPLICATE KEY UPDATE here: university_id is unique, so
-- a collision would rename whichever account already holds that number instead
-- of adding a new one.
INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified)
SELECT
    r.id,
    staff.full_name,
    staff.university_id,
    staff.email,
    '$2y$12$SM035BgYaiapS0y3OfpbQO5FsUKPx.t/.VCpIvsyZGgjgsRmHZE1i',
    'active',
    TRUE
FROM Role r
JOIN (
    SELECT 'Dr. Aisha Karim' AS full_name, 'LEC004' AS university_id, 'aisha@nextgen.edu' AS email
    UNION ALL
    SELECT 'Dr. Faiz Rahman', 'LEC003', 'faiz@nextgen.edu'
) AS staff
WHERE r.name = 'Lecturer'
  AND NOT EXISTS (SELECT 1 FROM User existing WHERE existing.email = staff.email)
  AND NOT EXISTS (SELECT 1 FROM User existing WHERE existing.university_id = staff.university_id);


INSERT INTO Lecturer (user_id, faculty_id, department_id, office, specialization, employment_status, hire_date)
SELECT
    u.id,
    d.faculty_id,
    d.id,
    staff.office,
    staff.specialization,
    'full_time',
    staff.hire_date
FROM (
    SELECT 'aisha@nextgen.edu' AS email, 'Computer Science' AS department,
           'B-204' AS office, 'Algorithms and data structures' AS specialization,
           '2019-09-01' AS hire_date
    UNION ALL
    SELECT 'faiz@nextgen.edu', 'Software Engineering',
           'C-118', 'Databases and requirements engineering',
           '2021-02-15'
) AS staff
JOIN User u ON u.email = staff.email
JOIN Department d ON d.name = staff.department
WHERE NOT EXISTS (SELECT 1 FROM Lecturer l WHERE l.user_id = u.id);


-- Each lecturer takes the courses that match what they teach.
UPDATE Section s
JOIN Course c ON c.id = s.course_id
JOIN User u ON u.email = 'aisha@nextgen.edu'
JOIN Lecturer l ON l.user_id = u.id
SET s.lecturer_id = l.id
WHERE c.course_code IN ('CS102', 'CS201');

UPDATE Section s
JOIN Course c ON c.id = s.course_id
JOIN User u ON u.email = 'faiz@nextgen.edu'
JOIN Lecturer l ON l.user_id = u.id
SET s.lecturer_id = l.id
WHERE c.course_code IN ('CS210', 'SE201');
