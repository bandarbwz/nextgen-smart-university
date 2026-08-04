<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Club;
use App\Models\Event;

class ClubService
{
    public function __construct(
        private readonly Club $clubs = new Club(),
        private readonly Event $events = new Event()
    ) {
    }

    public function list(array $user, ?string $category, ?string $status): array
    {
        return $this->clubs->listing($category, $this->visibleStatus($user, $status));
    }

    public function get(int $id, array $user): array
    {
        $club = $this->requireClub($id);

        if ($user['role'] === 'Student' && $club['status'] !== 'active') {
            throw new ApiException('Club not found.', 404);
        }

        $club['events'] = $this->events->upcomingForClub($id);

        return $club;
    }

    public function create(array $fields): array
    {
        $this->guardName($fields['club_name']);

        $id = $this->clubs->create([
            'club_name' => trim($fields['club_name']),
            'description' => $fields['description'] ?? null,
            'category' => $fields['category'] ?? null,
            'advisor_id' => $fields['advisor_id'] ?? null,
            'president_id' => $fields['president_id'] ?? null,
            'status' => $fields['status'] ?? 'active',
        ]);

        return $this->clubs->find($id);
    }

    public function update(int $id, array $fields): array
    {
        $this->requireClub($id);
        $this->guardName($fields['club_name'], $id);

        $this->clubs->update($id, [
            'club_name' => trim($fields['club_name']),
            'description' => $fields['description'] ?? null,
            'category' => $fields['category'] ?? null,
            'advisor_id' => $fields['advisor_id'] ?? null,
            'president_id' => $fields['president_id'] ?? null,
            'status' => $fields['status'] ?? 'active',
        ]);

        return $this->clubs->find($id);
    }

    public function delete(int $id): void
    {
        $this->requireClub($id);

        $this->clubs->delete($id);
    }

    private function guardName(string $name, ?int $ignoreId = null): void
    {
        if ($this->clubs->nameExists(trim($name), $ignoreId)) {
            throw new ApiException('A club with this name already exists.', 409);
        }
    }

    private function visibleStatus(array $user, ?string $status): ?string
    {
        return $user['role'] === 'Student' ? 'active' : $status;
    }

    private function requireClub(int $id): array
    {
        $club = $this->clubs->find($id);

        if ($club === null) {
            throw new ApiException('Club not found.', 404);
        }

        return $club;
    }
}
