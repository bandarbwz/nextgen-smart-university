<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;

class LecturerService
{
    public function __construct(
        private readonly Lecturer $lecturers = new Lecturer(),
        private readonly Department $departments = new Department(),
        private readonly User $users = new User()
    ) {
    }

    public function list(?int $departmentId): array
    {
        return $this->lecturers->allWithUser($departmentId);
    }

    public function get(int $id): array
    {
        $lecturer = $this->lecturers->findWithUser($id);

        if ($lecturer === null) {
            throw new ApiException('Lecturer not found.', 404);
        }

        return $lecturer;
    }

    public function getByUserId(int $userId): array
    {
        $lecturer = $this->lecturers->findByUserId($userId);

        if ($lecturer === null) {
            throw new ApiException('No lecturer record is linked to this account.', 404);
        }

        return $this->get((int) $lecturer['id']);
    }

    public function create(array $fields): array
    {
        $this->guardReferences($fields);

        if ($this->lecturers->findByUserId((int) $fields['user_id']) !== null) {
            throw new ApiException('This account already has a lecturer record.', 409);
        }

        return $this->get($this->lecturers->create($fields));
    }

    public function update(int $id, array $fields): array
    {
        $this->get($id);
        $this->guardReferences($fields);

        $this->lecturers->update($id, $fields);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);

        if ($this->lecturers->hasSections($id)) {
            throw new ApiException('This lecturer is still assigned to sections and cannot be deleted.', 409);
        }

        $this->lecturers->delete($id);
    }

    private function guardReferences(array $fields): void
    {
        if ($this->users->findById((int) $fields['user_id']) === null) {
            throw new ApiException('User account not found.', 404);
        }

        if (!$this->departments->exists((int) $fields['department_id'])) {
            throw new ApiException('Department not found.', 404);
        }
    }
}
