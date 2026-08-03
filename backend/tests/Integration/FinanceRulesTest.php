<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\EnrollmentService;
use App\Services\FinanceService;
use Tests\TestCase;

class FinanceRulesTest extends TestCase
{
    private FinanceService $finance;

    private array $structure;

    private array $student;

    private array $lecturer;

    private array $adminUser;

    private array $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = new FinanceService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);

        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
        $this->studentUser = $this->actingAs($this->student['user_id'], 'Student');
    }

    public function testAnInvoiceIsGeneratedFromTheProgrammeFeeStructure(): void
    {
        $this->addFee('Tuition', 4000);
        $this->addFee('Laboratory', 500);

        $invoice = $this->generateInvoice();

        $this->assertSame('4500.00', $invoice['gross_amount']);
        $this->assertSame('4500.00', $invoice['total_amount']);
        $this->assertSame('4500.00', $invoice['balance']);
        $this->assertSame('Pending', $invoice['status']);
    }

    public function testAnActiveScholarshipReducesTheInvoicedAmount(): void
    {
        $this->addFee('Tuition', 4000);

        $this->finance->awardScholarship($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'scholarship_name' => 'Merit Award',
            'amount' => 1000,
            'start_date' => gmdate('Y-m-d', strtotime('-1 day')),
            'end_date' => gmdate('Y-m-d', strtotime('+90 days')),
        ]);

        $invoice = $this->generateInvoice();

        $this->assertSame('4000.00', $invoice['gross_amount']);
        $this->assertSame('1000.00', $invoice['scholarship_amount']);
        $this->assertSame('3000.00', $invoice['total_amount']);
    }

    public function testAScholarshipCannotExceedTheTuitionFees(): void
    {
        $this->addFee('Tuition', 4000);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot exceed the tuition fees');

        $this->finance->awardScholarship($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'scholarship_name' => 'Too generous',
            'amount' => 5000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-15',
        ]);
    }

    public function testGeneratingAnInvoiceWithNoFeeStructureIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No tuition fees are configured');

        $this->generateInvoice();
    }

    public function testAStudentCannotBeInvoicedTwiceForOneSemester(): void
    {
        $this->addFee('Tuition', 4000);
        $this->generateInvoice();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already exists');

        $this->generateInvoice();
    }

    public function testRecordingAPaymentReducesTheBalanceAndMarksItPartiallyPaid(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 1500, 'TXN-0001');

        $updated = $this->finance->invoice((int) $invoice['id'], $this->adminUser);

        $this->assertSame('1500.00', $updated['paid_amount']);
        $this->assertSame('2500.00', $updated['balance']);
        $this->assertSame('Partially Paid', $updated['status']);
    }

    public function testPayingTheFullBalanceMarksTheInvoicePaid(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 4000, 'TXN-0002');

        $updated = $this->finance->invoice((int) $invoice['id'], $this->adminUser);

        $this->assertSame('0.00', $updated['balance']);
        $this->assertSame('Paid', $updated['status']);
    }

    public function testAPaymentCannotExceedTheOutstandingBalance(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('exceeds the outstanding balance');

        $this->pay($invoice, 4500, 'TXN-0003');
    }

    public function testTwoPaymentsCannotShareAReference(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 100, 'TXN-DUPLICATE');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already been recorded');

        $this->pay($invoice, 200, 'TXN-DUPLICATE');
    }

    public function testAnInvoiceWithPaymentsCannotBeCancelled(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 100, 'TXN-0004');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('recorded payments cannot be cancelled');

        $this->finance->cancelInvoice((int) $invoice['id']);
    }

    public function testACancelledInvoiceCannotReceivePayments(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->finance->cancelInvoice((int) $invoice['id']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('been cancelled');

        $this->pay($invoice, 100, 'TXN-0005');
    }

    public function testPaymentHistorySurvivesAndIsOrdered(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 1000, 'TXN-A');
        $this->pay($invoice, 500, 'TXN-B');

        $detail = $this->finance->invoice((int) $invoice['id'], $this->adminUser);

        $this->assertCount(2, $detail['payments']);
        $this->assertSame('1500.00', $detail['paid_amount']);
    }

    public function testAStudentSeesOnlyTheirOwnInvoices(): void
    {
        $this->addFee('Tuition', 4000);
        $this->generateInvoice();

        $otherStudent = $this->createStudent($this->structure, 'other@test.edu');

        $theirInvoices = $this->finance->invoicesFor(
            $this->actingAs($otherStudent['user_id'], 'Student'),
            null
        );

        $this->assertSame([], $theirInvoices);
        $this->assertCount(1, $this->finance->invoicesFor($this->studentUser, null));
    }

    public function testAStudentCannotReadAnotherStudentsInvoiceById(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $otherStudent = $this->createStudent($this->structure, 'other@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->finance->invoice(
            (int) $invoice['id'],
            $this->actingAs($otherStudent['user_id'], 'Student')
        );
    }

    public function testAStudentIdFilterIsIgnoredForStudents(): void
    {
        $this->addFee('Tuition', 4000);
        $this->generateInvoice();

        $otherStudent = $this->createStudent($this->structure, 'other@test.edu');

        $result = $this->finance->invoicesFor(
            $this->actingAs($otherStudent['user_id'], 'Student'),
            $this->student['student_id']
        );

        $this->assertSame(
            [],
            $result,
            'Passing another student_id must not let a student read someone else\'s invoices.'
        );
    }

    public function testAnActiveHoldBlocksCourseRegistration(): void
    {
        $sectionId = $this->openSection();

        $this->finance->applyHold($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'reason' => 'Outstanding balance',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('financial hold');

        (new EnrollmentService())->register($this->student['student_id'], $sectionId);
    }

    public function testReleasingTheHoldRestoresRegistration(): void
    {
        $sectionId = $this->openSection();

        $hold = $this->finance->applyHold($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'reason' => 'Outstanding balance',
        ]);

        $this->finance->releaseHold((int) $hold['id'], $this->adminUser);

        $enrollment = (new EnrollmentService())->register($this->student['student_id'], $sectionId);

        $this->assertSame('Pending', $enrollment['enrollment_status']);
    }

    public function testAnOverdueInvoiceBlocksCourseRegistration(): void
    {
        $sectionId = $this->openSection();

        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->db->prepare('UPDATE Invoice SET due_date = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d', strtotime('-10 days')), $invoice['id']]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('overdue tuition invoice');

        (new EnrollmentService())->register($this->student['student_id'], $sectionId);
    }

    public function testAFullyPaidOverdueInvoiceDoesNotBlockRegistration(): void
    {
        $sectionId = $this->openSection();

        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 4000, 'TXN-SETTLED');

        $this->db->prepare('UPDATE Invoice SET due_date = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d', strtotime('-10 days')), $invoice['id']]);

        $enrollment = (new EnrollmentService())->register($this->student['student_id'], $sectionId);

        $this->assertSame(
            'Pending',
            $enrollment['enrollment_status'],
            'A settled invoice must not block registration even if its due date has passed.'
        );
    }

    public function testStandingReportsWhetherTheStudentCanRegister(): void
    {
        $standing = $this->finance->standingFor($this->student['student_id']);

        $this->assertTrue($standing['can_register']);

        $this->finance->applyHold($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'reason' => 'Outstanding balance',
        ]);

        $this->assertFalse($this->finance->standingFor($this->student['student_id'])['can_register']);
    }

    public function testAStudentCannotHaveTwoActiveHolds(): void
    {
        $this->finance->applyHold($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'reason' => 'First',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already has an active');

        $this->finance->applyHold($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'reason' => 'Second',
        ]);
    }

    public function testRevenueReportSumsCompletedPayments(): void
    {
        $this->addFee('Tuition', 4000);
        $invoice = $this->generateInvoice();

        $this->pay($invoice, 1000, 'TXN-R1');
        $this->pay($invoice, 500, 'TXN-R2');

        $report = $this->finance->revenueReport(null);

        $this->assertCount(1, $report);
        $this->assertSame('1500.00', $report[0]['total_collected']);
        $this->assertSame(2, (int) $report[0]['payment_count']);
    }

    private function addFee(string $type, float $amount): void
    {
        $this->db->prepare(
            'INSERT INTO TuitionFee (program_id, semester_id, fee_type, amount) VALUES (?, ?, ?, ?)'
        )->execute([$this->structure['program_id'], $this->structure['semester_id'], $type, $amount]);
    }

    private function generateInvoice(): array
    {
        return $this->finance->generateInvoice($this->adminUser, [
            'student_id' => $this->student['student_id'],
            'semester_id' => $this->structure['semester_id'],
            'due_date' => gmdate('Y-m-d', strtotime('+30 days')),
        ]);
    }

    private function pay(array $invoice, float $amount, string $reference): array
    {
        return $this->finance->recordPayment($this->adminUser, [
            'invoice_id' => $invoice['id'],
            'payment_reference' => $reference,
            'payment_method' => 'Online Banking',
            'amount' => $amount,
        ]);
    }

    private function openSection(): int
    {
        $courseId = $this->createCourse($this->structure['department_id'], 'CS101');

        return $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
    }
}
