<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;

/**
 * The read side of the Notification Center. Everything here is scoped to the
 * caller, so a notification belonging to somebody else is reported missing
 * rather than forbidden.
 */
class NotificationCenterService
{
    public function __construct(
        private readonly Notification $notifications = new Notification(),
        private readonly NotificationPreference $preferences = new NotificationPreference()
    ) {
    }

    public function list(array $user, array $filters): array
    {
        return [
            'notifications' => $this->notifications->forUser($user['user_id'], $filters),
            'unread_count' => $this->notifications->unreadCount($user['user_id']),
        ];
    }

    public function unreadCount(array $user): int
    {
        return $this->notifications->unreadCount($user['user_id']);
    }

    public function get(int $id, array $user): array
    {
        return $this->requireOwn($id, $user);
    }

    public function markRead(int $id, array $user): array
    {
        $this->requireOwn($id, $user);
        $this->notifications->markRead($id);

        return $this->notifications->find($id);
    }

    public function markAllRead(array $user): int
    {
        return $this->notifications->markAllRead($user['user_id']);
    }

    public function archive(int $id, array $user): array
    {
        $this->requireOwn($id, $user);
        $this->notifications->archive($id);

        return $this->notifications->find($id);
    }

    public function delete(int $id, array $user): void
    {
        $this->requireOwn($id, $user);
        $this->notifications->delete($id);
    }

    public function deleteAll(array $user): int
    {
        return $this->notifications->deleteAllForUser($user['user_id']);
    }

    public function preferences(array $user): array
    {
        return $this->preferences->forUser($user['user_id']);
    }

    /**
     * The feature document requires at least one delivery method, and push has
     * no delivery mechanism yet, so it cannot be the only one enabled.
     */
    public function updatePreferences(array $user, array $fields): array
    {
        $inApp = (bool) ($fields['in_app_enabled'] ?? false);
        $email = (bool) ($fields['email_enabled'] ?? false);

        if (!$inApp && !$email) {
            throw new ApiException(
                'At least one of in app or email notifications must stay enabled.',
                422
            );
        }

        return $this->preferences->save($user['user_id'], [
            'in_app_enabled' => $inApp,
            'email_enabled' => $email,
            'push_enabled' => (bool) ($fields['push_enabled'] ?? false),
        ]);
    }

    private function requireOwn(int $id, array $user): array
    {
        $notification = $this->notifications->find($id);

        if ($notification === null || (int) $notification['user_id'] !== (int) $user['user_id']) {
            throw new ApiException('Notification not found.', 404);
        }

        return $notification;
    }
}
