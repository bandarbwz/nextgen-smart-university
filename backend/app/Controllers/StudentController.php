<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\StudentService;
use App\Validation\AcademicValidator;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $students = new StudentService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticateAs(['Coordinator']);

        $students = $this->students->list(
            $this->queryInt('program_id'),
            $this->queryInt('department_id')
        );

        Response::success('Students retrieved successfully.', ['students' => $students]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $student = $this->run(fn () => $this->students->get((int) $id));

        if ($user['role'] === 'Student' && (int) $student['user_id'] !== $user['user_id']) {
            Response::error('You can only view your own student profile.', 403);
        }

        Response::success('Student retrieved successfully.', ['student' => $student]);
    }

    public function profile(): void
    {
        $user = $this->authenticate();

        $student = $this->run(fn () => $this->students->getByUserId($user['user_id']));

        Response::success('Student profile retrieved successfully.', ['student' => $student]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->student($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $student = $this->run(fn () => $this->students->create($this->fields($data)));

        Response::success('Student created successfully.', ['student' => $student], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->student($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $student = $this->run(fn () => $this->students->update((int) $id, $this->fields($data)));

        Response::success('Student updated successfully.', ['student' => $student]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->students->delete((int) $id));

        Response::success('Student deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'user_id' => (int) $data['user_id'],
            'student_number' => trim($data['student_number']),
            'faculty_id' => (int) $data['faculty_id'],
            'department_id' => (int) $data['department_id'],
            'program_id' => (int) $data['program_id'],
            'advisor_id' => isset($data['advisor_id']) ? (int) $data['advisor_id'] : null,
            'current_semester_id' => isset($data['current_semester_id'])
                ? (int) $data['current_semester_id']
                : null,
            'study_mode' => $data['study_mode'] ?? 'full_time',
            'academic_level' => isset($data['academic_level']) ? (int) $data['academic_level'] : 1,
            'admission_date' => $data['admission_date'],
            'expected_graduation_date' => $data['expected_graduation_date'] ?? null,
            'academic_status' => $data['academic_status'] ?? 'active',
        ];
    }
}
