<?php

declare(strict_types=1);

namespace App\Models;

class SystemSetting extends Model
{
    protected string $table = 'SystemSetting';

    protected string $defaultOrder = 'category, setting_key';

    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $setting) {
            $grouped[$setting['category']][] = $setting;
        }

        return $grouped;
    }

    public function findByKey(string $key): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM SystemSetting WHERE setting_key = :key LIMIT 1'
        );

        $statement->execute(['key' => $key]);

        return $statement->fetch() ?: null;
    }

    /**
     * Reads a setting without throwing. Configuration lookups happen on paths
     * where a missing row must not take the request down, so the caller's
     * default wins instead.
     */
    public function value(string $key, string $default = ''): string
    {
        $setting = $this->findByKey($key);

        return $setting === null ? $default : (string) $setting['setting_value'];
    }

    public function isTrue(string $key): bool
    {
        return strtolower($this->value($key, 'false')) === 'true';
    }

    public function put(string $key, string $value, int $userId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE SystemSetting SET setting_value = :value, updated_by = :updated_by
             WHERE setting_key = :key'
        );

        return $statement->execute([
            'value' => $value,
            'updated_by' => $userId,
            'key' => $key,
        ]);
    }
}
