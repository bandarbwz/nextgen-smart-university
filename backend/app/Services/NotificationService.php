<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Throwable;

/**
 * The dispatch side of the Notification Center. Other modules call notify()
 * when something happens that a user should know about.
 *
 * Nothing here is allowed to throw into the caller. A notification that cannot
 * be written must never roll back the enrolment, payment or grade that caused
 * it, so failures are logged and swallowed.
 */
class NotificationService
{
    public function __construct(
        private readonly Notification $notifications = new Notification(),
        private readonly NotificationPreference $preferences = new NotificationPreference(),
        private readonly User $users = new User(),
        private readonly MailService $mail = new MailService()
    ) {
    }

    public function notify(int $userId, string $module, string $title, string $message, array $options = []): void
    {
        try {
            $priority = $options['priority'] ?? 'Normal';
            $preference = $this->preferences->forUser($userId);

            if (!$this->wantsInApp($preference, $priority)) {
                return;
            }

            $this->notifications->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'notification_type' => $options['type'] ?? 'info',
                'module' => $module,
                'priority' => $priority,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
            ]);

            $this->maybeEmail($userId, $preference, $priority, $title, $message);
        } catch (Throwable $exception) {
            Logger::error('Notification could not be delivered', [
                'user_id' => $userId,
                'module' => $module,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function notifyMany(array $userIds, string $module, string $title, string $message, array $options = []): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->notify((int) $userId, $module, $title, $message, $options);
        }
    }

    public function notifyRole(string $role, string $module, string $title, string $message, array $options = []): int
    {
        $userIds = $this->users->idsForRole($role);

        $this->notifyMany($userIds, $module, $title, $message, $options);

        return count($userIds);
    }

    /**
     * A critical notification is one the user cannot afford to miss, such as a
     * terminated examination or a financial hold, so it ignores the in-app
     * preference. Everything else respects it.
     */
    private function wantsInApp(array $preference, string $priority): bool
    {
        return $priority === 'Critical' || (bool) $preference['in_app_enabled'];
    }

    private function maybeEmail(
        int $userId,
        array $preference,
        string $priority,
        string $title,
        string $message
    ): void {
        if (!(bool) $preference['email_enabled'] && $priority !== 'Critical') {
            return;
        }

        if (!in_array($priority, ['High', 'Critical'], true)) {
            return;
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            return;
        }

        $this->mail->sendNotification($user['email'], $user['full_name'], $title, $message);
    }
}
