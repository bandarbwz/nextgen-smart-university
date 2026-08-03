<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Models\QRSession;
use App\Models\Section;

class QrSessionService
{
    public function __construct(
        private readonly QRSession $sessions = new QRSession(),
        private readonly Section $sections = new Section()
    ) {
    }

    public function open(int $sectionId, int $openedByUserId, array $options): array
    {
        $section = $this->sections->findDetailed($sectionId);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        $existing = $this->sessions->activeForSection($sectionId);

        if ($existing !== null) {
            throw new ApiException('An attendance session is already open for this section.', 409);
        }

        $ttl = Config::get('attendance.qr_ttl_minutes');

        $id = $this->sessions->create([
            'section_id' => $sectionId,
            'opened_by' => $openedByUserId,
            'qr_token' => bin2hex(random_bytes(24)),
            'session_date' => gmdate('Y-m-d'),
            'generated_at' => gmdate('Y-m-d H:i:s'),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttl * 60),
            'latitude' => $options['latitude'] ?? null,
            'longitude' => $options['longitude'] ?? null,
            'allowed_radius' => $options['allowed_radius'] ?? Config::get('attendance.default_radius_metres'),
            'status' => 'active',
        ]);

        return $this->present($this->sessions->find($id), $section);
    }

    public function activeForSection(int $sectionId): array
    {
        $session = $this->sessions->activeForSection($sectionId);

        if ($session === null) {
            throw new ApiException('No attendance session is currently open for this section.', 404);
        }

        return $this->present($session, $this->sections->findDetailed($sectionId));
    }

    public function close(int $id, int $lecturerUserId, string $role): array
    {
        $session = $this->sessions->find($id);

        if ($session === null) {
            throw new ApiException('Attendance session not found.', 404);
        }

        if ($role !== 'Administrator' && (int) $session['opened_by'] !== $lecturerUserId) {
            throw new ApiException('You can only close sessions that you opened.', 403);
        }

        if ($session['status'] !== 'active') {
            throw new ApiException('This attendance session is already closed.', 409);
        }

        $this->sessions->close($id);

        return $this->present(
            $this->sessions->find($id),
            $this->sections->findDetailed((int) $session['section_id'])
        );
    }

    public function requireActiveByToken(string $token): array
    {
        $session = $this->sessions->findByToken($token);

        if ($session === null) {
            throw new ApiException('This QR code is not valid.', 404);
        }

        if ($session['status'] !== 'active') {
            throw new ApiException('This attendance session has been closed.', 409);
        }

        if (strtotime($session['expires_at'] . ' UTC') <= time()) {
            throw new ApiException('This QR code has expired.', 409);
        }

        return $session;
    }

    private function present(array $session, ?array $section): array
    {
        return [
            'id' => (int) $session['id'],
            'section_id' => (int) $session['section_id'],
            'course_code' => $section['course_code'] ?? null,
            'course_name' => $section['course_name'] ?? null,
            'section_number' => $section['section_number'] ?? null,
            'qr_token' => $session['qr_token'],
            'session_date' => $session['session_date'],
            'expires_at' => $session['expires_at'],
            'allowed_radius' => (int) $session['allowed_radius'],
            'requires_location' => $session['latitude'] !== null,
            'status' => $session['status'],
            'attendee_count' => $this->sessions->attendeeCount((int) $session['id']),
        ];
    }
}
