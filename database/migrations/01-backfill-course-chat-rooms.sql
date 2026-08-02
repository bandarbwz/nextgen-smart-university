-- Backfill course chat rooms for sections created before the Chat module existed.
-- New sections get their room automatically; this covers historical data.
-- Safe to run more than once.

USE nextgen_university;


INSERT INTO ChatRoom (room_name, room_type, course_id, section_id, created_by)
SELECT CONCAT(c.course_code, ' - ', s.section_number), 'Course', c.id, s.id, l.user_id
FROM Section s
JOIN Course c ON c.id = s.course_id
JOIN Lecturer l ON l.id = s.lecturer_id
LEFT JOIN ChatRoom r ON r.section_id = s.id
WHERE s.deleted_at IS NULL AND r.id IS NULL;


INSERT IGNORE INTO ChatMember (room_id, user_id, role, joined_at)
SELECT r.id, l.user_id, 'Lecturer', UTC_TIMESTAMP()
FROM ChatRoom r
JOIN Section s ON s.id = r.section_id
JOIN Lecturer l ON l.id = s.lecturer_id
WHERE r.room_type = 'Course';


INSERT IGNORE INTO ChatMember (room_id, user_id, role, joined_at)
SELECT r.id, st.user_id, 'Student', UTC_TIMESTAMP()
FROM ChatRoom r
JOIN Enrollment e ON e.section_id = r.section_id
JOIN Student st ON st.id = e.student_id
WHERE r.room_type = 'Course'
  AND e.enrollment_status IN ('Approved', 'Completed');
