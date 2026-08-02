<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\Department;

class CourseService
{
    public function __construct(
        private readonly Course $courses = new Course(),
        private readonly Department $departments = new Department()
    ) {
    }

    public function search(?string $term, ?int $departmentId, ?int $programId): array
    {
        return $this->courses->search($term, $departmentId, $programId);
    }

    public function get(int $id): array
    {
        $course = $this->courses->find($id);

        if ($course === null) {
            throw new ApiException('Course not found.', 404);
        }

        return $course;
    }

    public function prerequisites(int $id): array
    {
        $this->get($id);

        return $this->courses->prerequisites($id);
    }

    public function create(array $fields, array $prerequisiteIds): array
    {
        if (!$this->departments->exists((int) $fields['department_id'])) {
            throw new ApiException('Department not found.', 404);
        }

        if ($this->courses->codeExists($fields['course_code'])) {
            throw new ApiException('A course with this course code already exists.', 409);
        }

        $courseId = $this->courses->create($fields);

        $this->replacePrerequisites($courseId, $prerequisiteIds);

        return $this->get($courseId);
    }

    public function update(int $id, array $fields, array $prerequisiteIds): array
    {
        $this->get($id);

        if (!$this->departments->exists((int) $fields['department_id'])) {
            throw new ApiException('Department not found.', 404);
        }

        if ($this->courses->codeExists($fields['course_code'], $id)) {
            throw new ApiException('A course with this course code already exists.', 409);
        }

        $this->courses->update($id, $fields);
        $this->replacePrerequisites($id, $prerequisiteIds);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);

        if ($this->courses->hasSections($id)) {
            throw new ApiException('This course still has sections and cannot be deleted.', 409);
        }

        $this->courses->delete($id);
    }

    private function replacePrerequisites(int $courseId, array $prerequisiteIds): void
    {
        foreach ($prerequisiteIds as $prerequisiteId) {
            $prerequisiteId = (int) $prerequisiteId;

            if ($prerequisiteId === $courseId) {
                throw new ApiException('A course cannot be its own prerequisite.', 422);
            }

            if (!$this->courses->exists($prerequisiteId)) {
                throw new ApiException('One of the selected prerequisite courses does not exist.', 404);
            }

            $this->courses->addPrerequisite($courseId, $prerequisiteId);
        }
    }
}
