<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;

class StructureService
{
    public function __construct(
        private readonly Faculty $faculties = new Faculty(),
        private readonly Department $departments = new Department(),
        private readonly Program $programs = new Program()
    ) {
    }

    public function listFaculties(): array
    {
        return $this->faculties->all();
    }

    public function getFaculty(int $id): array
    {
        $faculty = $this->faculties->find($id);

        if ($faculty === null) {
            throw new ApiException('Faculty not found.', 404);
        }

        return $faculty;
    }

    public function createFaculty(array $fields): array
    {
        if ($this->faculties->nameExists($fields['name'])) {
            throw new ApiException('A faculty with this name already exists.', 409);
        }

        return $this->getFaculty($this->faculties->create($fields));
    }

    public function updateFaculty(int $id, array $fields): array
    {
        $this->getFaculty($id);

        if ($this->faculties->nameExists($fields['name'], $id)) {
            throw new ApiException('A faculty with this name already exists.', 409);
        }

        $this->faculties->update($id, $fields);

        return $this->getFaculty($id);
    }

    public function deleteFaculty(int $id): void
    {
        $this->getFaculty($id);

        if ($this->faculties->hasDepartments($id)) {
            throw new ApiException('This faculty still has departments and cannot be deleted.', 409);
        }

        $this->faculties->delete($id);
    }

    public function listDepartments(?int $facultyId = null): array
    {
        if ($facultyId === null) {
            return $this->departments->allWithFaculty();
        }

        $this->getFaculty($facultyId);

        return $this->departments->byFaculty($facultyId);
    }

    public function getDepartment(int $id): array
    {
        $department = $this->departments->find($id);

        if ($department === null) {
            throw new ApiException('Department not found.', 404);
        }

        return $department;
    }

    public function createDepartment(array $fields): array
    {
        $this->getFaculty((int) $fields['faculty_id']);

        return $this->getDepartment($this->departments->create($fields));
    }

    public function updateDepartment(int $id, array $fields): array
    {
        $this->getDepartment($id);
        $this->getFaculty((int) $fields['faculty_id']);

        $this->departments->update($id, $fields);

        return $this->getDepartment($id);
    }

    public function deleteDepartment(int $id): void
    {
        $this->getDepartment($id);

        if ($this->departments->hasPrograms($id)) {
            throw new ApiException('This department still has programs and cannot be deleted.', 409);
        }

        $this->departments->delete($id);
    }

    public function listPrograms(): array
    {
        return $this->programs->allWithDepartment();
    }

    public function getProgram(int $id): array
    {
        $program = $this->programs->find($id);

        if ($program === null) {
            throw new ApiException('Program not found.', 404);
        }

        return $program;
    }

    public function createProgram(array $fields): array
    {
        $this->getDepartment((int) $fields['department_id']);

        return $this->getProgram($this->programs->create($fields));
    }

    public function updateProgram(int $id, array $fields): array
    {
        $this->getProgram($id);
        $this->getDepartment((int) $fields['department_id']);

        $this->programs->update($id, $fields);

        return $this->getProgram($id);
    }

    public function deleteProgram(int $id): void
    {
        $this->getProgram($id);

        if ($this->programs->hasStudents($id)) {
            throw new ApiException('This program still has students and cannot be deleted.', 409);
        }

        $this->programs->delete($id);
    }
}
