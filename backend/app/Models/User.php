<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM User u
             JOIN Role r ON r.id = u.role_id
             WHERE u.email = :email AND u.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['email' => $email]);

        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM User u
             JOIN Role r ON r.id = u.role_id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByPasswordResetToken(string $tokenHash): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM User
             WHERE password_reset_token = :token
               AND password_reset_expires_at > UTC_TIMESTAMP()
               AND deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['token' => $tokenHash]);

        return $statement->fetch() ?: null;
    }

    public function findByEmailVerificationToken(string $tokenHash): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM User
             WHERE email_verification_token = :token AND deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['token' => $tokenHash]);

        return $statement->fetch() ?: null;
    }

    public function updateProfile(int $id, array $fields): bool
    {
        $allowed = ['full_name', 'phone', 'profile_photo'];
        $updates = array_intersect_key($fields, array_flip($allowed));

        if ($updates === []) {
            return false;
        }

        $assignments = [];

        foreach (array_keys($updates) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }

        $statement = $this->db->prepare(
            'UPDATE User SET ' . implode(', ', $assignments) . ' WHERE id = :id'
        );

        return $statement->execute($updates + ['id' => $id]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User
             SET password = :password,
                 last_password_change = UTC_TIMESTAMP(),
                 password_reset_token = NULL,
                 password_reset_expires_at = NULL
             WHERE id = :id'
        );

        return $statement->execute([
            'password' => $passwordHash,
            'id' => $id,
        ]);
    }

    public function setPasswordResetToken(int $id, string $tokenHash, int $ttlSeconds): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User
             SET password_reset_token = :token,
                 password_reset_expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :ttl SECOND)
             WHERE id = :id'
        );

        return $statement->execute([
            'token' => $tokenHash,
            'ttl' => $ttlSeconds,
            'id' => $id,
        ]);
    }

    public function setEmailVerificationToken(int $id, string $tokenHash): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User SET email_verification_token = :token WHERE id = :id'
        );

        return $statement->execute([
            'token' => $tokenHash,
            'id' => $id,
        ]);
    }

    public function markEmailVerified(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User
             SET email_verified = TRUE, email_verification_token = NULL
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function recordSuccessfulLogin(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User
             SET last_login = UTC_TIMESTAMP(),
                 failed_login_attempts = 0,
                 locked_until = NULL
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function incrementFailedAttempts(int $id): int
    {
        $statement = $this->db->prepare(
            'UPDATE User SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id'
        );

        $statement->execute(['id' => $id]);

        $attempts = $this->db->prepare('SELECT failed_login_attempts FROM User WHERE id = :id');
        $attempts->execute(['id' => $id]);

        return (int) $attempts->fetchColumn();
    }

    public function lockAccount(int $id, int $minutes): bool
    {
        $statement = $this->db->prepare(
            'UPDATE User
             SET locked_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :minutes MINUTE)
             WHERE id = :id'
        );

        return $statement->execute([
            'minutes' => $minutes,
            'id' => $id,
        ]);
    }

    public function changeRole(int $id, int $roleId): bool
    {
        $statement = $this->db->prepare('UPDATE User SET role_id = :role_id WHERE id = :id');

        return $statement->execute([
            'role_id' => $roleId,
            'id' => $id,
        ]);
    }

    public function idsForRole(string $role): array
    {
        $statement = $this->db->prepare(
            "SELECT u.id
             FROM User u
             JOIN Role r ON r.id = u.role_id
             WHERE r.name = :role AND u.status = 'active' AND u.deleted_at IS NULL"
        );

        $statement->execute(['role' => $role]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function activeIds(): array
    {
        return array_map(
            'intval',
            $this->db
                ->query("SELECT id FROM User WHERE status = 'active' AND deleted_at IS NULL")
                ->fetchAll(\PDO::FETCH_COLUMN)
        );
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT 1 FROM User WHERE email = :email AND deleted_at IS NULL';
        $parameters = ['email' => $email];

        if ($excludeUserId !== null) {
            $sql .= ' AND id != :id';
            $parameters['id'] = $excludeUserId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }
}
