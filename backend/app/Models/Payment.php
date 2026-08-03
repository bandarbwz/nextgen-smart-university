<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends Model
{
    protected string $table = 'Payment';

    protected string $defaultOrder = 'payment_date DESC';

    public function referenceExists(string $reference): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Payment WHERE payment_reference = :reference LIMIT 1'
        );

        $statement->execute(['reference' => $reference]);

        return $statement->fetchColumn() !== false;
    }

    public function forInvoice(int $invoiceId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Payment WHERE invoice_id = :invoice_id ORDER BY payment_date'
        );

        $statement->execute(['invoice_id' => $invoiceId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, i.invoice_number, s.name AS semester_name
             FROM Payment p
             JOIN Invoice i ON i.id = p.invoice_id
             JOIN Semester s ON s.id = i.semester_id
             WHERE i.student_id = :student_id
             ORDER BY p.payment_date DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, i.invoice_number, i.student_id, u.full_name AS student_name
             FROM Payment p
             JOIN Invoice i ON i.id = p.invoice_id
             JOIN Student st ON st.id = i.student_id
             JOIN User u ON u.id = st.user_id
             WHERE p.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function invoiceHasPayments(int $invoiceId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Payment WHERE invoice_id = :invoice_id LIMIT 1'
        );

        $statement->execute(['invoice_id' => $invoiceId]);

        return $statement->fetchColumn() !== false;
    }

    public function revenueBySemester(?int $semesterId): array
    {
        $sql = 'SELECT s.id AS semester_id, s.name AS semester_name, s.academic_year,
                       COUNT(p.id) AS payment_count,
                       COALESCE(SUM(p.amount), 0) AS total_collected
                FROM Payment p
                JOIN Invoice i ON i.id = p.invoice_id
                JOIN Semester s ON s.id = i.semester_id
                WHERE p.payment_status = :completed';

        $parameters = ['completed' => 'completed'];

        if ($semesterId !== null) {
            $sql .= ' AND s.id = :semester_id';
            $parameters['semester_id'] = $semesterId;
        }

        $statement = $this->db->prepare(
            $sql . ' GROUP BY s.id, s.name, s.academic_year ORDER BY s.start_date DESC'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
