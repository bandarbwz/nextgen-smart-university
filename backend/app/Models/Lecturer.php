<?php

declare(strict_types=1);

namespace App\Models;

class Lecturer extends Model
{
    protected string $table = 'Lecturer';

    protected bool $softDeletes = true;

    public function allWithUser(?int $departmentId = null): array
    {
        $sql = 'SELECT l.*, u.full_name, u.email, u.phone, d.name AS department_name, f.name AS faculty_name
                FROM Lecturer l
                JOIN User u ON u.id = l.user_id
                JOIN Department d ON d.id = l.department_id
                JOIN Faculty f ON f.id = l.faculty_id
                WHERE l.deleted_at IS NULL';

        $parameters = [];

        if ($departmentId !== null) {
            $sql .= ' AND l.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY u.full_name');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findWithUser(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, u.full_name, u.email, u.phone, d.name AS department_name, f.name AS faculty_name
             FROM Lecturer l
             JOIN User u ON u.id = l.user_id
             JOIN Department d ON d.id = l.department_id
             JOIN Faculty f ON f.id = l.faculty_id
             WHERE l.id = :id AND l.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Lecturer WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: null;
    }

    public function hasSections(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Section WHERE lecturer_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    public function coordinatedDepartmentIds(int $lecturerId): array
    {
        $statement = $this->db->prepare(
            'SELECT department_id FROM Coordinator WHERE lecturer_id = :lecturer_id'
        );

        $statement->execute(['lecturer_id' => $lecturerId]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }
}
