<?php

declare(strict_types=1);

namespace App\Services;

class DeviceService
{
    public function describe(string $userAgent): array
    {
        return [
            'browser' => $this->browser($userAgent),
            'operating_system' => $this->operatingSystem($userAgent),
            'device_name' => $this->deviceType($userAgent),
        ];
    }

    private function browser(string $userAgent): string
    {
        $browsers = [
            'Edg' => 'Microsoft Edge',
            'OPR' => 'Opera',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Firefox' => 'Firefox',
        ];

        foreach ($browsers as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Unknown';
    }

    private function operatingSystem(string $userAgent): string
    {
        $systems = [
            'Windows' => 'Windows',
            'Mac OS X' => 'macOS',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
            'Linux' => 'Linux',
        ];

        foreach ($systems as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Unknown';
    }

    private function deviceType(string $userAgent): string
    {
        if (str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet')) {
            return 'Tablet';
        }

        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
