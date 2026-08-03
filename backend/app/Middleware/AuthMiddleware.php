<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Models\UserSession;
use App\Services\JwtService;

class AuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwt = new JwtService(),
        private readonly UserSession $sessions = new UserSession()
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

        return [
            'user_id' => (int) $payload['sub'],
            'session_id' => (int) $session['id'],
            'role' => $payload['role'],
            'permissions' => (array) $payload['permissions'],
        ];
    }
}
