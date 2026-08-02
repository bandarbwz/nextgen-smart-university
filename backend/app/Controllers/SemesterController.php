<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\SemesterService;
use App\Validation\AcademicValidator;

class SemesterController extends Controller
{
    public function __construct(
        private readonly SemesterService $semesters = new SemesterService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticate();

        Response::success('Semesters retrieved successfully.', [
            'semesters' => $this->semesters->list(),
        ]);
    }

    public function current(): void
    {
        $this->authenticate();

        $semester = $this->run(fn () => $this->semesters->current());

        Response::success('Current semester retrieved successfully.', ['semester' => $semester]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $semester = $this->run(fn () => $this->semesters->get((int) $id));

        Response::success('Semester retrieved successfully.', ['semester' => $semester]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->semester($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $semester = $this->run(fn () => $this->semesters->create($this->fields($data)));

        Response::success('Semester created successfully.', ['semester' => $semester], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->semester($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $semester = $this->run(fn () => $this->semesters->update((int) $id, $this->fields($data)));

        Response::success('Semester updated successfully.', ['semester' => $semester]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->semesters->delete((int) $id));

        Response::success('Semester deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'academic_year' => trim($data['academic_year']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'registration_start' => $data['registration_start'] ?? null,
            'registration_end' => $data['registration_end'] ?? null,
            'current_semester' => (bool) ($data['current_semester'] ?? false),
            'status' => $data['status'] ?? 'upcoming',
        ];
    }
}
