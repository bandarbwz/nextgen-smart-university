<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

abstract class Model
{
    protected PDO $db;

    protected string $table = '';

    protected bool $softDeletes = false;

    protected string $defaultOrder = 'id';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ' . $this->table . ' WHERE id = :id' . $this->notDeleted() . ' LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function all(): array
    {
        $where = $this->softDeletes ? ' WHERE deleted_at IS NULL' : '';

        return $this->db
            ->query('SELECT * FROM ' . $this->table . $where . ' ORDER BY ' . $this->defaultOrder)
            ->fetchAll();
    }

    public function create(array $fields): int
    {
        $columns = array_keys($fields);

        $statement = $this->db->prepare(
            'INSERT INTO ' . $this->table . ' (' . implode(', ', $columns) . ')
             VALUES (:' . implode(', :', $columns) . ')'
        );

        $statement->execute($this->normalise($fields));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
        if ($fields === []) {
            return false;
        }

        $assignments = [];

        foreach (array_keys($fields) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }

        $statement = $this->db->prepare(
            'UPDATE ' . $this->table . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id'
        );

        return $statement->execute($this->normalise($fields) + ['id' => $id]);
    }

    /**
     * Escapes the LIKE wildcards so a search for "%" matches a literal percent
     * sign instead of every row in the table.
     */
    protected function escapeLike(string $term): string
    {
        return addcslashes($term, '%_\\');
    }

    protected function normalise(array $fields): array
    {
        foreach ($fields as $column => $value) {
            if (is_bool($value)) {
                $fields[$column] = (int) $value;
            }
        }

        return $fields;
    }

    public function delete(int $id): bool
    {
        $sql = $this->softDeletes
            ? 'UPDATE ' . $this->table . ' SET deleted_at = UTC_TIMESTAMP() WHERE id = :id'
            : 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        return $this->db->prepare($sql)->execute(['id' => $id]);
    }

    public function exists(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM ' . $this->table . ' WHERE id = :id' . $this->notDeleted() . ' LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    private function notDeleted(): string
    {
        return $this->softDeletes ? ' AND deleted_at IS NULL' : '';
    }
}
