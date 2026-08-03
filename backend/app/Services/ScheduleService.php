<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\Semester;

class ScheduleService
{
    public function __construct(
        private readonly ClassSchedule $schedules = new ClassSchedule(),
        private readonly Semester $semesters = new Semester()
    ) {
    }

    public function weekly(int $studentId, ?int $semesterId = null): array
    {
        $slots = $this->schedules->forStudent($studentId, $this->resolveSemesterId($semesterId));

        $week = [];

        foreach ($slots as $slot) {
            $week[$slot['day_of_week']][] = $slot;
        }

        return $week;
    }

    public function daily(int $studentId, string $day): array
    {
        $slots = $this->schedules->forStudent($studentId, $this->resolveSemesterId(null));

        return array_values(
            array_filter($slots, static fn (array $slot): bool => $slot['day_of_week'] === $day)
        );
    }

    private function resolveSemesterId(?int $semesterId): int
    {
        if ($semesterId !== null) {
            if (!$this->semesters->exists($semesterId)) {
                throw new ApiException('Semester not found.', 404);
            }

            return $semesterId;
        }

        $semester = $this->semesters->current();

        if ($semester === null) {
            throw new ApiException('No current semester has been set.', 404);
        }

        return (int) $semester['id'];
    }
}
