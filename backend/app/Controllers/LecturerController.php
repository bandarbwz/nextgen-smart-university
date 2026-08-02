<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\LecturerService;
use App\Validation\AcademicValidator;

class LecturerController extends Controller
{
    public function __construct(
        private readonly LecturerService $lecturers = new LecturerService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticate();

        $lecturers = $this->lecturers->list($this->queryInt('department_id'));

        Response::success('Lecturers retrieved successfully.', ['lecturers' => $lecturers]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $lecturer = $this->run(fn () => $this->lecturers->get((int) $id));

        Response::success('Lecturer retrieved successfully.', ['lecturer' => $lecturer]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->lecturer($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $lecturer = $this->run(fn () => $this->lecturers->create($this->fields($data)));

        Response::success('Lecturer created successfully.', ['lecturer' => $lecturer], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->lecturer($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $lecturer = $this->run(fn () => $this->lecturers->update((int) $id, $this->fields($data)));

        Response::success('Lecturer updated successfully.', ['lecturer' => $lecturer]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->lecturers->delete((int) $id));

        Response::success('Lecturer deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'user_id' => (int) $data['user_id'],
            'faculty_id' => (int) $data['faculty_id'],
            'department_id' => (int) $data['department_id'],
            'office' => isset($data['office']) ? trim((string) $data['office']) : null,
            'specialization' => isset($data['specialization']) ? trim((string) $data['specialization']) : null,
            'employment_status' => $data['employment_status'] ?? 'full_time',
            'hire_date' => $data['hire_date'] ?? null,
        ];
    }
}
