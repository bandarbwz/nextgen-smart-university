<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\QuizService;
use App\Validation\LmsValidator;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes = new QuizService(),
        private readonly LmsValidator $validator = new LmsValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $quizzes = $this->run(fn () => $this->quizzes->list($user, $this->queryInt('section_id')));

        Response::success('Quizzes retrieved.', ['quizzes' => $quizzes]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $quiz = $this->run(fn () => $this->quizzes->get((int) $id, $user));

        Response::success('Quiz retrieved.', ['quiz' => $quiz]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->quiz($data);
        $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];

        $errors += $this->validator->quizQuestions($questions);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $quiz = $this->run(fn () => $this->quizzes->create($user, $data, $questions));

        Response::success('Quiz created.', ['quiz' => $quiz], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->quiz($data);
        $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];

        if ($questions !== []) {
            $errors += $this->validator->quizQuestions($questions);
        }

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $quiz = $this->run(fn () => $this->quizzes->update((int) $id, $user, $data, $questions));

        Response::success('Quiz updated.', ['quiz' => $quiz]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->quizzes->delete((int) $id, $user));

        Response::success('Quiz deleted.');
    }

    public function submit(string $id): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->quizSubmission($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $submission = $this->run(fn () => $this->quizzes->submit((int) $id, $user, $data['answers']));

        Response::success('Quiz submitted.', ['submission' => $submission], 201);
    }

    public function submissions(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $submissions = $this->run(fn () => $this->quizzes->submissions((int) $id, $user));

        Response::success('Quiz submissions retrieved.', ['submissions' => $submissions]);
    }
}
