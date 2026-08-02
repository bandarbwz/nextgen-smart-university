<?php

declare(strict_types=1);

namespace App\Models;

class ChatRoom extends Model
{
    protected string $table = 'ChatRoom';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'room_name';

    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, m.role AS my_role, m.last_read_message_id,
                    (SELECT COUNT(*) FROM ChatMember WHERE room_id = r.id) AS member_count,
                    (SELECT MAX(id) FROM Message WHERE room_id = r.id AND deleted_at IS NULL)
                        AS last_message_id,
                    (SELECT COUNT(*) FROM Message
                     WHERE room_id = r.id
                       AND deleted_at IS NULL
                       AND id > COALESCE(m.last_read_message_id, 0)
                       AND sender_id != :self_id) AS unread_count
             FROM ChatRoom r
             JOIN ChatMember m ON m.room_id = r.id
             WHERE m.user_id = :user_id AND r.deleted_at IS NULL
             ORDER BY last_message_id DESC, r.room_name'
        );

        $statement->execute([
            'user_id' => $userId,
            'self_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public function findBySection(int $sectionId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ChatRoom WHERE section_id = :section_id LIMIT 1'
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetch() ?: null;
    }

    public function findPrivateBetween(int $userA, int $userB): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*
             FROM ChatRoom r
             JOIN ChatMember a ON a.room_id = r.id AND a.user_id = :user_a
             JOIN ChatMember b ON b.room_id = r.id AND b.user_id = :user_b
             WHERE r.room_type = :type AND r.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute([
            'user_a' => $userA,
            'user_b' => $userB,
            'type' => 'Private',
        ]);

        return $statement->fetch() ?: null;
    }
}
