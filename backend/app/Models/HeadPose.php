<?php

declare(strict_types=1);

namespace App\Models;

class HeadPose extends Model
{
    protected string $table = 'HeadPose';

    protected string $defaultOrder = 'captured_at';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM HeadPose WHERE session_id = :session_id ORDER BY captured_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }
}
