<?php

declare(strict_types=1);

namespace App\Validation;

class CalendarValidator
{
    private const EVENT_TYPES = [
        'Class', 'Assignment', 'Quiz', 'Examination', 'Meeting',
        'Student Activity', 'Food Order Pickup', 'Payment Deadline',
        'Holiday', 'Personal Event',
    ];

    private const METHODS = ['In-App Notification', 'Email', 'Push Notification'];

    public function event(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'start_datetime', 'Start time')
            ->date($data, 'start_datetime', 'Start time')
            ->required($data, 'end_datetime', 'End time')
            ->date($data, 'end_datetime', 'End time')
            ->maxLength($data, 'location', 255, 'Location')
            ->inList($data, 'event_type', self::EVENT_TYPES, 'Event type')
            ->errors();
    }

    public function reminder(array $data): array
    {
        return (new Validator())
            ->required($data, 'calendar_event_id', 'Calendar event')
            ->integer($data, 'calendar_event_id', 'Calendar event')
            ->required($data, 'reminder_time', 'Reminder time')
            ->date($data, 'reminder_time', 'Reminder time')
            ->inList($data, 'reminder_method', self::METHODS, 'Reminder method')
            ->errors();
    }

    public function reminderUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'reminder_time', 'Reminder time')
            ->date($data, 'reminder_time', 'Reminder time')
            ->inList($data, 'reminder_method', self::METHODS, 'Reminder method')
            ->errors();
    }
}
