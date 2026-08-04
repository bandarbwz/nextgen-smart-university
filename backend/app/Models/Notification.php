<?php

declare(strict_types=1);

namespace App\Models;

class Notification extends Model
{
    protected string $table = 'Notification';

    protected string $defaultOrder = 'created_at DESC';

    public function forUser(int $userId, array $filters): array
    {
        $conditions = ['user_id = :user_id'];
        $parameters = ['user_id' => $userId];

        if (($filters['unread'] ?? false) === true) {
            $conditions[] = 'is_read = 0';
        }

        if (($filters['archived'] ?? false) === true) {
            $conditions[] = 'archived_at IS NOT NULL';
        } else {
            $conditions[] = 'archived_at IS NULL';
        }

        if (isset($filters['module'])) {
            $conditions[] = 'module = :module';
            $parameters['module'] = $filters['module'];
        }

        $statement = $this->db->prepare(
            'SELECT * FROM Notification
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY created_at DESC
             LIMIT 200'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM Notification
             WHERE user_id = :user_id AND is_read = 0 AND archived_at IS NULL'
        );

        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function markRead(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Notification SET is_read = 1, read_at = UTC_TIMESTAMP()
             WHERE id = :id AND is_read = 0'
        );

        return $statement->execute(['id' => $id]);
    }

    public function markAllRead(int $userId): int
    {
        $statement = $this->db->prepare(
            'UPDATE Notification SET is_read = 1, read_at = UTC_TIMESTAMP()
             WHERE user_id = :user_id AND is_read = 0'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    public function archive(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Notification SET archived_at = UTC_TIMESTAMP() WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function deleteAllForUser(int $userId): int
    {
        $statement = $this->db->prepare('DELETE FROM Notification WHERE user_id = :user_id');

        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }
}
