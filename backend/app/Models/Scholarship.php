<?php

declare(strict_types=1);

namespace App\Models;

class Scholarship extends Model
{
    protected string $table = 'Scholarship';

    protected string $defaultOrder = 'created_at DESC';

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Scholarship WHERE student_id = :student_id ORDER BY created_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function activeAmountForStudent(int $studentId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM Scholarship
             WHERE student_id = :student_id
               AND status = :active
               AND UTC_DATE() BETWEEN start_date AND end_date'
        );

        $statement->execute([
            'student_id' => $studentId,
            'active' => 'active',
        ]);

        return (float) $statement->fetchColumn();
    }

    public function allWithStudent(): array
    {
        return $this->db->query(
            'SELECT sc.*, st.student_number, u.full_name AS student_name
             FROM Scholarship sc
             JOIN Student st ON st.id = sc.student_id
             JOIN User u ON u.id = st.user_id
             ORDER BY sc.created_at DESC'
        )->fetchAll();
    }

    public function revoke(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Scholarship SET status = :revoked WHERE id = :id'
        );

        return $statement->execute([
            'revoked' => 'revoked',
            'id' => $id,
        ]);
    }
}
