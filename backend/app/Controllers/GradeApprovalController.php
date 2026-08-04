<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\GradeApprovalService;
use App\Validation\GradeApprovalValidator;

class GradeApprovalController extends Controller
{
    public function __construct(
        private readonly GradeApprovalService $approvals = new GradeApprovalService(),
        private readonly GradeApprovalValidator $validator = new GradeApprovalValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $approvals = $this->run(
            fn () => $this->approvals->list($user, $this->queryString('status'))
        );

        Response::success('Grade approvals retrieved.', ['approvals' => $approvals]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $approval = $this->run(fn () => $this->approvals->get((int) $id, $user));

        Response::success('Grade approval retrieved.', ['approval' => $approval]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->submission($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $approval = $this->run(
            fn () => $this->approvals->submit((int) $data['section_id'], $user)
        );

        Response::success('Grades submitted for approval.', ['approval' => $approval], 201);
    }

    public function approve(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();

        $approval = $this->run(
            fn () => $this->approvals->approve((int) $id, $user, $data['remarks'] ?? null)
        );

        Response::success('Grades approved and published.', ['approval' => $approval]);
    }

    public function reject(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->decision($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $approval = $this->run(
            fn () => $this->approvals->reject((int) $id, $user, $data['remarks'])
        );

        Response::success('Grades rejected.', ['approval' => $approval]);
    }

    public function returnForRevision(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->decision($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $approval = $this->run(
            fn () => $this->approvals->returnForRevision((int) $id, $user, $data['remarks'])
        );

        Response::success('Grades returned for revision.', ['approval' => $approval]);
    }

    public function history(): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $history = $this->run(
            fn () => $this->approvals->history($user, $this->queryInt('department_id'))
        );

        Response::success('Approval history retrieved.', ['history' => $history]);
    }
}
