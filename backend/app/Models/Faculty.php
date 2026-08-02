<?php

declare(strict_types=1);

namespace App\Models;

class Faculty extends Model
{
    protected string $table = 'Faculty';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'name';

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM Faculty WHERE name = :name AND deleted_at IS NULL';
        $parameters = ['name' => $name];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $parameters['id'] = $excludeId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function hasDepartments(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Department WHERE faculty_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
