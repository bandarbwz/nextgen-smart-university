<?php

declare(strict_types=1);

namespace App\Validation;

class AssessmentValidator
{
    private const TYPES = ['Assignment', 'Quiz', 'Midterm', 'Final', 'Project', 'Participation'];

    public function assessment(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 200, 'Title')
            ->required($data, 'assessment_type', 'Assessment type')
            ->inList($data, 'assessment_type', self::TYPES, 'Assessment type')
            ->required($data, 'total_marks', 'Total marks')
            ->numberBetween($data, 'total_marks', 0.01, 9999, 'Total marks')
            ->required($data, 'weight_percentage', 'Weight percentage')
            ->numberBetween($data, 'weight_percentage', 0, 100, 'Weight percentage')
            ->date($data, 'due_date', 'Due date')
            ->inList($data, 'status', ['draft', 'published', 'closed'], 'Status')
            ->errors();
    }

    public function assessmentUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 200, 'Title')
            ->required($data, 'assessment_type', 'Assessment type')
            ->inList($data, 'assessment_type', self::TYPES, 'Assessment type')
            ->required($data, 'total_marks', 'Total marks')
            ->numberBetween($data, 'total_marks', 0.01, 9999, 'Total marks')
            ->required($data, 'weight_percentage', 'Weight percentage')
            ->numberBetween($data, 'weight_percentage', 0, 100, 'Weight percentage')
            ->date($data, 'due_date', 'Due date')
            ->inList($data, 'status', ['draft', 'published', 'closed'], 'Status')
            ->errors();
    }

    public function rubric(array $rubric): array
    {
        $errors = [];

        foreach ($rubric as $index => $criterion) {
            $label = 'rubric.' . $index;

            if (!isset($criterion['criterion']) || trim((string) $criterion['criterion']) === '') {
                $errors[$label][] = 'The criterion is required.';
            }

            $marks = filter_var($criterion['maximum_marks'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($marks === false || $marks <= 0) {
                $errors[$label][] = 'The maximum marks must be greater than zero.';
            }
        }

        return $errors;
    }

    public function result(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'marks', 'Marks')
            ->numberBetween($data, 'marks', 0, 9999, 'Marks')
            ->errors();
    }
}
