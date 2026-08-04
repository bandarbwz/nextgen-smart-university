<?php

declare(strict_types=1);

namespace App\Models;

class NotificationPreference extends Model
{
    protected string $table = 'NotificationPreference';

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM NotificationPreference WHERE user_id = :user_id LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: null;
    }

    /**
     * A user who has never opened the settings page has no row, and the default
     * is everything on except push, which has no delivery mechanism yet.
     */
    public function forUser(int $userId): array
    {
        return $this->findByUserId($userId) ?? [
            'user_id' => $userId,
            'in_app_enabled' => 1,
            'email_enabled' => 1,
            'push_enabled' => 0,
        ];
    }

    public function save(int $userId, array $fields): array
    {
        $existing = $this->findByUserId($userId);

        if ($existing === null) {
            $this->create($fields + ['user_id' => $userId]);
        } else {
            $this->update((int) $existing['id'], $fields);
        }

        return $this->forUser($userId);
    }
}
