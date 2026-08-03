<?php

declare(strict_types=1);

namespace App\Models;

class FinancialHold extends Model
{
    protected string $table = 'FinancialHold';

    protected string $defaultOrder = 'applied_date DESC';

    public function activeForStudent(int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM FinancialHold
             WHERE student_id = :student_id AND status = :active
             ORDER BY applied_date DESC
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'active' => 'active',
        ]);

        return $statement->fetch() ?: null;
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM FinancialHold WHERE student_id = :student_id ORDER BY applied_date DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function allWithStudent(?string $status): array
    {
        $sql = 'SELECT h.*, st.student_number, u.full_name AS student_name
                FROM FinancialHold h
                JOIN Student st ON st.id = h.student_id
                JOIN User u ON u.id = st.user_id';

        $parameters = [];

        if ($status !== null) {
            $sql .= ' WHERE h.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY h.applied_date DESC');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function release(int $id, int $releasedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE FinancialHold
             SET status = :released, released_date = UTC_TIMESTAMP(), released_by = :released_by
             WHERE id = :id'
        );

        return $statement->execute([
            'released' => 'released',
            'released_by' => $releasedBy,
            'id' => $id,
        ]);
    }
}
