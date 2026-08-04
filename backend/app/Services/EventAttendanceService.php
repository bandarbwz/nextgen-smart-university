<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Models\EventAttendance;
use App\Models\EventQrSession;
use App\Models\EventRegistration;

class EventAttendanceService
{
    public function __construct(
        private readonly EventQrSession $sessions = new EventQrSession(),
        private readonly EventAttendance $attendance = new EventAttendance(),
        private readonly EventRegistration $registrations = new EventRegistration(),
        private readonly EventService $events = new EventService()
    ) {
    }

    public function openQr(int $eventId, array $user): array
    {
        $event = $this->events->requireEvent($eventId);

        if (!(bool) $event['qr_enabled']) {
            throw new ApiException('QR attendance is disabled for this event.', 409);
        }

        $this->guardEventRunning($event);

        $existing = $this->sessions->activeForEvent($eventId);

        if ($existing !== null) {
            return $this->present($existing);
        }

        $ttl = Config::get('attendance.qr_ttl_minutes');

        $id = $this->sessions->create([
            'event_id' => $eventId,
            'opened_by' => $user['user_id'],
            'qr_token' => bin2hex(random_bytes(24)),
            'generated_at' => gmdate('Y-m-d H:i:s'),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttl * 60),
            'status' => 'active',
        ]);

        return $this->present($this->sessions->find($id));
    }

    public function closeQr(int $eventId, array $user): void
    {
        $this->events->requireEvent($eventId);

        $session = $this->sessions->activeForEvent($eventId);

        if ($session === null) {
            throw new ApiException('No QR session is open for this event.', 404);
        }

        $this->sessions->close((int) $session['id'], 'closed');
    }

    public function scan(array $user, string $token): array
    {
        $session = $this->sessions->findByToken($token);

        if ($session === null) {
            throw new ApiException('This QR code is not valid.', 404);
        }

        if ($session['status'] !== 'active') {
            throw new ApiException('This QR code is no longer active.', 409);
        }

        if (time() > strtotime($session['expires_at'] . ' UTC')) {
            $this->sessions->close((int) $session['id'], 'expired');

            throw new ApiException('This QR code has expired.', 409);
        }

        $eventId = (int) $session['event_id'];
        $studentId = $this->events->requireStudentId($user['user_id']);

        $registration = $this->registrations->approvedForStudentAndEvent($eventId, $studentId);

        if ($registration === null) {
            throw new ApiException(
                'You do not have an approved registration for this event.',
                403
            );
        }

        return $this->record((int) $registration['id'], 'QR', null);
    }

    public function recordManually(int $registrationId, array $user): array
    {
        $registration = $this->registrations->findDetailed($registrationId);

        if ($registration === null) {
            throw new ApiException('Registration not found.', 404);
        }

        if ($registration['status'] !== 'Approved') {
            throw new ApiException(
                'Attendance can only be recorded for an approved registration.',
                409
            );
        }

        return $this->record($registrationId, 'Manual', $user['user_id']);
    }

    public function forEvent(int $eventId): array
    {
        $this->events->requireEvent($eventId);

        return $this->attendance->forEvent($eventId);
    }

    private function record(int $registrationId, string $method, ?int $verifiedBy): array
    {
        if ($this->attendance->findForRegistration($registrationId) !== null) {
            throw new ApiException('Attendance has already been recorded.', 409);
        }

        $id = $this->attendance->create([
            'registration_id' => $registrationId,
            'attendance_time' => gmdate('Y-m-d H:i:s'),
            'attendance_method' => $method,
            'verified_by' => $verifiedBy,
        ]);

        return $this->attendance->find($id);
    }

    private function present(array $session): array
    {
        $session['seconds_remaining'] = max(
            0,
            strtotime($session['expires_at'] . ' UTC') - time()
        );

        return $session;
    }

    /**
     * A QR code that can be opened before the day, or after it, is a QR code
     * that can be photographed and reused. It only opens while the event runs.
     */
    private function guardEventRunning(array $event): void
    {
        $now = time();
        $start = strtotime($event['event_date'] . ' ' . $event['start_time'] . ' UTC');
        $end = strtotime($event['event_date'] . ' ' . $event['end_time'] . ' UTC');

        if ($now < $start) {
            throw new ApiException('This event has not started yet.', 409);
        }

        if ($now > $end) {
            throw new ApiException('This event has already finished.', 409);
        }
    }
}
