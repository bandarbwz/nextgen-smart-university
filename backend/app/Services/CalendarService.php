<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Reminder;

class CalendarService
{
    private const TYPE_COLOURS = [
        'Class' => '#2563eb',
        'Assignment' => '#b45309',
        'Quiz' => '#7c3aed',
        'Examination' => '#dc2626',
        'Meeting' => '#0891b2',
        'Student Activity' => '#059669',
        'Food Order Pickup' => '#ca8a04',
        'Payment Deadline' => '#dc2626',
        'Holiday' => '#16a34a',
        'Personal Event' => '#475569',
    ];

    public function __construct(
        private readonly CalendarEvent $events = new CalendarEvent(),
        private readonly Reminder $reminders = new Reminder()
    ) {
    }

    public function range(int $userId, string $from, string $to, ?string $eventType): array
    {
        return $this->events->forUserInRange($userId, $from, $to, $eventType);
    }

    public function daily(int $userId, string $date): array
    {
        return $this->range($userId, $date . ' 00:00:00', $date . ' 23:59:59', null);
    }

    public function weekly(int $userId, string $startDate): array
    {
        $end = gmdate('Y-m-d', strtotime($startDate . ' +7 days'));

        return $this->range($userId, $startDate . ' 00:00:00', $end . ' 00:00:00', null);
    }

    public function monthly(int $userId, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to = gmdate('Y-m-d 00:00:00', strtotime($from . ' +1 month'));

        return $this->range($userId, $from, $to, null);
    }

    public function overview(int $userId): array
    {
        return [
            'upcoming' => $this->events->upcomingForUser($userId, 10),
            'totals' => $this->events->countForUser($userId),
            'due_reminders' => $this->reminders->dueForUser($userId),
        ];
    }

    public function get(int $id, int $userId): array
    {
        $event = $this->events->findForUser($id, $userId);

        if ($event === null) {
            throw new ApiException('Calendar event not found.', 404);
        }

        return $event;
    }

    public function create(int $userId, array $fields): array
    {
        $this->guardPeriod($fields);

        $eventType = $fields['event_type'] ?? 'Personal Event';

        $id = $this->events->create([
            'user_id' => $userId,
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'event_type' => $eventType,
            'start_datetime' => $fields['start_datetime'],
            'end_datetime' => $fields['end_datetime'],
            'location' => $fields['location'] ?? null,
            'color' => self::TYPE_COLOURS[$eventType] ?? null,
            'is_all_day' => (bool) ($fields['is_all_day'] ?? false),
            'reminder_enabled' => (bool) ($fields['reminder_enabled'] ?? false),
            'status' => 'active',
        ]);

        return $this->events->find($id);
    }

    public function update(int $id, int $userId, array $fields): array
    {
        $event = $this->get($id, $userId);

        if ($event['module'] !== null) {
            throw new ApiException(
                'This event is generated from another module and cannot be edited here.',
                409
            );
        }

        $this->guardPeriod($fields);

        $this->events->update($id, [
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'start_datetime' => $fields['start_datetime'],
            'end_datetime' => $fields['end_datetime'],
            'location' => $fields['location'] ?? null,
            'is_all_day' => (bool) ($fields['is_all_day'] ?? false),
            'reminder_enabled' => (bool) ($fields['reminder_enabled'] ?? false),
            'status' => $fields['status'] ?? $event['status'],
        ]);

        return $this->events->find($id);
    }

    public function delete(int $id, int $userId): void
    {
        $event = $this->get($id, $userId);

        if ($event['module'] !== null) {
            throw new ApiException(
                'This event is generated from another module and cannot be deleted here.',
                409
            );
        }

        $this->events->delete($id);
    }

    public function reminders(int $userId, ?string $status): array
    {
        return $this->reminders->forUser($userId, $status);
    }

    public function createReminder(int $userId, array $fields): array
    {
        $event = $this->get((int) $fields['calendar_event_id'], $userId);

        if (strtotime($fields['reminder_time']) >= strtotime($event['start_datetime'])) {
            throw new ApiException('The reminder must be scheduled before the event starts.', 422);
        }

        $id = $this->reminders->create([
            'calendar_event_id' => (int) $event['id'],
            'reminder_time' => $fields['reminder_time'],
            'reminder_method' => $fields['reminder_method'] ?? 'In-App Notification',
            'reminder_status' => 'pending',
        ]);

        $this->events->update((int) $event['id'], ['reminder_enabled' => true]);

        return $this->reminders->find($id);
    }

    public function updateReminder(int $id, int $userId, array $fields): array
    {
        $reminder = $this->requireReminder($id, $userId);

        if (strtotime($fields['reminder_time']) >= strtotime($reminder['start_datetime'])) {
            throw new ApiException('The reminder must be scheduled before the event starts.', 422);
        }

        $this->reminders->update($id, [
            'reminder_time' => $fields['reminder_time'],
            'reminder_method' => $fields['reminder_method'] ?? $reminder['reminder_method'],
        ]);

        return $this->reminders->find($id);
    }

    public function completeReminder(int $id, int $userId): array
    {
        $this->requireReminder($id, $userId);

        $this->reminders->updateStatus($id, 'completed');

        return $this->reminders->find($id);
    }

    public function deleteReminder(int $id, int $userId): void
    {
        $this->requireReminder($id, $userId);

        $this->reminders->delete($id);
    }

    private function requireReminder(int $id, int $userId): array
    {
        $reminder = $this->reminders->findForUser($id, $userId);

        if ($reminder === null) {
            throw new ApiException('Reminder not found.', 404);
        }

        return $reminder;
    }

    private function guardPeriod(array $fields): void
    {
        if (strtotime($fields['end_datetime']) <= strtotime($fields['start_datetime'])) {
            throw new ApiException('The end time must be after the start time.', 422);
        }
    }
}
