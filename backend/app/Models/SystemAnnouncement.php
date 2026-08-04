<?php

declare(strict_types=1);

namespace App\Models;

class SystemAnnouncement extends Model
{
    protected string $table = 'SystemAnnouncement';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'published_at DESC';

    public function forRole(string $role): array
    {
        $statement = $this->db->prepare(
            "SELECT a.*, u.full_name AS published_by_name
             FROM SystemAnnouncement a
             JOIN User u ON u.id = a.published_by
             WHERE a.deleted_at IS NULL AND a.status = 'published'
               AND (a.audience = 'All' OR a.audience = :role)
               AND (a.expires_at IS NULL OR a.expires_at > UTC_TIMESTAMP())
             ORDER BY a.published_at DESC"
        );

        $statement->execute(['role' => $role]);

        return $statement->fetchAll();
    }

    public function listing(): array
    {
        return $this->db
            ->query(
                'SELECT a.*, u.full_name AS published_by_name
                 FROM SystemAnnouncement a
                 JOIN User u ON u.id = a.published_by
                 WHERE a.deleted_at IS NULL
                 ORDER BY a.created_at DESC'
            )
            ->fetchAll();
    }
}
