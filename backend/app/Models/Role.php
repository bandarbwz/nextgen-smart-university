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
}
