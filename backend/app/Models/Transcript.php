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

    /**
     * One row per student per course. Re-approving a corrected grade replaces
     * the entry rather than leaving two conflicting records behind.
     */
    public function record(
        int $studentId,
        int $courseId,
        int $semesterId,
        string $grade,
        float $gradePoints,
        int $earnedCreditHours
    ): bool {
        $statement = $this->db->prepare(
            'INSERT INTO Transcript
                (student_id, course_id, semester_id, grade, grade_points, earned_credit_hours)
             VALUES (:student_id, :course_id, :semester_id, :grade, :grade_points, :earned)
             ON DUPLICATE KEY UPDATE
                grade = VALUES(grade),
                grade_points = VALUES(grade_points),
                earned_credit_hours = VALUES(earned_credit_hours)'
        );

        return $statement->execute([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'semester_id' => $semesterId,
            'grade' => $grade,
            'grade_points' => $gradePoints,
            'earned' => $earnedCreditHours,
        ]);
    }
}
