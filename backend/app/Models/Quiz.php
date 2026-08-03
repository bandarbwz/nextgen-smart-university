<?php

declare(strict_types=1);

namespace App\Models;

class Quiz extends Model
{
    protected string $table = 'Quiz';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'start_time DESC';

    public function forSections(array $sectionIds): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));

        $statement = $this->db->prepare(
            'SELECT q.*, c.course_code, c.course_name, s.section_number
             FROM Quiz q
             JOIN Section s ON s.id = q.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE q.section_id IN (' . $placeholders . ') AND q.deleted_at IS NULL
             ORDER BY q.start_time DESC'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT q.*, c.course_code, c.course_name, s.section_number, s.lecturer_id
             FROM Quiz q
             JOIN Section s ON s.id = q.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE q.id = :id AND q.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function recalculateTotalMarks(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Quiz
             SET total_marks = COALESCE(
                 (SELECT SUM(marks) FROM QuizQuestion WHERE quiz_id = :quiz_id), 0
             )
             WHERE id = :id'
        );

        return $statement->execute([
            'quiz_id' => $id,
            'id' => $id,
        ]);
    }
}
