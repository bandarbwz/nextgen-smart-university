<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\NotificationCenterService;
use App\Services\NotificationService;
use App\Validation\NotificationValidator;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationCenterService $centre = new NotificationCenterService(),
        private readonly NotificationService $dispatcher = new NotificationService(),
        private readonly NotificationValidator $validator = new NotificationValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $filters = [
            'unread' => $this->queryString('unread') === 'true',
            'archived' => $this->queryString('archived') === 'true',
        ];

        $module = $this->queryString('module');

        if ($module !== null) {
            $filters['module'] = $module;
        }

        $result = $this->run(fn () => $this->centre->list($user, $filters));

        Response::success('Notifications retrieved.', $result);
    }

    public function unread(): void
    {
        $user = $this->authenticate();

        $count = $this->run(fn () => $this->centre->unreadCount($user));

        Response::success('Unread count retrieved.', ['unread_count' => $count]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $notification = $this->run(fn () => $this->centre->get((int) $id, $user));

        Response::success('Notification retrieved.', ['notification' => $notification]);
    }

    public function store(): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->notification($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $this->run(fn () => $this->dispatcher->notify(
            (int) $data['user_id'],
            $data['module'] ?? 'System',
            $data['title'],
            $data['message'],
            [
                'priority' => $data['priority'] ?? 'Normal',
                'type' => $data['notification_type'] ?? 'info',
            ]
        ));

        Response::success('Notification sent.', [], 201);
    }

    public function markRead(string $id): void
    {
        $user = $this->authenticate();

        $notification = $this->run(fn () => $this->centre->markRead((int) $id, $user));

        Response::success('Notification marked as read.', ['notification' => $notification]);
    }

    public function markAllRead(): void
    {
        $user = $this->authenticate();

        $count = $this->run(fn () => $this->centre->markAllRead($user));

        Response::success('All notifications marked as read.', ['updated' => $count]);
    }

    public function archive(string $id): void
    {
        $user = $this->authenticate();

        $notification = $this->run(fn () => $this->centre->archive((int) $id, $user));

        Response::success('Notification archived.', ['notification' => $notification]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->centre->delete((int) $id, $user));

        Response::success('Notification deleted.');
    }

    public function destroyAll(): void
    {
        $user = $this->authenticate();

        $count = $this->run(fn () => $this->centre->deleteAll($user));

        Response::success('Notifications deleted.', ['deleted' => $count]);
    }

    public function preferences(): void
    {
        $user = $this->authenticate();

        $preferences = $this->run(fn () => $this->centre->preferences($user));

        Response::success('Preferences retrieved.', ['preferences' => $preferences]);
    }

    public function updatePreferences(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->preferences($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $preferences = $this->run(fn () => $this->centre->updatePreferences($user, $data));

        Response::success('Preferences updated.', ['preferences' => $preferences]);
    }

    /**
     * The specification documents push and SMS endpoints. Neither has a
     * provider, so they say so rather than accepting a request and quietly
     * doing nothing.
     */
    public function push(): void
    {
        $this->authenticateAsAdministrator();

        Response::error(
            'Push notifications are not implemented. No push provider is configured.',
            501
        );
    }

    public function sms(): void
    {
        $this->authenticateAsAdministrator();

        Response::error(
            'SMS notifications are not implemented. No SMS provider is configured.',
            501
        );
    }
}
