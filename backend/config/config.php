<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'NextGen Smart University',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        'frontend_url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173',
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'issuer' => $_ENV['JWT_ISSUER'] ?? 'nextgen-smart-university',
        'access_token_ttl' => (int) ($_ENV['JWT_ACCESS_TOKEN_TTL'] ?? 3600),
        'refresh_token_ttl' => (int) ($_ENV['JWT_REFRESH_TOKEN_TTL'] ?? 604800),
    ],

    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@nextgen.edu',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'NextGen Smart University',
    ],

    'ai' => [
        'service_url' => rtrim($_ENV['AI_SERVICE_URL'] ?? '', '/'),
        'timeout' => (int) ($_ENV['AI_SERVICE_TIMEOUT'] ?? 10),
    ],

    'attendance' => [
        'qr_ttl_minutes' => (int) ($_ENV['ATTENDANCE_QR_TTL_MINUTES'] ?? 10),
        'late_after_minutes' => (int) ($_ENV['ATTENDANCE_LATE_AFTER_MINUTES'] ?? 15),
        'default_radius_metres' => (int) ($_ENV['ATTENDANCE_DEFAULT_RADIUS'] ?? 150),
    ],

    'security' => [
        'max_login_attempts' => (int) ($_ENV['SECURITY_MAX_LOGIN_ATTEMPTS'] ?? 5),
        'lockout_minutes' => (int) ($_ENV['SECURITY_LOCKOUT_MINUTES'] ?? 15),
        'password_reset_ttl' => (int) ($_ENV['SECURITY_PASSWORD_RESET_TTL'] ?? 3600),
    ],
];
