<?php

declare(strict_types=1);

namespace App\Models;

class Resource extends Model
{
    protected string $table = 'Resource';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'created_at DESC';

    public function forSections(array $sectionIds): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($sectionIds), '?'));

        $statement = $this->db->prepare(
            'SELECT r.*, c.course_code, c.course_name, s.section_number
             FROM Resource r
             JOIN Section s ON s.id = r.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE r.section_id IN (' . $placeholders . ') AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC'
        );

        $statement->execute($sectionIds);

        return $statement->fetchAll();
    }
}
