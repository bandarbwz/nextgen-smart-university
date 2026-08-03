<?php

declare(strict_types=1);

namespace App\Models;

class ClassSchedule extends Model
{
    protected string $table = 'ClassSchedule';

    public function forSection(int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ClassSchedule WHERE section_id = :section_id ORDER BY day_of_week, start_time'
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId, int $semesterId): array
    {
        $statement = $this->db->prepare(
            'SELECT cs.day_of_week, cs.start_time, cs.end_time, cs.room,
                    c.course_code, c.course_name, s.section_number, s.building, s.delivery_mode,
                    u.full_name AS lecturer_name
             FROM Enrollment e
             JOIN Section s ON s.id = e.section_id
             JOIN ClassSchedule cs ON cs.section_id = s.id
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = s.lecturer_id
             JOIN User u ON u.id = l.user_id
             WHERE e.student_id = :student_id
               AND s.semester_id = :semester_id
               AND e.enrollment_status = :status
             ORDER BY cs.day_of_week, cs.start_time'
        );

        $statement->execute([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'status' => 'Approved',
        ]);

        return $statement->fetchAll();
    }

    public function conflictsForStudent(int $studentId, int $semesterId, int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT c.course_code, c.course_name, existing.day_of_week,
                    existing.start_time, existing.end_time
             FROM ClassSchedule candidate
             JOIN ClassSchedule existing ON existing.day_of_week = candidate.day_of_week
             JOIN Section s ON s.id = existing.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Enrollment e ON e.section_id = s.id
             WHERE candidate.section_id = :section_id
               AND e.student_id = :student_id
               AND s.semester_id = :semester_id
               AND e.enrollment_status IN (:pending, :approved)
               AND candidate.start_time < existing.end_time
               AND existing.start_time < candidate.end_time'
        );

        $statement->execute([
            'section_id' => $sectionId,
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'pending' => 'Pending',
            'approved' => 'Approved',
        ]);

        return $statement->fetchAll();
    }

    public function deleteForSection(int $sectionId): bool
    {
        $statement = $this->db->prepare('DELETE FROM ClassSchedule WHERE section_id = :section_id');

        return $statement->execute(['section_id' => $sectionId]);
    }
}
