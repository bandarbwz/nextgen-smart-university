<?php

declare(strict_types=1);

namespace App\Models;

class Invoice extends Model
{
    protected string $table = 'Invoice';

    protected string $defaultOrder = 'created_at DESC';

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT i.*, s.name AS semester_name, s.academic_year
             FROM Invoice i
             JOIN Semester s ON s.id = i.semester_id
             WHERE i.student_id = :student_id
             ORDER BY i.created_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT i.*, s.name AS semester_name, s.academic_year,
                    st.student_number, u.full_name AS student_name
             FROM Invoice i
             JOIN Semester s ON s.id = i.semester_id
             JOIN Student st ON st.id = i.student_id
             JOIN User u ON u.id = st.user_id
             WHERE i.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function existsForSemester(int $studentId, int $semesterId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Invoice WHERE student_id = :student_id AND semester_id = :semester_id LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT i.*, st.student_number, u.full_name AS student_name
             FROM Invoice i
             JOIN Student st ON st.id = i.student_id
             JOIN User u ON u.id = st.user_id
             ORDER BY i.created_at DESC'
        )->fetchAll();
    }

    /**
     * Locks the row and computes the new totals in PHP. Doing the arithmetic in a
     * single UPDATE is tempting, but MySQL evaluates assignments left to right, so
     * a later expression reads the column values already written by an earlier one.
     * Caller must be inside a transaction for the lock to hold.
     */
    public function applyPayment(int $id, float $amount): bool
    {
        $locked = $this->db->prepare(
            'SELECT total_amount, paid_amount FROM Invoice WHERE id = :id FOR UPDATE'
        );

        $locked->execute(['id' => $id]);
        $invoice = $locked->fetch();

        if ($invoice === false) {
            return false;
        }

        $total = (float) $invoice['total_amount'];
        $paid = round((float) $invoice['paid_amount'] + $amount, 2);
        $balance = round($total - $paid, 2);

        $statement = $this->db->prepare(
            'UPDATE Invoice
             SET paid_amount = :paid, balance = :balance, status = :status
             WHERE id = :id'
        );

        return $statement->execute([
            'paid' => $paid,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'Paid' : 'Partially Paid',
            'id' => $id,
        ]);
    }

    public function markOverdueInvoices(): int
    {
        $statement = $this->db->prepare(
            'UPDATE Invoice
             SET status = :overdue
             WHERE balance > 0 AND due_date < UTC_DATE() AND status IN (:pending, :partial)'
        );

        $statement->execute([
            'overdue' => 'Overdue',
            'pending' => 'Pending',
            'partial' => 'Partially Paid',
        ]);

        return $statement->rowCount();
    }

    public function outstandingBalanceForStudent(int $studentId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(balance), 0)
             FROM Invoice
             WHERE student_id = :student_id AND status != :cancelled'
        );

        $statement->execute([
            'student_id' => $studentId,
            'cancelled' => 'Cancelled',
        ]);

        return (float) $statement->fetchColumn();
    }

    public function hasOverdueInvoice(int $studentId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Invoice
             WHERE student_id = :student_id
               AND balance > 0
               AND due_date < UTC_DATE()
               AND status != :cancelled
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'cancelled' => 'Cancelled',
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function nextInvoiceNumber(): string
    {
        $year = gmdate('Y');

        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM Invoice WHERE invoice_number LIKE :prefix'
        );

        $statement->execute(['prefix' => 'INV-' . $year . '-%']);

        return sprintf('INV-%s-%05d', $year, (int) $statement->fetchColumn() + 1);
    }
}
