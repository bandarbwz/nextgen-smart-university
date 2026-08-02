<?php

declare(strict_types=1);

namespace App\Validation;

class LmsValidator
{
    public function material(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->inList($data, 'visibility', ['visible', 'hidden'], 'Visibility')
            ->errors();
    }

    public function materialUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->inList($data, 'visibility', ['visible', 'hidden'], 'Visibility')
            ->errors();
    }

    public function assignment(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'total_marks', 'Total marks')
            ->numberBetween($data, 'total_marks', 0.01, 9999, 'Total marks')
            ->required($data, 'due_date', 'Due date')
            ->date($data, 'due_date', 'Due date')
            ->errors();
    }

    public function assignmentUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'total_marks', 'Total marks')
            ->numberBetween($data, 'total_marks', 0.01, 9999, 'Total marks')
            ->required($data, 'due_date', 'Due date')
            ->date($data, 'due_date', 'Due date')
            ->errors();
    }

    public function gradeSubmission(array $data): array
    {
        return (new Validator())
            ->required($data, 'marks', 'Marks')
            ->numberBetween($data, 'marks', 0, 9999, 'Marks')
            ->errors();
    }

    public function quiz(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'duration', 'Duration')
            ->positiveInteger($data, 'duration', 'Duration')
            ->required($data, 'start_time', 'Start time')
            ->date($data, 'start_time', 'Start time')
            ->required($data, 'end_time', 'End time')
            ->date($data, 'end_time', 'End time')
            ->positiveInteger($data, 'attempts', 'Attempts')
            ->errors();
    }

    public function quizQuestions(array $questions): array
    {
        $errors = [];
        $types = ['Multiple Choice', 'True / False', 'Short Answer', 'Essay'];

        if ($questions === []) {
            return ['questions' => ['At least one question is required.']];
        }

        foreach ($questions as $index => $question) {
            $label = 'questions.' . $index;

            if (!isset($question['question']) || trim((string) $question['question']) === '') {
                $errors[$label][] = 'The question text is required.';
            }

            if (!in_array($question['question_type'] ?? '', $types, true)) {
                $errors[$label][] = 'The question type must be one of: ' . implode(', ', $types) . '.';
            }

            $marks = filter_var($question['marks'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($marks === false || $marks <= 0) {
                $errors[$label][] = 'The marks must be greater than zero.';
            }
        }

        return $errors;
    }

    public function quizSubmission(array $data): array
    {
        $answers = $data['answers'] ?? null;

        if (!is_array($answers) || $answers === []) {
            return ['answers' => ['Answers are required.']];
        }

        return [];
    }

    public function announcement(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'content', 'Content')
            ->errors();
    }

    public function announcementUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'content', 'Content')
            ->errors();
    }

    public function resource(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'link', 'Link')
            ->url($data, 'link', 'Link')
            ->required($data, 'resource_type', 'Resource type')
            ->inList(
                $data,
                'resource_type',
                ['PDF', 'Video', 'Website', 'Document', 'External Link'],
                'Resource type'
            )
            ->errors();
    }

    public function grade(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'assessment_type', 'Assessment type')
            ->inList(
                $data,
                'assessment_type',
                ['Assignment', 'Quiz', 'Midterm', 'Final', 'Project', 'Other'],
                'Assessment type'
            )
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'marks', 'Marks')
            ->numberBetween($data, 'marks', 0, 9999, 'Marks')
            ->required($data, 'total_marks', 'Total marks')
            ->numberBetween($data, 'total_marks', 0.01, 9999, 'Total marks')
            ->errors();
    }

    public function publish(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->errors();
    }
}
