<?php

declare(strict_types=1);

namespace App\Models;

class Student extends Model
{
    protected string $table = 'Student';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'student_number';

    public function allWithUser(?int $programId = null, ?int $departmentId = null): array
    {
        $sql = 'SELECT s.*, u.full_name, u.email, u.phone, u.university_id,
                       p.name AS program_name, d.name AS department_name, f.name AS faculty_name
                FROM Student s
                JOIN User u ON u.id = s.user_id
                JOIN Program p ON p.id = s.program_id
                JOIN Department d ON d.id = s.department_id
                JOIN Faculty f ON f.id = s.faculty_id
                WHERE s.deleted_at IS NULL';

        $parameters = [];

        if ($programId !== null) {
            $sql .= ' AND s.program_id = :program_id';
            $parameters['program_id'] = $programId;
        }

        if ($departmentId !== null) {
            $sql .= ' AND s.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY s.student_number');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findWithUser(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT s.*, u.full_name, u.email, u.phone, u.university_id, u.profile_photo,
                    p.name AS program_name, p.required_credit_hours,
                    d.name AS department_name, f.name AS faculty_name
             FROM Student s
             JOIN User u ON u.id = s.user_id
             JOIN Program p ON p.id = s.program_id
             JOIN Department d ON d.id = s.department_id
             JOIN Faculty f ON f.id = s.faculty_id
             WHERE s.id = :id AND s.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Student WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: null;
    }

    public function studentNumberExists(string $studentNumber, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM Student WHERE student_number = :student_number AND deleted_at IS NULL';
        $parameters = ['student_number' => $studentNumber];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $parameters['id'] = $excludeId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function updateAcademicProgress(
        int $id,
        float $currentGpa,
        float $cumulativeGpa,
        int $completedCreditHours
    ): bool {
        $statement = $this->db->prepare(
            'UPDATE Student
             SET current_gpa = :current_gpa,
                 cumulative_gpa = :cumulative_gpa,
                 completed_credit_hours = :completed_credit_hours
             WHERE id = :id'
        );

        return $statement->execute([
            'current_gpa' => $currentGpa,
            'cumulative_gpa' => $cumulativeGpa,
            'completed_credit_hours' => $completedCreditHours,
            'id' => $id,
        ]);
    }
}
