<?php

declare(strict_types=1);

namespace App\Models;

class DownloadFile extends Model
{
    protected string $table = 'DownloadFile';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'created_at DESC';

    public function visibleTo(array $visibilities, ?string $category): array
    {
        $placeholders = implode(', ', array_fill(0, count($visibilities), '?'));

        $sql = 'SELECT f.*, u.full_name AS uploaded_by_name
                FROM DownloadFile f
                JOIN User u ON u.id = f.uploaded_by
                WHERE f.deleted_at IS NULL AND f.visibility IN (' . $placeholders . ')';

        $parameters = $visibilities;

        if ($category !== null) {
            $sql .= ' AND f.category = ?';
            $parameters[] = $category;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY f.category, f.title');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function incrementDownloadCount(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE DownloadFile SET download_count = download_count + 1 WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }
}
