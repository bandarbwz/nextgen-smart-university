<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtService
{
    public function issueAccessToken(int $userId, string $role, array $permissions, string $sessionId): string
    {
        $issuedAt = time();

        $payload = [
            'iss' => Config::get('jwt.issuer'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + Config::get('jwt.access_token_ttl'),
            'sub' => $userId,
            'sid' => $sessionId,
            'role' => $role,
            'permissions' => $permissions,
        ];

        return JWT::encode($payload, Config::get('jwt.secret'), Config::get('jwt.algorithm'));
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key(Config::get('jwt.secret'), Config::get('jwt.algorithm'))
            );

            return (array) $decoded;
        } catch (Throwable) {
            return null;
        }
    }

    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
