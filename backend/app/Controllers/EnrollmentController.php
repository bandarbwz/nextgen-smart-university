<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\EnrollmentService;
use App\Services\StudentService;
use App\Validation\AcademicValidator;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments = new EnrollmentService(),
        private readonly StudentService $students = new StudentService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function register(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->registration($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $enrollment = $this->run(function () use ($user, $data) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->enrollments->register((int) $student['id'], (int) $data['section_id']);
        });

        Response::success('Course registered successfully.', ['enrollment' => $enrollment], 201);
    }

    public function drop(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->drop($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $this->run(function () use ($user, $data) {
            $student = $this->students->getByUserId($user['user_id']);

            $this->enrollments->drop((int) $student['id'], (int) $data['enrollment_id']);
        });

        Response::success('Course dropped successfully.');
    }

    public function current(): void
    {
        $user = $this->authenticateAs(['Student']);

        $enrollments = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->enrollments->currentForStudent((int) $student['id']);
        });

        Response::success('Current enrollments retrieved successfully.', ['enrollments' => $enrollments]);
    }

    public function history(): void
    {
        $user = $this->authenticateAs(['Student']);

        $enrollments = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->enrollments->historyForStudent((int) $student['id']);
        });

        Response::success('Enrollment history retrieved successfully.', ['enrollments' => $enrollments]);
    }

    public function pending(): void
    {
        $this->authenticateAs(['Coordinator']);

        $departmentId = $this->queryInt('department_id');

        if ($departmentId === null) {
            Response::error('A department_id query parameter is required.', 400);
        }

        $enrollments = $this->enrollments->pendingForDepartment($departmentId);

        Response::success('Pending enrollments retrieved successfully.', ['enrollments' => $enrollments]);
    }

    public function approve(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $enrollment = $this->run(fn () => $this->enrollments->approve((int) $id, $user['user_id']));

        Response::success('Enrollment approved successfully.', ['enrollment' => $enrollment]);
    }

    public function reject(string $id): void
    {
        $user = $this->authenticateAs(['Coordinator']);

        $enrollment = $this->run(fn () => $this->enrollments->reject((int) $id, $user['user_id']));

        Response::success('Enrollment rejected successfully.', ['enrollment' => $enrollment]);
    }
}
