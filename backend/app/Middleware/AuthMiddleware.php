<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Models\UserSession;
use App\Services\JwtService;
use App\Services\SystemService;

class AuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwt = new JwtService(),
        private readonly UserSession $sessions = new UserSession(),
        private readonly SystemService $system = new SystemService()
    ) {
    }

    public function authenticate(): array
    {
        $token = Request::bearerToken();

        if ($token === null) {
            Response::error('Authentication token is missing.', 401);
        }

        $payload = $this->jwt->decode($token);

        if ($payload === null) {
            Response::error('Invalid or expired authentication token.', 401);
        }

        $session = $this->sessions->findActiveByAccessToken($this->jwt->hashToken($token));

        if ($session === null) {
            Response::error('This session is no longer active.', 401);
        }

        $this->sessions->touch((int) $session['id']);

        $this->guardMaintenance($payload['role']);

        return [
            'user_id' => (int) $payload['sub'],
            'session_id' => (int) $session['id'],
            'role' => $payload['role'],
            'permissions' => (array) $payload['permissions'],
        ];
    }

    /**
     * Maintenance mode has to bite somewhere every authenticated request passes
     * through, or it is only a label. Administrators are let through so the
     * platform can be brought back up from inside itself.
     */
    private function guardMaintenance(string $role): void
    {
        if ($this->system->shouldBlock($role)) {
            Response::error($this->system->maintenanceMessage(), 503);
        }
    }
}
