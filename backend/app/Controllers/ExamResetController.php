<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ExamResetService;
use App\Validation\ExamResetValidator;

class ExamResetController extends Controller
{
    public function __construct(
        private readonly ExamResetService $resets = new ExamResetService(),
        private readonly ExamResetValidator $validator = new ExamResetValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $requests = $this->run(fn () => $this->resets->list($user, $this->queryString('status')));

        Response::success('Reset requests retrieved.', ['requests' => $requests]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $request = $this->run(fn () => $this->resets->get((int) $id, $user));

        Response::success('Reset request retrieved.', ['request' => $request]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->request($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $request = $this->run(
            fn () => $this->resets->request($user, (int) $data['exam_id'], $data['request_reason'])
        );

        Response::success('Reset request submitted.', ['request' => $request], 201);
    }

    public function recommend(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();

        $request = $this->run(
            fn () => $this->resets->recommend((int) $id, $user, $data['remarks'] ?? null)
        );

        Response::success('Reset request recommended.', ['request' => $request]);
    }

    public function approve(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();

        $request = $this->run(
            fn () => $this->resets->approve((int) $id, $user, $data['remarks'] ?? null)
        );

        Response::success('Reset approved and applied.', ['request' => $request]);
    }

    public function reject(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->decision($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $request = $this->run(
            fn () => $this->resets->reject((int) $id, $user, $data['remarks'])
        );

        Response::success('Reset request rejected.', ['request' => $request]);
    }

    public function history(): void
    {
        $this->authenticateAsAdministrator();

        $history = $this->run(fn () => $this->resets->history());

        Response::success('Reset history retrieved.', ['history' => $history]);
    }
}
