<?php

declare(strict_types=1);

namespace App\Models;

class Assessment extends Model
{
    protected string $table = 'Assessment';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'due_date, id';

    public function forSections(array $sectionIds, bool $publishedOnly): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));
        $visibility = $publishedOnly ? " AND a.status <> 'draft'" : '';

        $statement = $this->db->prepare(
            'SELECT a.*, c.course_code, c.course_name, s.section_number,
                    (SELECT COUNT(*) FROM AssessmentResult r WHERE r.assessment_id = a.id) AS graded_count
             FROM Assessment a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.section_id IN (' . $placeholders . ') AND a.deleted_at IS NULL' . $visibility . '
             ORDER BY a.due_date, a.id'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT a.*, c.course_code, c.course_name, s.section_number, s.lecturer_id
             FROM Assessment a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.id = :id AND a.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function forSection(int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Assessment
             WHERE section_id = :section_id AND deleted_at IS NULL
             ORDER BY due_date, id'
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetchAll();
    }

    /**
     * The weight already committed to a section, so a new or edited assessment
     * can be checked against the hundred per cent the section has to add up to.
     */
    public function weightUsed(int $sectionId, ?int $ignoreId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(weight_percentage), 0) FROM Assessment
                WHERE section_id = :section_id AND deleted_at IS NULL';

        $parameters = ['section_id' => $sectionId];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $parameters['ignore_id'] = $ignoreId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return round((float) $statement->fetchColumn(), 2);
    }
}
