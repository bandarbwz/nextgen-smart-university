<?php

declare(strict_types=1);

namespace App\Models;

class FaceDetection extends Model
{
    protected string $table = 'FaceDetection';

    protected string $defaultOrder = 'captured_at';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM FaceDetection WHERE session_id = :session_id ORDER BY captured_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }
}
