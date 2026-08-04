<?php

declare(strict_types=1);

namespace App\Models;

class AssessmentRubric extends Model
{
    protected string $table = 'AssessmentRubric';

    protected string $defaultOrder = 'position, id';

    public function forAssessment(int $assessmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM AssessmentRubric
             WHERE assessment_id = :assessment_id
             ORDER BY position, id'
        );

        $statement->execute(['assessment_id' => $assessmentId]);

        return $statement->fetchAll();
    }

    public function totalMarks(int $assessmentId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(maximum_marks), 0) FROM AssessmentRubric
             WHERE assessment_id = :assessment_id'
        );

        $statement->execute(['assessment_id' => $assessmentId]);

        return round((float) $statement->fetchColumn(), 2);
    }

    public function deleteForAssessment(int $assessmentId): bool
    {
        return $this->db
            ->prepare('DELETE FROM AssessmentRubric WHERE assessment_id = :assessment_id')
            ->execute(['assessment_id' => $assessmentId]);
    }
}
