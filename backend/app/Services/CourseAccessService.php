<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Student;

class CourseAccessService
{
    public function __construct(
        private readonly Student $students = new Student(),
        private readonly Lecturer $lecturers = new Lecturer(),
        private readonly Enrollment $enrollments = new Enrollment(),
        private readonly Section $sections = new Section()
    ) {
    }

    public function visibleSectionIds(array $user): array
    {
        if ($user['role'] === 'Student') {
            return $this->enrollments->activeSectionIds($this->requireStudentId($user['user_id']));
        }

        if ($user['role'] === 'Lecturer') {
            return $this->sections->idsForLecturer($this->requireLecturerId($user['user_id']));
        }

        return array_map(
            static fn (array $section): int => (int) $section['id'],
            $this->sections->all()
        );
    }

    public function guardSectionVisible(int $sectionId, array $user): void
    {
        if ($user['role'] === 'Administrator' || $user['role'] === 'Coordinator') {
            return;
        }

        if (!in_array($sectionId, $this->visibleSectionIds($user), true)) {
            throw new ApiException('You do not have access to this course section.', 403);
        }
    }

    public function guardSectionOwned(int $sectionId, array $user): int
    {
        if ($user['role'] === 'Administrator') {
            $section = $this->sections->find($sectionId);

            if ($section === null) {
                throw new ApiException('Section not found.', 404);
            }

            return (int) $section['lecturer_id'];
        }

        $lecturerId = $this->requireLecturerId($user['user_id']);
        $section = $this->sections->find($sectionId);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        if ((int) $section['lecturer_id'] !== $lecturerId) {
            throw new ApiException('You can only manage content for your own sections.', 403);
        }

        return $lecturerId;
    }

    public function requireStudentId(int $userId): int
    {
        $student = $this->students->findByUserId($userId);

        if ($student === null) {
            throw new ApiException('No student record is linked to this account.', 404);
        }

        return (int) $student['id'];
    }

    public function requireLecturerId(int $userId): int
    {
        $lecturer = $this->lecturers->findByUserId($userId);

        if ($lecturer === null) {
            throw new ApiException('No lecturer record is linked to this account.', 404);
        }

        return (int) $lecturer['id'];
    }
}
