<?php

declare(strict_types=1);

namespace App\Models;

class AttendanceExcuse extends Model
{
    protected string $table = 'AttendanceExcuse';

    protected string $defaultOrder = 'created_at DESC';

    public function existsForAttendance(int $attendanceId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM AttendanceExcuse WHERE attendance_id = :attendance_id LIMIT 1'
        );

        $statement->execute(['attendance_id' => $attendanceId]);

        return $statement->fetchColumn() !== false;
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, a.attendance_date, c.course_code, c.course_name
             FROM AttendanceExcuse e
             JOIN Attendance a ON a.id = e.attendance_id
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.student_id = :student_id
             ORDER BY e.created_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function forLecturer(int $lecturerId, ?string $status = null): array
    {
        $sql = 'SELECT e.*, a.attendance_date, c.course_code, c.course_name,
                       st.student_number, u.full_name AS student_name
                FROM AttendanceExcuse e
                JOIN Attendance a ON a.id = e.attendance_id
                JOIN Section s ON s.id = a.section_id
                JOIN Course c ON c.id = s.course_id
                JOIN Student st ON st.id = e.student_id
                JOIN User u ON u.id = st.user_id
                WHERE s.lecturer_id = :lecturer_id';

        $parameters = ['lecturer_id' => $lecturerId];

        if ($status !== null) {
            $sql .= ' AND e.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY e.created_at DESC');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, a.attendance_date, a.section_id, s.lecturer_id
             FROM AttendanceExcuse e
             JOIN Attendance a ON a.id = e.attendance_id
             JOIN Section s ON s.id = a.section_id
             WHERE e.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function review(int $id, string $status, int $approvedBy, ?string $note): bool
    {
        $statement = $this->db->prepare(
            'UPDATE AttendanceExcuse
             SET status = :status,
                 approved_by = :approved_by,
                 approval_date = UTC_TIMESTAMP(),
                 review_note = :review_note
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'approved_by' => $approvedBy,
            'review_note' => $note,
            'id' => $id,
        ]);
    }
}
