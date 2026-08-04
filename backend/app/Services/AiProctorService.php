<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Logger;

/**
 * Client for the Python computer vision service. Nothing here invents a
 * detection result. When the service is not configured or cannot be reached the
 * call fails with a 503 so a missing proctor is never mistaken for a clean one.
 */
class AiProctorService
{
    public function isConfigured(): bool
    {
        return Config::get('ai.service_url') !== '';
    }

    public function detectFaces(array $payload): array
    {
        $result = $this->call('/detect-faces', $payload);

        return [
            'faces_detected' => (int) ($result['faces_detected'] ?? 0),
            'confidence' => $this->confidence($result),
        ];
    }

    public function trackEyes(array $payload): array
    {
        $result = $this->call('/eye-tracking', $payload);

        return [
            'gaze_direction' => $this->gaze($result['gaze_direction'] ?? null),
            'off_screen_seconds' => max(0, (int) ($result['off_screen_seconds'] ?? 0)),
            'confidence' => $this->confidence($result),
        ];
    }

    public function estimateHeadPose(array $payload): array
    {
        $result = $this->call('/head-pose', $payload);

        return [
            'yaw' => (float) ($result['yaw'] ?? 0),
            'pitch' => (float) ($result['pitch'] ?? 0),
            'roll' => (float) ($result['roll'] ?? 0),
            'confidence' => $this->confidence($result),
        ];
    }

    private function call(string $path, array $payload): array
    {
        $baseUrl = Config::get('ai.service_url');

        if ($baseUrl === '') {
            throw new ApiException(
                'AI proctoring is unavailable because the AI service is not configured.',
                503
            );
        }

        $handle = curl_init($baseUrl . $path);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Config::get('ai.timeout'),
        ]);

        $response = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);

        curl_close($handle);

        if ($response === false || $statusCode === 0) {
            Logger::error('AI proctoring service unreachable', ['path' => $path, 'error' => $error]);

            throw new ApiException('The AI proctoring service is currently unavailable.', 503);
        }

        $decoded = json_decode((string) $response, true);

        if ($statusCode >= 400 || !is_array($decoded)) {
            Logger::error('AI proctoring request failed', ['path' => $path, 'status' => $statusCode]);

            throw new ApiException('The AI proctoring request could not be completed.', 502);
        }

        return $decoded;
    }

    private function confidence(array $result): ?float
    {
        return isset($result['confidence']) ? round((float) $result['confidence'], 3) : null;
    }

    private function gaze(?string $direction): string
    {
        $allowed = ['centre', 'left', 'right', 'up', 'down', 'off-screen'];

        return in_array($direction, $allowed, true) ? $direction : 'centre';
    }
}
