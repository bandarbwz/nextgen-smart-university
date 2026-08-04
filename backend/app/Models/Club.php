<?php

declare(strict_types=1);

namespace App\Models;

class Club extends Model
{
    protected string $table = 'Club';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'club_name';

    public function listing(?string $category, ?string $status): array
    {
        $conditions = ['c.deleted_at IS NULL'];
        $parameters = [];

        if ($category !== null) {
            $conditions[] = 'c.category = :category';
            $parameters['category'] = $category;
        }

        if ($status !== null) {
            $conditions[] = 'c.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare(
            'SELECT c.*, advisor.full_name AS advisor_name, president.full_name AS president_name,
                    (SELECT COUNT(*) FROM Event e WHERE e.club_id = c.id AND e.deleted_at IS NULL)
                        AS event_count
             FROM Club c
             LEFT JOIN Lecturer l ON l.id = c.advisor_id
             LEFT JOIN User advisor ON advisor.id = l.user_id
             LEFT JOIN Student s ON s.id = c.president_id
             LEFT JOIN User president ON president.id = s.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY c.club_name'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM Club WHERE club_name = :name AND deleted_at IS NULL';
        $parameters = ['name' => $name];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $ignoreId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }
}
