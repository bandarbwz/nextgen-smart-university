<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemLog;
use App\Models\SystemSetting;
use App\Models\UserSetting;

class SettingsService
{
    private const LANGUAGES = ['en', 'ar'];

    private const THEMES = ['light', 'dark', 'system'];

    public function __construct(
        private readonly UserSetting $userSettings = new UserSetting(),
        private readonly SystemSetting $systemSettings = new SystemSetting(),
        private readonly SystemLog $log = new SystemLog()
    ) {
    }

    public function mine(array $user): array
    {
        return $this->userSettings->forUser($user['user_id']);
    }

    public function updateMine(array $user, array $fields): array
    {
        $language = $fields['language'] ?? 'en';
        $theme = $fields['theme'] ?? 'system';

        if (!in_array($language, self::LANGUAGES, true)) {
            throw new ApiException('The language must be one of: en, ar.', 422);
        }

        if (!in_array($theme, self::THEMES, true)) {
            throw new ApiException('The theme must be one of: light, dark, system.', 422);
        }

        return $this->userSettings->save($user['user_id'], [
            'language' => $language,
            'theme' => $theme,
            'timezone' => $fields['timezone'] ?? 'UTC',
        ]);
    }

    public function system(): array
    {
        return $this->systemSettings->grouped();
    }

    /**
     * Settings are written one at a time by key, and a value is checked against
     * the type the setting declares. A max_credit_hours of "banana" would not
     * fail until somebody tried to register for a course.
     */
    public function updateSystem(array $user, array $changes): array
    {
        if ($changes === []) {
            throw new ApiException('No settings were supplied.', 422);
        }

        foreach ($changes as $key => $value) {
            $setting = $this->systemSettings->findByKey((string) $key);

            if ($setting === null) {
                throw new ApiException('Unknown setting: ' . $key . '.', 422);
            }

            if (!(bool) $setting['is_editable']) {
                throw new ApiException('The setting ' . $key . ' cannot be changed.', 409);
            }

            $clean = $this->cast((string) $key, $setting['value_type'], $value);

            $this->systemSettings->put((string) $key, $clean, $user['user_id']);

            $this->log->record(
                'Settings',
                'Setting changed',
                'info',
                $key . ' set to ' . $clean,
                $user['user_id']
            );
        }

        return $this->system();
    }

    private function cast(string $key, string $type, mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new ApiException('The value for ' . $key . ' cannot be empty.', 422);
        }

        if ($type === 'integer') {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);

            if ($parsed === false || $parsed < 0) {
                throw new ApiException($key . ' must be a whole number of zero or more.', 422);
            }

            return (string) $parsed;
        }

        if ($type === 'boolean') {
            if (!in_array($value, [true, false, 'true', 'false'], true)) {
                throw new ApiException($key . ' must be true or false.', 422);
            }

            return ($value === true || $value === 'true') ? 'true' : 'false';
        }

        $string = trim((string) $value);

        if (mb_strlen($string) > 500) {
            throw new ApiException($key . ' must not exceed 500 characters.', 422);
        }

        return $string;
    }
}
