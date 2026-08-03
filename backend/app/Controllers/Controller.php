<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\ApiException;
use Closure;

abstract class Controller
{
    protected AuthMiddleware $auth;

    protected RoleMiddleware $roles;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
        $this->roles = new RoleMiddleware();
    }

    protected function authenticate(): array
    {
        return $this->auth->authenticate();
    }

    protected function authenticateAs(array $allowedRoles): array
    {
        $user = $this->auth->authenticate();

        $this->roles->requireRole($user, $allowedRoles);

        return $user;
    }

    protected function authenticateAsAdministrator(): array
    {
        return $this->authenticateAs([]);
    }

    protected function run(Closure $action): mixed
    {
        try {
            return $action();
        } catch (ApiException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }
    }

    protected function queryInt(string $key): ?int
    {
        $value = $_GET[$key] ?? null;

        if ($value === null || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (int) $value;
    }

    protected function queryString(string $key): ?string
    {
        $value = $_GET[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
