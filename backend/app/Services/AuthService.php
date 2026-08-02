<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Models\AuthenticationLog;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserSession;

class AuthService
{
    public function __construct(
        private readonly User $users = new User(),
        private readonly UserSession $sessions = new UserSession(),
        private readonly Permission $permissions = new Permission(),
        private readonly AuthenticationLog $logs = new AuthenticationLog(),
        private readonly JwtService $jwt = new JwtService(),
        private readonly MailService $mail = new MailService(),
        private readonly DeviceService $devices = new DeviceService()
    ) {
    }

    public function login(string $email, string $password, string $ipAddress, string $userAgent): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->logs->record(null, 'login', 'failed', $ipAddress, $userAgent);

            throw new ApiException('Invalid email or password.', 401);
        }

        $this->guardAccountIsUsable($user, $ipAddress, $userAgent);

        if (!password_verify($password, $user['password'])) {
            $this->handleFailedAttempt($user, $ipAddress, $userAgent);

            throw new ApiException('Invalid email or password.', 401);
        }

        $this->users->recordSuccessfulLogin((int) $user['id']);
        $this->logs->record((int) $user['id'], 'login', 'success', $ipAddress, $userAgent);

        return $this->startSession($user, $ipAddress, $userAgent);
    }

    public function logout(int $sessionId, int $userId, string $ipAddress, string $userAgent): void
    {
        $this->sessions->revoke($sessionId);
        $this->logs->record($userId, 'logout', 'success', $ipAddress, $userAgent);
    }

    public function refresh(string $refreshToken, string $ipAddress, string $userAgent): array
    {
        $session = $this->sessions->findActiveByRefreshToken($this->jwt->hashToken($refreshToken));

        if ($session === null) {
            throw new ApiException('Invalid or expired refresh token.', 401);
        }

        $user = $this->users->findById((int) $session['user_id']);

        if ($user === null) {
            throw new ApiException('Invalid or expired refresh token.', 401);
        }

        $this->guardAccountIsUsable($user, $ipAddress, $userAgent);

        $permissions = $this->permissions->namesForRole((int) $user['role_id']);
        $newRefreshToken = $this->jwt->generateRefreshToken();

        $accessToken = $this->jwt->issueAccessToken(
            (int) $user['id'],
            (string) $user['role_name'],
            $permissions,
            (string) $session['id']
        );

        $this->sessions->rotateTokens(
            (int) $session['id'],
            $this->jwt->hashToken($accessToken),
            $this->jwt->hashToken($newRefreshToken),
            $this->refreshExpiryTimestamp()
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => Config::get('jwt.access_token_ttl'),
            'user' => $this->presentUser($user, $permissions),
        ];
    }

    public function forgotPassword(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $this->users->setPasswordResetToken(
            (int) $user['id'],
            $this->jwt->hashToken($token),
            Config::get('security.password_reset_ttl')
        );

        $this->mail->sendPasswordReset($user['email'], $user['full_name'], $token);
    }

    public function resetPassword(string $token, string $password, string $ipAddress, string $userAgent): void
    {
        $user = $this->users->findByPasswordResetToken($this->jwt->hashToken($token));

        if ($user === null) {
            throw new ApiException('This password reset link is invalid or has expired.', 400);
        }

        $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));
        $this->sessions->revokeAllForUser((int) $user['id']);
        $this->logs->record((int) $user['id'], 'password_reset', 'success', $ipAddress, $userAgent);

        $this->mail->sendSecurityAlert($user['email'], $user['full_name'], 'password reset');
    }

    public function changePassword(
        int $userId,
        int $sessionId,
        string $currentPassword,
        string $newPassword,
        string $ipAddress,
        string $userAgent
    ): void {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new ApiException('Account not found.', 404);
        }

        if (!password_verify($currentPassword, $user['password'])) {
            $this->logs->record($userId, 'password_change', 'failed', $ipAddress, $userAgent);

            throw new ApiException('The current password is incorrect.', 401);
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT));
        $this->sessions->revokeAllForUser($userId, $sessionId);
        $this->logs->record($userId, 'password_change', 'success', $ipAddress, $userAgent);

        $this->mail->sendSecurityAlert($user['email'], $user['full_name'], 'password change');
    }

    public function sendEmailVerification(int $userId): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new ApiException('Account not found.', 404);
        }

        if ((bool) $user['email_verified']) {
            throw new ApiException('This email address is already verified.', 409);
        }

        $token = bin2hex(random_bytes(32));

        $this->users->setEmailVerificationToken($userId, $this->jwt->hashToken($token));
        $this->mail->sendEmailVerification($user['email'], $user['full_name'], $token);
    }

    public function verifyEmail(string $token, string $ipAddress, string $userAgent): void
    {
        $user = $this->users->findByEmailVerificationToken($this->jwt->hashToken($token));

        if ($user === null) {
            throw new ApiException('This verification link is invalid or has expired.', 400);
        }

        $this->users->markEmailVerified((int) $user['id']);
        $this->logs->record((int) $user['id'], 'email_verification', 'success', $ipAddress, $userAgent);
    }

    public function profile(int $userId): array
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new ApiException('Account not found.', 404);
        }

        return $this->presentUser($user, $this->permissions->namesForRole((int) $user['role_id']));
    }

    public function updateProfile(int $userId, array $fields, string $ipAddress, string $userAgent): array
    {
        $this->users->updateProfile($userId, $fields);
        $this->logs->record($userId, 'profile_update', 'success', $ipAddress, $userAgent);

        return $this->profile($userId);
    }

    public function activeSessions(int $userId, int $currentSessionId): array
    {
        $sessions = $this->sessions->activeForUser($userId);

        foreach ($sessions as $index => $session) {
            $sessions[$index]['is_current'] = (int) $session['id'] === $currentSessionId;
        }

        return $sessions;
    }

    public function revokeSession(int $sessionId, int $userId, string $ipAddress, string $userAgent): void
    {
        $session = $this->sessions->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            throw new ApiException('Session not found.', 404);
        }

        $this->sessions->revoke($sessionId);
        $this->logs->record($userId, 'session_revoked', 'success', $ipAddress, $userAgent);
    }

    private function startSession(array $user, string $ipAddress, string $userAgent): array
    {
        $permissions = $this->permissions->namesForRole((int) $user['role_id']);
        $device = $this->devices->describe($userAgent);
        $refreshToken = $this->jwt->generateRefreshToken();

        $sessionId = $this->sessions->create([
            'user_id' => (int) $user['id'],
            'jwt_token' => $this->jwt->hashToken($refreshToken . ':pending'),
            'refresh_token' => $this->jwt->hashToken($refreshToken),
            'device_name' => $device['device_name'],
            'browser' => $device['browser'],
            'operating_system' => $device['operating_system'],
            'ip_address' => $ipAddress,
            'expires_at' => $this->refreshExpiryTimestamp(),
            'status' => 'active',
        ]);

        $accessToken = $this->jwt->issueAccessToken(
            (int) $user['id'],
            (string) $user['role_name'],
            $permissions,
            (string) $sessionId
        );

        $this->sessions->rotateTokens(
            $sessionId,
            $this->jwt->hashToken($accessToken),
            $this->jwt->hashToken($refreshToken),
            $this->refreshExpiryTimestamp()
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => Config::get('jwt.access_token_ttl'),
            'user' => $this->presentUser($user, $permissions),
        ];
    }

    private function guardAccountIsUsable(array $user, string $ipAddress, string $userAgent): void
    {
        if ($user['status'] !== 'active') {
            $this->logs->record((int) $user['id'], 'login', 'failed', $ipAddress, $userAgent);

            throw new ApiException('This account is not active. Please contact the administration.', 403);
        }

        if ($user['locked_until'] !== null && strtotime($user['locked_until'] . ' UTC') > time()) {
            $this->logs->record((int) $user['id'], 'login', 'failed', $ipAddress, $userAgent);

            throw new ApiException('This account is temporarily locked. Please try again later.', 403);
        }
    }

    private function handleFailedAttempt(array $user, string $ipAddress, string $userAgent): void
    {
        $attempts = $this->users->incrementFailedAttempts((int) $user['id']);
        $this->logs->record((int) $user['id'], 'login', 'failed', $ipAddress, $userAgent);

        if ($attempts >= Config::get('security.max_login_attempts')) {
            $this->users->lockAccount((int) $user['id'], Config::get('security.lockout_minutes'));
            $this->mail->sendSecurityAlert($user['email'], $user['full_name'], 'account locked after failed logins');
        }
    }

    private function refreshExpiryTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s', time() + Config::get('jwt.refresh_token_ttl'));
    }

    private function presentUser(array $user, array $permissions): array
    {
        return [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'university_id' => $user['university_id'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'profile_photo' => $user['profile_photo'],
            'status' => $user['status'],
            'email_verified' => (bool) $user['email_verified'],
            'role' => $user['role_name'] ?? null,
            'permissions' => $permissions,
            'last_login' => $user['last_login'],
        ];
    }
}
