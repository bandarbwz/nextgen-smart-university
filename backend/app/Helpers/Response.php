<?php

declare(strict_types=1);

namespace App\Helpers;

class Response
{
    public static function success(string $message, array $data = [], int $status = 200): void
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== []) {
            $payload['data'] = $data;
        }

        self::send($payload, $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        self::send($payload, $status);
    }

    public static function validationError(array $errors): void
    {
        self::error('Validation failed.', 422, $errors);
    }

    private static function send(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}
