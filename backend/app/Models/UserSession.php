<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class UserSession
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $session): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO UserSession
                (user_id, jwt_token, refresh_token, device_name, browser, operating_system,
                 ip_address, login_time, last_activity, expires_at, status)
             VALUES
                (:user_id, :jwt_token, :refresh_token, :device_name, :browser, :operating_system,
                 :ip_address, UTC_TIMESTAMP(), UTC_TIMESTAMP(), :expires_at, :status)'
        );

        $statement->execute($session);

        return (int) $this->db->lastInsertId();
    }

    public function findActiveByAccessToken(string $tokenHash): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM UserSession
             WHERE jwt_token = :token AND status = :status AND expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );

        $statement->execute([
            'token' => $tokenHash,
            'status' => 'active',
        ]);

        return $statement->fetch() ?: null;
    }

    public function findActiveByRefreshToken(string $tokenHash): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM UserSession
             WHERE refresh_token = :token AND status = :status AND expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );

        $statement->execute([
            'token' => $tokenHash,
            'status' => 'active',
        ]);

        return $statement->fetch() ?: null;
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM UserSession WHERE id = :id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function activeForUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, device_name, browser, operating_system, ip_address,
                    login_time, last_activity, expires_at
             FROM UserSession
             WHERE user_id = :user_id AND status = :status AND expires_at > UTC_TIMESTAMP()
             ORDER BY last_activity DESC'
        );

        $statement->execute([
            'user_id' => $userId,
            'status' => 'active',
        ]);

        return $statement->fetchAll();
    }

    public function rotateTokens(int $id, string $accessTokenHash, string $refreshTokenHash, string $expiresAt): bool
    {
        $statement = $this->db->prepare(
            'UPDATE UserSession
             SET jwt_token = :jwt_token,
                 refresh_token = :refresh_token,
                 expires_at = :expires_at,
                 last_activity = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'jwt_token' => $accessTokenHash,
            'refresh_token' => $refreshTokenHash,
            'expires_at' => $expiresAt,
            'id' => $id,
        ]);
    }

    public function touch(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE UserSession SET last_activity = UTC_TIMESTAMP() WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function revoke(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE UserSession
             SET status = :status, logout_time = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => 'revoked',
            'id' => $id,
        ]);
    }

    public function revokeAllForUser(int $userId, ?int $exceptSessionId = null): bool
    {
        $sql = 'UPDATE UserSession
                SET status = :status, logout_time = UTC_TIMESTAMP()
                WHERE user_id = :user_id AND status = :active_status';

        $parameters = [
            'status' => 'revoked',
            'user_id' => $userId,
            'active_status' => 'active',
        ];

        if ($exceptSessionId !== null) {
            $sql .= ' AND id != :except_id';
            $parameters['except_id'] = $exceptSessionId;
        }

        return $this->db->prepare($sql)->execute($parameters);
    }
}
