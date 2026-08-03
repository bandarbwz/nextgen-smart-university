<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ExcuseService;
use App\Services\StudentService;
use App\Validation\AttendanceValidator;

class ExcuseController extends Controller
{
    public function __construct(
        private readonly ExcuseService $excuses = new ExcuseService(),
        private readonly StudentService $students = new StudentService(),
        private readonly AttendanceValidator $validator = new AttendanceValidator()
    ) {
        parent::__construct();
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::formOrBody();
        $errors = $this->validator->excuse($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $document = $_FILES['document'] ?? null;

        if ($document !== null && ($document['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $document = null;
        }

        $excuse = $this->run(function () use ($user, $data, $document) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->excuses->submit((int) $student['id'], [
                'attendance_id' => $data['attendance_id'],
                'excuse_type' => $data['excuse_type'],
                'reason' => trim((string) $data['reason']),
            ], $document);
        });

        Response::success('Excuse submitted for review.', ['excuse' => $excuse], 201);
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $excuses = $this->run(function () use ($user) {
            if ($user['role'] === 'Student') {
                $student = $this->students->getByUserId($user['user_id']);

                return $this->excuses->forStudent((int) $student['id']);
            }

            return $this->excuses->forReviewer(
                $user['user_id'],
                $user['role'],
                $this->queryString('status'),
            );
        });

        Response::success('Excuses retrieved.', ['excuses' => $excuses]);
    }

    public function approve(string $id): void
    {
        $this->review($id, 'Approved', 'Excuse approved.');
    }

    public function reject(string $id): void
    {
        $this->review($id, 'Rejected', 'Excuse rejected.');
    }

    private function review(string $id, string $status, string $message): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->review($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $excuse = $this->run(fn () => $this->excuses->review(
            (int) $id,
            $user['user_id'],
            $user['role'],
            $status,
            isset($data['review_note']) ? trim((string) $data['review_note']) : null,
        ));

        Response::success($message, ['excuse' => $excuse]);
    }
}
