<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Logger;

class FaceVerificationService
{
    public function verify(int $userId, string $imageData): array
    {
        $baseUrl = Config::get('ai.service_url');

        if ($baseUrl === '') {
            throw new ApiException(
                'Face verification is unavailable because the AI service is not configured.',
                503
            );
        }

        $payload = json_encode([
            'user_id' => $userId,
            'image' => $imageData,
        ]);

        $handle = curl_init($baseUrl . '/verify-face');

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Config::get('ai.timeout'),
        ]);

        $response = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);

        curl_close($handle);

        if ($response === false || $statusCode === 0) {
            Logger::error('AI face verification unreachable', ['error' => $error]);

            throw new ApiException('The AI verification service is currently unavailable.', 503);
        }

        $decoded = json_decode((string) $response, true);

        if ($statusCode >= 400 || !is_array($decoded)) {
            Logger::error('AI face verification failed', ['status' => $statusCode]);

            throw new ApiException('Face verification could not be completed. Please try again.', 502);
        }

        return [
            'verified' => (bool) ($decoded['verified'] ?? false),
            'confidence' => (float) ($decoded['confidence'] ?? 0),
        ];
    }
}
