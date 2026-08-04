<?php

declare(strict_types=1);

namespace App\Models;

class EventQrSession extends Model
{
    protected string $table = 'EventQrSession';

    protected string $defaultOrder = 'generated_at DESC';

    public function activeForEvent(int $eventId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM EventQrSession
             WHERE event_id = :event_id AND status = 'active' AND expires_at > UTC_TIMESTAMP()
             ORDER BY id DESC
             LIMIT 1"
        );

        $statement->execute(['event_id' => $eventId]);

        return $statement->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM EventQrSession WHERE qr_token = :token LIMIT 1'
        );

        $statement->execute(['token' => $token]);

        return $statement->fetch() ?: null;
    }

    public function close(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE EventQrSession
             SET status = :status, closed_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }
}
