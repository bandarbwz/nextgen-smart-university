<?php

declare(strict_types=1);

namespace App\Helpers;

class Config
{
    private static array $items = [];

    public static function load(string $path): void
    {
        self::$items = require $path;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
