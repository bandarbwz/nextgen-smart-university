<?php

declare(strict_types=1);

namespace App\Models;

class Department extends Model
{
    protected string $table = 'Department';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'name';

    public function allWithFaculty(): array
    {
        return $this->db->query(
            'SELECT d.*, f.name AS faculty_name
             FROM Department d
             JOIN Faculty f ON f.id = d.faculty_id
             WHERE d.deleted_at IS NULL
             ORDER BY f.name, d.name'
        )->fetchAll();
    }

    public function byFaculty(int $facultyId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Department
             WHERE faculty_id = :faculty_id AND deleted_at IS NULL
             ORDER BY name'
        );

        $statement->execute(['faculty_id' => $facultyId]);

        return $statement->fetchAll();
    }

    public function hasPrograms(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Program WHERE department_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
