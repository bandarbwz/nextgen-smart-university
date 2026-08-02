<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\FileUpload;
use App\Models\ChatMember;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\MessageRead;
use App\Models\User;
use Throwable;

class ChatService
{
    private const MAX_MESSAGE_LENGTH = 4000;

    private const MODERATOR_ROLES = ['Owner', 'Lecturer', 'Moderator'];

    public function __construct(
        private readonly ChatRoom $rooms = new ChatRoom(),
        private readonly ChatMember $members = new ChatMember(),
        private readonly Message $messages = new Message(),
        private readonly MessageAttachment $attachments = new MessageAttachment(),
        private readonly MessageReaction $reactions = new MessageReaction(),
        private readonly MessageRead $reads = new MessageRead(),
        private readonly User $users = new User()
    ) {
    }

    public function rooms(int $userId): array
    {
        return $this->rooms->forUser($userId);
    }

    public function room(int $roomId, array $user): array
    {
        $membership = $this->requireMembership($roomId, $user);
        $room = $this->rooms->find($roomId);

        $room['my_role'] = $membership['role'];
        $room['members'] = $this->members->forRoom($roomId);
        $room['pinned'] = $this->messages->pinnedForRoom($roomId);

        return $room;
    }

    public function members(int $roomId, array $user): array
    {
        $this->requireMembership($roomId, $user);

        return $this->members->forRoom($roomId);
    }

    public function createGroupRoom(array $user, array $fields): array
    {
        $id = $this->rooms->create([
            'room_name' => $fields['room_name'],
            'room_type' => $fields['room_type'] ?? 'Group',
            'created_by' => $user['user_id'],
        ]);

        $this->members->join($id, $user['user_id'], 'Owner');

        foreach ($fields['member_ids'] ?? [] as $memberId) {
            if ($this->users->findById((int) $memberId) !== null) {
                $this->members->join($id, (int) $memberId, 'Student');
            }
        }

        return $this->room($id, $user);
    }

    public function openPrivateRoom(array $user, int $otherUserId): array
    {
        if ($otherUserId === $user['user_id']) {
            throw new ApiException('You cannot start a private chat with yourself.', 422);
        }

        $other = $this->users->findById($otherUserId);

        if ($other === null) {
            throw new ApiException('User not found.', 404);
        }

        $existing = $this->rooms->findPrivateBetween($user['user_id'], $otherUserId);

        if ($existing !== null) {
            return $this->room((int) $existing['id'], $user);
        }

        $id = $this->rooms->create([
            'room_name' => $other['full_name'],
            'room_type' => 'Private',
            'created_by' => $user['user_id'],
        ]);

        $this->members->join($id, $user['user_id'], 'Owner');
        $this->members->join($id, $otherUserId, 'Student');

        return $this->room($id, $user);
    }

    public function messages(int $roomId, array $user, ?int $afterId, ?int $beforeId, int $limit): array
    {
        $this->requireMembership($roomId, $user);

        $messages = $this->messages->forRoom($roomId, $afterId, $beforeId, $limit);

        if ($messages !== []) {
            $latest = max(array_map(static fn (array $m): int => (int) $m['id'], $messages));

            $this->members->markRead($roomId, $user['user_id'], $latest);
        }

        return $messages;
    }

    public function send(array $user, array $fields, ?array $file): array
    {
        $roomId = (int) $fields['room_id'];
        $membership = $this->requireMembership($roomId, $user);
        $room = $this->rooms->find($roomId);

        $this->guardCanPost($room, $membership, $user);

        $body = trim((string) ($fields['message'] ?? ''));

        if ($body === '' && $file === null) {
            throw new ApiException('A message cannot be empty.', 422);
        }

        if (mb_strlen($body) > self::MAX_MESSAGE_LENGTH) {
            throw new ApiException(
                'A message cannot be longer than ' . self::MAX_MESSAGE_LENGTH . ' characters.',
                422
            );
        }

        if (isset($fields['reply_to']) && $fields['reply_to'] !== null) {
            $parent = $this->messages->find((int) $fields['reply_to']);

            if ($parent === null || (int) $parent['room_id'] !== $roomId) {
                throw new ApiException('The message being replied to was not found.', 404);
            }
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $messageId = $this->messages->create([
                'room_id' => $roomId,
                'sender_id' => $user['user_id'],
                'message_type' => $file === null ? 'Text' : $this->typeForFile($file),
                'message' => $body === '' ? null : $body,
                'reply_to' => isset($fields['reply_to']) ? (int) $fields['reply_to'] : null,
                'sent_at' => gmdate('Y-m-d H:i:s'),
            ]);

            if ($file !== null) {
                $stored = FileUpload::store($file, 'chat', FileUpload::PROFILE_CHAT_FILE);

                $this->attachments->create([
                    'message_id' => $messageId,
                    'file_name' => substr((string) $file['name'], 0, 255),
                    'file_path' => $stored,
                    'file_size' => (int) $file['size'],
                    'file_type' => pathinfo($stored, PATHINFO_EXTENSION),
                ]);
            }

            $this->members->markRead($roomId, $user['user_id'], $messageId);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->messages->forRoom($roomId, $messageId - 1, null, 1)[0]
            ?? $this->messages->find($messageId);
    }

    public function edit(int $messageId, array $user, string $body): array
    {
        $message = $this->requireMessage($messageId);

        $this->requireMembership((int) $message['room_id'], $user);

        if ((int) $message['sender_id'] !== $user['user_id']) {
            throw new ApiException('You can only edit your own messages.', 403);
        }

        if ($message['deleted_at'] !== null) {
            throw new ApiException('This message has been deleted.', 409);
        }

        $body = trim($body);

        if ($body === '') {
            throw new ApiException('A message cannot be empty.', 422);
        }

        $this->messages->edit($messageId, $body);

        return $this->messages->findDetailed($messageId);
    }

    public function delete(int $messageId, array $user): void
    {
        $message = $this->requireMessage($messageId);
        $membership = $this->requireMembership((int) $message['room_id'], $user);

        $isOwner = (int) $message['sender_id'] === $user['user_id'];
        $isModerator = $user['role'] === 'Administrator'
            || in_array($membership['role'], self::MODERATOR_ROLES, true);

        if (!$isOwner && !$isModerator) {
            throw new ApiException('You can only delete your own messages.', 403);
        }

        if ($message['deleted_at'] !== null) {
            throw new ApiException('This message has already been deleted.', 409);
        }

        $this->messages->softDelete($messageId, $user['user_id']);
    }

    public function setPinned(int $messageId, array $user, bool $pinned): array
    {
        $message = $this->requireMessage($messageId);
        $membership = $this->requireMembership((int) $message['room_id'], $user);

        if ($user['role'] !== 'Administrator'
            && !in_array($membership['role'], self::MODERATOR_ROLES, true)) {
            throw new ApiException('Only lecturers and moderators can pin messages.', 403);
        }

        $this->messages->setPinned($messageId, $pinned, $user['user_id']);

        return $this->messages->findDetailed($messageId);
    }

    public function react(int $messageId, array $user, string $reaction): array
    {
        $message = $this->requireMessage($messageId);

        $this->requireMembership((int) $message['room_id'], $user);
        $this->reactions->set($messageId, $user['user_id'], $reaction);

        return ['message_id' => $messageId, 'reaction' => $reaction];
    }

    public function removeReaction(int $messageId, array $user): void
    {
        $message = $this->requireMessage($messageId);

        $this->requireMembership((int) $message['room_id'], $user);
        $this->reactions->remove($messageId, $user['user_id']);
    }

    public function markRead(int $messageId, array $user): array
    {
        $message = $this->requireMessage($messageId);

        $this->requireMembership((int) $message['room_id'], $user);
        $this->reads->record($messageId, $user['user_id']);
        $this->members->markRead((int) $message['room_id'], $user['user_id'], $messageId);

        return ['message_id' => $messageId];
    }

    public function readReceipts(int $messageId, array $user): array
    {
        $message = $this->requireMessage($messageId);

        $this->requireMembership((int) $message['room_id'], $user);

        return $this->reads->forMessage($messageId);
    }

    public function search(int $userId, string $keyword): array
    {
        if (trim($keyword) === '') {
            throw new ApiException('A search keyword is required.', 422);
        }

        return $this->messages->search($userId, trim($keyword));
    }

    public function attachmentForDownload(int $attachmentId, array $user): array
    {
        $attachment = $this->attachments->findWithRoom($attachmentId);

        if ($attachment === null || $attachment['message_deleted_at'] !== null) {
            throw new ApiException('Attachment not found.', 404);
        }

        $this->requireMembership((int) $attachment['room_id'], $user);

        $absolute = FileUpload::absolutePath($attachment['file_path']);

        if (!is_readable($absolute)) {
            throw new ApiException('The stored file is no longer available.', 404);
        }

        return [
            'path' => $absolute,
            'name' => $attachment['file_name'],
        ];
    }

    public function join(int $roomId, array $user): array
    {
        $room = $this->rooms->find($roomId);

        if ($room === null) {
            throw new ApiException('Chat room not found.', 404);
        }

        if ($room['room_type'] !== 'Group') {
            throw new ApiException('This room does not accept self service joining.', 403);
        }

        $this->members->join($roomId, $user['user_id'], 'Student');

        return $this->room($roomId, $user);
    }

    public function leave(int $roomId, array $user): void
    {
        $membership = $this->requireMembership($roomId, $user);
        $room = $this->rooms->find($roomId);

        if ($room['room_type'] === 'Course') {
            throw new ApiException(
                'Course chat membership follows your enrolment and cannot be left manually.',
                409
            );
        }

        if ($membership['role'] === 'Owner') {
            throw new ApiException('Transfer ownership before leaving this room.', 409);
        }

        $this->members->leave($roomId, $user['user_id']);
    }

    public function requireMembership(int $roomId, array $user): array
    {
        $membership = $this->members->findMembership($roomId, $user['user_id']);

        if ($membership !== null) {
            return $membership;
        }

        if ($user['role'] === 'Administrator') {
            return ['role' => 'Moderator'];
        }

        throw new ApiException('You do not have access to this chat room.', 403);
    }

    private function requireMessage(int $id): array
    {
        $message = $this->messages->find($id);

        if ($message === null) {
            throw new ApiException('Message not found.', 404);
        }

        return $message;
    }

    private function guardCanPost(array $room, array $membership, array $user): void
    {
        if ($room['room_type'] !== 'Announcement') {
            return;
        }

        if ($user['role'] === 'Administrator'
            || in_array($membership['role'], self::MODERATOR_ROLES, true)) {
            return;
        }

        throw new ApiException('This is an announcement room and is read only.', 403);
    }

    private function typeForFile(array $file): string
    {
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png' => 'Image',
            'mp4' => 'Video',
            'mp3' => 'Voice',
            default => 'File',
        };
    }
}
