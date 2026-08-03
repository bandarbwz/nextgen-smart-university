<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ProctoringService;
use App\Validation\ExamValidator;

class ProctoringController extends Controller
{
    public function __construct(
        private readonly ProctoringService $proctoring = new ProctoringService(),
        private readonly ExamValidator $validator = new ExamValidator()
    ) {
        parent::__construct();
    }

    public function verifyFace(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->capture(...));

        $result = $this->run(
            fn () => $this->proctoring->verifyFace($user, (int) $data['session_id'], $data['image'])
        );

        Response::success('Face check completed.', $result);
    }

    public function eyeTracking(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->capture(...));

        $result = $this->run(
            fn () => $this->proctoring->trackEyes($user, (int) $data['session_id'], $data['image'])
        );

        Response::success('Eye tracking recorded.', $result);
    }

    public function headPose(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->capture(...));

        $result = $this->run(
            fn () => $this->proctoring->estimateHeadPose($user, (int) $data['session_id'], $data['image'])
        );

        Response::success('Head pose recorded.', $result);
    }

    public function browserMonitor(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->browserActivity(...));

        $session = $this->run(fn () => $this->proctoring->recordBrowserActivity(
            $user,
            (int) $data['session_id'],
            $data['activity_type'],
            $data['detail'] ?? null
        ));

        Response::success('Browser activity recorded.', ['session' => $session]);
    }

    public function deviceMonitor(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->deviceMonitor(...));

        $session = $this->run(
            fn () => $this->proctoring->recordDevice($user, (int) $data['session_id'], $data)
        );

        Response::success('Device state recorded.', ['session' => $session]);
    }

    public function storeViolation(): void
    {
        $user = $this->authenticateAs(['Student']);
        $data = $this->validated($this->validator->violation(...));

        $violation = $this->run(
            fn () => $this->proctoring->reportViolation($user, (int) $data['session_id'], $data)
        );

        Response::success('Violation recorded.', ['violation' => $violation], 201);
    }

    public function violations(): void
    {
        $user = $this->authenticate();

        $examId = $this->queryInt('exam_id');
        $sessionId = $this->queryInt('session_id');

        if ($examId === null && $sessionId === null) {
            Response::error('Provide either an exam_id or a session_id.', 422);
        }

        $violations = $this->run(fn () => $examId !== null
            ? $this->proctoring->violationsForExam($examId, $user)
            : $this->proctoring->violationsForSession($sessionId, $user));

        Response::success('Violations retrieved.', ['violations' => $violations]);
    }

    public function studentViolations(string $studentId): void
    {
        $user = $this->authenticate();

        $violations = $this->run(
            fn () => $this->proctoring->violationsForStudent((int) $studentId, $user)
        );

        Response::success('Violations retrieved.', ['violations' => $violations]);
    }

    public function storeRecording(): void
    {
        $user = $this->authenticateAs(['Student']);

        $sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId === null) {
            Response::validationError(['session_id' => ['Session is required.']]);
        }

        if (!isset($_FILES['recording'])) {
            Response::validationError(['recording' => ['A recording file is required.']]);
        }

        $recording = $this->run(
            fn () => $this->proctoring->storeRecording($user, (int) $sessionId, $_FILES['recording'])
        );

        Response::success('Recording uploaded.', ['recording' => $recording], 201);
    }

    public function recording(string $id): void
    {
        $user = $this->authenticate();

        $file = $this->run(fn () => $this->proctoring->recording((int) $id, $user));

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Content-Length: ' . filesize($file['path']));
        header('X-Content-Type-Options: nosniff');

        readfile($file['path']);

        exit;
    }

    private function validated(callable $rule): array
    {
        $data = Request::body();
        $errors = $rule($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        return $data;
    }
}
