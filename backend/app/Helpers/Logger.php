<?php

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $directory = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $line = sprintf(
            "[%s] %s: %s %s%s",
            gmdate('Y-m-d H:i:s'),
            $level,
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        file_put_contents($directory . '/app-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
