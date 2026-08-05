-- Authentication Module Seed Data
-- Default roles and permissions

USE nextgen_university;


INSERT INTO Role (name, description, is_system) VALUES
    ('Student', 'Enrolled university student', TRUE),
    ('Lecturer', 'Teaching staff member', TRUE),
    ('Coordinator', 'Academic program coordinator', TRUE),
    ('Administrator', 'System administrator with full access', TRUE),
    ('Restaurant Owner', 'Campus food court restaurant owner', TRUE),
    ('STAD Staff', 'Student affairs and activities staff', TRUE)
ON DUPLICATE KEY UPDATE description = VALUES(description), is_system = TRUE;


INSERT INTO Permission (module, name, description) VALUES
    ('Authentication', 'auth.login', 'Log in to the platform'),
    ('Authentication', 'auth.view_profile', 'View own profile'),
    ('Authentication', 'auth.update_profile', 'Update own profile'),
    ('Authentication', 'auth.change_password', 'Change own password'),

    ('Academic', 'academic.view_dashboard', 'View academic dashboard'),
    ('Academic', 'academic.register_courses', 'Register for courses'),
    ('Academic', 'academic.view_grades', 'View own grades'),
    ('Academic', 'academic.manage_courses', 'Create and manage courses'),
    ('Academic', 'academic.open_registration', 'Open and close the registration period'),

    ('Attendance', 'attendance.manage', 'Record and manage attendance'),
    ('Attendance', 'attendance.view_own', 'View own attendance records'),

    ('LMS', 'lms.upload_materials', 'Upload course materials'),
    ('LMS', 'lms.grade_students', 'Grade student submissions'),
    ('LMS', 'lms.submit_assignment', 'Submit assignments'),

    ('Finance', 'finance.view_own', 'View own invoices and payments'),
    ('Finance', 'finance.manage', 'Manage invoices, payments and refunds'),

    ('Food Court', 'foodcourt.manage_menu', 'Manage restaurant menu'),
    ('Food Court', 'foodcourt.accept_orders', 'Accept and process orders'),
    ('Food Court', 'foodcourt.place_order', 'Place food orders'),

    ('Student Activities', 'activities.manage_clubs', 'Manage student clubs'),
    ('Student Activities', 'activities.manage_events', 'Manage student events'),
    ('Student Activities', 'activities.join', 'Join clubs and register for events'),

    ('System', 'system.manage_users', 'Create, update and deactivate users'),
    ('System', 'system.manage_roles', 'Manage roles and permissions'),
    ('System', 'system.manage_settings', 'Manage system settings')
ON DUPLICATE KEY UPDATE description = VALUES(description);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p
WHERE r.name = 'Administrator'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p ON p.name IN (
    'auth.login',
    'auth.view_profile',
    'auth.update_profile',
    'auth.change_password',
    'academic.view_dashboard',
    'academic.register_courses',
    'academic.view_grades',
    'attendance.view_own',
    'lms.submit_assignment',
    'finance.view_own',
    'foodcourt.place_order',
    'activities.join'
)
WHERE r.name = 'Student'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p ON p.name IN (
    'auth.login',
    'auth.view_profile',
    'auth.update_profile',
    'auth.change_password',
    'academic.view_dashboard',
    'attendance.manage',
    'lms.upload_materials',
    'lms.grade_students'
)
WHERE r.name = 'Lecturer'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p ON p.name IN (
    'auth.login',
    'auth.view_profile',
    'auth.update_profile',
    'auth.change_password',
    'academic.view_dashboard',
    'academic.manage_courses',
    'academic.open_registration',
    'attendance.manage',
    'lms.grade_students'
)
WHERE r.name = 'Coordinator'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p ON p.name IN (
    'auth.login',
    'auth.view_profile',
    'auth.change_password',
    'foodcourt.manage_menu',
    'foodcourt.accept_orders'
)
WHERE r.name = 'Restaurant Owner'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


INSERT INTO RolePermission (role_id, permission_id)
SELECT r.id, p.id
FROM Role r
JOIN Permission p ON p.name IN (
    'auth.login',
    'auth.view_profile',
    'auth.change_password',
    'activities.manage_clubs',
    'activities.manage_events'
)
WHERE r.name = 'STAD Staff'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
