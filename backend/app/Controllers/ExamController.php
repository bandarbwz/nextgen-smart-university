<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ExamService;
use App\Validation\ExamValidator;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams = new ExamService(),
        private readonly ExamValidator $validator = new ExamValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $exams = $this->run(fn () => $this->exams->list($user, $this->queryInt('section_id')));

        Response::success('Examinations retrieved.', ['examinations' => $exams]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $exam = $this->run(fn () => $this->exams->get((int) $id, $user));

        Response::success('Examination retrieved.', ['examination' => $exam]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];

        $errors = $this->validator->exam($data) + $this->validator->questions($questions);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $exam = $this->run(fn () => $this->exams->create($user, $data, $questions));

        Response::success('Examination created.', ['examination' => $exam], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];

        $errors = $this->validator->examUpdate($data);

        if ($questions !== []) {
            $errors += $this->validator->questions($questions);
        }

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $exam = $this->run(fn () => $this->exams->update((int) $id, $user, $data, $questions));

        Response::success('Examination updated.', ['examination' => $exam]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->exams->delete((int) $id, $user));

        Response::success('Examination deleted.');
    }

    public function submissions(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $submissions = $this->run(fn () => $this->exams->submissions((int) $id, $user));

        Response::success('Examination submissions retrieved.', ['submissions' => $submissions]);
    }

    public function grade(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->grade($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $submission = $this->run(
            fn () => $this->exams->grade((int) $id, $user, (float) $data['score'])
        );

        Response::success('Submission graded.', ['submission' => $submission]);
    }
}
