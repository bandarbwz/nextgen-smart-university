<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ExamSessionService;
use App\Validation\ExamValidator;

class ExamSessionController extends Controller
{
    public function __construct(
        private readonly ExamSessionService $sessions = new ExamSessionService(),
        private readonly ExamValidator $validator = new ExamValidator()
    ) {
        parent::__construct();
    }

    public function start(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->sessionStart($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $context = [
            'image' => $data['image'] ?? null,
            'ip_address' => Request::ipAddress(),
            'browser' => $data['browser'] ?? null,
            'device' => $data['device'] ?? null,
        ];

        $session = $this->run(fn () => $this->sessions->start($user, (int) $data['exam_id'], $context));

        Response::success('Examination session started.', ['session' => $session], 201);
    }

    public function end(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->session($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];

        $submission = $this->run(
            fn () => $this->sessions->end($user, (int) $data['session_id'], $answers)
        );

        Response::success('Examination submitted.', ['submission' => $submission], 201);
    }

    public function pause(): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $data = Request::body();
        $errors = $this->validator->session($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $session = $this->run(fn () => $this->sessions->pause($user, (int) $data['session_id']));

        Response::success('Examination session paused.', ['session' => $session]);
    }

    public function resume(): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $data = Request::body();
        $errors = $this->validator->session($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $session = $this->run(fn () => $this->sessions->resume($user, (int) $data['session_id']));

        Response::success('Examination session resumed.', ['session' => $session]);
    }

    public function mine(): void
    {
        $user = $this->authenticateAs(['Student']);

        $sessions = $this->run(fn () => $this->sessions->mine($user));

        Response::success('Examination sessions retrieved.', ['sessions' => $sessions]);
    }

    public function forExam(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $sessions = $this->run(fn () => $this->sessions->forExam((int) $id, $user));

        Response::success('Examination sessions retrieved.', ['sessions' => $sessions]);
    }
}
