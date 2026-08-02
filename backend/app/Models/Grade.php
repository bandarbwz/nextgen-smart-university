<?php

declare(strict_types=1);

namespace App\Models;

class Grade extends Model
{
    protected string $table = 'Grade';

    protected string $defaultOrder = 'created_at DESC';

    public function forStudent(int $studentId, bool $publishedOnly): array
    {
        $sql = 'SELECT g.*, c.course_code, c.course_name, s.section_number
                FROM Grade g
                JOIN Section s ON s.id = g.section_id
                JOIN Course c ON c.id = s.course_id
                WHERE g.student_id = :student_id';

        if ($publishedOnly) {
            $sql .= ' AND g.published_at IS NOT NULL';
        }

        $statement = $this->db->prepare($sql . ' ORDER BY c.course_code, g.assessment_type');
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function forSection(int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT g.*, st.student_number, u.full_name AS student_name
             FROM Grade g
             JOIN Student st ON st.id = g.student_id
             JOIN User u ON u.id = st.user_id
             WHERE g.section_id = :section_id
             ORDER BY st.student_number, g.assessment_type'
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetchAll();
    }

    public function findExisting(
        int $studentId,
        int $sectionId,
        string $assessmentType,
        ?int $assessmentId
    ): ?array {
        $sql = 'SELECT * FROM Grade
                WHERE student_id = :student_id
                  AND section_id = :section_id
                  AND assessment_type = :assessment_type
                  AND assessment_id ' . ($assessmentId === null ? 'IS NULL' : '= :assessment_id');

        $parameters = [
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'assessment_type' => $assessmentType,
        ];

        if ($assessmentId !== null) {
            $parameters['assessment_id'] = $assessmentId;
        }

        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetch() ?: null;
    }

    public function publishForSection(int $sectionId, int $publishedBy): int
    {
        $statement = $this->db->prepare(
            'UPDATE Grade
             SET published_at = UTC_TIMESTAMP(), published_by = :published_by
             WHERE section_id = :section_id AND published_at IS NULL'
        );

        $statement->execute([
            'published_by' => $publishedBy,
            'section_id' => $sectionId,
        ]);

        return $statement->rowCount();
    }
}
