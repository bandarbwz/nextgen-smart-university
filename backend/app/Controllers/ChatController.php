<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ChatService;
use App\Validation\ChatValidator;

class ChatController extends Controller
{
    private const MAX_PAGE_SIZE = 100;

    public function __construct(
        private readonly ChatService $chat = new ChatService(),
        private readonly ChatValidator $validator = new ChatValidator()
    ) {
        parent::__construct();
    }

    public function rooms(): void
    {
        $user = $this->authenticate();

        Response::success('Chat rooms retrieved.', ['rooms' => $this->chat->rooms($user['user_id'])]);
    }

    public function room(string $id): void
    {
        $user = $this->authenticate();

        $room = $this->run(fn () => $this->chat->room((int) $id, $user));

        Response::success('Chat room retrieved.', ['room' => $room]);
    }

    public function members(string $id): void
    {
        $user = $this->authenticate();

        $members = $this->run(fn () => $this->chat->members((int) $id, $user));

        Response::success('Room members retrieved.', ['members' => $members]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->room($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $room = $this->run(fn () => $this->chat->createGroupRoom($user, [
            'room_name' => trim((string) $data['room_name']),
            'room_type' => $data['room_type'] ?? 'Group',
            'member_ids' => is_array($data['member_ids'] ?? null) ? $data['member_ids'] : [],
        ]));

        Response::success('Chat room created.', ['room' => $room], 201);
    }

    public function openPrivate(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->privateRoom($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $room = $this->run(fn () => $this->chat->openPrivateRoom($user, (int) $data['user_id']));

        Response::success('Private chat opened.', ['room' => $room], 201);
    }

    public function join(string $id): void
    {
        $user = $this->authenticate();

        $room = $this->run(fn () => $this->chat->join((int) $id, $user));

        Response::success('Joined chat room.', ['room' => $room]);
    }

    public function leave(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->chat->leave((int) $id, $user));

        Response::success('Left chat room.');
    }

    public function messages(string $id): void
    {
        $user = $this->authenticate();

        $limit = $this->queryInt('limit') ?? 50;
        $limit = max(1, min($limit, self::MAX_PAGE_SIZE));

        $messages = $this->run(fn () => $this->chat->messages(
            (int) $id,
            $user,
            $this->queryInt('after_id'),
            $this->queryInt('before_id'),
            $limit,
        ));

        Response::success('Messages retrieved.', [
            'messages' => $messages,
            'latest_id' => $messages === []
                ? null
                : max(array_map(static fn (array $m): int => (int) $m['id'], $messages)),
        ]);
    }

    public function send(): void
    {
        $user = $this->authenticate();

        $data = Request::formOrBody();
        $errors = $this->validator->message($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $file = $_FILES['file'] ?? null;

        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $file = null;
        }

        $message = $this->run(fn () => $this->chat->send($user, $data, $file));

        Response::success('Message sent.', ['message' => $message], 201);
    }

    public function reply(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::formOrBody();
        $data['reply_to'] = (int) $id;

        $errors = $this->validator->message($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $message = $this->run(fn () => $this->chat->send($user, $data, null));

        Response::success('Reply sent.', ['message' => $message], 201);
    }

    public function edit(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->edit($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $message = $this->run(
            fn () => $this->chat->edit((int) $id, $user, (string) $data['message'])
        );

        Response::success('Message updated.', ['message' => $message]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->chat->delete((int) $id, $user));

        Response::success('Message deleted.');
    }

    public function pin(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $pinned = (bool) ($data['pinned'] ?? true);

        $message = $this->run(fn () => $this->chat->setPinned((int) $id, $user, $pinned));

        Response::success($pinned ? 'Message pinned.' : 'Message unpinned.', ['message' => $message]);
    }

    public function react(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->reaction($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(
            fn () => $this->chat->react((int) $id, $user, (string) $data['reaction'])
        );

        Response::success('Reaction saved.', $result);
    }

    public function removeReaction(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->chat->removeReaction((int) $id, $user));

        Response::success('Reaction removed.');
    }

    public function markRead(string $id): void
    {
        $user = $this->authenticate();

        $result = $this->run(fn () => $this->chat->markRead((int) $id, $user));

        Response::success('Message marked as read.', $result);
    }

    public function readReceipts(string $id): void
    {
        $user = $this->authenticate();

        $receipts = $this->run(fn () => $this->chat->readReceipts((int) $id, $user));

        Response::success('Read receipts retrieved.', ['receipts' => $receipts]);
    }

    public function search(): void
    {
        $user = $this->authenticate();

        $results = $this->run(
            fn () => $this->chat->search($user['user_id'], (string) ($this->queryString('keyword') ?? ''))
        );

        Response::success('Search completed.', ['results' => $results]);
    }

    public function downloadAttachment(string $id): void
    {
        $user = $this->authenticate();

        $file = $this->run(fn () => $this->chat->attachmentForDownload((int) $id, $user));

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Content-Length: ' . filesize($file['path']));
        header('X-Content-Type-Options: nosniff');

        readfile($file['path']);

        exit;
    }
}
