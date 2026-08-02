<?php

declare(strict_types=1);

namespace App\Models;

class Enrollment extends Model
{
    protected string $table = 'Enrollment';

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, st.student_number, st.department_id, u.full_name AS student_name,
                    c.course_code, c.course_name, c.credit_hours, s.section_number, s.semester_id
             FROM Enrollment e
             JOIN Student st ON st.id = e.student_id
             JOIN User u ON u.id = st.user_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function forStudentInSemester(int $studentId, int $semesterId): array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, c.course_code, c.course_name, c.credit_hours,
                    s.section_number, s.classroom, s.building, s.delivery_mode,
                    u.full_name AS lecturer_name
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = s.lecturer_id
             JOIN User u ON u.id = l.user_id
             WHERE e.student_id = :student_id AND s.semester_id = :semester_id
             ORDER BY c.course_code'
        );

        $statement->execute([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
        ]);

        return $statement->fetchAll();
    }

    public function historyForStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, c.course_code, c.course_name, c.credit_hours,
                    sem.name AS semester_name, sem.academic_year
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Semester sem ON sem.id = s.semester_id
             WHERE e.student_id = :student_id
             ORDER BY sem.start_date DESC, c.course_code'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function pendingForDepartment(int $departmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, st.student_number, u.full_name AS student_name,
                    c.course_code, c.course_name, s.section_number
             FROM Enrollment e
             JOIN Student st ON st.id = e.student_id
             JOIN User u ON u.id = st.user_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.enrollment_status = :status AND c.department_id = :department_id
             ORDER BY e.registration_date'
        );

        $statement->execute([
            'status' => 'Pending',
            'department_id' => $departmentId,
        ]);

        return $statement->fetchAll();
    }

    public function activeForStudentAndCourse(int $studentId, int $courseId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             WHERE e.student_id = :student_id
               AND s.course_id = :course_id
               AND e.enrollment_status IN (:pending, :approved, :completed)
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'pending' => 'Pending',
            'approved' => 'Approved',
            'completed' => 'Completed',
        ]);

        return $statement->fetch() ?: null;
    }

    public function registeredCreditHours(int $studentId, int $semesterId): int
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(c.credit_hours), 0)
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.student_id = :student_id
               AND s.semester_id = :semester_id
               AND e.enrollment_status IN (:pending, :approved)'
        );

        $statement->execute([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'pending' => 'Pending',
            'approved' => 'Approved',
        ]);

        return (int) $statement->fetchColumn();
    }

    public function activeSectionIds(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT section_id FROM Enrollment
             WHERE student_id = :student_id AND enrollment_status IN (:approved, :completed)'
        );

        $statement->execute([
            'student_id' => $studentId,
            'approved' => 'Approved',
            'completed' => 'Completed',
        ]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function completedCourseIds(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT s.course_id
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             WHERE e.student_id = :student_id AND e.enrollment_status = :status'
        );

        $statement->execute([
            'student_id' => $studentId,
            'status' => 'Completed',
        ]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Enrollment SET enrollment_status = :status WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }

    public function recordDecision(int $id, string $status, int $approvedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Enrollment
             SET enrollment_status = :status,
                 approved_by = :approved_by,
                 approved_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'approved_by' => $approvedBy,
            'id' => $id,
        ]);
    }
}
