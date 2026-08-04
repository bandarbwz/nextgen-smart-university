<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class MailService
{
    public function sendPasswordReset(string $email, string $fullName, string $token): bool
    {
        $link = Config::get('app.frontend_url') . '/reset-password?token=' . urlencode($token);

        $body = $this->template(
            'Password Reset Request',
            $fullName,
            'We received a request to reset your password. Use the link below to choose a new password.',
            $link,
            'Reset Password',
            'This link expires in one hour. If you did not request a reset, you can safely ignore this email.'
        );

        return $this->send($email, $fullName, 'Reset your password', $body);
    }

    public function sendEmailVerification(string $email, string $fullName, string $token): bool
    {
        $link = Config::get('app.frontend_url') . '/verify-email?token=' . urlencode($token);

        $body = $this->template(
            'Verify Your Email',
            $fullName,
            'Please confirm your email address to activate full access to your account.',
            $link,
            'Verify Email',
            'If you did not create this account, please contact the university administration.'
        );

        return $this->send($email, $fullName, 'Verify your email address', $body);
    }

    public function sendNotification(
        string $email,
        string $fullName,
        string $title,
        string $message
    ): bool {
        $body = $this->template(
            $title,
            $fullName,
            $message,
            Config::get('app.frontend_url') . '/notifications',
            'Open Notification Center',
            'You are receiving this because email notifications are enabled on your account.'
        );

        return $this->send($email, $fullName, $title, $body);
    }

    public function sendSecurityAlert(string $email, string $fullName, string $event): bool
    {
        $body = $this->template(
            'Security Notification',
            $fullName,
            'A security event was recorded on your account: ' . htmlspecialchars($event, ENT_QUOTES, 'UTF-8') . '.',
            null,
            null,
            'If this was not you, please contact the university administration immediately.'
        );

        return $this->send($email, $fullName, 'Security notification', $body);
    }

    private function send(string $email, string $fullName, string $subject, string $body): bool
    {
        try {
            $mailer = new PHPMailer(true);

            $mailer->isSMTP();
            $mailer->Host = Config::get('mail.host');
            $mailer->Port = Config::get('mail.port');
            $mailer->SMTPAuth = true;
            $mailer->Username = Config::get('mail.username');
            $mailer->Password = Config::get('mail.password');
            $mailer->SMTPSecure = Config::get('mail.encryption');
            $mailer->CharSet = 'UTF-8';

            $mailer->setFrom(Config::get('mail.from_address'), Config::get('mail.from_name'));
            $mailer->addAddress($email, $fullName);

            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);

            $mailer->send();

            return true;
        } catch (Throwable $exception) {
            Logger::error('Email delivery failed', [
                'subject' => $subject,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function template(
        string $heading,
        string $fullName,
        string $intro,
        ?string $link,
        ?string $buttonLabel,
        string $footer
    ): string {
        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');

        $button = '';

        if ($link !== null && $buttonLabel !== null) {
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

            $button = '<p style="margin: 24px 0;">'
                . '<a href="' . $safeLink . '" style="background: #1d4ed8; color: #ffffff; padding: 12px 24px; '
                . 'border-radius: 6px; text-decoration: none; display: inline-block;">' . $buttonLabel . '</a>'
                . '</p>';
        }

        return '<div style="font-family: Arial, sans-serif; color: #1f2937; max-width: 560px;">'
            . '<h2 style="color: #111827;">' . $heading . '</h2>'
            . '<p>Dear ' . $safeName . ',</p>'
            . '<p>' . $intro . '</p>'
            . $button
            . '<p style="color: #6b7280; font-size: 13px;">' . $footer . '</p>'
            . '<p style="color: #6b7280; font-size: 13px;">NextGen Smart University</p>'
            . '</div>';
    }
}
