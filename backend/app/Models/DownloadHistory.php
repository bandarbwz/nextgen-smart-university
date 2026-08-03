<?php

declare(strict_types=1);

namespace App\Models;

class DownloadHistory extends Model
{
    protected string $table = 'DownloadHistory';

    protected string $defaultOrder = 'downloaded_at DESC';

    public function record(?int $fileId, string $fileTitle, int $userId, string $ipAddress): int
    {
        return $this->create([
            'file_id' => $fileId,
            'file_title' => $fileTitle,
            'user_id' => $userId,
            'downloaded_at' => gmdate('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
        ]);
    }

    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM DownloadHistory WHERE user_id = :user_id ORDER BY downloaded_at DESC'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT h.*, u.full_name
             FROM DownloadHistory h
             JOIN User u ON u.id = h.user_id
             ORDER BY h.downloaded_at DESC
             LIMIT 200'
        )->fetchAll();
    }
}
