<?php

declare(strict_types=1);

namespace App\Validation;

class AuthValidator
{
    public function login(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'email', 'Email')
            ->email($data, 'email', 'Email')
            ->required($data, 'password', 'Password');

        return $validator->errors();
    }

    public function forgotPassword(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'email', 'Email')
            ->email($data, 'email', 'Email');

        return $validator->errors();
    }

    public function resetPassword(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'token', 'Token')
            ->required($data, 'password', 'Password')
            ->password($data, 'password', 'Password')
            ->required($data, 'password_confirmation', 'Password confirmation')
            ->matches($data, 'password', 'password_confirmation', 'Password');

        return $validator->errors();
    }

    public function changePassword(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'current_password', 'Current password')
            ->required($data, 'new_password', 'New password')
            ->password($data, 'new_password', 'New password')
            ->required($data, 'password_confirmation', 'Password confirmation')
            ->matches($data, 'new_password', 'password_confirmation', 'Password');

        return $validator->errors();
    }

    public function updateProfile(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'full_name', 'Full name')
            ->maxLength($data, 'full_name', 255, 'Full name')
            ->phone($data, 'phone', 'Phone');

        return $validator->errors();
    }

    public function verifyEmail(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'token', 'Token');

        return $validator->errors();
    }

    public function refreshToken(array $data): array
    {
        $validator = (new Validator())
            ->required($data, 'refresh_token', 'Refresh token');

        return $validator->errors();
    }
}
