<?php

declare(strict_types=1);

namespace App\Models;

class MessageRead extends Model
{
    protected string $table = 'MessageRead';

    public function record(int $messageId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO MessageRead (message_id, user_id, read_at)
             VALUES (:message_id, :user_id, UTC_TIMESTAMP())'
        );

        return $statement->execute([
            'message_id' => $messageId,
            'user_id' => $userId,
        ]);
    }

    public function forMessage(int $messageId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.read_at, u.id AS user_id, u.full_name
             FROM MessageRead r
             JOIN User u ON u.id = r.user_id
             WHERE r.message_id = :message_id
             ORDER BY r.read_at'
        );

        $statement->execute(['message_id' => $messageId]);

        return $statement->fetchAll();
    }
}
