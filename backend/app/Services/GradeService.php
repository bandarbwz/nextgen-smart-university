<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Grade;

class GradeService
{
    public function __construct(
        private readonly Grade $grades = new Grade(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($user['role'] === 'Student') {
            return $this->grades->forStudent(
                $this->access->requireStudentId($user['user_id']),
                true
            );
        }

        if ($sectionId === null) {
            throw new ApiException('A section_id query parameter is required.', 400);
        }

        $this->access->guardSectionVisible($sectionId, $user);

        return $this->grades->forSection($sectionId);
    }

    public function record(array $user, array $fields): array
    {
        $sectionId = (int) $fields['section_id'];

        $this->access->guardSectionOwned($sectionId, $user);

        $marks = (float) $fields['marks'];
        $totalMarks = (float) $fields['total_marks'];

        if ($marks > $totalMarks) {
            throw new ApiException('The marks cannot exceed the total marks.', 422);
        }

        $assessmentId = isset($fields['assessment_id']) ? (int) $fields['assessment_id'] : null;

        $existing = $this->grades->findExisting(
            (int) $fields['student_id'],
            $sectionId,
            $fields['assessment_type'],
            $assessmentId
        );

        if ($existing !== null && $existing['published_at'] !== null) {
            throw new ApiException('This grade has been published and can no longer be changed.', 409);
        }

        [$letter, $points] = $this->letterFor($marks, $totalMarks);

        $payload = [
            'title' => $fields['title'],
            'marks' => $marks,
            'total_marks' => $totalMarks,
            'grade_letter' => $letter,
            'grade_points' => $points,
        ];

        if ($existing !== null) {
            $this->grades->update((int) $existing['id'], $payload);

            return $this->grades->find((int) $existing['id']);
        }

        $id = $this->grades->create($payload + [
            'student_id' => (int) $fields['student_id'],
            'section_id' => $sectionId,
            'assessment_type' => $fields['assessment_type'],
            'assessment_id' => $assessmentId,
        ]);

        return $this->grades->find($id);
    }

    public function publish(int $sectionId, array $user): array
    {
        $this->access->guardSectionOwned($sectionId, $user);

        $published = $this->grades->publishForSection($sectionId, $user['user_id']);

        return [
            'section_id' => $sectionId,
            'published_count' => $published,
        ];
    }

    private function letterFor(float $marks, float $totalMarks): array
    {
        return GradeScale::forMarks($marks, $totalMarks);
    }
}
