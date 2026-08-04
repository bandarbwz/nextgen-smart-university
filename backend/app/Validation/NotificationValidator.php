<?php

declare(strict_types=1);

namespace App\Validation;

class NotificationValidator
{
    private const PRIORITIES = ['Low', 'Normal', 'High', 'Critical'];

    private const AUDIENCES = [
        'All', 'Student', 'Lecturer', 'Coordinator', 'Administrator',
        'Restaurant Owner', 'STAD Staff',
    ];

    public function notification(array $data): array
    {
        return (new Validator())
            ->required($data, 'user_id', 'Recipient')
            ->integer($data, 'user_id', 'Recipient')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 200, 'Title')
            ->required($data, 'message', 'Message')
            ->inList($data, 'priority', self::PRIORITIES, 'Priority')
            ->inList($data, 'notification_type', ['info', 'success', 'warning', 'error'], 'Type')
            ->errors();
    }

    public function preferences(array $data): array
    {
        return (new Validator())
            ->inList($data, 'in_app_enabled', [true, false], 'In app notifications')
            ->inList($data, 'email_enabled', [true, false], 'Email notifications')
            ->inList($data, 'push_enabled', [true, false], 'Push notifications')
            ->errors();
    }

    public function announcement(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 200, 'Title')
            ->required($data, 'content', 'Content')
            ->inList($data, 'audience', self::AUDIENCES, 'Audience')
            ->inList($data, 'priority', self::PRIORITIES, 'Priority')
            ->inList($data, 'status', ['draft', 'published'], 'Status')
            ->date($data, 'expires_at', 'Expiry date')
            ->errors();
    }

    public function broadcast(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 200, 'Title')
            ->required($data, 'message', 'Message')
            ->inList($data, 'audience', self::AUDIENCES, 'Audience')
            ->inList($data, 'priority', self::PRIORITIES, 'Priority')
            ->errors();
    }
}
