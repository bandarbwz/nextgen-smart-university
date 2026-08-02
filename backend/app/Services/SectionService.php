<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Semester;

class SectionService
{
    public function __construct(
        private readonly Section $sections = new Section(),
        private readonly Course $courses = new Course(),
        private readonly Lecturer $lecturers = new Lecturer(),
        private readonly Semester $semesters = new Semester(),
        private readonly ClassSchedule $schedules = new ClassSchedule()
    ) {
    }

    public function search(?int $semesterId, ?int $courseId, ?int $lecturerId): array
    {
        return $this->sections->search($semesterId, $courseId, $lecturerId);
    }

    public function get(int $id): array
    {
        $section = $this->sections->findDetailed($id);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        $section['schedule'] = $this->schedules->forSection($id);

        return $section;
    }

    public function students(int $id): array
    {
        $this->get($id);

        return $this->sections->students($id);
    }

    public function create(array $fields, array $schedule): array
    {
        $this->guardReferences($fields);

        $sectionId = $this->sections->create($fields);

        $this->replaceSchedule($sectionId, $schedule);

        return $this->get($sectionId);
    }

    public function update(int $id, array $fields, array $schedule): array
    {
        $this->get($id);
        $this->guardReferences($fields);

        $this->sections->update($id, $fields);
        $this->replaceSchedule($id, $schedule);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);

        if ($this->sections->hasEnrollments($id)) {
            throw new ApiException('This section has active enrollments and cannot be deleted.', 409);
        }

        $this->sections->delete($id);
    }

    public function changeStatus(int $id, string $status): array
    {
        $this->get($id);
        $this->sections->update($id, ['status' => $status]);

        return $this->get($id);
    }

    public function changeCapacity(int $id, int $capacity): array
    {
        $section = $this->get($id);

        if ($capacity < (int) $section['registered_students']) {
            throw new ApiException('The capacity cannot be lower than the number of registered students.', 422);
        }

        $this->sections->update($id, ['capacity' => $capacity]);

        return $this->get($id);
    }

    public function assignLecturer(int $id, int $lecturerId): array
    {
        $this->get($id);

        if (!$this->lecturers->exists($lecturerId)) {
            throw new ApiException('Lecturer not found.', 404);
        }

        $this->sections->update($id, ['lecturer_id' => $lecturerId]);

        return $this->get($id);
    }

    public function changeClassroom(int $id, ?string $classroom, ?string $building): array
    {
        $this->get($id);

        $this->sections->update($id, [
            'classroom' => $classroom,
            'building' => $building,
        ]);

        return $this->get($id);
    }

    private function guardReferences(array $fields): void
    {
        if (!$this->courses->exists((int) $fields['course_id'])) {
            throw new ApiException('Course not found.', 404);
        }

        if (!$this->lecturers->exists((int) $fields['lecturer_id'])) {
            throw new ApiException('Lecturer not found.', 404);
        }

        if (!$this->semesters->exists((int) $fields['semester_id'])) {
            throw new ApiException('Semester not found.', 404);
        }
    }

    private function replaceSchedule(int $sectionId, array $schedule): void
    {
        if ($schedule === []) {
            return;
        }

        $this->schedules->deleteForSection($sectionId);

        foreach ($schedule as $slot) {
            if (strtotime($slot['start_time']) >= strtotime($slot['end_time'])) {
                throw new ApiException('Each class start time must be before its end time.', 422);
            }

            $this->schedules->create([
                'section_id' => $sectionId,
                'day_of_week' => $slot['day_of_week'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'room' => $slot['room'] ?? null,
            ]);
        }
    }
}
