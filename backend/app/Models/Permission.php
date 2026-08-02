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
}
