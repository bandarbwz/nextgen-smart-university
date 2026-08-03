<?php

declare(strict_types=1);

namespace App\Models;

class Assignment extends Model
{
    protected string $table = 'Assignment';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'due_date DESC';

    public function forSections(array $sectionIds): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));

        $statement = $this->db->prepare(
            'SELECT a.*, c.course_code, c.course_name, s.section_number
             FROM Assignment a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.section_id IN (' . $placeholders . ') AND a.deleted_at IS NULL
             ORDER BY a.due_date DESC'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT a.*, c.course_code, c.course_name, s.section_number, s.lecturer_id
             FROM Assignment a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.id = :id AND a.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function submissionSummary(int $assignmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) AS submitted,
                    SUM(submission_status = :graded) AS graded,
                    SUM(submission_status = :late) AS late_count
             FROM AssignmentSubmission
             WHERE assignment_id = :assignment_id'
        );

        $statement->execute([
            'assignment_id' => $assignmentId,
            'graded' => 'Graded',
            'late' => 'Late',
        ]);

        return $statement->fetch() ?: ['submitted' => 0, 'graded' => 0, 'late_count' => 0];
    }
}
