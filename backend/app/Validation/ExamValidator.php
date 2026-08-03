<?php

declare(strict_types=1);

namespace App\Validation;

class ExamValidator
{
    private const QUESTION_TYPES = ['Multiple Choice', 'True / False', 'Short Answer', 'Essay'];

    private const VIOLATION_TYPES = [
        'Multiple Faces',
        'Face Not Detected',
        'Looking Away',
        'Head Pose Warning',
        'Tab Switching',
        'Fullscreen Exit',
        'Camera Disabled',
    ];

    private const BROWSER_ACTIVITIES = [
        'tab_hidden',
        'tab_visible',
        'fullscreen_exit',
        'fullscreen_enter',
        'window_blur',
        'window_focus',
        'copy',
        'paste',
    ];

    public function exam(array $data): array
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
            ->numberBetween($data, 'passing_marks', 0, 9999, 'Passing marks')
            ->inList($data, 'status', ['draft', 'published', 'closed'], 'Status')
            ->errors();
    }

    public function examUpdate(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'duration', 'Duration')
            ->positiveInteger($data, 'duration', 'Duration')
            ->required($data, 'start_time', 'Start time')
            ->date($data, 'start_time', 'Start time')
            ->required($data, 'end_time', 'End time')
            ->date($data, 'end_time', 'End time')
            ->numberBetween($data, 'passing_marks', 0, 9999, 'Passing marks')
            ->inList($data, 'status', ['draft', 'published', 'closed'], 'Status')
            ->errors();
    }

    public function questions(array $questions): array
    {
        if ($questions === []) {
            return ['questions' => ['At least one question is required.']];
        }

        $errors = [];

        foreach ($questions as $index => $question) {
            $label = 'questions.' . $index;

            if (!isset($question['question']) || trim((string) $question['question']) === '') {
                $errors[$label][] = 'The question text is required.';
            }

            if (!in_array($question['question_type'] ?? '', self::QUESTION_TYPES, true)) {
                $errors[$label][] = 'The question type must be one of: '
                    . implode(', ', self::QUESTION_TYPES) . '.';
            }

            $marks = filter_var($question['marks'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($marks === false || $marks <= 0) {
                $errors[$label][] = 'The marks must be greater than zero.';
            }
        }

        return $errors;
    }

    public function sessionStart(array $data): array
    {
        return (new Validator())
            ->required($data, 'exam_id', 'Examination')
            ->integer($data, 'exam_id', 'Examination')
            ->errors();
    }

    public function session(array $data): array
    {
        return (new Validator())
            ->required($data, 'session_id', 'Session')
            ->integer($data, 'session_id', 'Session')
            ->errors();
    }

    public function capture(array $data): array
    {
        return (new Validator())
            ->required($data, 'session_id', 'Session')
            ->integer($data, 'session_id', 'Session')
            ->required($data, 'image', 'Image')
            ->errors();
    }

    public function browserActivity(array $data): array
    {
        return (new Validator())
            ->required($data, 'session_id', 'Session')
            ->integer($data, 'session_id', 'Session')
            ->required($data, 'activity_type', 'Activity type')
            ->inList($data, 'activity_type', self::BROWSER_ACTIVITIES, 'Activity type')
            ->maxLength($data, 'detail', 255, 'Detail')
            ->errors();
    }

    public function deviceMonitor(array $data): array
    {
        return (new Validator())
            ->required($data, 'session_id', 'Session')
            ->integer($data, 'session_id', 'Session')
            ->maxLength($data, 'browser', 100, 'Browser')
            ->maxLength($data, 'device', 100, 'Device')
            ->errors();
    }

    public function violation(array $data): array
    {
        return (new Validator())
            ->required($data, 'session_id', 'Session')
            ->integer($data, 'session_id', 'Session')
            ->required($data, 'violation_type', 'Violation type')
            ->inList($data, 'violation_type', self::VIOLATION_TYPES, 'Violation type')
            ->inList($data, 'severity', ['info', 'warning', 'critical'], 'Severity')
            ->required($data, 'detected_at', 'Detected at')
            ->date($data, 'detected_at', 'Detected at')
            ->required($data, 'confidence_score', 'Confidence score')
            ->numberBetween($data, 'confidence_score', 0, 1, 'Confidence score')
            ->maxLength($data, 'detail', 255, 'Detail')
            ->errors();
    }

    public function grade(array $data): array
    {
        return (new Validator())
            ->required($data, 'score', 'Score')
            ->numberBetween($data, 'score', 0, 9999, 'Score')
            ->errors();
    }
}
