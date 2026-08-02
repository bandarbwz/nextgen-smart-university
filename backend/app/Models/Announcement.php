<?php

declare(strict_types=1);

namespace App\Models;

class Announcement extends Model
{
    protected string $table = 'Announcement';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'published_at DESC';

    public function forSections(array $sectionIds): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));

        $statement = $this->db->prepare(
            'SELECT a.*, c.course_code, c.course_name, s.section_number, u.full_name AS lecturer_name
             FROM Announcement a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = a.lecturer_id
             JOIN User u ON u.id = l.user_id
             WHERE a.section_id IN (' . $placeholders . ') AND a.deleted_at IS NULL
             ORDER BY a.published_at DESC'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }
}
