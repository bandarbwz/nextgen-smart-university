<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\StructureService;
use App\Validation\AcademicValidator;

class FacultyController extends Controller
{
    public function __construct(
        private readonly StructureService $structure = new StructureService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticate();

        Response::success('Faculties retrieved successfully.', [
            'faculties' => $this->structure->listFaculties(),
        ]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $faculty = $this->run(fn () => $this->structure->getFaculty((int) $id));

        Response::success('Faculty retrieved successfully.', ['faculty' => $faculty]);
    }

    public function departments(string $facultyId): void
    {
        $this->authenticate();

        $departments = $this->run(fn () => $this->structure->listDepartments((int) $facultyId));

        Response::success('Departments retrieved successfully.', ['departments' => $departments]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->faculty($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $faculty = $this->run(fn () => $this->structure->createFaculty($this->fields($data)));

        Response::success('Faculty created successfully.', ['faculty' => $faculty], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->faculty($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $faculty = $this->run(fn () => $this->structure->updateFaculty((int) $id, $this->fields($data)));

        Response::success('Faculty updated successfully.', ['faculty' => $faculty]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->structure->deleteFaculty((int) $id));

        Response::success('Faculty deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'dean_name' => isset($data['dean_name']) ? trim((string) $data['dean_name']) : null,
        ];
    }
}
