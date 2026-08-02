<?php

declare(strict_types=1);

namespace App\Models;

class ChatMember extends Model
{
    protected string $table = 'ChatMember';

    protected string $defaultOrder = 'joined_at';

    public function findMembership(int $roomId, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ChatMember WHERE room_id = :room_id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function forRoom(int $roomId): array
    {
        $statement = $this->db->prepare(
            'SELECT m.id, m.role, m.joined_at, u.id AS user_id, u.full_name, u.email
             FROM ChatMember m
             JOIN User u ON u.id = m.user_id
             WHERE m.room_id = :room_id
             ORDER BY FIELD(m.role, :owner, :lecturer, :moderator, :student), u.full_name'
        );

        $statement->execute([
            'room_id' => $roomId,
            'owner' => 'Owner',
            'lecturer' => 'Lecturer',
            'moderator' => 'Moderator',
            'student' => 'Student',
        ]);

        return $statement->fetchAll();
    }

    public function join(int $roomId, int $userId, string $role): bool
    {
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO ChatMember (room_id, user_id, role, joined_at)
             VALUES (:room_id, :user_id, :role, UTC_TIMESTAMP())'
        );

        return $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function leave(int $roomId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM ChatMember WHERE room_id = :room_id AND user_id = :user_id'
        );

        return $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);
    }

    public function markRead(int $roomId, int $userId, int $messageId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE ChatMember
             SET last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), :message_id)
             WHERE room_id = :room_id AND user_id = :user_id'
        );

        return $statement->execute([
            'message_id' => $messageId,
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);
    }
}
