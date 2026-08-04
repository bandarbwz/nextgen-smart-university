<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;

class EventService
{
    public function __construct(
        private readonly Event $events = new Event(),
        private readonly EventRegistration $registrations = new EventRegistration(),
        private readonly Student $students = new Student()
    ) {
    }

    public function list(array $user, array $filters): array
    {
        $isStudent = $user['role'] === 'Student';
        $events = $this->events->listing($filters, $isStudent);

        if (!$isStudent) {
            return array_map(fn (array $event): array => $this->withSeats($event), $events);
        }

        $studentId = $this->requireStudentId($user['user_id']);

        return array_map(function (array $event) use ($studentId): array {
            $event = $this->withSeats($event);
            $registration = $this->registrations->findForStudent((int) $event['id'], $studentId);

            $event['my_registration_status'] = $registration['status'] ?? null;
            $event['my_registration_id'] = isset($registration['id']) ? (int) $registration['id'] : null;

            return $event;
        }, $events);
    }

    public function get(int $id, array $user): array
    {
        $event = $this->requireEvent($id);

        if ($user['role'] === 'Student') {
            if ($event['status'] === 'draft') {
                throw new ApiException('Event not found.', 404);
            }

            $studentId = $this->requireStudentId($user['user_id']);
            $registration = $this->registrations->findForStudent($id, $studentId);

            $event['my_registration_status'] = $registration['status'] ?? null;
            $event['my_registration_id'] = isset($registration['id']) ? (int) $registration['id'] : null;
        }

        return $this->withSeats($event);
    }

    public function create(array $user, array $fields): array
    {
        $this->guardDates($fields);

        $id = $this->events->create([
            'club_id' => $fields['club_id'] ?? null,
            'event_name' => $fields['event_name'],
            'description' => $fields['description'] ?? null,
            'event_type' => $fields['event_type'] ?? 'Event',
            'venue' => $fields['venue'] ?? null,
            'event_date' => $fields['event_date'],
            'start_time' => $fields['start_time'],
            'end_time' => $fields['end_time'],
            'registration_deadline' => $fields['registration_deadline'],
            'maximum_participants' => (int) $fields['maximum_participants'],
            'award_points' => (int) ($fields['award_points'] ?? 0),
            'qr_enabled' => (bool) ($fields['qr_enabled'] ?? true),
            'status' => $fields['status'] ?? 'draft',
            'created_by' => $user['user_id'],
        ]);

        return $this->get($id, $user);
    }

    public function update(int $id, array $user, array $fields): array
    {
        $event = $this->requireEvent($id);

        $this->guardDates($fields);

        $capacity = (int) $fields['maximum_participants'];
        $approved = $this->registrations->approvedCount($id);

        if ($capacity < $approved) {
            throw new ApiException(
                'The limit cannot be lower than the ' . $approved . ' student(s) already approved.',
                409
            );
        }

        $this->events->update($id, [
            'club_id' => $fields['club_id'] ?? null,
            'event_name' => $fields['event_name'],
            'description' => $fields['description'] ?? null,
            'event_type' => $fields['event_type'] ?? $event['event_type'],
            'venue' => $fields['venue'] ?? null,
            'event_date' => $fields['event_date'],
            'start_time' => $fields['start_time'],
            'end_time' => $fields['end_time'],
            'registration_deadline' => $fields['registration_deadline'],
            'maximum_participants' => $capacity,
            'award_points' => (int) ($fields['award_points'] ?? $event['award_points']),
            'qr_enabled' => (bool) ($fields['qr_enabled'] ?? $event['qr_enabled']),
            'status' => $fields['status'] ?? $event['status'],
        ]);

        return $this->get($id, $user);
    }

    public function cancel(int $id, array $user): array
    {
        $event = $this->requireEvent($id);

        if ($event['status'] === 'cancelled') {
            throw new ApiException('This event is already cancelled.', 409);
        }

        $this->events->update($id, ['status' => 'cancelled']);

        return $this->get($id, $user);
    }

    /**
     * Deleting an event with registrations would destroy the participation
     * record, so cancelling is the only way out once anyone has signed up.
     */
    public function delete(int $id): void
    {
        $this->requireEvent($id);

        if ($this->registrations->forEvent($id, null) !== []) {
            throw new ApiException(
                'This event has registrations and cannot be deleted. Cancel it instead.',
                409
            );
        }

        $this->events->delete($id);
    }

    public function requireEvent(int $id): array
    {
        $event = $this->events->findDetailed($id);

        if ($event === null) {
            throw new ApiException('Event not found.', 404);
        }

        return $event;
    }

    public function requireStudentId(int $userId): int
    {
        $student = $this->students->findByUserId($userId);

        if ($student === null) {
            throw new ApiException('No student record is linked to this account.', 404);
        }

        return (int) $student['id'];
    }

    private function withSeats(array $event): array
    {
        $registered = (int) ($event['registered_count'] ?? 0);

        $event['registered_count'] = $registered;
        $event['seats_remaining'] = max(0, (int) $event['maximum_participants'] - $registered);

        return $event;
    }

    private function guardDates(array $fields): void
    {
        if (strtotime($fields['start_time']) >= strtotime($fields['end_time'])) {
            throw new ApiException('The end time must be after the start time.', 422);
        }

        $deadline = strtotime($fields['registration_deadline']);
        $eventStart = strtotime($fields['event_date'] . ' ' . $fields['start_time']);

        if ($deadline >= $eventStart) {
            throw new ApiException(
                'The registration deadline must be before the event starts.',
                422
            );
        }
    }
}
