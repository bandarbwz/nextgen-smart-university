<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\FinancialHold;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Scholarship;
use App\Models\Student;
use App\Models\TuitionFee;
use Throwable;

class FinanceService
{
    public function __construct(
        private readonly Invoice $invoices = new Invoice(),
        private readonly Payment $payments = new Payment(),
        private readonly Scholarship $scholarships = new Scholarship(),
        private readonly FinancialHold $holds = new FinancialHold(),
        private readonly TuitionFee $fees = new TuitionFee(),
        private readonly Student $students = new Student()
    ) {
    }

    public function invoicesFor(array $user, ?int $studentId): array
    {
        if ($user['role'] === 'Student') {
            return $this->invoices->forStudent($this->requireOwnStudentId($user));
        }

        return $studentId === null
            ? $this->invoices->all()
            : $this->invoices->forStudent($studentId);
    }

    public function invoice(int $id, array $user): array
    {
        $invoice = $this->invoices->findDetailed($id);

        if ($invoice === null) {
            throw new ApiException('Invoice not found.', 404);
        }

        $this->guardOwnership((int) $invoice['student_id'], $user, 'Invoice not found.');

        $invoice['payments'] = $this->payments->forInvoice($id);

        return $invoice;
    }

    public function generateInvoice(array $user, array $fields): array
    {
        $studentId = (int) $fields['student_id'];
        $semesterId = (int) $fields['semester_id'];

        $student = $this->students->find($studentId);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        if ($this->invoices->existsForSemester($studentId, $semesterId)) {
            throw new ApiException('An invoice already exists for this student and semester.', 409);
        }

        $gross = $this->fees->totalFor((int) $student['program_id'], $semesterId);

        if ($gross <= 0) {
            throw new ApiException(
                'No tuition fees are configured for this programme and semester.',
                404
            );
        }

        $scholarship = min($this->scholarships->activeAmountForStudent($studentId), $gross);
        $total = round($gross - $scholarship, 2);

        $id = $this->invoices->create([
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'gross_amount' => $gross,
            'scholarship_amount' => $scholarship,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance' => $total,
            'due_date' => $fields['due_date'],
            'status' => $total <= 0 ? 'Paid' : 'Pending',
            'issued_by' => $user['user_id'],
        ]);

        return $this->invoices->findDetailed($id);
    }

    public function cancelInvoice(int $id): array
    {
        $invoice = $this->invoices->find($id);

        if ($invoice === null) {
            throw new ApiException('Invoice not found.', 404);
        }

        if ($this->payments->invoiceHasPayments($id)) {
            throw new ApiException('An invoice with recorded payments cannot be cancelled.', 409);
        }

        $this->invoices->update($id, ['status' => 'Cancelled', 'balance' => 0]);

        return $this->invoices->findDetailed($id);
    }

    public function recordPayment(array $user, array $fields): array
    {
        $invoiceId = (int) $fields['invoice_id'];
        $amount = round((float) $fields['amount'], 2);
        $reference = trim((string) $fields['payment_reference']);

        $invoice = $this->invoices->find($invoiceId);

        if ($invoice === null) {
            throw new ApiException('Invoice not found.', 404);
        }

        if ($invoice['status'] === 'Cancelled') {
            throw new ApiException('This invoice has been cancelled.', 409);
        }

        if ($this->payments->referenceExists($reference)) {
            throw new ApiException('This payment reference has already been recorded.', 409);
        }

        if ($amount > (float) $invoice['balance']) {
            throw new ApiException(
                'The payment exceeds the outstanding balance of ' . $invoice['balance'] . '.',
                409
            );
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $paymentId = $this->payments->create([
                'invoice_id' => $invoiceId,
                'payment_reference' => $reference,
                'payment_method' => $fields['payment_method'],
                'amount' => $amount,
                'payment_date' => gmdate('Y-m-d H:i:s'),
                'payment_status' => 'completed',
                'recorded_by' => $user['user_id'],
            ]);

            $this->invoices->applyPayment($invoiceId, $amount);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->payments->findDetailed($paymentId);
    }

    public function paymentsFor(array $user, ?int $studentId): array
    {
        if ($user['role'] === 'Student') {
            return $this->payments->forStudent($this->requireOwnStudentId($user));
        }

        return $studentId === null ? $this->payments->all() : $this->payments->forStudent($studentId);
    }

    public function payment(int $id, array $user): array
    {
        $payment = $this->payments->findDetailed($id);

        if ($payment === null) {
            throw new ApiException('Payment not found.', 404);
        }

        $this->guardOwnership((int) $payment['student_id'], $user, 'Payment not found.');

        return $payment;
    }

    public function scholarshipsFor(array $user, ?int $studentId): array
    {
        if ($user['role'] === 'Student') {
            return $this->scholarships->forStudent($this->requireOwnStudentId($user));
        }

        return $studentId === null
            ? $this->scholarships->allWithStudent()
            : $this->scholarships->forStudent($studentId);
    }

    public function awardScholarship(array $user, array $fields): array
    {
        $studentId = (int) $fields['student_id'];
        $student = $this->students->find($studentId);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        if (strtotime($fields['end_date']) <= strtotime($fields['start_date'])) {
            throw new ApiException('The end date must be after the start date.', 422);
        }

        $amount = round((float) $fields['amount'], 2);
        $currentSemesterFees = $this->highestSemesterFeeFor((int) $student['program_id']);

        if ($currentSemesterFees > 0 && $amount > $currentSemesterFees) {
            throw new ApiException(
                'A scholarship cannot exceed the tuition fees of ' . $currentSemesterFees . '.',
                422
            );
        }

        $id = $this->scholarships->create([
            'student_id' => $studentId,
            'scholarship_name' => $fields['scholarship_name'],
            'amount' => $amount,
            'start_date' => $fields['start_date'],
            'end_date' => $fields['end_date'],
            'status' => 'active',
            'awarded_by' => $user['user_id'],
        ]);

        return $this->scholarships->find($id);
    }

    public function revokeScholarship(int $id): array
    {
        $scholarship = $this->scholarships->find($id);

        if ($scholarship === null) {
            throw new ApiException('Scholarship not found.', 404);
        }

        $this->scholarships->revoke($id);

        return $this->scholarships->find($id);
    }

    public function holdsFor(array $user, ?string $status): array
    {
        if ($user['role'] === 'Student') {
            return $this->holds->forStudent($this->requireOwnStudentId($user));
        }

        return $this->holds->allWithStudent($status);
    }

    public function applyHold(array $user, array $fields): array
    {
        $studentId = (int) $fields['student_id'];

        if ($this->students->find($studentId) === null) {
            throw new ApiException('Student not found.', 404);
        }

        if ($this->holds->activeForStudent($studentId) !== null) {
            throw new ApiException('This student already has an active financial hold.', 409);
        }

        $id = $this->holds->create([
            'student_id' => $studentId,
            'reason' => $fields['reason'],
            'applied_date' => gmdate('Y-m-d H:i:s'),
            'status' => 'active',
            'applied_by' => $user['user_id'],
        ]);

        return $this->holds->find($id);
    }

    public function releaseHold(int $id, array $user): array
    {
        $hold = $this->holds->find($id);

        if ($hold === null) {
            throw new ApiException('Financial hold not found.', 404);
        }

        if ($hold['status'] !== 'active') {
            throw new ApiException('This hold has already been released.', 409);
        }

        $this->holds->release($id, $user['user_id']);

        return $this->holds->find($id);
    }

    public function standingFor(int $studentId): array
    {
        $hold = $this->holds->activeForStudent($studentId);
        $outstanding = $this->invoices->outstandingBalanceForStudent($studentId);
        $overdue = $this->invoices->hasOverdueInvoice($studentId);

        return [
            'outstanding_balance' => $outstanding,
            'has_overdue_invoice' => $overdue,
            'active_hold' => $hold,
            'can_register' => $hold === null && !$overdue,
        ];
    }

    public function balanceReport(): array
    {
        $this->invoices->markOverdueInvoices();

        return Database::connection()->query(
            'SELECT st.id AS student_id, st.student_number, u.full_name,
                    COALESCE(SUM(i.total_amount), 0) AS invoiced,
                    COALESCE(SUM(i.paid_amount), 0) AS paid,
                    COALESCE(SUM(i.balance), 0) AS balance
             FROM Student st
             JOIN User u ON u.id = st.user_id
             LEFT JOIN Invoice i ON i.student_id = st.id AND i.status != \'Cancelled\'
             WHERE st.deleted_at IS NULL
             GROUP BY st.id, st.student_number, u.full_name
             ORDER BY balance DESC, st.student_number'
        )->fetchAll();
    }

    public function revenueReport(?int $semesterId): array
    {
        return $this->payments->revenueBySemester($semesterId);
    }

    public function outstandingReport(): array
    {
        $this->invoices->markOverdueInvoices();

        return Database::connection()->query(
            'SELECT i.invoice_number, i.total_amount, i.paid_amount, i.balance, i.due_date, i.status,
                    st.student_number, u.full_name AS student_name
             FROM Invoice i
             JOIN Student st ON st.id = i.student_id
             JOIN User u ON u.id = st.user_id
             WHERE i.balance > 0 AND i.status != \'Cancelled\'
             ORDER BY i.due_date'
        )->fetchAll();
    }

    private function highestSemesterFeeFor(int $programId): float
    {
        $statement = Database::connection()->prepare(
            'SELECT COALESCE(MAX(total), 0) FROM (
                 SELECT SUM(amount) AS total
                 FROM TuitionFee
                 WHERE program_id = :program_id AND deleted_at IS NULL
                 GROUP BY semester_id
             ) AS semester_totals'
        );

        $statement->execute(['program_id' => $programId]);

        return (float) $statement->fetchColumn();
    }

    private function requireOwnStudentId(array $user): int
    {
        $student = $this->students->findByUserId($user['user_id']);

        if ($student === null) {
            throw new ApiException('No student record is linked to this account.', 404);
        }

        return (int) $student['id'];
    }

    private function guardOwnership(int $studentId, array $user, string $message): void
    {
        if ($user['role'] !== 'Student') {
            return;
        }

        if ($this->requireOwnStudentId($user) !== $studentId) {
            throw new ApiException($message, 404);
        }
    }
}
