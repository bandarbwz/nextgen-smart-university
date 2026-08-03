<?php

declare(strict_types=1);

namespace App\Validation;

class ChatValidator
{
    public function room(array $data): array
    {
        return (new Validator())
            ->required($data, 'room_name', 'Room name')
            ->maxLength($data, 'room_name', 255, 'Room name')
            ->inList($data, 'room_type', ['Group', 'Announcement'], 'Room type')
            ->errors();
    }

    public function privateRoom(array $data): array
    {
        return (new Validator())
            ->required($data, 'user_id', 'User')
            ->integer($data, 'user_id', 'User')
            ->errors();
    }

    public function message(array $data): array
    {
        return (new Validator())
            ->required($data, 'room_id', 'Room')
            ->integer($data, 'room_id', 'Room')
            ->integer($data, 'reply_to', 'Reply target')
            ->errors();
    }

    public function edit(array $data): array
    {
        return (new Validator())
            ->required($data, 'message', 'Message')
            ->errors();
    }

    public function reaction(array $data): array
    {
        return (new Validator())
            ->required($data, 'reaction', 'Reaction')
            ->inList($data, 'reaction', ['Like', 'Love', 'Laugh', 'Sad', 'Angry'], 'Reaction')
            ->errors();
    }
}
