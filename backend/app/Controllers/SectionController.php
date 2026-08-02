<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\SectionService;
use App\Validation\AcademicValidator;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionService $sections = new SectionService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticate();

        $sections = $this->sections->search(
            $this->queryInt('semester_id'),
            $this->queryInt('course_id'),
            $this->queryInt('lecturer_id')
        );

        Response::success('Sections retrieved successfully.', ['sections' => $sections]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $section = $this->run(fn () => $this->sections->get((int) $id));

        Response::success('Section retrieved successfully.', ['section' => $section]);
    }

    public function byCourse(string $courseId): void
    {
        $this->authenticate();

        $sections = $this->sections->search($this->queryInt('semester_id'), (int) $courseId, null);

        Response::success('Sections retrieved successfully.', ['sections' => $sections]);
    }

    public function students(string $id): void
    {
        $this->authenticateAs(['Lecturer', 'Coordinator']);

        $students = $this->run(fn () => $this->sections->students((int) $id));

        Response::success('Students retrieved successfully.', ['students' => $students]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->section($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $section = $this->run(
            fn () => $this->sections->create($this->fields($data), $this->schedule($data), $user['user_id'])
        );

        Response::success('Section created successfully.', ['section' => $section], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->section($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $section = $this->run(
            fn () => $this->sections->update((int) $id, $this->fields($data), $this->schedule($data))
        );

        Response::success('Section updated successfully.', ['section' => $section]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $this->run(fn () => $this->sections->delete((int) $id));

        Response::success('Section deleted successfully.');
    }

    public function openRegistration(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $section = $this->run(fn () => $this->sections->changeStatus((int) $id, 'open'));

        Response::success('Section registration opened.', ['section' => $section]);
    }

    public function closeRegistration(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $section = $this->run(fn () => $this->sections->changeStatus((int) $id, 'closed'));

        Response::success('Section registration closed.', ['section' => $section]);
    }

    public function updateCapacity(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->capacity($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $section = $this->run(fn () => $this->sections->changeCapacity((int) $id, (int) $data['capacity']));

        Response::success('Section capacity updated.', ['section' => $section]);
    }

    public function assignLecturer(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->lecturerAssignment($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $section = $this->run(fn () => $this->sections->assignLecturer((int) $id, (int) $data['lecturer_id']));

        Response::success('Lecturer assigned successfully.', ['section' => $section]);
    }

    public function changeClassroom(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();

        $section = $this->run(fn () => $this->sections->changeClassroom(
            (int) $id,
            isset($data['classroom']) ? trim((string) $data['classroom']) : null,
            isset($data['building']) ? trim((string) $data['building']) : null
        ));

        Response::success('Classroom updated successfully.', ['section' => $section]);
    }

    private function fields(array $data): array
    {
        return [
            'course_id' => (int) $data['course_id'],
            'lecturer_id' => (int) $data['lecturer_id'],
            'semester_id' => (int) $data['semester_id'],
            'section_number' => trim($data['section_number']),
            'classroom' => isset($data['classroom']) ? trim((string) $data['classroom']) : null,
            'building' => isset($data['building']) ? trim((string) $data['building']) : null,
            'delivery_mode' => $data['delivery_mode'] ?? 'Physical',
            'capacity' => (int) $data['capacity'],
            'status' => $data['status'] ?? 'open',
        ];
    }

    private function schedule(array $data): array
    {
        $schedule = $data['schedule'] ?? [];

        return is_array($schedule) ? $schedule : [];
    }
}
