<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Models\TuitionFee;
use App\Services\FinanceService;
use App\Services\StudentService;
use App\Validation\FinanceValidator;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceService $finance = new FinanceService(),
        private readonly StudentService $students = new StudentService(),
        private readonly TuitionFee $fees = new TuitionFee(),
        private readonly FinanceValidator $validator = new FinanceValidator()
    ) {
        parent::__construct();
    }

    public function tuitionFees(): void
    {
        $this->authenticateAsAdministrator();

        $fees = $this->fees->search($this->queryInt('program_id'), $this->queryInt('semester_id'));

        Response::success('Tuition fees retrieved.', ['tuition_fees' => $fees]);
    }

    public function storeTuitionFee(): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->tuitionFee($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $id = $this->fees->create([
            'program_id' => (int) $data['program_id'],
            'semester_id' => (int) $data['semester_id'],
            'fee_type' => $data['fee_type'],
            'amount' => (float) $data['amount'],
        ]);

        Response::success('Tuition fee created.', ['tuition_fee' => $this->fees->find($id)], 201);
    }

    public function updateTuitionFee(string $id): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->tuitionFee($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        if (!$this->fees->exists((int) $id)) {
            Response::error('Tuition fee not found.', 404);
        }

        $this->fees->update((int) $id, [
            'program_id' => (int) $data['program_id'],
            'semester_id' => (int) $data['semester_id'],
            'fee_type' => $data['fee_type'],
            'amount' => (float) $data['amount'],
        ]);

        Response::success('Tuition fee updated.', ['tuition_fee' => $this->fees->find((int) $id)]);
    }

    public function destroyTuitionFee(string $id): void
    {
        $this->authenticateAsAdministrator();

        if (!$this->fees->exists((int) $id)) {
            Response::error('Tuition fee not found.', 404);
        }

        $this->fees->delete((int) $id);

        Response::success('Tuition fee deleted.');
    }

    public function invoices(): void
    {
        $user = $this->authenticate();

        $invoices = $this->run(
            fn () => $this->finance->invoicesFor($user, $this->queryInt('student_id'))
        );

        Response::success('Invoices retrieved.', ['invoices' => $invoices]);
    }

    public function invoice(string $id): void
    {
        $user = $this->authenticate();

        $invoice = $this->run(fn () => $this->finance->invoice((int) $id, $user));

        Response::success('Invoice retrieved.', ['invoice' => $invoice]);
    }

    public function generateInvoice(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->generateInvoice($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $invoice = $this->run(fn () => $this->finance->generateInvoice($user, $data));

        Response::success('Invoice generated.', ['invoice' => $invoice], 201);
    }

    public function cancelInvoice(string $id): void
    {
        $this->authenticateAsAdministrator();

        $invoice = $this->run(fn () => $this->finance->cancelInvoice((int) $id));

        Response::success('Invoice cancelled.', ['invoice' => $invoice]);
    }

    public function payments(): void
    {
        $user = $this->authenticate();

        $payments = $this->run(
            fn () => $this->finance->paymentsFor($user, $this->queryInt('student_id'))
        );

        Response::success('Payments retrieved.', ['payments' => $payments]);
    }

    public function payment(string $id): void
    {
        $user = $this->authenticate();

        $payment = $this->run(fn () => $this->finance->payment((int) $id, $user));

        Response::success('Payment retrieved.', ['payment' => $payment]);
    }

    public function storePayment(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->payment($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $payment = $this->run(fn () => $this->finance->recordPayment($user, $data));

        Response::success('Payment recorded.', ['payment' => $payment], 201);
    }

    public function scholarships(): void
    {
        $user = $this->authenticate();

        $scholarships = $this->run(
            fn () => $this->finance->scholarshipsFor($user, $this->queryInt('student_id'))
        );

        Response::success('Scholarships retrieved.', ['scholarships' => $scholarships]);
    }

    public function storeScholarship(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->scholarship($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $scholarship = $this->run(fn () => $this->finance->awardScholarship($user, $data));

        Response::success('Scholarship awarded.', ['scholarship' => $scholarship], 201);
    }

    public function revokeScholarship(string $id): void
    {
        $this->authenticateAsAdministrator();

        $scholarship = $this->run(fn () => $this->finance->revokeScholarship((int) $id));

        Response::success('Scholarship revoked.', ['scholarship' => $scholarship]);
    }

    public function holds(): void
    {
        $user = $this->authenticate();

        $holds = $this->run(
            fn () => $this->finance->holdsFor($user, $this->queryString('status'))
        );

        Response::success('Financial holds retrieved.', ['holds' => $holds]);
    }

    public function storeHold(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->hold($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $hold = $this->run(fn () => $this->finance->applyHold($user, $data));

        Response::success('Financial hold applied.', ['hold' => $hold], 201);
    }

    public function releaseHold(string $id): void
    {
        $user = $this->authenticateAsAdministrator();

        $hold = $this->run(fn () => $this->finance->releaseHold((int) $id, $user));

        Response::success('Financial hold released.', ['hold' => $hold]);
    }

    public function standing(): void
    {
        $user = $this->authenticate();

        $standing = $this->run(function () use ($user) {
            $studentId = $user['role'] === 'Student'
                ? (int) $this->students->getByUserId($user['user_id'])['id']
                : $this->queryInt('student_id');

            if ($studentId === null) {
                throw new \App\Services\ApiException('A student_id query parameter is required.', 400);
            }

            return $this->finance->standingFor($studentId);
        });

        Response::success('Financial standing retrieved.', $standing);
    }

    public function balanceReport(): void
    {
        $this->authenticateAsAdministrator();

        Response::success('Balance report generated.', ['report' => $this->finance->balanceReport()]);
    }

    public function revenueReport(): void
    {
        $this->authenticateAsAdministrator();

        Response::success('Revenue report generated.', [
            'report' => $this->finance->revenueReport($this->queryInt('semester_id')),
        ]);
    }

    public function outstandingReport(): void
    {
        $this->authenticateAsAdministrator();

        Response::success('Outstanding report generated.', [
            'report' => $this->finance->outstandingReport(),
        ]);
    }
}
