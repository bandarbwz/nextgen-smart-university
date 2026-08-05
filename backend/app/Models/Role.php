<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class Role
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM Role WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM Role WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);

        return $statement->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM Role ORDER BY name')->fetchAll();
    }

    public function withPermissionCounts(): array
    {
        return $this->db
            ->query(
                'SELECT r.*,
                        (SELECT COUNT(*) FROM RolePermission rp WHERE rp.role_id = r.id)
                            AS permission_count,
                        (SELECT COUNT(*) FROM User u WHERE u.role_id = r.id AND u.deleted_at IS NULL)
                            AS user_count
                 FROM Role r
                 ORDER BY r.is_system DESC, r.name'
            )
            ->fetchAll();
    }

    public function detailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*,
                    (SELECT COUNT(*) FROM User u WHERE u.role_id = r.id AND u.deleted_at IS NULL)
                        AS user_count
             FROM Role r
             WHERE r.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM Role WHERE name = :name';
        $parameters = ['name' => $name];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $ignoreId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $fields): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO Role (name, description, status, is_system)
             VALUES (:name, :description, :status, 0)'
        );

        $statement->execute([
            'name' => $fields['name'],
            'description' => $fields['description'] ?? null,
            'status' => $fields['status'] ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Role SET name = :name, description = :description, status = :status
             WHERE id = :id'
        );

        return $statement->execute([
            'name' => $fields['name'],
            'description' => $fields['description'] ?? null,
            'status' => $fields['status'] ?? 'active',
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM Role WHERE id = :id')->execute(['id' => $id]);
    }

    public function userCount(int $id): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM User WHERE role_id = :id AND deleted_at IS NULL'
        );

        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn();
    }
}
