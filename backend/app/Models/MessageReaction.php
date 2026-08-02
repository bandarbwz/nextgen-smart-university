<?php

declare(strict_types=1);

namespace App\Models;

class MessageReaction extends Model
{
    protected string $table = 'MessageReaction';

    public function set(int $messageId, int $userId, string $reaction): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO MessageReaction (message_id, user_id, reaction)
             VALUES (:message_id, :user_id, :reaction)
             ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)'
        );

        return $statement->execute([
            'message_id' => $messageId,
            'user_id' => $userId,
            'reaction' => $reaction,
        ]);
    }

    public function remove(int $messageId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM MessageReaction WHERE message_id = :message_id AND user_id = :user_id'
        );

        return $statement->execute([
            'message_id' => $messageId,
            'user_id' => $userId,
        ]);
    }
}
