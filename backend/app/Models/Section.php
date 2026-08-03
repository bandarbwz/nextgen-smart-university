<?php

declare(strict_types=1);

namespace App\Models;

class Section extends Model
{
    protected string $table = 'Section';

    protected bool $softDeletes = true;

    public function search(?int $semesterId, ?int $courseId, ?int $lecturerId): array
    {
        $sql = 'SELECT s.*, c.course_code, c.course_name, c.credit_hours,
                       u.full_name AS lecturer_name, sem.name AS semester_name
                FROM Section s
                JOIN Course c ON c.id = s.course_id
                JOIN Lecturer l ON l.id = s.lecturer_id
                JOIN User u ON u.id = l.user_id
                JOIN Semester sem ON sem.id = s.semester_id
                WHERE s.deleted_at IS NULL';

        $parameters = [];

        if ($semesterId !== null) {
            $sql .= ' AND s.semester_id = :semester_id';
            $parameters['semester_id'] = $semesterId;
        }

        if ($courseId !== null) {
            $sql .= ' AND s.course_id = :course_id';
            $parameters['course_id'] = $courseId;
        }

        if ($lecturerId !== null) {
            $sql .= ' AND s.lecturer_id = :lecturer_id';
            $parameters['lecturer_id'] = $lecturerId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY c.course_code, s.section_number');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT s.*, c.course_code, c.course_name, c.credit_hours, c.department_id,
                    u.full_name AS lecturer_name, sem.name AS semester_name
             FROM Section s
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = s.lecturer_id
             JOIN User u ON u.id = l.user_id
             JOIN Semester sem ON sem.id = s.semester_id
             WHERE s.id = :id AND s.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function students(int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT st.id, st.student_number, u.full_name, u.email,
                    e.enrollment_status, e.registration_date, e.final_grade
             FROM Enrollment e
             JOIN Student st ON st.id = e.student_id
             JOIN User u ON u.id = st.user_id
             WHERE e.section_id = :section_id AND e.enrollment_status IN (:approved, :completed)
             ORDER BY st.student_number'
        );

        $statement->execute([
            'section_id' => $sectionId,
            'approved' => 'Approved',
            'completed' => 'Completed',
        ]);

        return $statement->fetchAll();
    }

    public function idsForLecturer(int $lecturerId): array
    {
        $statement = $this->db->prepare(
            'SELECT id FROM Section WHERE lecturer_id = :lecturer_id AND deleted_at IS NULL'
        );

        $statement->execute(['lecturer_id' => $lecturerId]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function incrementRegistered(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Section SET registered_students = registered_students + 1 WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function decrementRegistered(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Section
             SET registered_students = GREATEST(registered_students - 1, 0)
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function hasAvailableSeats(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Section
             WHERE id = :id AND registered_students < capacity AND deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    public function hasEnrollments(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Enrollment
             WHERE section_id = :id AND enrollment_status NOT IN (:dropped, :rejected)
             LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
            'dropped' => 'Dropped',
            'rejected' => 'Rejected',
        ]);

        return $statement->fetchColumn() !== false;
    }
}
