<?php

declare(strict_types=1);

namespace App\Models;

class MessageAttachment extends Model
{
    protected string $table = 'MessageAttachment';

    public function findWithRoom(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT a.*, m.room_id, m.deleted_at AS message_deleted_at
             FROM MessageAttachment a
             JOIN Message m ON m.id = a.message_id
             WHERE a.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }
}
