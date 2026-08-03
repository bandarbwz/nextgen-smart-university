<?php

declare(strict_types=1);

namespace App\Models;

class QRSession extends Model
{
    protected string $table = 'QRSession';

    public function findByToken(string $token): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM QRSession WHERE qr_token = :token LIMIT 1'
        );

        $statement->execute(['token' => $token]);

        return $statement->fetch() ?: null;
    }

    public function activeForSection(int $sectionId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM QRSession
             WHERE section_id = :section_id
               AND status = :status
               AND expires_at > UTC_TIMESTAMP()
             ORDER BY generated_at DESC
             LIMIT 1'
        );

        $statement->execute([
            'section_id' => $sectionId,
            'status' => 'active',
        ]);

        return $statement->fetch() ?: null;
    }

    public function close(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE QRSession
             SET status = :status, closed_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => 'closed',
            'id' => $id,
        ]);
    }

    public function attendeeCount(int $id): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM Attendance WHERE qr_session_id = :id'
        );

        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn();
    }
}
