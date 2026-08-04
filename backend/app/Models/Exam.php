<?php

declare(strict_types=1);

namespace App\Models;

class Exam extends Model
{
    protected string $table = 'Exam';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'start_time DESC';

    public function forSections(array $sectionIds, bool $publishedOnly): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));
        $visibility = $publishedOnly ? " AND e.status IN ('published', 'closed')" : '';

        $statement = $this->db->prepare(
            'SELECT e.*, c.course_code, c.course_name, s.section_number
             FROM Exam e
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.section_id IN (' . $placeholders . ') AND e.deleted_at IS NULL' . $visibility . '
             ORDER BY e.start_time DESC'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, c.course_code, c.course_name, s.section_number, s.lecturer_id
             FROM Exam e
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE e.id = :id AND e.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function recalculateTotalMarks(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Exam
             SET total_marks = COALESCE(
                 (SELECT SUM(marks) FROM ExamQuestion WHERE exam_id = :exam_id), 0
             )
             WHERE id = :id'
        );

        return $statement->execute([
            'exam_id' => $id,
            'id' => $id,
        ]);
    }
}
