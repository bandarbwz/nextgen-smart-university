<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;

class StudentService
{
    public function __construct(
        private readonly Student $students = new Student(),
        private readonly Program $programs = new Program(),
        private readonly Department $departments = new Department(),
        private readonly User $users = new User()
    ) {
    }

    public function list(?int $programId, ?int $departmentId): array
    {
        return $this->students->allWithUser($programId, $departmentId);
    }

    public function get(int $id): array
    {
        $student = $this->students->findWithUser($id);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        return $student;
    }

    public function getByUserId(int $userId): array
    {
        $student = $this->students->findByUserId($userId);

        if ($student === null) {
            throw new ApiException('No student record is linked to this account.', 404);
        }

        return $this->get((int) $student['id']);
    }

    public function create(array $fields): array
    {
        $this->guardReferences($fields);

        if ($this->students->studentNumberExists($fields['student_number'])) {
            throw new ApiException('A student with this student number already exists.', 409);
        }

        if ($this->students->findByUserId((int) $fields['user_id']) !== null) {
            throw new ApiException('This account already has a student record.', 409);
        }

        return $this->get($this->students->create($fields));
    }

    public function update(int $id, array $fields): array
    {
        $this->get($id);
        $this->guardReferences($fields);

        if ($this->students->studentNumberExists($fields['student_number'], $id)) {
            throw new ApiException('A student with this student number already exists.', 409);
        }

        $this->students->update($id, $fields);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);

        $this->students->delete($id);
    }

    private function guardReferences(array $fields): void
    {
        if ($this->users->findById((int) $fields['user_id']) === null) {
            throw new ApiException('User account not found.', 404);
        }

        if (!$this->programs->exists((int) $fields['program_id'])) {
            throw new ApiException('Program not found.', 404);
        }

        if (!$this->departments->exists((int) $fields['department_id'])) {
            throw new ApiException('Department not found.', 404);
        }
    }
}
