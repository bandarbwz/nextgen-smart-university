<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemAnnouncement;
use App\Models\User;

class AnnouncementBroadcastService
{
    private const AUDIENCES = [
        'All', 'Student', 'Lecturer', 'Coordinator', 'Administrator',
        'Restaurant Owner', 'STAD Staff',
    ];

    public function __construct(
        private readonly SystemAnnouncement $announcements = new SystemAnnouncement(),
        private readonly User $users = new User(),
        private readonly NotificationService $notifications = new NotificationService()
    ) {
    }

    public function list(array $user): array
    {
        return $user['role'] === 'Administrator'
            ? $this->announcements->listing()
            : $this->announcements->forRole($user['role']);
    }

    public function create(array $user, array $fields): array
    {
        $this->guardAudience($fields['audience'] ?? 'All');

        $status = $fields['status'] ?? 'draft';

        $id = $this->announcements->create([
            'title' => $fields['title'],
            'content' => $fields['content'],
            'audience' => $fields['audience'] ?? 'All',
            'priority' => $fields['priority'] ?? 'Normal',
            'status' => $status,
            'published_by' => $user['user_id'],
            'published_at' => $status === 'published' ? gmdate('Y-m-d H:i:s') : null,
            'expires_at' => $fields['expires_at'] ?? null,
        ]);

        if ($status === 'published') {
            $this->fanOut($this->announcements->find($id));
        }

        return $this->announcements->find($id);
    }

    public function update(int $id, array $fields): array
    {
        $announcement = $this->requireAnnouncement($id);

        $this->guardAudience($fields['audience'] ?? $announcement['audience']);

        $status = $fields['status'] ?? $announcement['status'];
        $justPublished = $status === 'published' && $announcement['status'] !== 'published';

        $this->announcements->update($id, [
            'title' => $fields['title'],
            'content' => $fields['content'],
            'audience' => $fields['audience'] ?? $announcement['audience'],
            'priority' => $fields['priority'] ?? $announcement['priority'],
            'status' => $status,
            'published_at' => $justPublished
                ? gmdate('Y-m-d H:i:s')
                : $announcement['published_at'],
            'expires_at' => $fields['expires_at'] ?? null,
        ]);

        if ($justPublished) {
            $this->fanOut($this->announcements->find($id));
        }

        return $this->announcements->find($id);
    }

    public function delete(int $id): void
    {
        $this->requireAnnouncement($id);

        $this->announcements->delete($id);
    }

    /**
     * A broadcast is a notification sent to everyone at once without leaving an
     * announcement behind, used for things like planned downtime.
     */
    public function broadcast(array $user, array $fields): int
    {
        $audience = $fields['audience'] ?? 'All';

        $this->guardAudience($audience);

        $userIds = $audience === 'All'
            ? $this->users->activeIds()
            : $this->users->idsForRole($audience);

        $this->notifications->notifyMany(
            $userIds,
            'System',
            $fields['title'],
            $fields['message'],
            [
                'priority' => $fields['priority'] ?? 'High',
                'type' => $fields['type'] ?? 'info',
            ]
        );

        return count($userIds);
    }

    private function fanOut(array $announcement): void
    {
        $userIds = $announcement['audience'] === 'All'
            ? $this->users->activeIds()
            : $this->users->idsForRole($announcement['audience']);

        $this->notifications->notifyMany(
            $userIds,
            'System',
            $announcement['title'],
            $announcement['content'],
            [
                'priority' => $announcement['priority'],
                'reference_type' => 'SystemAnnouncement',
                'reference_id' => (int) $announcement['id'],
            ]
        );
    }

    private function guardAudience(string $audience): void
    {
        if (!in_array($audience, self::AUDIENCES, true)) {
            throw new ApiException(
                'The audience must be one of: ' . implode(', ', self::AUDIENCES) . '.',
                422
            );
        }
    }

    private function requireAnnouncement(int $id): array
    {
        $announcement = $this->announcements->find($id);

        if ($announcement === null) {
            throw new ApiException('Announcement not found.', 404);
        }

        return $announcement;
    }
}
