<?php

declare(strict_types=1);

namespace App\Models;

class Program extends Model
{
    protected string $table = 'Program';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'name';

    public function allWithDepartment(): array
    {
        return $this->db->query(
            'SELECT p.*, d.name AS department_name, f.name AS faculty_name
             FROM Program p
             JOIN Department d ON d.id = p.department_id
             JOIN Faculty f ON f.id = d.faculty_id
             WHERE p.deleted_at IS NULL
             ORDER BY f.name, d.name, p.name'
        )->fetchAll();
    }

    public function hasStudents(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Student WHERE program_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
