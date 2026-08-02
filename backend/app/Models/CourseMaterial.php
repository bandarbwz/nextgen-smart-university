<?php

declare(strict_types=1);

namespace App\Models;

class CourseMaterial extends Model
{
    protected string $table = 'CourseMaterial';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'upload_date DESC';

    public function forSection(int $sectionId, bool $includeHidden): array
    {
        $sql = 'SELECT m.*, u.full_name AS lecturer_name
                FROM CourseMaterial m
                JOIN Lecturer l ON l.id = m.lecturer_id
                JOIN User u ON u.id = l.user_id
                WHERE m.section_id = :section_id AND m.deleted_at IS NULL';

        if (!$includeHidden) {
            $sql .= " AND m.visibility = 'visible'";
        }

        $statement = $this->db->prepare($sql . ' ORDER BY m.upload_date DESC');
        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetchAll();
    }

    public function forSections(array $sectionIds, bool $includeHidden): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));

        $sql = 'SELECT m.*, c.course_code, c.course_name, s.section_number
                FROM CourseMaterial m
                JOIN Section s ON s.id = m.section_id
                JOIN Course c ON c.id = s.course_id
                WHERE m.section_id IN (' . $placeholders . ') AND m.deleted_at IS NULL';

        if (!$includeHidden) {
            $sql .= " AND m.visibility = 'visible'";
        }

        $statement = $this->db->prepare($sql . ' ORDER BY m.upload_date DESC');
        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }
}
