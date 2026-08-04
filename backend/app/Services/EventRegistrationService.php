<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventAttendance;
use App\Models\EventRegistration;
use App\Models\Student;

class EventRegistrationService
{
    public function __construct(
        private readonly EventRegistration $registrations = new EventRegistration(),
        private readonly EventAttendance $attendance = new EventAttendance(),
        private readonly Student $students = new Student(),
        private readonly EventService $events = new EventService(),
        private readonly NotificationService $notifications = new NotificationService()
    ) {
    }

    public function register(array $user, int $eventId): array
    {
        $event = $this->events->requireEvent($eventId);
        $studentId = $this->events->requireStudentId($user['user_id']);

        $this->guardStudentActive($studentId);
        $this->guardEventOpen($event);

        if ($this->registrations->findForStudent($eventId, $studentId) !== null) {
            throw new ApiException('You are already registered for this event.', 409);
        }

        if ($this->isFull($event)) {
            throw new ApiException('This event is full.', 409);
        }

        $id = $this->registrations->create([
            'event_id' => $eventId,
            'student_id' => $studentId,
            'registration_date' => gmdate('Y-m-d H:i:s'),
            'status' => 'Pending',
        ]);

        return $this->registrations->find($id);
    }

    public function cancel(int $id, array $user): array
    {
        $registration = $this->requireOwnRegistration($id, $user);

        if ($registration['status'] === 'Cancelled') {
            throw new ApiException('This registration is already cancelled.', 409);
        }

        if ($this->attendance->findForRegistration($id) !== null) {
            throw new ApiException(
                'Attendance has already been recorded, so this registration cannot be cancelled.',
                409
            );
        }

        $this->registrations->update($id, ['status' => 'Cancelled']);

        return $this->registrations->find($id);
    }

    public function mine(array $user): array
    {
        return $this->registrations->forStudent($this->events->requireStudentId($user['user_id']));
    }

    public function forEvent(int $eventId, ?string $status): array
    {
        $this->events->requireEvent($eventId);

        return $this->registrations->forEvent($eventId, $status);
    }

    /**
     * The seat is taken here, not when the student asks. A pending request
     * holds nothing, so the limit is checked against approvals at this moment.
     */
    public function approve(int $id, array $user): array
    {
        $registration = $this->requireRegistration($id);

        if ($registration['status'] === 'Approved') {
            throw new ApiException('This registration is already approved.', 409);
        }

        $event = $this->events->requireEvent((int) $registration['event_id']);

        if ($this->isFull($event)) {
            throw new ApiException(
                'The event is full, so this registration cannot be approved.',
                409
            );
        }

        $this->registrations->decide($id, 'Approved', $user['user_id'], null);

        $this->notifyStudent(
            (int) $registration['student_id'],
            'Event registration approved',
            'You are confirmed for ' . $event['event_name'] . '.',
            'success'
        );

        return $this->registrations->findDetailed($id);
    }

    public function reject(int $id, array $user, ?string $reason): array
    {
        $registration = $this->requireRegistration($id);

        if ($registration['status'] === 'Rejected') {
            throw new ApiException('This registration is already rejected.', 409);
        }

        $this->registrations->decide($id, 'Rejected', $user['user_id'], $reason);

        $event = $this->events->requireEvent((int) $registration['event_id']);

        $this->notifyStudent(
            (int) $registration['student_id'],
            'Event registration not approved',
            'Your place at ' . $event['event_name'] . ' was not confirmed.'
                . ($reason === null ? '' : ' Reason: ' . $reason),
            'warning'
        );

        return $this->registrations->findDetailed($id);
    }

    private function notifyStudent(int $studentId, string $title, string $message, string $type): void
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            return;
        }

        $this->notifications->notify(
            (int) $student['user_id'],
            'Student Activities',
            $title,
            $message,
            ['type' => $type, 'priority' => 'Normal']
        );
    }

    public function requireOwnRegistration(int $id, array $user): array
    {
        $registration = $this->requireRegistration($id);
        $studentId = $this->events->requireStudentId($user['user_id']);

        if ((int) $registration['student_id'] !== $studentId) {
            throw new ApiException('Registration not found.', 404);
        }

        return $registration;
    }

    private function requireRegistration(int $id): array
    {
        $registration = $this->registrations->find($id);

        if ($registration === null) {
            throw new ApiException('Registration not found.', 404);
        }

        return $registration;
    }

    private function isFull(array $event): bool
    {
        return $this->registrations->approvedCount((int) $event['id'])
            >= (int) $event['maximum_participants'];
    }

    private function guardStudentActive(int $studentId): void
    {
        $student = $this->students->find($studentId);

        if ($student === null || $student['academic_status'] !== 'active') {
            throw new ApiException('Only an active student can register for events.', 403);
        }
    }

    private function guardEventOpen(array $event): void
    {
        if ($event['status'] === 'cancelled') {
            throw new ApiException('This event has been cancelled.', 409);
        }

        if ($event['status'] !== 'published') {
            throw new ApiException('This event is not open for registration.', 409);
        }

        if (time() > strtotime($event['registration_deadline'] . ' UTC')) {
            throw new ApiException('The registration deadline has passed.', 409);
        }
    }
}
