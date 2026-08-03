<?php

declare(strict_types=1);

namespace App\Models;

class Transcript extends Model
{
    protected string $table = 'Transcript';

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT t.*, c.course_code, c.course_name, c.credit_hours,
                    sem.name AS semester_name, sem.academic_year, sem.start_date
             FROM Transcript t
             JOIN Course c ON c.id = t.course_id
             JOIN Semester sem ON sem.id = t.semester_id
             WHERE t.student_id = :student_id
             ORDER BY sem.start_date, c.course_code'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function forStudentInSemester(int $studentId, int $semesterId): array
    {
        $statement = $this->db->prepare(
            'SELECT t.*, c.course_code, c.course_name, c.credit_hours
             FROM Transcript t
             JOIN Course c ON c.id = t.course_id
             WHERE t.student_id = :student_id AND t.semester_id = :semester_id
             ORDER BY c.course_code'
        );

        $statement->execute([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
        ]);

        return $statement->fetchAll();
    }
}
