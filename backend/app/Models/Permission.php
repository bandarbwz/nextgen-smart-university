<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class Permission
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function namesForRole(int $roleId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.name
             FROM Permission p
             JOIN RolePermission rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.name'
        );

        $statement->execute(['role_id' => $roleId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM Permission ORDER BY module, name')->fetchAll();
    }

    public function idsForRole(int $roleId): array
    {
        $statement = $this->db->prepare(
            'SELECT permission_id FROM RolePermission WHERE role_id = :role_id'
        );

        $statement->execute(['role_id' => $roleId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function existingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $statement = $this->db->prepare(
            'SELECT id FROM Permission WHERE id IN (' . $placeholders . ')'
        );

        $statement->execute($ids);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Replaces the whole set for a role in one transaction rather than diffing.
     * A half applied permission change is worse than none at all.
     */
    public function replaceForRole(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->prepare('DELETE FROM RolePermission WHERE role_id = :role_id')
                ->execute(['role_id' => $roleId]);

            $insert = $this->db->prepare(
                'INSERT INTO RolePermission (role_id, permission_id) VALUES (:role_id, :permission_id)'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();

            throw $exception;
        }
    }
}
