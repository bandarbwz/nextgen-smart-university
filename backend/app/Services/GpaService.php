<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Semester;
use App\Models\Student;
use App\Models\Transcript;

class GpaService
{
    public function __construct(
        private readonly Transcript $transcripts = new Transcript(),
        private readonly Student $students = new Student(),
        private readonly Semester $semesters = new Semester()
    ) {
    }

    public function currentGpa(int $studentId): array
    {
        $semester = $this->semesters->current();

        if ($semester === null) {
            throw new ApiException('No current semester has been set.', 404);
        }

        $records = $this->transcripts->forStudentInSemester($studentId, (int) $semester['id']);

        return [
            'semester' => $semester['name'],
            'academic_year' => $semester['academic_year'],
            'gpa' => $this->calculate($records),
            'credit_hours' => $this->totalCreditHours($records),
        ];
    }

    public function cumulativeGpa(int $studentId): array
    {
        $records = $this->transcripts->forStudent($studentId);

        return [
            'cgpa' => $this->calculate($records),
            'completed_credit_hours' => $this->earnedCreditHours($records),
        ];
    }

    public function recalculate(int $studentId): array
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        $allRecords = $this->transcripts->forStudent($studentId);
        $cumulative = $this->calculate($allRecords);
        $earned = $this->earnedCreditHours($allRecords);

        $semester = $this->semesters->current();
        $current = $cumulative;

        if ($semester !== null) {
            $semesterRecords = $this->transcripts->forStudentInSemester($studentId, (int) $semester['id']);
            $current = $this->calculate($semesterRecords);
        }

        $this->students->updateAcademicProgress($studentId, $current, $cumulative, $earned);

        return [
            'current_gpa' => $current,
            'cumulative_gpa' => $cumulative,
            'completed_credit_hours' => $earned,
        ];
    }

    private function calculate(array $records): float
    {
        $totalPoints = 0.0;
        $totalCreditHours = 0;

        foreach ($records as $record) {
            $creditHours = (int) $record['credit_hours'];

            $totalPoints += (float) $record['grade_points'] * $creditHours;
            $totalCreditHours += $creditHours;
        }

        if ($totalCreditHours === 0) {
            return 0.00;
        }

        return round($totalPoints / $totalCreditHours, 2);
    }

    private function totalCreditHours(array $records): int
    {
        return array_sum(array_map(static fn (array $record): int => (int) $record['credit_hours'], $records));
    }

    private function earnedCreditHours(array $records): int
    {
        return array_sum(
            array_map(static fn (array $record): int => (int) $record['earned_credit_hours'], $records)
        );
    }
}
