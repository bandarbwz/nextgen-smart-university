<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\CourseService;
use App\Validation\AcademicValidator;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses = new CourseService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticate();

        $courses = $this->courses->search(
            $this->queryString('search'),
            $this->queryInt('department_id'),
            $this->queryInt('program_id')
        );

        Response::success('Courses retrieved successfully.', ['courses' => $courses]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $course = $this->run(fn () => $this->courses->get((int) $id));

        Response::success('Course retrieved successfully.', ['course' => $course]);
    }

    public function prerequisites(string $id): void
    {
        $this->authenticate();

        $prerequisites = $this->run(fn () => $this->courses->prerequisites((int) $id));

        Response::success('Prerequisites retrieved successfully.', ['prerequisites' => $prerequisites]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->course($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $course = $this->run(
            fn () => $this->courses->create($this->fields($data), $this->prerequisiteIds($data))
        );

        Response::success('Course created successfully.', ['course' => $course], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $data = Request::body();
        $errors = $this->validator->course($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $course = $this->run(
            fn () => $this->courses->update((int) $id, $this->fields($data), $this->prerequisiteIds($data))
        );

        Response::success('Course updated successfully.', ['course' => $course]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Coordinator']);

        $this->run(fn () => $this->courses->delete((int) $id));

        Response::success('Course deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'department_id' => (int) $data['department_id'],
            'program_id' => isset($data['program_id']) ? (int) $data['program_id'] : null,
            'course_code' => strtoupper(trim($data['course_code'])),
            'course_name' => trim($data['course_name']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'credit_hours' => (int) $data['credit_hours'],
            'course_type' => $data['course_type'],
            'level' => isset($data['level']) ? (int) $data['level'] : 1,
            'course_status' => $data['course_status'] ?? 'active',
        ];
    }

    private function prerequisiteIds(array $data): array
    {
        $prerequisites = $data['prerequisites'] ?? [];

        return is_array($prerequisites) ? $prerequisites : [];
    }
}
