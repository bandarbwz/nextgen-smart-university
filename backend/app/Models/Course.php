<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Course extends Model
{
    protected string $table = 'Course';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'course_code';

    public function search(?string $term, ?int $departmentId, ?int $programId): array
    {
        $sql = 'SELECT c.*, d.name AS department_name
                FROM Course c
                JOIN Department d ON d.id = c.department_id
                WHERE c.deleted_at IS NULL';

        $parameters = [];

        if ($term !== null && $term !== '') {
            $sql .= ' AND (c.course_name LIKE :name_term OR c.course_code LIKE :code_term)';
            $pattern = '%' . $this->escapeLike($term) . '%';

            $parameters['name_term'] = $pattern;
            $parameters['code_term'] = $pattern;
        }

        if ($departmentId !== null) {
            $sql .= ' AND c.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        if ($programId !== null) {
            $sql .= ' AND c.program_id = :program_id';
            $parameters['program_id'] = $programId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY c.course_code');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function codeExists(string $courseCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM Course WHERE course_code = :course_code AND deleted_at IS NULL';
        $parameters = ['course_code' => $courseCode];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $parameters['id'] = $excludeId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function prerequisites(int $courseId): array
    {
        $statement = $this->db->prepare(
            'SELECT c.id, c.course_code, c.course_name, c.credit_hours
             FROM CoursePrerequisite cp
             JOIN Course c ON c.id = cp.prerequisite_course_id
             WHERE cp.course_id = :course_id
             ORDER BY c.course_code'
        );

        $statement->execute(['course_id' => $courseId]);

        return $statement->fetchAll();
    }

    public function prerequisiteIds(int $courseId): array
    {
        $statement = $this->db->prepare(
            'SELECT prerequisite_course_id FROM CoursePrerequisite WHERE course_id = :course_id'
        );

        $statement->execute(['course_id' => $courseId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function addPrerequisite(int $courseId, int $prerequisiteCourseId): bool
    {
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO CoursePrerequisite (course_id, prerequisite_course_id)
             VALUES (:course_id, :prerequisite_course_id)'
        );

        return $statement->execute([
            'course_id' => $courseId,
            'prerequisite_course_id' => $prerequisiteCourseId,
        ]);
    }

    public function hasSections(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Section WHERE course_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
