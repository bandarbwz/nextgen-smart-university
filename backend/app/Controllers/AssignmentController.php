<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\AssignmentService;
use App\Validation\LmsValidator;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments = new AssignmentService(),
        private readonly LmsValidator $validator = new LmsValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $assignments = $this->run(
            fn () => $this->assignments->list($user, $this->queryInt('section_id'))
        );

        Response::success('Assignments retrieved.', ['assignments' => $assignments]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $assignment = $this->run(fn () => $this->assignments->get((int) $id, $user));

        Response::success('Assignment retrieved.', ['assignment' => $assignment]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->assignment($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $assignment = $this->run(fn () => $this->assignments->create($user, $this->fields($data)));

        Response::success('Assignment created.', ['assignment' => $assignment], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->assignmentUpdate($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $assignment = $this->run(
            fn () => $this->assignments->update((int) $id, $user, $this->fields($data))
        );

        Response::success('Assignment updated.', ['assignment' => $assignment]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->assignments->delete((int) $id, $user));

        Response::success('Assignment deleted.');
    }

    public function submit(string $id): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::formOrBody();
        $file = $_FILES['file'] ?? null;

        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $file = null;
        }

        $submission = $this->run(fn () => $this->assignments->submit(
            (int) $id,
            $user,
            $file,
            isset($data['comment']) ? trim((string) $data['comment']) : null,
        ));

        Response::success('Assignment submitted.', ['submission' => $submission], 201);
    }

    public function showSubmission(string $id): void
    {
        $user = $this->authenticate();

        $submission = $this->run(fn () => $this->assignments->viewSubmission((int) $id, $user));

        Response::success('Submission retrieved.', ['submission' => $submission]);
    }

    public function gradeSubmission(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->gradeSubmission($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $submission = $this->run(fn () => $this->assignments->grade(
            (int) $id,
            $user,
            (float) $data['marks'],
            isset($data['feedback']) ? trim((string) $data['feedback']) : null,
        ));

        Response::success('Submission graded.', ['submission' => $submission]);
    }

    private function fields(array $data): array
    {
        return [
            'section_id' => $data['section_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'total_marks' => $data['total_marks'],
            'due_date' => $data['due_date'],
            'allow_late_submission' => (bool) ($data['allow_late_submission'] ?? false),
        ];
    }
}
