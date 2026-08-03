-- Academic Module Seed Data
-- Sample academic structure for development

USE nextgen_university;


INSERT INTO Faculty (name, description, dean_name) VALUES
    ('Faculty of Computing', 'Computing and information technology programs', 'Dr. Amal Yusuf'),
    ('Faculty of Engineering', 'Engineering programs', 'Dr. Omar Haddad'),
    ('Faculty of Business', 'Business and management programs', 'Dr. Layla Rahman')
ON DUPLICATE KEY UPDATE description = VALUES(description);


INSERT INTO Department (faculty_id, name, description)
SELECT f.id, d.name, d.description
FROM Faculty f
JOIN (
    SELECT 'Faculty of Computing' AS faculty, 'Computer Science' AS name,
           'Core computer science department' AS description
    UNION ALL SELECT 'Faculty of Computing', 'Software Engineering', 'Software engineering department'
    UNION ALL SELECT 'Faculty of Computing', 'Artificial Intelligence', 'AI and data science department'
    UNION ALL SELECT 'Faculty of Engineering', 'Electrical Engineering', 'Electrical engineering department'
    UNION ALL SELECT 'Faculty of Business', 'Accounting', 'Accounting department'
) d ON d.faculty = f.name
ON DUPLICATE KEY UPDATE Department.description = VALUES(description);


INSERT INTO Program (department_id, name, degree, required_credit_hours)
SELECT d.id, p.name, p.degree, p.required_credit_hours
FROM Department d
JOIN (
    SELECT 'Computer Science' AS department, 'Bachelor of Computer Science' AS name,
           'Bachelor' AS degree, 132 AS required_credit_hours
    UNION ALL SELECT 'Software Engineering', 'Bachelor of Software Engineering', 'Bachelor', 132
    UNION ALL SELECT 'Artificial Intelligence', 'Bachelor of Artificial Intelligence', 'Bachelor', 136
    UNION ALL SELECT 'Electrical Engineering', 'Bachelor of Electrical Engineering', 'Bachelor', 140
    UNION ALL SELECT 'Accounting', 'Bachelor of Accounting', 'Bachelor', 126
) p ON p.department = d.name
ON DUPLICATE KEY UPDATE Program.degree = VALUES(degree);


INSERT INTO Semester (name, academic_year, start_date, end_date,
                      registration_start, registration_end, current_semester, status)
VALUES
    ('Semester 1', '2026/2027', '2026-09-01', '2027-01-15',
     '2026-08-01 00:00:00', '2026-09-07 23:59:59', TRUE, 'active'),
    ('Semester 2', '2026/2027', '2027-02-01', '2027-06-15',
     '2027-01-05 00:00:00', '2027-02-07 23:59:59', FALSE, 'upcoming')
ON DUPLICATE KEY UPDATE status = VALUES(status);


INSERT INTO Course (department_id, program_id, course_code, course_name, description,
                    credit_hours, course_type, level, course_status)
SELECT d.id, p.id, c.course_code, c.course_name, c.description,
       c.credit_hours, c.course_type, c.level, 'active'
FROM Department d
JOIN Program p ON p.department_id = d.id
JOIN (
    SELECT 'Computer Science' AS department, 'CS101' AS course_code,
           'Introduction to Programming' AS course_name,
           'Fundamentals of programming using a high level language' AS description,
           3 AS credit_hours, 'Core' AS course_type, 1 AS level
    UNION ALL SELECT 'Computer Science', 'CS102', 'Data Structures',
           'Linear and non linear data structures', 3, 'Core', 1
    UNION ALL SELECT 'Computer Science', 'CS201', 'Algorithms',
           'Algorithm design and complexity analysis', 3, 'Core', 2
    UNION ALL SELECT 'Computer Science', 'CS210', 'Database Systems',
           'Relational database design and SQL', 3, 'Core', 2
    UNION ALL SELECT 'Software Engineering', 'SE201', 'Software Requirements',
           'Requirements elicitation and specification', 3, 'Core', 2
    UNION ALL SELECT 'Artificial Intelligence', 'AI301', 'Machine Learning',
           'Supervised and unsupervised learning', 4, 'Core', 3
) c ON c.department = d.name
ON DUPLICATE KEY UPDATE Course.course_name = VALUES(course_name);


INSERT IGNORE INTO CoursePrerequisite (course_id, prerequisite_course_id)
SELECT c.id, pre.id
FROM Course c
JOIN Course pre
JOIN (
    SELECT 'CS102' AS course, 'CS101' AS prerequisite
    UNION ALL SELECT 'CS201', 'CS102'
    UNION ALL SELECT 'CS210', 'CS102'
    UNION ALL SELECT 'AI301', 'CS201'
) link ON link.course = c.course_code AND link.prerequisite = pre.course_code;
