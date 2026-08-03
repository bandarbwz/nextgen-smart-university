<?php

declare(strict_types=1);

use App\Helpers\Config;
use App\Helpers\Logger;
use App\Helpers\Response;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

Dotenv::createImmutable($basePath)->safeLoad();
Config::load($basePath . '/config/config.php');

if (Config::get('jwt.secret') === '') {
    Logger::error('JWT_SECRET is not configured');

    Response::error('The server is not configured correctly.', 500);
}

$allowedOrigin = Config::get('app.frontend_url');

header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);

    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    Logger::error('Unhandled exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    Response::error('Something went wrong. Please try again later.', 500);
});

$router = require $basePath . '/routes/api.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
