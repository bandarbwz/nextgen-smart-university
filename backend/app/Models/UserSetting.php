<?php

declare(strict_types=1);

namespace App\Models;

class UserSetting extends Model
{
    protected string $table = 'UserSetting';

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM UserSetting WHERE user_id = :user_id LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: null;
    }

    /**
     * A user who has never opened the settings page has no row, so the defaults
     * are returned rather than nothing.
     */
    public function forUser(int $userId): array
    {
        return $this->findByUserId($userId) ?? [
            'user_id' => $userId,
            'language' => 'en',
            'theme' => 'system',
            'timezone' => 'UTC',
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
