<?php

declare(strict_types=1);

namespace App\Models;

class BrowserActivity extends Model
{
    protected string $table = 'BrowserActivity';

    protected string $defaultOrder = 'occurred_at';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM BrowserActivity WHERE session_id = :session_id ORDER BY occurred_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }
}
