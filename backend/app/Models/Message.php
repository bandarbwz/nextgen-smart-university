<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Message extends Model
{
    protected string $table = 'Message';

    protected string $defaultOrder = 'sent_at DESC';

    public function forRoom(int $roomId, ?int $afterId, ?int $beforeId, int $limit): array
    {
        $sql = 'SELECT m.*, u.full_name AS sender_name, u.profile_photo AS sender_photo,
                       parent.message AS reply_preview, parentUser.full_name AS reply_sender_name
                FROM Message m
                JOIN User u ON u.id = m.sender_id
                LEFT JOIN Message parent ON parent.id = m.reply_to
                LEFT JOIN User parentUser ON parentUser.id = parent.sender_id
                WHERE m.room_id = :room_id';

        $parameters = ['room_id' => $roomId];

        if ($afterId !== null) {
            $sql .= ' AND m.id > :after_id';
            $parameters['after_id'] = $afterId;
        }

        if ($beforeId !== null) {
            $sql .= ' AND m.id < :before_id';
            $parameters['before_id'] = $beforeId;
        }

        $sql .= $afterId !== null ? ' ORDER BY m.id ASC' : ' ORDER BY m.id DESC';

        $statement = $this->db->prepare($sql . ' LIMIT :row_limit');

        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value, PDO::PARAM_INT);
        }

        $statement->bindValue('row_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $messages = $statement->fetchAll();

        if ($afterId === null) {
            $messages = array_reverse($messages);
        }

        return $this->attachExtras($messages);
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT m.*, u.full_name AS sender_name, r.room_type
             FROM Message m
             JOIN User u ON u.id = m.sender_id
             JOIN ChatRoom r ON r.id = m.room_id
             WHERE m.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function pinnedForRoom(int $roomId): array
    {
        $statement = $this->db->prepare(
            'SELECT m.*, u.full_name AS sender_name
             FROM Message m
             JOIN User u ON u.id = m.sender_id
             WHERE m.room_id = :room_id AND m.pinned = 1 AND m.deleted_at IS NULL
             ORDER BY m.pinned_at DESC'
        );

        $statement->execute(['room_id' => $roomId]);

        return $statement->fetchAll();
    }

    public function search(int $userId, string $keyword): array
    {
        $statement = $this->db->prepare(
            'SELECT m.id, m.room_id, m.message, m.sent_at, u.full_name AS sender_name,
                    r.room_name
             FROM Message m
             JOIN ChatMember cm ON cm.room_id = m.room_id AND cm.user_id = :user_id
             JOIN User u ON u.id = m.sender_id
             JOIN ChatRoom r ON r.id = m.room_id
             WHERE m.deleted_at IS NULL AND m.message LIKE :keyword
             ORDER BY m.sent_at DESC
             LIMIT 50'
        );

        $statement->execute([
            'user_id' => $userId,
            'keyword' => '%' . $keyword . '%',
        ]);

        return $statement->fetchAll();
    }

    public function softDelete(int $id, int $deletedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Message
             SET deleted_at = UTC_TIMESTAMP(), deleted_by = :deleted_by
             WHERE id = :id'
        );

        return $statement->execute([
            'deleted_by' => $deletedBy,
            'id' => $id,
        ]);
    }

    public function edit(int $id, string $body): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Message
             SET message = :message, edited = 1, edited_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'message' => $body,
            'id' => $id,
        ]);
    }

    public function setPinned(int $id, bool $pinned, int $userId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Message
             SET pinned = :pinned,
                 pinned_by = :pinned_by,
                 pinned_at = CASE WHEN :is_pinned = 1 THEN UTC_TIMESTAMP() ELSE NULL END
             WHERE id = :id'
        );

        return $statement->execute([
            'pinned' => (int) $pinned,
            'pinned_by' => $pinned ? $userId : null,
            'is_pinned' => (int) $pinned,
            'id' => $id,
        ]);
    }

    private function attachExtras(array $messages): array
    {
        if ($messages === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $messages);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $attachments = $this->db->prepare(
            'SELECT * FROM MessageAttachment WHERE message_id IN (' . $placeholders . ')'
        );
        $attachments->execute($ids);

        $reactions = $this->db->prepare(
            'SELECT message_id, reaction, COUNT(*) AS total
             FROM MessageReaction
             WHERE message_id IN (' . $placeholders . ')
             GROUP BY message_id, reaction'
        );
        $reactions->execute($ids);

        $byMessage = [];

        foreach ($attachments->fetchAll() as $row) {
            $byMessage[(int) $row['message_id']]['attachments'][] = $row;
        }

        foreach ($reactions->fetchAll() as $row) {
            $byMessage[(int) $row['message_id']]['reactions'][$row['reaction']] = (int) $row['total'];
        }

        foreach ($messages as $index => $message) {
            $id = (int) $message['id'];

            $messages[$index]['attachments'] = $byMessage[$id]['attachments'] ?? [];
            $messages[$index]['reactions'] = $byMessage[$id]['reactions'] ?? [];

            if ($message['deleted_at'] !== null) {
                $messages[$index]['message'] = null;
                $messages[$index]['attachments'] = [];
            }
        }

        return $messages;
    }
}
