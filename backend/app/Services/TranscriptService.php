<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\Transcript;

class TranscriptService
{
    public function __construct(
        private readonly Transcript $transcripts = new Transcript(),
        private readonly Student $students = new Student(),
        private readonly GpaService $gpa = new GpaService()
    ) {
    }

    public function forStudent(int $studentId): array
    {
        $student = $this->students->findWithUser($studentId);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        $records = $this->transcripts->forStudent($studentId);

        return [
            'student' => [
                'student_number' => $student['student_number'],
                'full_name' => $student['full_name'],
                'program' => $student['program_name'],
                'department' => $student['department_name'],
                'faculty' => $student['faculty_name'],
                'required_credit_hours' => (int) $student['required_credit_hours'],
            ],
            'semesters' => $this->groupBySemester($records),
            'summary' => $this->gpa->cumulativeGpa($studentId),
        ];
    }

    private function groupBySemester(array $records): array
    {
        $grouped = [];

        foreach ($records as $record) {
            $key = $record['academic_year'] . ' ' . $record['semester_name'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'semester' => $record['semester_name'],
                    'academic_year' => $record['academic_year'],
                    'courses' => [],
                ];
            }

            $grouped[$key]['courses'][] = [
                'course_code' => $record['course_code'],
                'course_name' => $record['course_name'],
                'credit_hours' => (int) $record['credit_hours'],
                'grade' => $record['grade'],
                'grade_points' => (float) $record['grade_points'],
                'earned_credit_hours' => (int) $record['earned_credit_hours'],
            ];
        }

        return array_values($grouped);
    }
}
