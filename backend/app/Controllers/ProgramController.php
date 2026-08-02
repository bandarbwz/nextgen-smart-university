<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\StructureService;
use App\Validation\AcademicValidator;

class ProgramController extends Controller
{
    public function __construct(
        private readonly StructureService $structure = new StructureService(),
        private readonly AcademicValidator $validator = new AcademicValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticateAs(['Coordinator', 'Lecturer']);

        Response::success('Programs retrieved successfully.', [
            'programs' => $this->structure->listPrograms(),
        ]);
    }

    public function show(string $id): void
    {
        $this->authenticate();

        $program = $this->run(fn () => $this->structure->getProgram((int) $id));

        Response::success('Program retrieved successfully.', ['program' => $program]);
    }

    public function store(): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->program($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $program = $this->run(fn () => $this->structure->createProgram($this->fields($data)));

        Response::success('Program created successfully.', ['program' => $program], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $data = Request::body();
        $errors = $this->validator->program($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $program = $this->run(fn () => $this->structure->updateProgram((int) $id, $this->fields($data)));

        Response::success('Program updated successfully.', ['program' => $program]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAs(['Administrator']);

        $this->run(fn () => $this->structure->deleteProgram((int) $id));

        Response::success('Program deleted successfully.');
    }

    private function fields(array $data): array
    {
        return [
            'department_id' => (int) $data['department_id'],
            'name' => trim($data['name']),
            'degree' => trim($data['degree']),
            'required_credit_hours' => (int) $data['required_credit_hours'],
        ];
    }
}
