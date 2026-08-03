<?php

declare(strict_types=1);

namespace App\Models;

class EyeTracking extends Model
{
    protected string $table = 'EyeTracking';

    protected string $defaultOrder = 'captured_at';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM EyeTracking WHERE session_id = :session_id ORDER BY captured_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function offScreenSeconds(int $sessionId): int
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(off_screen_seconds), 0) FROM EyeTracking WHERE session_id = :session_id'
        );

        $statement->execute(['session_id' => $sessionId]);

        return (int) $statement->fetchColumn();
    }
}
