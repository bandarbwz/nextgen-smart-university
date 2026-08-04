<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Enrollment;
use App\Models\Student;

class AssessmentResultService
{
    public function __construct(
        private readonly Assessment $assessments = new Assessment(),
        private readonly AssessmentResult $results = new AssessmentResult(),
        private readonly Enrollment $enrollments = new Enrollment(),
        private readonly Student $students = new Student(),
        private readonly AssessmentService $assessmentService = new AssessmentService(),
        private readonly CourseAccessService $access = new CourseAccessService(),
        private readonly NotificationService $notifications = new NotificationService()
    ) {
    }

    public function record(int $assessmentId, array $user, array $fields): array
    {
        $assessment = $this->assessmentService->requireAssessment($assessmentId);

        $this->access->guardSectionOwned((int) $assessment['section_id'], $user);

        $studentId = (int) $fields['student_id'];
        $marks = (float) $fields['marks'];

        $this->guardEnrolled($studentId, (int) $assessment['section_id']);

        if ($marks > (float) $assessment['total_marks']) {
            throw new ApiException(
                'The marks cannot exceed the assessment total of ' . $assessment['total_marks'] . '.',
                422
            );
        }

        $existing = $this->results->findForStudent($assessmentId, $studentId);

        if ($existing !== null && $existing['published_at'] !== null) {
            throw new ApiException(
                'This result is published and cannot be changed without approval.',
                409
            );
        }

        $percentage = round($marks / (float) $assessment['total_marks'] * 100, 2);
        [$letter] = GradeScale::forPercentage($percentage);

        $values = [
            'marks' => $marks,
            'percentage' => $percentage,
            'grade' => $letter,
            'feedback' => $fields['feedback'] ?? null,
            'graded_by' => $user['user_id'],
            'graded_at' => gmdate('Y-m-d H:i:s'),
        ];

        if ($existing === null) {
            $id = $this->results->create(
                $values + ['assessment_id' => $assessmentId, 'student_id' => $studentId]
            );
        } else {
            $id = (int) $existing['id'];
            $this->results->update($id, $values);
        }

        return $this->results->find($id);
    }

    public function forAssessment(int $assessmentId, array $user): array
    {
        $assessment = $this->assessmentService->requireAssessment($assessmentId);

        $this->access->guardSectionOwned((int) $assessment['section_id'], $user);

        return $this->results->forAssessment($assessmentId);
    }

    public function mine(array $user): array
    {
        return $this->results->forStudent($this->access->requireStudentId($user['user_id']));
    }

    /**
     * Publishing is the point of no return. Until then a result can be
     * corrected freely; afterwards the student has seen it and it is locked.
     */
    public function publish(int $assessmentId, array $user): array
    {
        $assessment = $this->assessmentService->requireAssessment($assessmentId);

        $this->access->guardSectionOwned((int) $assessment['section_id'], $user);

        $published = $this->results->publishForAssessment($assessmentId);

        $this->assessments->update($assessmentId, ['status' => 'closed']);

        foreach ($this->results->forAssessment($assessmentId) as $result) {
            $this->notifyStudent(
                (int) $result['student_id'],
                'Result published',
                'Your result for ' . $assessment['title'] . ' in ' . $assessment['course_code']
                    . ' is now available.'
            );
        }

        return [
            'published' => $published,
            'assessment_id' => $assessmentId,
        ];
    }

    /**
     * The weighted course total. Each published result contributes its
     * percentage scaled by the weight of its assessment, so a midterm worth
     * thirty per cent counts for thirty per cent regardless of its mark total.
     */
    public function courseResult(int $studentId, int $sectionId, array $user): array
    {
        $this->access->guardSectionVisible($sectionId, $user);

        if ($user['role'] === 'Student'
            && $this->access->requireStudentId($user['user_id']) !== $studentId) {
            throw new ApiException('You can only view your own results.', 403);
        }

        $results = $this->results->publishedForStudentInSection($studentId, $sectionId);

        $weightedTotal = 0.0;
        $weightCounted = 0.0;

        foreach ($results as $result) {
            $weight = (float) $result['weight_percentage'];

            $weightedTotal += (float) $result['percentage'] * $weight / 100;
            $weightCounted += $weight;
        }

        $weightedTotal = round($weightedTotal, 2);
        [$letter, $points] = GradeScale::forPercentage($weightedTotal);

        return [
            'section_id' => $sectionId,
            'student_id' => $studentId,
            'components' => $results,
            'weight_counted' => round($weightCounted, 2),
            'weighted_percentage' => $weightedTotal,
            'grade_letter' => $letter,
            'grade_points' => $points,
            'is_complete' => abs($weightCounted - 100) < 0.01,
        ];
    }

    private function guardEnrolled(int $studentId, int $sectionId): void
    {
        if (!in_array($sectionId, $this->enrollments->activeSectionIds($studentId), true)) {
            throw new ApiException('This student is not enrolled in the section.', 409);
        }
    }

    private function notifyStudent(int $studentId, string $title, string $message): void
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            return;
        }

        $this->notifications->notify(
            (int) $student['user_id'],
            'Assessment',
            $title,
            $message,
            ['type' => 'success', 'priority' => 'High']
        );
    }
}
