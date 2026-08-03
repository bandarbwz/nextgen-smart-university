<?php

declare(strict_types=1);

namespace App\Models;

class TuitionFee extends Model
{
    protected string $table = 'TuitionFee';

    protected bool $softDeletes = true;

    public function search(?int $programId, ?int $semesterId): array
    {
        $sql = 'SELECT f.*, p.name AS program_name, s.name AS semester_name
                FROM TuitionFee f
                JOIN Program p ON p.id = f.program_id
                JOIN Semester s ON s.id = f.semester_id
                WHERE f.deleted_at IS NULL';

        $parameters = [];

        if ($programId !== null) {
            $sql .= ' AND f.program_id = :program_id';
            $parameters['program_id'] = $programId;
        }

        if ($semesterId !== null) {
            $sql .= ' AND f.semester_id = :semester_id';
            $parameters['semester_id'] = $semesterId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY p.name, f.fee_type');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function totalFor(int $programId, int $semesterId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM TuitionFee
             WHERE program_id = :program_id AND semester_id = :semester_id AND deleted_at IS NULL'
        );

        $statement->execute([
            'program_id' => $programId,
            'semester_id' => $semesterId,
        ]);

        return (float) $statement->fetchColumn();
    }
}
