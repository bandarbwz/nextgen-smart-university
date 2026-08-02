<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

class RoleMiddleware
{
    public function requireRole(array $authenticatedUser, array $allowedRoles): void
    {
        if ($authenticatedUser['role'] === 'Administrator') {
            return;
        }

        if (!in_array($authenticatedUser['role'], $allowedRoles, true)) {
            Response::error('You do not have permission to perform this action.', 403);
        }
    }

    public function requirePermission(array $authenticatedUser, string $permission): void
    {
        if ($authenticatedUser['role'] === 'Administrator') {
            return;
        }

        if (!in_array($permission, $authenticatedUser['permissions'], true)) {
            Response::error('You do not have permission to perform this action.', 403);
        }
    }
}
