<?php

declare(strict_types=1);

namespace App\Validation;

class ActivityValidator
{
    private const EVENT_TYPES = ['Event', 'Competition', 'Workshop', 'Seminar', 'Volunteering'];

    private const EVENT_STATUSES = ['draft', 'published', 'cancelled', 'completed'];

    public function club(array $data): array
    {
        return (new Validator())
            ->required($data, 'club_name', 'Club name')
            ->maxLength($data, 'club_name', 150, 'Club name')
            ->maxLength($data, 'category', 80, 'Category')
            ->integer($data, 'advisor_id', 'Advisor')
            ->integer($data, 'president_id', 'President')
            ->inList($data, 'status', ['active', 'inactive'], 'Status')
            ->errors();
    }

    public function event(array $data): array
    {
        return (new Validator())
            ->required($data, 'event_name', 'Event name')
            ->maxLength($data, 'event_name', 200, 'Event name')
            ->integer($data, 'club_id', 'Club')
            ->inList($data, 'event_type', self::EVENT_TYPES, 'Event type')
            ->maxLength($data, 'venue', 150, 'Venue')
            ->required($data, 'event_date', 'Event date')
            ->date($data, 'event_date', 'Event date')
            ->required($data, 'start_time', 'Start time')
            ->required($data, 'end_time', 'End time')
            ->required($data, 'registration_deadline', 'Registration deadline')
            ->date($data, 'registration_deadline', 'Registration deadline')
            ->required($data, 'maximum_participants', 'Maximum participants')
            ->positiveInteger($data, 'maximum_participants', 'Maximum participants')
            ->integer($data, 'award_points', 'Award points')
            ->inList($data, 'status', self::EVENT_STATUSES, 'Status')
            ->errors();
    }

    public function registration(array $data): array
    {
        return (new Validator())
            ->required($data, 'event_id', 'Event')
            ->integer($data, 'event_id', 'Event')
            ->errors();
    }

    public function rejection(array $data): array
    {
        return (new Validator())
            ->maxLength($data, 'reason', 255, 'Reason')
            ->errors();
    }

    public function scan(array $data): array
    {
        return (new Validator())
            ->required($data, 'token', 'Token')
            ->maxLength($data, 'token', 64, 'Token')
            ->errors();
    }

    public function manualAttendance(array $data): array
    {
        return (new Validator())
            ->required($data, 'registration_id', 'Registration')
            ->integer($data, 'registration_id', 'Registration')
            ->errors();
    }

    public function award(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'event_id', 'Event')
            ->integer($data, 'event_id', 'Event')
            ->required($data, 'points', 'Points')
            ->positiveInteger($data, 'points', 'Points')
            ->errors();
    }
}
