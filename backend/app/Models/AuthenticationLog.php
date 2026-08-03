<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class AuthenticationLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function record(?int $userId, string $action, string $status, string $ipAddress, string $device): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO AuthenticationLog (user_id, action, status, ip_address, device)
             VALUES (:user_id, :action, :status, :ip_address, :device)'
        );

        return $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'status' => $status,
            'ip_address' => $ipAddress,
            'device' => $device,
        ]);
    }

    public function historyForUser(int $userId, int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT id, action, status, ip_address, device, created_at
             FROM AuthenticationLog
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );

        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
