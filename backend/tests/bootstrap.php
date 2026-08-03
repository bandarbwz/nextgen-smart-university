<?php

declare(strict_types=1);

use App\Helpers\Config;
use Dotenv\Dotenv;
use Tests\TestDatabase;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/TestDatabase.php';
require __DIR__ . '/TestCase.php';

$basePath = dirname(__DIR__);

Dotenv::createImmutable($basePath)->safeLoad();

$_ENV['DB_DATABASE'] = $_ENV['DB_TEST_DATABASE'] ?? 'nextgen_university_test';
$_ENV['JWT_SECRET'] = $_ENV['JWT_SECRET'] ?: str_repeat('a', 64);
$_ENV['AI_SERVICE_URL'] = '';

Config::load($basePath . '/config/config.php');

TestDatabase::rebuild();
