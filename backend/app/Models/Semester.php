<?php

declare(strict_types=1);

namespace App\Models;

class Semester extends Model
{
    protected string $table = 'Semester';

    protected string $defaultOrder = 'academic_year DESC, start_date DESC';

    public function current(): ?array
    {
        $statement = $this->db->query(
            'SELECT * FROM Semester WHERE current_semester = TRUE LIMIT 1'
        );

        return $statement->fetch() ?: null;
    }

    public function clearCurrentFlag(): bool
    {
        return $this->db->exec('UPDATE Semester SET current_semester = FALSE') !== false;
    }

    public function registrationIsOpen(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Semester
             WHERE id = :id
               AND registration_start IS NOT NULL
               AND registration_end IS NOT NULL
               AND UTC_TIMESTAMP() BETWEEN registration_start AND registration_end
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    public function hasSections(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Section WHERE semester_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
