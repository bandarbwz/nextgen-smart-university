<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\AssessmentRubric;
use Throwable;

class AssessmentService
{
    public function __construct(
        private readonly Assessment $assessments = new Assessment(),
        private readonly AssessmentRubric $rubrics = new AssessmentRubric(),
        private readonly AssessmentResult $results = new AssessmentResult(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);
        }

        $sectionIds = $sectionId === null ? $this->access->visibleSectionIds($user) : [$sectionId];

        return $this->assessments->forSections($sectionIds, $user['role'] === 'Student');
    }

    public function get(int $id, array $user): array
    {
        $assessment = $this->requireAssessment($id);

        $this->access->guardSectionVisible((int) $assessment['section_id'], $user);

        if ($user['role'] === 'Student' && $assessment['status'] === 'draft') {
            throw new ApiException('Assessment not found.', 404);
        }

        $assessment['rubric'] = $this->rubrics->forAssessment($id);

        if ($user['role'] === 'Student') {
            $studentId = $this->access->requireStudentId($user['user_id']);
            $result = $this->results->findForStudent($id, $studentId);

            $assessment['my_result'] = ($result !== null && $result['published_at'] !== null)
                ? $result
                : null;

            return $assessment;
        }

        $assessment['results'] = $this->results->forAssessment($id);
        $assessment['statistics'] = $this->results->statistics($id);

        return $assessment;
    }

    public function create(array $user, array $fields, array $rubric): array
    {
        $sectionId = (int) $fields['section_id'];

        $this->access->guardSectionOwned($sectionId, $user);
        $this->guardWeight($sectionId, (float) $fields['weight_percentage'], null);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $id = $this->assessments->create([
                'section_id' => $sectionId,
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'assessment_type' => $fields['assessment_type'],
                'total_marks' => (float) $fields['total_marks'],
                'weight_percentage' => (float) $fields['weight_percentage'],
                'due_date' => $fields['due_date'] ?? null,
                'status' => $fields['status'] ?? 'draft',
                'created_by' => $user['user_id'],
            ]);

            $this->replaceRubric($id, $rubric, (float) $fields['total_marks']);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->get($id, $user);
    }

    public function update(int $id, array $user, array $fields, array $rubric): array
    {
        $assessment = $this->requireAssessment($id);

        $this->access->guardSectionOwned((int) $assessment['section_id'], $user);
        $this->guardWeight(
            (int) $assessment['section_id'],
            (float) $fields['weight_percentage'],
            $id
        );

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->assessments->update($id, [
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'assessment_type' => $fields['assessment_type'],
                'total_marks' => (float) $fields['total_marks'],
                'weight_percentage' => (float) $fields['weight_percentage'],
                'due_date' => $fields['due_date'] ?? null,
                'status' => $fields['status'] ?? $assessment['status'],
            ]);

            if ($rubric !== []) {
                $this->rubrics->deleteForAssessment($id);
                $this->replaceRubric($id, $rubric, (float) $fields['total_marks']);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->get($id, $user);
    }

    public function delete(int $id, array $user): void
    {
        $assessment = $this->requireAssessment($id);

        $this->access->guardSectionOwned((int) $assessment['section_id'], $user);

        if ($this->results->forAssessment($id) !== []) {
            throw new ApiException(
                'This assessment has results recorded and cannot be deleted.',
                409
            );
        }

        $this->assessments->delete($id);
    }

    /**
     * The section has to add up to a hundred per cent. Anything else makes the
     * weighted course total meaningless, so the sum is checked on every write
     * rather than hoped for.
     */
    public function weightSummary(int $sectionId, array $user): array
    {
        $this->access->guardSectionVisible($sectionId, $user);

        $used = $this->assessments->weightUsed($sectionId);

        return [
            'weight_used' => $used,
            'weight_remaining' => round(100 - $used, 2),
            'is_complete' => abs($used - 100) < 0.01,
            'assessments' => $this->assessments->forSection($sectionId),
        ];
    }

    public function requireAssessment(int $id): array
    {
        $assessment = $this->assessments->findDetailed($id);

        if ($assessment === null) {
            throw new ApiException('Assessment not found.', 404);
        }

        return $assessment;
    }

    private function guardWeight(int $sectionId, float $weight, ?int $ignoreId): void
    {
        $used = $this->assessments->weightUsed($sectionId, $ignoreId);

        if (round($used + $weight, 2) > 100) {
            throw new ApiException(
                'The section already uses ' . $used . ' per cent, so this weight would exceed 100.',
                422
            );
        }
    }

    /**
     * A rubric that does not add up to the assessment total is a rubric that
     * cannot be marked against, so it is refused rather than stored broken.
     */
    private function replaceRubric(int $assessmentId, array $rubric, float $totalMarks): void
    {
        if ($rubric === []) {
            return;
        }

        $position = 1;
        $sum = 0.0;

        foreach ($rubric as $criterion) {
            $marks = (float) $criterion['maximum_marks'];
            $sum += $marks;

            $this->rubrics->create([
                'assessment_id' => $assessmentId,
                'criterion' => $criterion['criterion'],
                'description' => $criterion['description'] ?? null,
                'maximum_marks' => $marks,
                'position' => $position,
            ]);

            $position++;
        }

        if (round($sum, 2) !== round($totalMarks, 2)) {
            throw new ApiException(
                'The rubric adds up to ' . round($sum, 2) . ' but the assessment is out of '
                    . round($totalMarks, 2) . '.',
                422
            );
        }
    }
}
