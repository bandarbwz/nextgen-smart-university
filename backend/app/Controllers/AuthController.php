<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\AuthException;
use App\Services\AuthService;
use App\Validation\AuthValidator;

class AuthController
{
    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly AuthValidator $validator = new AuthValidator(),
        private readonly AuthMiddleware $middleware = new AuthMiddleware()
    ) {
    }

    public function login(): void
    {
        $data = Request::body();
        $errors = $this->validator->login($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $result = $this->auth->login(
                trim($data['email']),
                $data['password'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Login successful.', $result);
    }

    public function logout(): void
    {
        $user = $this->middleware->authenticate();

        $this->auth->logout(
            $user['session_id'],
            $user['user_id'],
            Request::ipAddress(),
            Request::userAgent()
        );

        Response::success('Logout successful.');
    }

    public function refresh(): void
    {
        $data = Request::body();
        $errors = $this->validator->refreshToken($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $result = $this->auth->refresh(
                $data['refresh_token'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Token refreshed successfully.', $result);
    }

    public function forgotPassword(): void
    {
        $data = Request::body();
        $errors = $this->validator->forgotPassword($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $this->auth->forgotPassword(trim($data['email']));

        Response::success('If an account matches that email, password reset instructions have been sent.');
    }

    public function resetPassword(): void
    {
        $data = Request::body();
        $errors = $this->validator->resetPassword($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $this->auth->resetPassword(
                $data['token'],
                $data['password'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Password reset successfully.');
    }

    public function changePassword(): void
    {
        $user = $this->middleware->authenticate();

        $data = Request::body();
        $errors = $this->validator->changePassword($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $this->auth->changePassword(
                $user['user_id'],
                $user['session_id'],
                $data['current_password'],
                $data['new_password'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Password changed successfully.');
    }

    public function verifyEmail(): void
    {
        $data = Request::body();
        $errors = $this->validator->verifyEmail($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $this->auth->verifyEmail(
                $data['token'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Email verified successfully.');
    }

    public function resendVerification(): void
    {
        $user = $this->middleware->authenticate();

        try {
            $this->auth->sendEmailVerification($user['user_id']);
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Verification email sent.');
    }

    public function profile(): void
    {
        $user = $this->middleware->authenticate();

        try {
            $profile = $this->auth->profile($user['user_id']);
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Profile retrieved successfully.', ['user' => $profile]);
    }

    public function updateProfile(): void
    {
        $user = $this->middleware->authenticate();

        $data = Request::body();
        $errors = $this->validator->updateProfile($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        try {
            $profile = $this->auth->updateProfile(
                $user['user_id'],
                [
                    'full_name' => trim($data['full_name']),
                    'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
                ],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Profile updated successfully.', ['user' => $profile]);
    }

    public function sessions(): void
    {
        $user = $this->middleware->authenticate();

        $sessions = $this->auth->activeSessions($user['user_id'], $user['session_id']);

        Response::success('Active sessions retrieved successfully.', ['sessions' => $sessions]);
    }

    public function revokeSession(string $sessionId): void
    {
        $user = $this->middleware->authenticate();

        try {
            $this->auth->revokeSession(
                (int) $sessionId,
                $user['user_id'],
                Request::ipAddress(),
                Request::userAgent()
            );
        } catch (AuthException $exception) {
            Response::error($exception->getMessage(), $exception->statusCode());
        }

        Response::success('Session revoked successfully.');
    }
}
