<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Database;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use Throwable;

/**
 * Health, maintenance and the system log.
 *
 * Every check here measures something real. A dashboard that reports green
 * because it never looked is worse than no dashboard, because somebody will
 * trust it.
 */
class SystemService
{
    public function __construct(
        private readonly SystemSetting $settings = new SystemSetting(),
        private readonly SystemLog $log = new SystemLog(),
        private readonly AiProctorService $ai = new AiProctorService()
    ) {
    }

    public function health(): array
    {
        return [
            'checked_at' => gmdate('Y-m-d H:i:s'),
            'maintenance_mode' => $this->settings->isTrue('maintenance_mode'),
            'checks' => [
                $this->databaseCheck(),
                $this->storageCheck(),
                $this->aiServiceCheck(),
                $this->mailCheck(),
            ],
        ];
    }

    /**
     * The maintenance decision, kept here rather than inside the middleware so
     * it can be tested directly instead of only through an HTTP request.
     * Administrators are always let through, so the platform can be brought
     * back up from inside itself.
     */
    public function shouldBlock(string $role): bool
    {
        if ($role === 'Administrator') {
            return false;
        }

        return $this->settings->isTrue('maintenance_mode');
    }

    public function maintenanceMessage(): string
    {
        return $this->settings->value(
            'maintenance_message',
            'The platform is under maintenance. Please try again shortly.'
        );
    }

    public function logs(array $filters): array
    {
        return [
            'log' => $this->log->recent($filters),
            'counts' => $this->log->counts(),
        ];
    }

    public function setMaintenance(array $user, bool $enabled, ?string $message): array
    {
        $this->settings->put('maintenance_mode', $enabled ? 'true' : 'false', $user['user_id']);

        if ($message !== null && trim($message) !== '') {
            $this->settings->put('maintenance_message', trim($message), $user['user_id']);
        }

        $this->log->record(
            'System',
            $enabled ? 'Maintenance enabled' : 'Maintenance disabled',
            $enabled ? 'warning' : 'info',
            $enabled
                ? 'Only administrators can use the platform.'
                : 'Normal service resumed.',
            $user['user_id']
        );

        return [
            'maintenance_mode' => $enabled,
            'maintenance_message' => $this->settings->value('maintenance_message'),
        ];
    }

    private function databaseCheck(): array
    {
        $started = microtime(true);

        try {
            $connection = Database::connection();
            $tables = (int) $connection
                ->query(
                    "SELECT COUNT(*) FROM information_schema.tables
                     WHERE table_schema = DATABASE()"
                )
                ->fetchColumn();

            return $this->result('Database', 'up', [
                'tables' => $tables,
                'response_ms' => round((microtime(true) - $started) * 1000, 1),
            ]);
        } catch (Throwable $exception) {
            return $this->result('Database', 'down', ['error' => $exception->getMessage()]);
        }
    }

    private function storageCheck(): array
    {
        $path = dirname(__DIR__, 2) . '/storage';

        if (!is_dir($path)) {
            return $this->result('Storage', 'down', ['error' => 'The storage folder is missing.']);
        }

        if (!is_writable($path)) {
            return $this->result('Storage', 'down', ['error' => 'The storage folder is not writable.']);
        }

        $free = disk_free_space($path);
        $total = disk_total_space($path);

        $freeGb = $free === false ? null : round($free / 1073741824, 1);
        $usedPercent = ($free === false || $total === false || $total <= 0)
            ? null
            : round(100 - ($free / $total * 100), 1);

        return $this->result(
            'Storage',
            ($usedPercent !== null && $usedPercent > 95) ? 'degraded' : 'up',
            [
                'free_gb' => $freeGb,
                'used_percent' => $usedPercent,
            ]
        );
    }

    /**
     * Reports not configured rather than down. The service being absent is the
     * documented state of this platform, not a fault to be alarmed about.
     */
    private function aiServiceCheck(): array
    {
        if (!$this->ai->isConfigured()) {
            return $this->result('AI Service', 'not configured', [
                'detail' => 'AI_SERVICE_URL is empty. Face, eye and head pose checks return 503.',
            ]);
        }

        return $this->result('AI Service', 'configured', [
            'url' => Config::get('ai.service_url'),
            'detail' => 'Configured. Reachability is proven only when a check is actually run.',
        ]);
    }

    private function mailCheck(): array
    {
        $host = (string) Config::get('mail.host', '');
        $username = (string) Config::get('mail.username', '');
        $password = (string) Config::get('mail.password', '');

        if ($host === '') {
            return $this->result('Email', 'not configured', [
                'detail' => 'MAIL_HOST is empty. Password reset and notification emails will fail.',
            ]);
        }

        if ($username === '' || $password === '') {
            return $this->result('Email', 'not configured', [
                'host' => $host,
                'detail' => 'A host is set but the credentials are empty, so the server will refuse to send.',
            ]);
        }

        return $this->result('Email', 'configured', ['host' => $host]);
    }

    private function result(string $name, string $status, array $detail): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'detail' => $detail,
        ];
    }
}
