<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\StructureService;
use App\Validation\AcademicValidator;

class DepartmentController extends Controller
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

        Response::success('Departments retrieved successfully.', [
            'departments' => $this->structure->listDepartments(),
        ]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $department = $this->run(fn () => $this->structure->getDepartment((int) $id));

        Response::success('Department retrieved successfully.', ['department' => $department]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->department($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $department = $this->run(fn () => $this->structure->createDepartment($this->fields($data)));

        Response::success('Department created successfully.', ['department' => $department], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->department($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $department = $this->run(fn () => $this->structure->updateDepartment((int) $id, $this->fields($data)));

        Response::success('Department updated successfully.', ['department' => $department]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->structure->deleteDepartment((int) $id));

        Response::success('Department deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'faculty_id' => (int) $data['faculty_id'],
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
        ];
    }
}
